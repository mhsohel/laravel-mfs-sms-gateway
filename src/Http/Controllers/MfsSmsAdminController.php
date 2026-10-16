<?php

namespace Prantiksoft\MfsSmsGateway\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Prantiksoft\MfsSmsGateway\Facades\MfsSms;
use Prantiksoft\MfsSmsGateway\Models\MfsSmsSetting;
use Prantiksoft\MfsSmsGateway\Models\MfsSmsTransaction;

class MfsSmsAdminController extends Controller
{
    /**
     * Display the MFS SMS Gateway Admin Dashboard.
     */
    public function index(Request $request)
    {
        $providers = ['bkash', 'nagad', 'rocket'];
        $gateways = [];

        foreach ($providers as $prov) {
            $gateways[$prov] = MfsSms::getGatewayConfig($prov);
        }

        // Statistics
        try {
            $stats = [
                'total_transactions'   => MfsSmsTransaction::count(),
                'matched_transactions' => MfsSmsTransaction::where('status', 'matched')->count(),
                'unmatched_transactions' => MfsSmsTransaction::where('status', 'unmatched')->count(),
                'today_received'       => MfsSmsTransaction::whereDate('created_at', today())->count(),
                'total_volume'         => (float) MfsSmsTransaction::where('status', 'matched')->sum('amount'),
            ];

            $recentTransactions = MfsSmsTransaction::with('order')->latest()->take(10)->get();
        } catch (\Throwable $e) {
            $stats = [
                'total_transactions'     => 0,
                'matched_transactions'   => 0,
                'unmatched_transactions' => 0,
                'today_received'         => 0,
                'total_volume'           => 0,
            ];
            $recentTransactions = collect();
        }

        // Webhook URLs
        $apiPrefix = config('mfs-sms.routes.api_prefix', 'api/mfs-sms');
        $webhookUrls = [
            'unified' => url("{$apiPrefix}/webhook"),
            'bkash'   => url("{$apiPrefix}/bkash/webhook"),
            'nagad'   => url("{$apiPrefix}/nagad/webhook"),
            'rocket'  => url("{$apiPrefix}/rocket/webhook"),
        ];

        return view('mfs-sms-gateway::admin.index', compact(
            'gateways',
            'stats',
            'recentTransactions',
            'webhookUrls'
        ));
    }

    /**
     * Save settings for a specific provider.
     */
    public function saveProvider(string $provider, Request $request)
    {
        $provider = strtolower($provider);
        if (!in_array($provider, ['bkash', 'nagad', 'rocket', 'upay'])) {
            return response()->json(['success' => false, 'message' => 'Invalid provider.'], 422);
        }

        $validated = $request->validate([
            'number'        => 'required|string|max:30',
            'type'          => 'required|string|max:30',
            'webhook_token' => 'required|string|min:8|max:100',
            'enabled'       => 'nullable',
            'title'         => 'nullable|string|max:100',
            'ussd_code'     => 'nullable|string|max:20',
        ]);

        $enabled = $request->has('enabled') ? (bool) $request->input('enabled') : false;

        MfsSmsSetting::setForProvider($provider, [
            'enabled'       => $enabled,
            'title'         => $request->input('title') ?: (ucfirst($provider) . ' Direct (Auto-Verified)'),
            'number'        => trim($request->input('number')),
            'type'          => $request->input('type', 'Personal'),
            'webhook_token' => trim($request->input('webhook_token')),
            'ussd_code'     => $request->input('ussd_code'),
        ]);

        // Sync to host application's PaymentGateway model if it exists
        try {
            if (class_exists('App\\Models\\PaymentGateway')) {
                $hostGateway = \App\Models\PaymentGateway::where('type', $provider . '_sms')->first();
                if ($hostGateway) {
                    $hostGateway->update([
                        'status'     => $enabled ? 1 : 0,
                        'title'      => $request->input('title') ?: $hostGateway->title,
                        'app_key'    => trim($request->input('number')),
                        'username'   => $request->input('type', 'Personal'),
                        'app_secret' => trim($request->input('webhook_token')),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Could not sync to host PaymentGateway: " . $e->getMessage());
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => ucfirst($provider) . ' credentials and settings updated successfully!',
            ]);
        }

        return redirect()->back()->with('success', ucfirst($provider) . ' credentials saved successfully!');
    }

    /**
     * Toggle enabled status of a provider.
     */
    public function toggleProvider(string $provider, Request $request)
    {
        $provider = strtolower($provider);
        $setting = MfsSmsSetting::getForProvider($provider);

        $currentConfig = MfsSms::getGatewayConfig($provider);
        $currentEnabled = $setting ? $setting->enabled : ($currentConfig['enabled'] ?? false);
        $newStatus = !$currentEnabled;

        MfsSmsSetting::setForProvider($provider, [
            'enabled'       => $newStatus,
            'number'        => $setting->number ?? ($currentConfig['number'] ?? '01700000000'),
            'type'          => $setting->type ?? ($currentConfig['type'] ?? 'Personal'),
            'webhook_token' => $setting->webhook_token ?? ($currentConfig['webhook_token'] ?? Str::random(24)),
        ]);

        // Sync toggle to host application's PaymentGateway model if it exists
        try {
            if (class_exists('App\\Models\\PaymentGateway')) {
                $hostGateway = \App\Models\PaymentGateway::where('type', $provider . '_sms')->first();
                if ($hostGateway) {
                    $hostGateway->status = $newStatus ? 1 : 0;
                    $hostGateway->save();
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Could not sync toggle to host PaymentGateway: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'enabled' => $newStatus,
            'message' => ucfirst($provider) . ' status changed to ' . ($newStatus ? 'ENABLED' : 'DISABLED'),
        ]);
    }

    /**
     * Generate a new cryptographically secure webhook token.
     */
    public function generateToken(string $provider)
    {
        $token = 'mfs_' . bin2hex(random_bytes(16));

        return response()->json([
            'success' => true,
            'token'   => $token,
        ]);
    }

    /**
     * View all transactions.
     */
    public function transactions(Request $request)
    {
        $query = MfsSmsTransaction::with('order');

        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('trx_id', 'LIKE', "%{$q}%")
                    ->orWhere('sender_number', 'LIKE', "%{$q}%")
                    ->orWhere('reference', 'LIKE', "%{$q}%")
                    ->orWhere('order_id', 'LIKE', "%{$q}%");
            });
        }

        if ($request->filled('provider')) {
            $query->where('provider', strtolower($request->input('provider')));
        }

        if ($request->filled('status')) {
            $query->where('status', strtolower($request->input('status')));
        }

        $transactions = $query->latest()->paginate(25);

        return view('mfs-sms-gateway::admin.transactions', compact('transactions'));
    }

    /**
     * Manually match an unmatched transaction to an order.
     */
    public function manualMatch($id, Request $request)
    {
        $request->validate([
            'order_id' => 'required',
        ]);

        $transaction = MfsSmsTransaction::findOrFail($id);
        $orderId = trim($request->input('order_id'));

        $orderModelClass = config('mfs-sms.order.model', 'App\\Models\\Order');
        $primaryKey = config('mfs-sms.order.primary_key', 'id');

        $order = $orderModelClass::where($primaryKey, $orderId)->first();
        if (!$order) {
            $invoiceCol = config('mfs-sms.order.invoice_column', 'invoice_id');
            if ($invoiceCol) {
                $order = $orderModelClass::where($invoiceCol, $orderId)->first();
            }
        }

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "Order #{$orderId} not found in database.",
            ], 404);
        }

        MfsSms::getOrderMatcher()->applyPaymentToOrder($order, $transaction);

        return response()->json([
            'success' => true,
            'message' => "Transaction {$transaction->trx_id} successfully linked and applied to Order #{$orderId}!",
        ]);
    }
}

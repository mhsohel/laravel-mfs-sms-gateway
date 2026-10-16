<?php

namespace Prantiksoft\MfsSmsGateway\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Prantiksoft\MfsSmsGateway\Facades\MfsSms;
use Prantiksoft\MfsSmsGateway\Models\MfsSmsTransaction;

class MfsSmsPaymentController extends Controller
{
    /**
     * Show customer automated payment screen.
     */
    /**
     * Show customer automated payment screen.
     */
    public function pay($order_id, Request $request)
    {
        $orderModelClass = config('mfs-sms.order.model', 'App\\Models\\Order');
        $primaryKey = config('mfs-sms.order.primary_key', 'id');

        $order = $orderModelClass::where($primaryKey, $order_id)->firstOrFail();

        // Check if already paid
        $statusCol = config('mfs-sms.order.status_column', 'order_status');
        $paidVal = config('mfs-sms.order.paid_status_value', 7);
        $payStatusCol = config('mfs-sms.order.payment_status_column', 'payment_status');
        $paidPayStatusVal = config('mfs-sms.order.paid_payment_status_value', 'paid');

        $currentStatus = (string) ($order->{$statusCol} ?? '');
        $currentPayStatus = strtolower((string) ($order->{$payStatusCol} ?? ''));

        if ($currentStatus === (string) $paidVal || $currentPayStatus === strtolower((string) $paidPayStatusVal) || $currentPayStatus === 'paid') {
            return redirect($this->resolveSuccessUrl($order));
        }

        // Determine provider (bkash, nagad, rocket)
        $provider = strtolower($request->query('provider') ?? $request->input('provider') ?? 'bkash');
        if (!in_array($provider, ['bkash', 'nagad', 'rocket'])) {
            $provider = 'bkash';
        }

        $gatewayConfig = MfsSms::getGatewayConfig($provider);

        $amountCol = config('mfs-sms.order.amount_column', 'amount');
        $payableAmount = (float) ($order->{$amountCol} ?? 0);

        $refPrefix = config('mfs-sms.order.reference_prefix', 'Order');
        $reference = "{$refPrefix} #{$order_id}";

        $phoneCol = config('mfs-sms.order.phone_column', 'phoneNumber');
        $senderPhone = $order->{$phoneCol} ?? '';

        $timeoutMinutes = (int) config('mfs-sms.order.timeout_minutes', 15);
        $expiresAt = now()->addMinutes($timeoutMinutes)->timestamp;

        return view('mfs-sms-gateway::pay', compact(
            'order',
            'order_id',
            'provider',
            'gatewayConfig',
            'payableAmount',
            'reference',
            'senderPhone',
            'expiresAt'
        ));
    }

    /**
     * Polling endpoint to check if order has been auto-verified.
     */
    public function checkStatus($order_id)
    {
        $orderModelClass = config('mfs-sms.order.model', 'App\\Models\\Order');
        $primaryKey = config('mfs-sms.order.primary_key', 'id');

        $order = $orderModelClass::where($primaryKey, $order_id)->first();
        if (!$order) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $statusCol = config('mfs-sms.order.status_column', 'order_status');
        $paidVal = config('mfs-sms.order.paid_status_value', 7);
        $payStatusCol = config('mfs-sms.order.payment_status_column', 'payment_status');
        $paidPayStatusVal = config('mfs-sms.order.paid_payment_status_value', 'paid');

        $currentStatus = (string) ($order->{$statusCol} ?? '');
        $currentPayStatus = strtolower((string) ($order->{$payStatusCol} ?? ''));

        $isPaid = ($currentStatus === (string) $paidVal) ||
                  ($currentPayStatus === strtolower((string) $paidPayStatusVal)) ||
                  ($currentPayStatus === 'paid');

        if ($isPaid) {
            $matchedTrx = MfsSmsTransaction::where('order_id', $order_id)->latest()->first();

            return response()->json([
                'status'       => 'paid',
                'redirect_url' => $this->resolveSuccessUrl($order),
                'trx_id'       => $matchedTrx ? $matchedTrx->trx_id : ($order->trx_id ?? null),
                'amount'       => $matchedTrx ? $matchedTrx->amount : null,
            ]);
        }

        return response()->json([
            'status'  => 'pending',
            'message' => 'Waiting for SMS auto-verification...',
        ]);
    }

    /**
     * Manual Fallback: Verify TrxID entered by customer.
     */
    public function manualVerify($order_id, Request $request)
    {
        $request->validate([
            'trx_id' => 'required|string|min:4|max:50',
        ]);

        $trxId = strtoupper(trim($request->input('trx_id')));
        $provider = strtolower($request->input('provider', 'bkash'));

        $orderModelClass = config('mfs-sms.order.model', 'App\\Models\\Order');
        $primaryKey = config('mfs-sms.order.primary_key', 'id');
        $order = $orderModelClass::where($primaryKey, $order_id)->firstOrFail();

        $amountCol = config('mfs-sms.order.amount_column', 'amount');
        $payableAmount = (float) ($order->{$amountCol} ?? 0);

        // 1. Check if this TrxID exists in our received SMS database
        $transaction = MfsSmsTransaction::where('trx_id', $trxId)->first();

        if ($transaction) {
            // If already matched to THIS order, return success
            if ($transaction->status === 'matched' && (string) $transaction->order_id === (string) $order_id) {
                return response()->json([
                    'success'      => true,
                    'message'      => 'Payment already verified for this order.',
                    'redirect_url' => $this->resolveSuccessUrl($order),
                ]);
            }

            // If matched to a DIFFERENT order, reject
            if ($transaction->status === 'matched' && (string) $transaction->order_id !== (string) $order_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This Transaction ID has already been used for another order.',
                ], 422);
            }

            // Verify amount matching to prevent fraud (e.g. paying Tk 10 for a Tk 50,000 order)
            if ($transaction->amount > 0 && $payableAmount > 0 && abs($transaction->amount - $payableAmount) > 1.5) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction amount (৳' . number_format($transaction->amount, 2) . ') does not match the order payable amount (৳' . number_format($payableAmount, 2) . ').',
                ], 422);
            }

            // Match and apply payment
            MfsSms::getOrderMatcher()->applyPaymentToOrder($order, $transaction);

            return response()->json([
                'success'      => true,
                'message'      => 'Transaction verified and matched successfully!',
                'redirect_url' => $this->resolveSuccessUrl($order),
            ]);
        }

        // 2. If SMS has not yet arrived on forwarder phone, create a pending transaction for manual review
        $phoneCol = config('mfs-sms.order.phone_column', 'phoneNumber');

        $newTrx = MfsSmsTransaction::create([
            'provider'      => $provider,
            'order_id'      => $order_id,
            'sender'        => ucfirst($provider) . ' (Manual Fallback)',
            'sender_number' => $request->input('sender_number', $order->{$phoneCol} ?? null),
            'amount'        => $payableAmount,
            'trx_id'        => $trxId,
            'reference'     => "Order #{$order_id}",
            'raw_sms'       => "Manual Entry by Customer for Order #{$order_id}: TrxID {$trxId}",
            'status'        => 'manual_verified',
            'meta'          => ['submitted_by_customer' => true, 'ip' => $request->ip()],
        ]);

        MfsSms::getOrderMatcher()->applyPaymentToOrder($order, $newTrx);

        return response()->json([
            'success'      => true,
            'message'      => 'Transaction ID submitted and order marked for verification!',
            'redirect_url' => $this->resolveSuccessUrl($order),
        ]);
    }

    /**
     * Extend countdown timer.
     */
    public function extendTimer($order_id)
    {
        $minutes = (int) config('mfs-sms.order.timeout_minutes', 15);
        $newExpiry = now()->addMinutes($minutes)->timestamp;

        return response()->json([
            'success'    => true,
            'new_expiry' => $newExpiry,
        ]);
    }

    /**
     * Resolve the success redirect URL.
     */
    protected function resolveSuccessUrl($order): string
    {
        $template = config('mfs-sms.order.success_url', '/customer/order-success/{order_id}');
        $primaryKey = config('mfs-sms.order.primary_key', 'id');
        $orderId = $order->{$primaryKey};

        $invoiceCol = config('mfs-sms.order.invoice_column', 'invoice_id');
        $invoiceId = $order->{$invoiceCol} ?? $orderId;

        $targetUrl = str_replace(
            ['{order_id}', '{id}', '{invoice_id}'],
            [$orderId, $orderId, $invoiceId],
            $template
        );

        if (\Illuminate\Support\Str::startsWith($targetUrl, ['http://', 'https://'])) {
            return $targetUrl;
        }

        return url($targetUrl);
    }
}

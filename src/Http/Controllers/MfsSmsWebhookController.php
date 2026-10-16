<?php

namespace Prantiksoft\MfsSmsGateway\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Prantiksoft\MfsSmsGateway\Facades\MfsSms;

class MfsSmsWebhookController extends Controller
{
    /**
     * Handle incoming forwarded SMS from Android apps.
     *
     * @param Request $request
     * @param string|null $provider
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, ?string $provider = null)
    {
        // 1. Validate Secret Token
        $token = $request->query('token')
            ?? $request->input('token')
            ?? $request->header('X-Webhook-Token')
            ?? $request->bearerToken();

        if (!MfsSms::validateToken($token, $provider)) {
            Log::warning("[MfsSmsGateway] Unauthorized webhook attempt: Token='{$token}', Provider='{$provider}', IP=" . $request->ip());
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized: Invalid or missing webhook security token.',
            ], 401);
        }

        // 2. Extract SMS Text and Sender
        $smsBody = $request->input('text')
            ?? $request->input('message')
            ?? $request->input('body')
            ?? $request->input('sms_body')
            ?? $request->input('content')
            ?? $request->getContent();

        $sender = $request->input('from')
            ?? $request->input('sender')
            ?? $request->input('phone');

        if (empty($smsBody)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Empty SMS payload received.',
            ], 422);
        }

        Log::info("[MfsSmsGateway] Incoming SMS received. Provider: " . ($provider ?: 'Auto') . ", Sender: {$sender}, Body: {$smsBody}");

        // 3. Process SMS via Manager
        $meta = [
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'received_at'=> now()->toIso8601String(),
        ];

        $result = MfsSms::processSms($smsBody, $sender, $provider, $meta);

        $httpCode = ($result['success'] || !empty($result['is_duplicate'])) ? 200 : 422;

        return response()->json([
            'status'         => $result['success'] ? 'success' : 'failed',
            'matched'        => $result['matched'] ?? false,
            'order_id'       => $result['order_id'] ?? null,
            'is_duplicate'   => $result['is_duplicate'] ?? false,
            'trx_id'         => $result['trx_id'] ?? null,
            'amount'         => $result['amount'] ?? null,
            'provider'       => $result['provider'] ?? $provider,
            'transaction_id' => $result['transaction_id'] ?? null,
            'message'        => $result['message'],
        ], $httpCode);
    }

    /**
     * Admin Simulator Endpoint for testing parser without real Android device.
     */
    public function testSimulator(Request $request)
    {
        $testSms = $request->input('test_sms');
        $provider = $request->input('provider');

        if (empty($testSms)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide test SMS text.',
            ], 422);
        }

        $result = MfsSms::processSms($testSms, ucfirst($provider ?: 'Simulator'), $provider, ['simulated_by' => 'admin_test']);

        return response()->json($result);
    }
}

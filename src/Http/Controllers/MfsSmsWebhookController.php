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
        // Try decoding raw JSON if payload was sent without application/json header
        $rawContent = $request->getContent();
        $jsonData = null;
        if (!empty($rawContent) && (\Illuminate\Support\Str::startsWith(trim($rawContent), '{') || $request->isJson())) {
            $decoded = json_decode($rawContent, true);
            if (is_array($decoded)) {
                $jsonData = $decoded;
            }
        }

        // 1. Validate Secret Token
        $token = $request->query('token')
            ?? $request->input('token')
            ?? ($jsonData['token'] ?? null)
            ?? ($jsonData['secret'] ?? null)
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
            ?? ($jsonData['text'] ?? null)
            ?? ($jsonData['message'] ?? null)
            ?? ($jsonData['body'] ?? null)
            ?? ($jsonData['sms_body'] ?? null)
            ?? ($jsonData['content'] ?? null)
            ?? ($jsonData ? null : $rawContent);

        $sender = $request->input('from')
            ?? $request->input('sender')
            ?? $request->input('phone')
            ?? ($jsonData['from'] ?? null)
            ?? ($jsonData['sender'] ?? null)
            ?? ($jsonData['phone'] ?? null);

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

        // Acknowledge non-payment or duplicate SMS with 200 so Android forwarder doesn't loop retry endlessly
        $statusStr = $result['success']
            ? 'success'
            : (!empty($result['is_duplicate']) ? 'duplicate' : 'ignored');

        return response()->json([
            'status'         => $statusStr,
            'matched'        => $result['matched'] ?? false,
            'order_id'       => $result['order_id'] ?? null,
            'is_duplicate'   => $result['is_duplicate'] ?? false,
            'trx_id'         => $result['trx_id'] ?? null,
            'amount'         => $result['amount'] ?? null,
            'provider'       => $result['provider'] ?? $provider,
            'transaction_id' => $result['transaction_id'] ?? null,
            'message'        => $result['message'],
        ], 200);
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

<?php

namespace Prantiksoft\MfsSmsGateway;

use Illuminate\Support\Facades\Log;
use Prantiksoft\MfsSmsGateway\Contracts\SmsParserInterface;
use Prantiksoft\MfsSmsGateway\Events\MfsSmsReceived;
use Prantiksoft\MfsSmsGateway\Models\MfsSmsTransaction;
use Prantiksoft\MfsSmsGateway\Parsers\BkashParser;
use Prantiksoft\MfsSmsGateway\Parsers\NagadParser;
use Prantiksoft\MfsSmsGateway\Parsers\RocketParser;
use Prantiksoft\MfsSmsGateway\Services\OrderMatcher;

class MfsSmsManager
{
    /**
     * @var array<string, SmsParserInterface>
     */
    protected array $parsers = [];

    protected OrderMatcher $orderMatcher;

    public function __construct(OrderMatcher $orderMatcher)
    {
        $this->orderMatcher = $orderMatcher;
        $this->registerDefaultParsers();
    }

    /**
     * Register default parsers (bKash, Nagad, Rocket).
     */
    protected function registerDefaultParsers(): void
    {
        $this->registerParser(new BkashParser());
        $this->registerParser(new NagadParser());
        $this->registerParser(new RocketParser());
    }

    /**
     * Register a custom parser.
     */
    public function registerParser(SmsParserInterface $parser): self
    {
        $this->parsers[$parser->getProvider()] = $parser;
        return $this;
    }

    /**
     * Get all registered parsers.
     */
    public function getParsers(): array
    {
        return $this->parsers;
    }

    /**
     * Process an incoming SMS message.
     *
     * @param string $smsBody
     * @param string|null $sender
     * @param string|null $explicitProvider
     * @param array $meta
     * @return array
     */
    public function processSms(string $smsBody, ?string $sender = null, ?string $explicitProvider = null, array $meta = []): array
    {
        $parser = $this->resolveParser($smsBody, $sender, $explicitProvider);

        if (!$parser) {
            return [
                'success' => false,
                'message' => 'Unrecognized SMS format or unsupported MFS provider.',
                'raw_sms' => $smsBody,
            ];
        }

        $parsed = $parser->parse($smsBody, $sender);

        if (!$parsed['is_valid']) {
            return [
                'success' => false,
                'message' => 'Could not extract valid TrxID or Amount from SMS.',
                'extracted' => $parsed,
                'raw_sms' => $smsBody,
            ];
        }

        $provider = $parsed['provider'];
        $trxId    = $parsed['trx_id'];
        $amount   = $parsed['amount'];

        // 1. Duplicate check (prevent replay / fraud)
        $existing = MfsSmsTransaction::where('trx_id', $trxId)->first();
        if ($existing) {
            return [
                'success'      => false,
                'is_duplicate' => true,
                'message'      => "TrxID {$trxId} has already been processed previously.",
                'transaction'  => $existing,
                'order_id'     => $existing->order_id,
            ];
        }

        // 2. Record Transaction
        $transaction = MfsSmsTransaction::create([
            'provider'      => $provider,
            'sender'        => $sender ?: ucfirst($provider),
            'sender_number' => $parsed['sender_number'],
            'amount'        => $amount,
            'trx_id'        => $trxId,
            'reference'     => $parsed['reference'],
            'raw_sms'       => $smsBody,
            'status'        => 'unmatched',
            'meta'          => $meta,
        ]);

        // Fire SMS received event
        event(new MfsSmsReceived($transaction, $parsed));

        // 3. Attempt matching against pending orders
        $matchedOrder = $this->orderMatcher->match($transaction);

        return [
            'success'        => true,
            'matched'        => $matchedOrder !== null,
            'order_id'       => $matchedOrder ? $matchedOrder->id : null,
            'trx_id'         => $trxId,
            'amount'         => $amount,
            'provider'       => $provider,
            'transaction_id' => $transaction->id,
            'message'        => $matchedOrder
                ? "Payment successfully matched to Order #{$matchedOrder->id}!"
                : "SMS saved. No pending order immediately matched. Saved for auto-detection polling.",
        ];
    }

    /**
     * Resolve the appropriate parser.
     */
    protected function resolveParser(string $smsBody, ?string $sender = null, ?string $explicitProvider = null): ?SmsParserInterface
    {
        if ($explicitProvider && isset($this->parsers[strtolower($explicitProvider)])) {
            return $this->parsers[strtolower($explicitProvider)];
        }

        // Try checking each parser by canHandle
        foreach ($this->parsers as $parser) {
            if ($parser->canHandle($smsBody, $sender)) {
                return $parser;
            }
        }

        return null;
    }

    /**
     * Get Gateway Config.
     */
    public function getGatewayConfig(string $provider): array
    {
        $provider = strtolower($provider);
        return config("mfs-sms.gateways.{$provider}", []);
    }

    /**
     * Check if security token is valid for a provider or generic webhook.
     */
    public function validateToken(?string $token, ?string $provider = null): bool
    {
        if (empty($token)) {
            return false;
        }

        if ($provider) {
            $expected = config("mfs-sms.gateways.{$provider}.webhook_token");
            return !empty($expected) && hash_equals((string)$expected, (string)$token);
        }

        // Check against any configured gateway token
        foreach (config('mfs-sms.gateways', []) as $gw) {
            if (!empty($gw['webhook_token']) && hash_equals((string)$gw['webhook_token'], (string)$token)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get OrderMatcher instance.
     */
    public function getOrderMatcher(): OrderMatcher
    {
        return $this->orderMatcher;
    }
}

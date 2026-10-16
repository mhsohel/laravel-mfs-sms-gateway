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
        $primaryKey = config('mfs-sms.order.primary_key', 'id');
        $matchedOrderId = $matchedOrder ? ($matchedOrder->{$primaryKey} ?? $matchedOrder->id) : null;

        return [
            'success'        => true,
            'matched'        => $matchedOrder !== null,
            'order_id'       => $matchedOrderId,
            'trx_id'         => $trxId,
            'amount'         => $amount,
            'provider'       => $provider,
            'transaction_id' => $transaction->id,
            'message'        => $matchedOrder
                ? "Payment successfully matched to Order #{$matchedOrderId}!"
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
        $defaults = config("mfs-sms.gateways.{$provider}", []);

        // 1. Check package's own MfsSmsSetting table
        try {
            $dbSetting = \Prantiksoft\MfsSmsGateway\Models\MfsSmsSetting::getForProvider($provider);
            if ($dbSetting) {
                return array_merge($defaults, array_filter([
                    'enabled'       => $dbSetting->enabled,
                    'title'         => $dbSetting->title ?: ($defaults['title'] ?? null),
                    'number'        => $dbSetting->number ?: ($defaults['number'] ?? null),
                    'type'          => $dbSetting->type ?: ($defaults['type'] ?? 'Personal'),
                    'webhook_token' => $dbSetting->webhook_token ?: ($defaults['webhook_token'] ?? null),
                    'brand_color'   => $dbSetting->brand_color ?: ($defaults['brand_color'] ?? null),
                    'ussd_code'     => $dbSetting->ussd_code ?: ($defaults['ussd_code'] ?? null),
                    'sender_id'     => $dbSetting->sender_id ?: ($defaults['sender_id'] ?? null),
                ], fn($val) => $val !== null));
            }
        } catch (\Throwable $e) {
            // Silently fallback
        }

        // 2. Check host application's PaymentGateway model if available
        try {
            $hostGatewayClass = 'App\\Models\\PaymentGateway';
            if (class_exists($hostGatewayClass)) {
                $hostGw = $hostGatewayClass::where('type', "{$provider}_sms")->first();
                if ($hostGw) {
                    return array_merge($defaults, array_filter([
                        'enabled'       => (bool) $hostGw->status,
                        'title'         => $hostGw->title ?: ($defaults['title'] ?? null),
                        'number'        => $hostGw->app_key ?: ($defaults['number'] ?? null),
                        'type'          => $hostGw->username ?: ($defaults['type'] ?? 'Personal'),
                        'webhook_token' => $hostGw->app_secret ?: ($defaults['webhook_token'] ?? null),
                    ], fn($val) => $val !== null));
                }
            }
        } catch (\Throwable $e) {
            // Silently fallback
        }

        return $defaults;
    }

    /**
     * Check if security token is valid for a provider or generic webhook.
     */
    public function validateToken(?string $token, ?string $provider = null): bool
    {
        if (empty($token)) {
            return false;
        }

        // 1. Check database settings if available
        try {
            if ($provider) {
                $dbSetting = \Prantiksoft\MfsSmsGateway\Models\MfsSmsSetting::getForProvider($provider);
                if ($dbSetting && !empty($dbSetting->webhook_token)) {
                    if (hash_equals((string) $dbSetting->webhook_token, (string) $token)) {
                        return true;
                    }
                }
            } else {
                $allDb = \Prantiksoft\MfsSmsGateway\Models\MfsSmsSetting::whereNotNull('webhook_token')->get();
                foreach ($allDb as $st) {
                    if (hash_equals((string) $st->webhook_token, (string) $token)) {
                        return true;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently continue
        }

        // 2. Check host application PaymentGateway tokens if available
        try {
            $hostGatewayClass = 'App\\Models\\PaymentGateway';
            if (class_exists($hostGatewayClass)) {
                if ($provider) {
                    $hostGw = $hostGatewayClass::where('type', "{$provider}_sms")->first();
                    if ($hostGw && !empty($hostGw->app_secret) && hash_equals((string) $hostGw->app_secret, (string) $token)) {
                        return true;
                    }
                } else {
                    $hostGws = $hostGatewayClass::where('type', 'LIKE', '%_sms')->whereNotNull('app_secret')->get();
                    foreach ($hostGws as $hgw) {
                        if (hash_equals((string) $hgw->app_secret, (string) $token)) {
                            return true;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently continue
        }

        // 3. Check against configured gateway tokens in config file
        if ($provider) {
            $expected = config("mfs-sms.gateways.{$provider}.webhook_token");
            return !empty($expected) && hash_equals((string) $expected, (string) $token);
        }

        foreach (config('mfs-sms.gateways', []) as $gw) {
            if (!empty($gw['webhook_token']) && hash_equals((string) $gw['webhook_token'], (string) $token)) {
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

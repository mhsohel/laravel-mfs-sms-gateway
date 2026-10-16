<?php

namespace Prantiksoft\MfsSmsGateway\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array processSms(string $smsBody, ?string $sender = null, ?string $explicitProvider = null, array $meta = [])
 * @method static array getGatewayConfig(string $provider)
 * @method static bool validateToken(?string $token, ?string $provider = null)
 * @method static \Prantiksoft\MfsSmsGateway\MfsSmsManager registerParser(\Prantiksoft\MfsSmsGateway\Contracts\SmsParserInterface $parser)
 * @method static \Prantiksoft\MfsSmsGateway\Services\OrderMatcher getOrderMatcher()
 *
 * @see \Prantiksoft\MfsSmsGateway\MfsSmsManager
 */
class MfsSms extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mfs-sms';
    }
}

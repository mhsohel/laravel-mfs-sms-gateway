<?php

namespace Prantiksoft\MfsSmsGateway\Events;

use Illuminate\Queue\SerializesModels;
use Prantiksoft\MfsSmsGateway\Models\MfsSmsTransaction;

class MfsPaymentMatched
{
    use SerializesModels;

    public $order;
    public MfsSmsTransaction $transaction;
    public string $provider;

    public function __construct($order, MfsSmsTransaction $transaction, string $provider)
    {
        $this->order = $order;
        $this->transaction = $transaction;
        $this->provider = $provider;
    }
}

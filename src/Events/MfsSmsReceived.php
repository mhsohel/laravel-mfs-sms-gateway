<?php

namespace Prantiksoft\MfsSmsGateway\Events;

use Illuminate\Queue\SerializesModels;
use Prantiksoft\MfsSmsGateway\Models\MfsSmsTransaction;

class MfsSmsReceived
{
    use SerializesModels;

    public MfsSmsTransaction $transaction;
    public array $parsedData;

    public function __construct(MfsSmsTransaction $transaction, array $parsedData = [])
    {
        $this->transaction = $transaction;
        $this->parsedData = $parsedData;
    }
}

<?php

namespace Prantiksoft\MfsSmsGateway\Parsers;

use Prantiksoft\MfsSmsGateway\Contracts\SmsParserInterface;

class BkashParser implements SmsParserInterface
{
    public function getProvider(): string
    {
        return 'bkash';
    }

    public function canHandle(string $smsBody, ?string $sender = null): bool
    {
        if ($sender && (stripos($sender, 'bkash') !== false || $sender === '16247')) {
            return true;
        }

        // Check distinctive bKash text patterns
        return (stripos($smsBody, 'You have received Tk') !== false || stripos($smsBody, 'received Tk') !== false)
            && (stripos($smsBody, 'TrxID') !== false || stripos($smsBody, 'TxnID') !== false)
            && (stripos($smsBody, 'bKash') !== false || stripos($smsBody, 'Ref') !== false);
    }

    public function parse(string $smsBody, ?string $sender = null): array
    {
        $amount = null;
        $trxId = null;
        $senderNumber = null;
        $reference = null;

        // 1. Extract Amount: "You have received Tk 7,710.00" or "Tk 7,710.00"
        if (preg_match('/(?:received\s+Tk|Tk\.?)\s*([0-9,]+(?:\.[0-9]{1,2})?)/i', $smsBody, $matches)) {
            $amount = (float) str_replace(',', '', $matches[1]);
        }

        // 2. Extract TrxID: "TrxID 9K8B7C6D5E" or "TxnID: 9K8B7C6D5E"
        if (preg_match('/(?:TrxID|TxnID)[\s:]*([A-Za-z0-9]+)/i', $smsBody, $matches)) {
            $trxId = strtoupper(trim($matches[1]));
        }

        // 3. Extract Sender Phone Number: "from 01735254295" or "from +8801735254295"
        if (preg_match('/from\s*(\+?880?1[3-9]\d{8}|01[3-9]\d{8})/i', $smsBody, $matches)) {
            $senderNumber = preg_replace('/^\+?88/', '', trim($matches[1]));
        }

        // 4. Extract Reference: "Ref B2B-202610-8379" or "Ref: 46"
        if (preg_match('/Ref[:\s]+([^\.\,\;\n]+)/i', $smsBody, $matches)) {
            $ref = trim($matches[1]);
            // Remove trailing words like Fee, Balance, at
            $ref = preg_replace('/\s+(Fee|Balance|at|\.)$/i', '', $ref);
            $reference = trim($ref);
        }

        $isValid = !empty($trxId) && ($amount !== null && $amount > 0);

        return [
            'provider'      => $this->getProvider(),
            'amount'        => $amount,
            'trx_id'        => $trxId,
            'sender_number' => $senderNumber,
            'reference'     => $reference,
            'is_valid'      => $isValid,
        ];
    }
}

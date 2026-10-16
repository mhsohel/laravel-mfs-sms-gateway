<?php

namespace Prantiksoft\MfsSmsGateway\Parsers;

use Prantiksoft\MfsSmsGateway\Contracts\SmsParserInterface;

class NagadParser implements SmsParserInterface
{
    public function getProvider(): string
    {
        return 'nagad';
    }

    public function canHandle(string $smsBody, ?string $sender = null): bool
    {
        if ($sender && (stripos($sender, 'nagad') !== false || $sender === '16167')) {
            return true;
        }

        // Check distinctive Nagad text patterns
        return (stripos($smsBody, 'Money Received') !== false || stripos($smsBody, 'Nagad') !== false)
            && (stripos($smsBody, 'TxnID:') !== false || stripos($smsBody, 'TxnID') !== false);
    }

    public function parse(string $smsBody, ?string $sender = null): array
    {
        $amount = null;
        $trxId = null;
        $senderNumber = null;
        $reference = null;

        // 1. Extract Amount: "Amount: Tk 7,710.00" or "Tk 7,710.00"
        if (preg_match('/Amount:\s*Tk\s*([0-9,]+(?:\.[0-9]{1,2})?)/i', $smsBody, $matches) ||
            preg_match('/(?:received|amount|Tk\.?)\s*([0-9,]+(?:\.[0-9]{1,2})?)/i', $smsBody, $matches)) {
            $amount = (float) str_replace(',', '', $matches[1]);
        }

        // 2. Extract TxnID: "TxnID: 72J9KA21" or "TxnID 72J9KA21"
        if (preg_match('/TxnID[\s:]*([A-Za-z0-9]+)/i', $smsBody, $matches) ||
            preg_match('/TrxID[\s:]*([A-Za-z0-9]+)/i', $smsBody, $matches)) {
            $trxId = strtoupper(trim($matches[1]));
        }

        // 3. Extract Sender Phone Number: "Sender: 01735254295" or "from 01735254295"
        if (preg_match('/Sender:\s*(\+?880?1[3-9]\d{8}|01[3-9]\d{8})/i', $smsBody, $matches) ||
            preg_match('/from\s*(\+?880?1[3-9]\d{8}|01[3-9]\d{8})/i', $smsBody, $matches)) {
            $senderNumber = preg_replace('/^\+?88/', '', trim($matches[1]));
        }

        // 4. Extract Reference: "Ref: B2B-202610-8379" or "Ref B2B-202610-8379"
        if (preg_match('/Ref:\s*([^\.\,\;\n]+)/i', $smsBody, $matches) ||
            preg_match('/Ref\s+([^\.\,\;\n]+)/i', $smsBody, $matches)) {
            $ref = trim($matches[1]);
            // Remove trailing noise words
            $ref = preg_replace('/\s+(Current|Balance|at|\.)$/i', '', $ref);
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

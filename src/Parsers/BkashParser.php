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
        return (stripos($smsBody, 'received Tk') !== false || stripos($smsBody, 'received payment Tk') !== false || stripos($smsBody, 'payment received Tk') !== false || stripos($smsBody, 'bKash') !== false)
            && (stripos($smsBody, 'TrxID') !== false || stripos($smsBody, 'TxnID') !== false);
    }

    public function parse(string $smsBody, ?string $sender = null): array
    {
        $amount = null;
        $trxId = null;
        $senderNumber = null;
        $reference = null;

        // 1. Extract Amount: "You have received Tk 7,710.00" or "You have received payment Tk 1,500.00" or "Tk 7,710.00"
        if (preg_match('/(?:received(?:\s+payment)?\s+Tk\.?)\s*([0-9,]+(?:\.[0-9]{1,2})?)/i', $smsBody, $matches) ||
            preg_match('/(?:payment\s+received\s+Tk\.?)\s*([0-9,]+(?:\.[0-9]{1,2})?)/i', $smsBody, $matches) ||
            preg_match('/(?:Tk\.?)\s*([0-9,]+(?:\.[0-9]{1,2})?)/i', $smsBody, $matches)) {
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

        // 4. Extract Reference: cleanly terminate before Fee, Balance, TrxID, at, or punctuation
        if (preg_match('/Ref[:\s]+(.*?)(?=\.|\n|\r|Fee|Balance|TrxID|TxnID|at\s+\d|\,|$)/i', $smsBody, $matches)) {
            $ref = trim($matches[1]);
            // Strip any remaining noise
            $ref = preg_replace('/\s+(Fee|Balance|at|\.)$/i', '', $ref);
            $reference = trim($ref) ?: null;
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

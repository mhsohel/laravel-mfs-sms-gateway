<?php

namespace Prantiksoft\MfsSmsGateway\Parsers;

use Prantiksoft\MfsSmsGateway\Contracts\SmsParserInterface;

class RocketParser implements SmsParserInterface
{
    public function getProvider(): string
    {
        return 'rocket';
    }

    public function canHandle(string $smsBody, ?string $sender = null): bool
    {
        if ($sender && (stripos($sender, 'rocket') !== false || $sender === '16216' || stripos($sender, 'dbbl') !== false)) {
            return true;
        }

        return (stripos($smsBody, 'DBBL') !== false || stripos($smsBody, 'Rocket') !== false || stripos($smsBody, 'A/C:') !== false)
            && (stripos($smsBody, 'received') !== false || stripos($smsBody, 'credit') !== false)
            && (stripos($smsBody, 'Txn') !== false || stripos($smsBody, 'Trx') !== false);
    }

    public function parse(string $smsBody, ?string $sender = null): array
    {
        $amount = null;
        $trxId = null;
        $senderNumber = null;
        $reference = null;

        // DBBL Rocket: "Tk. 500.00 received from A/C: 017352542951. TxnId: 198273645."
        if (preg_match('/(?:Tk\.?|Amount:?|Credit:?\s*Tk\.?)\s*([0-9,]+(?:\.[0-9]{1,2})?)/i', $smsBody, $matches)) {
            $amount = (float) str_replace(',', '', $matches[1]);
        }

        if (preg_match('/(?:Txn\s*Id|Trx\s*Id)[\s:]*([A-Za-z0-9]+)/i', $smsBody, $matches)) {
            $trxId = strtoupper(trim($matches[1]));
        }

        if (preg_match('/(?:from\s+A\/C:?|from|A\/C:?)\s*(\+?880?1[3-9]\d{8,9}|01[3-9]\d{8,9})/i', $smsBody, $matches)) {
            $senderNumber = preg_replace('/^\+?88/', '', trim($matches[1]));
        }

        if (preg_match('/Ref[:\s]+(.*?)(?=\.|\n|\r|Balance|TxnId|TrxId|at\s+\d|\,|$)/i', $smsBody, $matches)) {
            $ref = trim($matches[1]);
            $ref = preg_replace('/\s+(Balance|at|\.)$/i', '', $ref);
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

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
        if ($sender && (stripos($sender, 'rocket') !== false || $sender === '16216')) {
            return true;
        }

        return (stripos($smsBody, 'DBBL') !== false || stripos($smsBody, 'Rocket') !== false)
            && (stripos($smsBody, 'received') !== false || stripos($smsBody, 'credit') !== false)
            && stripos($smsBody, 'TxnId') !== false;
    }

    public function parse(string $smsBody, ?string $sender = null): array
    {
        $amount = null;
        $trxId = null;
        $senderNumber = null;
        $reference = null;

        // DBBL Rocket: "Tk. 500.00 received from A/C: 017352542951. TxnId: 198273645."
        if (preg_match('/(?:Tk\.?|Amount:?)\s*([0-9,]+(?:\.[0-9]{1,2})?)/i', $smsBody, $matches)) {
            $amount = (float) str_replace(',', '', $matches[1]);
        }

        if (preg_match('/TxnId[:\s]+([A-Za-z0-9]+)/i', $smsBody, $matches)) {
            $trxId = strtoupper(trim($matches[1]));
        }

        if (preg_match('/(?:from\s+A\/C:?|from)\s*(\+?880?1[3-9]\d{8,9}|01[3-9]\d{8,9})/i', $smsBody, $matches)) {
            $senderNumber = preg_replace('/^\+?88/', '', trim($matches[1]));
        }

        if (preg_match('/Ref[:\s]+([^\.\,\;\n]+)/i', $smsBody, $matches)) {
            $reference = trim($matches[1]);
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

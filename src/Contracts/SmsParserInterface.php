<?php

namespace Prantiksoft\MfsSmsGateway\Contracts;

interface SmsParserInterface
{
    /**
     * Determine if this parser can handle the given SMS.
     *
     * @param string $smsBody
     * @param string|null $sender
     * @return bool
     */
    public function canHandle(string $smsBody, ?string $sender = null): bool;

    /**
     * Parse raw SMS and extract transaction details.
     *
     * @param string $smsBody
     * @param string|null $sender
     * @return array{
     *     provider: string,
     *     amount: float|null,
     *     trx_id: string|null,
     *     sender_number: string|null,
     *     reference: string|null,
     *     is_valid: bool
     * }
     */
    public function parse(string $smsBody, ?string $sender = null): array;

    /**
     * Get provider key (e.g. bkash, nagad, rocket).
     *
     * @return string
     */
    public function getProvider(): string;
}

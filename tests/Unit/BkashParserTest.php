<?php

namespace Prantiksoft\MfsSmsGateway\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prantiksoft\MfsSmsGateway\Parsers\BkashParser;

class BkashParserTest extends TestCase
{
    protected BkashParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new BkashParser();
    }

    public function test_can_handle_bkash_sender()
    {
        $this->assertTrue($this->parser->canHandle('Random text', 'bKash'));
        $this->assertTrue($this->parser->canHandle('Random text', '16247'));
        $this->assertFalse($this->parser->canHandle('Random text', 'GP'));
    }

    public function test_can_handle_distinctive_text()
    {
        $sms = "You have received Tk 7,710.00 from 01735254295. Ref Order #46. Fee Tk 0.00. Balance Tk 25,400.00. TrxID 9K8B7C6D5E at 16/10/2026 18:30";
        $this->assertTrue($this->parser->canHandle($sms, null));
    }

    public function test_parses_personal_received_sms()
    {
        $sms = "You have received Tk 7,710.00 from 01735254295. Ref Order #46. Fee Tk 0.00. Balance Tk 25,400.00. TrxID 9K8B7C6D5E at 16/10/2026 18:30";
        $result = $this->parser->parse($sms, 'bKash');

        $this->assertTrue($result['is_valid']);
        $this->assertEquals('bkash', $result['provider']);
        $this->assertEquals(7710.00, $result['amount']);
        $this->assertEquals('9K8B7C6D5E', $result['trx_id']);
        $this->assertEquals('01735254295', $result['sender_number']);
        $this->assertEquals('Order #46', $result['reference']);
    }

    public function test_parses_merchant_payment_sms()
    {
        $sms = "You have received payment Tk 1,500.00 from 01711000000. Ref 46 Fee Tk 0.00 Balance Tk 15,000.00. TrxID 9K8B7C6D5E";
        $result = $this->parser->parse($sms, 'bKash');

        $this->assertTrue($result['is_valid']);
        $this->assertEquals(1500.00, $result['amount']);
        $this->assertEquals('9K8B7C6D5E', $result['trx_id']);
        $this->assertEquals('01711000000', $result['sender_number']);
        $this->assertEquals('46', $result['reference']);
    }

    public function test_parses_invoice_reference_format()
    {
        $sms = "You have received Tk 500.00 from 01900000000. Ref: B2B-202610-8379. Fee Tk 0.00. Balance Tk 1,200.00. TrxID ABC123XYZ";
        $result = $this->parser->parse($sms, 'bKash');

        $this->assertTrue($result['is_valid']);
        $this->assertEquals('B2B-202610-8379', $result['reference']);
        $this->assertEquals('ABC123XYZ', $result['trx_id']);
        $this->assertEquals(500.00, $result['amount']);
    }

    public function test_invalid_sms_returns_is_valid_false()
    {
        $sms = "Your balance is Tk 50.00.";
        $result = $this->parser->parse($sms);

        $this->assertFalse($result['is_valid']);
        $this->assertNull($result['trx_id']);
    }
}

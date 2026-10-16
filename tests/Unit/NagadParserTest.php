<?php

namespace Prantiksoft\MfsSmsGateway\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prantiksoft\MfsSmsGateway\Parsers\NagadParser;

class NagadParserTest extends TestCase
{
    protected NagadParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new NagadParser();
    }

    public function test_can_handle_nagad_sender()
    {
        $this->assertTrue($this->parser->canHandle('Random text', 'NAGAD'));
        $this->assertTrue($this->parser->canHandle('Random text', '16167'));
        $this->assertFalse($this->parser->canHandle('Random text', 'GP'));
    }

    public function test_can_handle_distinctive_text()
    {
        $sms = "Money Received. Amount: Tk 7,710.00. Sender: 01735254295. Ref: 46. TxnID: 72J9KA21. Balance: Tk 15,200.00.";
        $this->assertTrue($this->parser->canHandle($sms));
    }

    public function test_parses_money_received_sms()
    {
        $sms = "Money Received. Amount: Tk 7,710.00. Sender: 01735254295. Ref: 46. TxnID: 72J9KA21. Balance: Tk 15,200.00. 16/10/2026 18:30";
        $result = $this->parser->parse($sms, 'NAGAD');

        $this->assertTrue($result['is_valid']);
        $this->assertEquals('nagad', $result['provider']);
        $this->assertEquals(7710.00, $result['amount']);
        $this->assertEquals('72J9KA21', $result['trx_id']);
        $this->assertEquals('01735254295', $result['sender_number']);
        $this->assertEquals('46', $result['reference']);
    }

    public function test_parses_dot_tk_format_and_alphanumeric_reference()
    {
        $sms = "Money Received. Amount: Tk. 1,500.00. Sender: 01711000000. Ref: B2B-202610-8379. TxnID: 72J9KA21. Balance: Tk 15,200.00.";
        $result = $this->parser->parse($sms, '16167');

        $this->assertTrue($result['is_valid']);
        $this->assertEquals(1500.00, $result['amount']);
        $this->assertEquals('72J9KA21', $result['trx_id']);
        $this->assertEquals('01711000000', $result['sender_number']);
        $this->assertEquals('B2B-202610-8379', $result['reference']);
    }

    public function test_parses_merchant_pay_sms()
    {
        $sms = "Merchant Pay Received. Amount: Tk 500.00. Sender: 01900000000. Ref: Order #46. TxnID: 888AAA. Current Balance: Tk 10,000.";
        $result = $this->parser->parse($sms, 'NAGAD');

        $this->assertTrue($result['is_valid']);
        $this->assertEquals(500.00, $result['amount']);
        $this->assertEquals('888AAA', $result['trx_id']);
        $this->assertEquals('01900000000', $result['sender_number']);
        $this->assertEquals('Order #46', $result['reference']);
    }
}

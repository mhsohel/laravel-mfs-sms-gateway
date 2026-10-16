<?php

namespace Prantiksoft\MfsSmsGateway\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prantiksoft\MfsSmsGateway\Parsers\RocketParser;

class RocketParserTest extends TestCase
{
    protected RocketParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new RocketParser();
    }

    public function test_can_handle_rocket_sender()
    {
        $this->assertTrue($this->parser->canHandle('Random text', '16216'));
        $this->assertTrue($this->parser->canHandle('Random text', 'Rocket'));
        $this->assertFalse($this->parser->canHandle('Random text', 'bKash'));
    }

    public function test_can_handle_distinctive_text()
    {
        $sms = "Tk. 500.00 received from A/C: 017352542951. TxnId: 198273645. Ref: 46. Balance: Tk. 2000.00";
        $this->assertTrue($this->parser->canHandle($sms));
    }

    public function test_parses_rocket_received_sms()
    {
        $sms = "Tk. 500.00 received from A/C: 017352542951. TxnId: 198273645. Ref: 46. Balance: Tk. 2000.00";
        $result = $this->parser->parse($sms, '16216');

        $this->assertTrue($result['is_valid']);
        $this->assertEquals('rocket', $result['provider']);
        $this->assertEquals(500.00, $result['amount']);
        $this->assertEquals('198273645', $result['trx_id']);
        $this->assertEquals('017352542951', $result['sender_number']);
        $this->assertEquals('46', $result['reference']);
    }

    public function test_parses_rocket_credit_format()
    {
        $sms = "Tk 1,500.00 received from 017110000009. TxnId: 987654321. Ref: B2B-202610-8379";
        $result = $this->parser->parse($sms, 'Rocket');

        $this->assertTrue($result['is_valid']);
        $this->assertEquals(1500.00, $result['amount']);
        $this->assertEquals('987654321', $result['trx_id']);
        $this->assertEquals('017110000009', $result['sender_number']);
        $this->assertEquals('B2B-202610-8379', $result['reference']);
    }
}

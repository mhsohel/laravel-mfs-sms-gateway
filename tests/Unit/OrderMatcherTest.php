<?php

namespace Prantiksoft\MfsSmsGateway\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Prantiksoft\MfsSmsGateway\Services\OrderMatcher;
use Prantiksoft\MfsSmsGateway\MfsSmsManager;

class OrderMatcherTest extends TestCase
{
    protected OrderMatcher $orderMatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderMatcher = new OrderMatcher();
    }

    public function test_is_amount_matching()
    {
        $mockOrder = new class {
            public $amount = 1500.00;
            public function getTable() { return 'orders'; }
        };

        // When Schema has no column or mock object without Schema, default checks
        $this->assertTrue($this->orderMatcher->isAmountMatching($mockOrder, 1500.00));
        $this->assertTrue($this->orderMatcher->isAmountMatching($mockOrder, 1500.50));
        $this->assertFalse($this->orderMatcher->isAmountMatching($mockOrder, 100.00));
    }

    public function test_is_pending_detection()
    {
        $unpaidOrder = new class {
            public $order_status = 1;
            public $payment_status = 'unpaid';
            public function getTable() { return 'orders'; }
        };
        $this->assertTrue($this->orderMatcher->isPending($unpaidOrder));

        $paidStatusOrder = new class {
            public $order_status = 7;
            public $payment_status = 'unpaid';
            public function getTable() { return 'orders'; }
        };
        $this->assertFalse($this->orderMatcher->isPending($paidStatusOrder));

        $paidPaymentOrder = new class {
            public $order_status = 1;
            public $payment_status = 'paid';
            public function getTable() { return 'orders'; }
        };
        $this->assertFalse($this->orderMatcher->isPending($paidPaymentOrder));
    }

    public function test_has_column_resilience()
    {
        $mockOrder = new class {
            public $custom_col = 'value';
            public function getTable() { return 'test_table'; }
        };

        $this->assertTrue($this->orderMatcher->hasColumn($mockOrder, 'custom_col'));
        $this->assertFalse($this->orderMatcher->hasColumn($mockOrder, 'non_existent_column'));
    }

    public function test_token_validation_in_manager()
    {
        $manager = new MfsSmsManager($this->orderMatcher);
        $this->assertFalse($manager->validateToken(null));
        $this->assertFalse($manager->validateToken(''));
        $this->assertTrue($manager->validateToken('mfs_secret_token_bkash_2026', 'bkash'));
        $this->assertFalse($manager->validateToken('wrong_token', 'bkash'));
    }
}

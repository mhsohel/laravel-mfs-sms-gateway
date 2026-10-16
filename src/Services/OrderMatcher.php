<?php

namespace Prantiksoft\MfsSmsGateway\Services;

use Illuminate\Support\Facades\Log;
use Prantiksoft\MfsSmsGateway\Events\MfsPaymentMatched;
use Prantiksoft\MfsSmsGateway\Models\MfsSmsTransaction;

class OrderMatcher
{
    /**
     * Match a transaction against pending orders.
     *
     * @param MfsSmsTransaction $transaction
     * @param mixed $targetOrderId
     * @return mixed|null
     */
    public function match(MfsSmsTransaction $transaction, mixed $targetOrderId = null)
    {
        $orderModelClass = config('mfs-sms.order.model', 'App\\Models\\Order');
        if (!class_exists($orderModelClass)) {
            Log::warning("[MfsSmsGateway] Configured Order model does not exist: {$orderModelClass}");
            return null;
        }

        $order = null;

        // 1. If explicit target order ID provided (e.g. manual verification or single order wait screen)
        if ($targetOrderId) {
            $order = $this->findOrderById($orderModelClass, $targetOrderId);
        }

        // 2. Try matching by Reference (e.g. "Order #46", "Invoice 46", "46", "B2B-202610-8379")
        if (!$order && !empty($transaction->reference)) {
            $order = $this->matchByReference($orderModelClass, $transaction->reference, (float) $transaction->amount);
        }

        // 3. Try matching by Customer Phone Number + Exact Amount within timeout window
        if (!$order && !empty($transaction->sender_number) && $transaction->amount > 0) {
            $order = $this->matchByPhoneAndAmount($orderModelClass, $transaction->sender_number, (float) $transaction->amount);
        }

        // 4. Try matching latest pending order with exact amount within timeout window
        if (!$order && $transaction->amount > 0) {
            $order = $this->matchRecentByAmount($orderModelClass, (float) $transaction->amount, $transaction->provider);
        }

        if ($order) {
            $this->applyPaymentToOrder($order, $transaction);
            return $order;
        }

        return null;
    }

    /**
     * Find order by primary key.
     */
    protected function findOrderById(string $modelClass, mixed $orderId)
    {
        $primaryKey = config('mfs-sms.order.primary_key', 'id');
        return $modelClass::where($primaryKey, $orderId)->first();
    }

    /**
     * Match order by reference text.
     */
    protected function matchByReference(string $modelClass, string $refText, float $amount)
    {
        $primaryKey = config('mfs-sms.order.primary_key', 'id');
        $invoiceCol = config('mfs-sms.order.invoice_column', 'invoice_id');
        $tableName  = (new $modelClass)->getTable();

        // 1. Direct match on invoice column
        if ($invoiceCol && $this->hasColumn($tableName, $invoiceCol)) {
            $order = $modelClass::where($invoiceCol, $refText)->first();
            if ($order && $this->isPending($order) && $this->isAmountMatching($order, $amount)) {
                return $order;
            }
        }

        // 2. Direct match on primary key
        $order = $modelClass::where($primaryKey, $refText)->first();
        if ($order && $this->isPending($order) && $this->isAmountMatching($order, $amount)) {
            return $order;
        }

        // 3. Extract numbers from reference (e.g. from "Order #46", "Invoice 46", "Ref: 46")
        if (preg_match('/#\s*(\d+)/', $refText, $matches) || preg_match('/(\d+)/', $refText, $matches)) {
            $number = $matches[1];

            $order = $modelClass::where($primaryKey, $number)->first();
            if ($order && $this->isPending($order) && $this->isAmountMatching($order, $amount)) {
                return $order;
            }

            if ($invoiceCol && $this->hasColumn($tableName, $invoiceCol)) {
                $order = $modelClass::where($invoiceCol, $number)->first();
                if ($order && $this->isPending($order) && $this->isAmountMatching($order, $amount)) {
                    return $order;
                }
            }
        }

        return null;
    }

    /**
     * Verify if order amount matches transaction amount within reasonable tolerance.
     */
    public function isAmountMatching($order, float $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }

        $amountCol = config('mfs-sms.order.amount_column', 'amount');

        if (!$this->hasColumn($order, $amountCol)) {
            return true;
        }

        $orderAmount = (float) ($order->{$amountCol} ?? 0);

        // Tolerance of ±1.5 BDT for rounding differences
        return abs($orderAmount - $amount) <= 1.5;
    }

    /**
     * Match order by customer phone and exact amount.
     */
    protected function matchByPhoneAndAmount(string $modelClass, string $senderNumber, float $amount)
    {
        $phoneCol = config('mfs-sms.order.phone_column', 'phoneNumber');
        $amountCol = config('mfs-sms.order.amount_column', 'amount');
        $timeoutMinutes = (int) config('mfs-sms.order.timeout_minutes', 15);
        $tableName = (new $modelClass)->getTable();

        // Normalize to last 10 digits
        $cleanPhone = substr(preg_replace('/[^0-9]/', '', $senderNumber), -10);
        if (strlen($cleanPhone) < 10) {
            return null;
        }

        $query = $modelClass::where('created_at', '>=', now()->subMinutes($timeoutMinutes + 30));

        if ($this->hasColumn($tableName, $phoneCol)) {
            $query->where($phoneCol, 'LIKE', "%{$cleanPhone}%");
        }

        if ($this->hasColumn($tableName, $amountCol)) {
            $query->whereBetween($amountCol, [$amount - 1.5, $amount + 1.5]);
        }

        $order = $query->latest()->first();
        if ($order && $this->isPending($order)) {
            return $order;
        }

        return null;
    }

    /**
     * Match recent pending order with exact amount.
     */
    protected function matchRecentByAmount(string $modelClass, float $amount, string $provider)
    {
        $amountCol = config('mfs-sms.order.amount_column', 'amount');
        $payMethodCol = config('mfs-sms.order.payment_method_column', 'payment_method');
        $timeoutMinutes = (int) config('mfs-sms.order.timeout_minutes', 15);
        $tableName = (new $modelClass)->getTable();

        $query = $modelClass::where('created_at', '>=', now()->subMinutes($timeoutMinutes + 15));

        if ($this->hasColumn($tableName, $amountCol)) {
            $query->whereBetween($amountCol, [$amount - 1.5, $amount + 1.5]);
        }

        if ($this->hasColumn($tableName, $payMethodCol)) {
            $query->where(function ($q) use ($provider, $payMethodCol) {
                $q->where($payMethodCol, 'LIKE', "%{$provider}%")
                  ->orWhereNull($payMethodCol);
            });
        }

        // Prevent cross-order collision if multiple distinct orders share the same amount
        $orders = $query->latest()->take(2)->get();
        if ($orders->count() > 1) {
            Log::notice("[MfsSmsGateway] Multiple pending orders found with amount {$amount}. Awaiting phone/reference or manual TrxID verification to prevent misallocation.");
            return null;
        }

        $order = $orders->first();
        if ($order && $this->isPending($order)) {
            return $order;
        }

        return null;
    }

    /**
     * Check if order is pending / unpaid.
     */
    public function isPending($order): bool
    {
        $statusCol = config('mfs-sms.order.status_column', 'order_status');
        $paidVal = config('mfs-sms.order.paid_status_value', 7);
        $payStatusCol = config('mfs-sms.order.payment_status_column', 'payment_status');
        $paidPayStatusVal = config('mfs-sms.order.paid_payment_status_value', 'paid');

        if (isset($order->{$statusCol}) && (string) $order->{$statusCol} === (string) $paidVal) {
            return false;
        }

        if (isset($order->{$payStatusCol})) {
            $currentPayStatus = strtolower((string) $order->{$payStatusCol});
            if ($currentPayStatus === strtolower((string) $paidPayStatusVal) || $currentPayStatus === 'paid') {
                return false;
            }
        }

        return true;
    }

    /**
     * Update order & transaction to paid and fire events.
     */
    public function applyPaymentToOrder($order, MfsSmsTransaction $transaction)
    {
        $statusCol = config('mfs-sms.order.status_column', 'order_status');
        $paidStatusVal = config('mfs-sms.order.paid_status_value', 7);
        $payStatusCol = config('mfs-sms.order.payment_status_column', 'payment_status');
        $paidPayStatusVal = config('mfs-sms.order.paid_payment_status_value', 'paid');
        $payMethodCol = config('mfs-sms.order.payment_method_column', 'payment_method');

        $primaryKey = config('mfs-sms.order.primary_key', 'id');
        $orderId = $order->{$primaryKey};

        // 1. Update Order
        $orderTable = $order->getTable();
        $updates = [];

        if ($this->hasColumn($order, $statusCol)) {
            $order->{$statusCol} = $paidStatusVal;
            $updates[$statusCol] = $paidStatusVal;
        }

        if ($this->hasColumn($order, $payStatusCol)) {
            $order->{$payStatusCol} = $paidPayStatusVal;
            $updates[$payStatusCol] = $paidPayStatusVal;
        }

        if ($this->hasColumn($order, $payMethodCol)) {
            $displayMethod = ucfirst($transaction->provider) . ' (SMS Auto)';
            $order->{$payMethodCol} = $displayMethod;
            $updates[$payMethodCol] = $displayMethod;
        }

        if ($this->hasColumn($order, 'trx_id')) {
            $order->trx_id = $transaction->trx_id;
            $updates['trx_id'] = $transaction->trx_id;
        } elseif ($this->hasColumn($order, 'transaction_id')) {
            $order->transaction_id = $transaction->trx_id;
            $updates['transaction_id'] = $transaction->trx_id;
        }

        $order->save();

        // 2. Update Transaction
        $transaction->update([
            'order_id'   => $orderId,
            'status'     => 'matched',
            'matched_at' => now(),
        ]);

        Log::info("[MfsSmsGateway] Order #{$orderId} successfully matched with {$transaction->provider} TrxID: {$transaction->trx_id} for Tk {$transaction->amount}");

        // 3. Fire Custom Hook Callback if provided
        $hook = config('mfs-sms.hooks.on_payment_success');
        if (!empty($hook)) {
            try {
                if (is_callable($hook)) {
                    call_user_func($hook, $order, $transaction);
                } elseif (is_string($hook) && str_contains($hook, '@')) {
                    app()->call($hook, ['order' => $order, 'transaction' => $transaction]);
                }
            } catch (\Throwable $e) {
                Log::error("[MfsSmsGateway] on_payment_success hook failed: " . $e->getMessage());
            }
        }

        // 4. Fire Laravel Event
        event(new MfsPaymentMatched($order, $transaction, $transaction->provider));
    }

    /**
     * Check if a column exists on a model or database table safely.
     */
    public function hasColumn($orderOrTable, string $column): bool
    {
        try {
            if (is_object($orderOrTable)) {
                if (method_exists($orderOrTable, 'getAttributes') && array_key_exists($column, $orderOrTable->getAttributes())) {
                    return true;
                }
                if (isset($orderOrTable->{$column})) {
                    return true;
                }
                $tableName = method_exists($orderOrTable, 'getTable') ? $orderOrTable->getTable() : (string) $orderOrTable;
            } else {
                $tableName = (string) $orderOrTable;
            }

            if (app()->bound('db.schema')) {
                return \Illuminate\Support\Facades\Schema::hasColumn($tableName, $column);
            }
        } catch (\Throwable $e) {
            // Silently fallback
        }

        return is_object($orderOrTable) && isset($orderOrTable->{$column});
    }
}

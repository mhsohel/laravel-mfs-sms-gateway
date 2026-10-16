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
     * @param int|null $targetOrderId
     * @return mixed|null
     */
    public function match(MfsSmsTransaction $transaction, ?int $targetOrderId = null)
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
            $order = $this->matchByReference($orderModelClass, $transaction->reference, $transaction->amount);
        }

        // 3. Try matching by Customer Phone Number + Exact Amount within timeout window
        if (!$order && !empty($transaction->sender_number) && $transaction->amount > 0) {
            $order = $this->matchByPhoneAndAmount($orderModelClass, $transaction->sender_number, $transaction->amount);
        }

        // 4. Try matching latest pending order with exact amount within 15-minute window
        if (!$order && $transaction->amount > 0) {
            $order = $this->matchRecentByAmount($orderModelClass, $transaction->amount, $transaction->provider);
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
    protected function findOrderById(string $modelClass, int $orderId)
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

        // Extract numbers from reference
        if (preg_match('/(\d+)/', $refText, $matches)) {
            $number = (int) $matches[1];
            $order = $modelClass::where($primaryKey, $number)->first();
            if ($order && $this->isPending($order)) {
                return $order;
            }

            if ($invoiceCol && \Illuminate\Support\Facades\Schema::hasColumn((new $modelClass)->getTable(), $invoiceCol)) {
                $order = $modelClass::where($invoiceCol, $refText)->orWhere($invoiceCol, $number)->first();
                if ($order && $this->isPending($order)) {
                    return $order;
                }
            }
        }

        return null;
    }

    /**
     * Match order by customer phone and exact amount.
     */
    protected function matchByPhoneAndAmount(string $modelClass, string $senderNumber, float $amount)
    {
        $phoneCol = config('mfs-sms.order.phone_column', 'phoneNumber');
        $amountCol = config('mfs-sms.order.amount_column', 'amount');
        $timeoutMinutes = (int) config('mfs-sms.order.timeout_minutes', 15);

        // Normalize last 10 digits
        $cleanPhone = substr(preg_replace('/[^0-9]/', '', $senderNumber), -10);

        $query = $modelClass::where('created_at', '>=', now()->subMinutes($timeoutMinutes + 30));

        if (\Illuminate\Support\Facades\Schema::hasColumn((new $modelClass)->getTable(), $phoneCol)) {
            $query->where($phoneCol, 'LIKE', "%{$cleanPhone}%");
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn((new $modelClass)->getTable(), $amountCol)) {
            $query->whereBetween($amountCol, [$amount - 1.0, $amount + 1.0]);
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

        $query = $modelClass::where('created_at', '>=', now()->subMinutes($timeoutMinutes));

        if (\Illuminate\Support\Facades\Schema::hasColumn((new $modelClass)->getTable(), $amountCol)) {
            $query->whereBetween($amountCol, [$amount - 1.0, $amount + 1.0]);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn((new $modelClass)->getTable(), $payMethodCol)) {
            $query->where(function ($q) use ($provider, $payMethodCol) {
                $q->where($payMethodCol, 'LIKE', "%{$provider}%")
                  ->orWhereNull($payMethodCol);
            });
        }

        $order = $query->latest()->first();
        if ($order && $this->isPending($order)) {
            return $order;
        }

        return null;
    }

    /**
     * Check if order is pending / unpaid.
     */
    protected function isPending($order): bool
    {
        $statusCol = config('mfs-sms.order.status_column', 'order_status');
        $paidVal = config('mfs-sms.order.paid_status_value', 7);
        $payStatusCol = config('mfs-sms.order.payment_status_column', 'payment_status');

        if (isset($order->{$statusCol}) && $order->{$statusCol} == $paidVal) {
            return false;
        }

        if (isset($order->{$payStatusCol}) && strtolower($order->{$payStatusCol}) === 'paid') {
            return false;
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

        if (\Illuminate\Support\Facades\Schema::hasColumn($orderTable, $statusCol)) {
            $order->{$statusCol} = $paidStatusVal;
            $updates[$statusCol] = $paidStatusVal;
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn($orderTable, $payStatusCol)) {
            $order->{$payStatusCol} = $paidPayStatusVal;
            $updates[$payStatusCol] = $paidPayStatusVal;
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn($orderTable, $payMethodCol)) {
            $displayMethod = ucfirst($transaction->provider) . ' (SMS Auto)';
            $order->{$payMethodCol} = $displayMethod;
            $updates[$payMethodCol] = $displayMethod;
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn($orderTable, 'trx_id')) {
            $order->trx_id = $transaction->trx_id;
            $updates['trx_id'] = $transaction->trx_id;
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
        if (is_callable($hook)) {
            try {
                call_user_func($hook, $order, $transaction);
            } catch (\Throwable $e) {
                Log::error("[MfsSmsGateway] on_payment_success hook failed: " . $e->getMessage());
            }
        }

        // 4. Fire Laravel Event
        event(new MfsPaymentMatched($order, $transaction, $transaction->provider));
    }
}

<?php

namespace Prantiksoft\MfsSmsGateway\Models;

use Illuminate\Database\Eloquent\Model;

class MfsSmsTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount'     => 'float',
        'matched_at' => 'datetime',
        'meta'       => 'array',
    ];

    public function getTable()
    {
        return config('mfs-sms.table_name', 'mfs_sms_transactions');
    }

    /**
     * Relationship with host application's Order model.
     */
    public function order()
    {
        $orderModel = config('mfs-sms.order.model', 'App\\Models\\Order');
        $primaryKey = config('mfs-sms.order.primary_key', 'id');

        return $this->belongsTo($orderModel, 'order_id', $primaryKey);
    }

    /**
     * Scope for provider.
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', strtolower($provider));
    }

    /**
     * Scope for unmatched records.
     */
    public function scopeUnmatched($query)
    {
        return $query->where('status', 'unmatched');
    }

    /**
     * Scope for matched records.
     */
    public function scopeMatched($query)
    {
        return $query->where('status', 'matched');
    }
}

<?php

namespace Prantiksoft\MfsSmsGateway\Models;

use Illuminate\Database\Eloquent\Model;

class MfsSmsSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
        'extra'   => 'array',
    ];

    public function getTable()
    {
        return config('mfs-sms.settings_table_name', 'mfs_sms_settings') ?? 'mfs_sms_settings';
    }

    /**
     * Get setting record for a specific provider.
     */
    public static function getForProvider(string $provider): ?self
    {
        try {
            return static::where('provider', strtolower($provider))->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Set or update settings for a provider.
     */
    public static function setForProvider(string $provider, array $attributes): self
    {
        return static::updateOrCreate(
            ['provider' => strtolower($provider)],
            $attributes
        );
    }
}

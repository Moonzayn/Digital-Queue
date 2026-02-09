<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get setting value by key with cache
     */
    public static function get(string $key, string $default = ''): string
    {
        return Cache::remember('setting_' . $key, 60, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set setting value and clear cache
     */
    public static function set(string $key, string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('setting_' . $key);
    }

    /**
     * Get all settings as array
     */
    public static function allAsArray(): array
    {
        return Cache::remember('settings_all', 60, function () {
            return self::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $keys = self::pluck('key');
        foreach ($keys as $key) {
            Cache::forget('setting_' . $key);
        }
        Cache::forget('settings_all');
    }

    /**
     * Check if store is currently open
     */
    public static function isStoreOpen(): bool
    {
        if (self::get('is_open', '1') !== '1') {
            return false;
        }

        $now = now();
        $openTime = self::getOpenTime();
        $closeTime = self::getCloseTime();

        $openToday = $now->copy()->setTimeFromTimeString($openTime);
        $closeToday = $now->copy()->setTimeFromTimeString($closeTime);

        return $now->between($openToday, $closeToday);
    }

    public static function getOpenTime(): string
    {
        return self::get('open_hour', '08:00');
    }

    public static function getCloseTime(): string
    {
        return self::get('close_hour', '17:00');
    }

    public static function getSlotDuration(): int
    {
        return (int) self::get('slot_duration', '15');
    }

    public static function getSlotCapacity(): int
    {
        return (int) self::get('slot_capacity', '4');
    }

    public static function getStoreName(): string
    {
        return self::get('store_name', 'Digital Queue');
    }
}
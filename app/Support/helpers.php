<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (! function_exists('setting')) {
    /**
     * Read a store setting by key, cached for the request/app lifetime.
     * Values are stored as JSON in the `settings` table.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', function () {
            return Setting::query()->pluck('value', 'key')->all();
        });

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }
}

if (! function_exists('setting_set')) {
    /**
     * Persist a store setting and flush the cache.
     */
    function setting_set(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }
}

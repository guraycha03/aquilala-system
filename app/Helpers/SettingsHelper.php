<?php

namespace App\Helpers;

use App\Models\SystemSetting;

/**
 * SettingsHelper — Reads and writes lightweight key/value system settings,
 * and formats harvest yields according to the admin's interface preference.
 *
 * Currently scoped to how the Harvest Report Summary quantities are shown
 * (metric tons, kilograms, or 50-kg cavans), but built to grow.
 */
class SettingsHelper
{
    /** In-memory cache of the settings table, keyed by column `key`. */
    protected static ?array $cache = null;

    /**
     * Read a setting value.
     *
     * @return mixed The stored value (string) or $default when unset.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$cache === null) {
            self::$cache = SystemSetting::pluck('value', 'key')->all();
        }

        return array_key_exists($key, self::$cache) ? self::$cache[$key] : $default;
    }

    /**
     * Write a setting value (upsert).
     */
    public static function set(string $key, mixed $value): void
    {
        SystemSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);

        if (self::$cache === null) {
            self::$cache = [];
        }
        self::$cache[$key] = (string) $value;
    }

    /**
     * Which unit the admin wants harvest yields shown in.
     * Valid: 'auto', 'mt', 'kg', 'cavan'.
     */
    public static function yieldUnit(): string
    {
        $unit = self::get('yield_unit', 'mt');

        return in_array($unit, ['auto', 'mt', 'kg', 'cavan'], true) ? $unit : 'mt';
    }

    /**
     * Decimal places for the numeric part of a yield (0–2).
     */
    public static function yieldDecimals(): int
    {
        return max(0, min(2, (int) self::get('yield_decimals', 1)));
    }

    /**
     * Thousands separator for formatted yields.
     * Stored value is one of ',', '.', or '' (none).
     */
    public static function yieldSeparator(): string
    {
        $sep = self::get('yield_number_separator', ',');

        return in_array($sep, [',', '.', ''], true) ? $sep : ',';
    }

    /**
     * Whether a zero / empty total should render as a dash instead of "0".
     */
    public static function yieldZeroDash(): bool
    {
        return in_array(self::get('yield_zero_dash', '0'), ['1', 'true'], true);
    }

    /**
     * Format a yield (already stored in metric tons) using the current
     * interface preference. Returns a compact string such as "2.4 MT",
     * "850 kg", "48 cavan", or "—".
     */
    public static function formatYield(mixed $metricTons): string
    {
        $mt = (float) $metricTons;
        $unit = self::yieldUnit();
        $dec = self::yieldDecimals();
        $sep = self::yieldSeparator();

        if ($mt == 0 && self::yieldZeroDash()) {
            return '—';
        }

        if ($unit === 'auto') {
            $unit = $mt < 1 ? 'kg' : 'mt';
        }

        $num = match ($unit) {
            'kg'    => round($mt * 1000, $dec),
            'cavan' => round($mt * 20, $dec),   // 1 cavan ≈ 50 kg of palay
            default => round($mt, $dec),
        };

        $suffix = match ($unit) {
            'kg'    => 'kg',
            'cavan' => 'cavan',
            default => 'MT',
        };

        $decPoint = $sep === '.' ? ',' : '.';

        return numbers($num, $dec, $decPoint, $sep) . ' ' . $suffix;
    }

    /**
     * Human-readable caption describing the current yield unit.
     */
    public static function yieldUnitCaption(): string
    {
        return match (self::yieldUnit()) {
            'kg'    => 'in kilograms',
            'cavan' => 'in 50-kg cavans',
            'auto'  => 'auto units (kg below 1 MT, otherwise MT)',
            default => 'in metric tons',
        };
    }

    /**
     * Short label of the current yield unit (for badges/headers).
     */
    public static function yieldUnitLabel(): string
    {
        return match (self::yieldUnit()) {
            'kg'    => 'Kilograms (kg)',
            'cavan' => 'Cavan / Bag (50 kg)',
            'auto'  => 'Automatic',
            default => 'Metric Tons (MT)',
        };
    }
}

/**
 * Convenience wrapper for SettingsHelper::get().
 */
function system_setting(string $key, mixed $default = null): mixed
{
    return SettingsHelper::get($key, $default);
}

/**
 * Convenience wrapper for SettingsHelper::set().
 */
function system_set_setting(string $key, mixed $value): void
{
    SettingsHelper::set($key, $value);
}

/**
 * Tiny helper to keep number_format() calls readable.
 */
function numbers(float $num, int $decimals, string $decPoint, string $thousandsSep): string
{
    return number_format($num, $decimals, $decPoint, $thousandsSep);
}
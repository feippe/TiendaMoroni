<?php
declare(strict_types=1);

namespace TiendaMoroni\Models;

use TiendaMoroni\Core\Database;

class SettingModel
{
    /** In-request cache to avoid repeated DB hits for the same key. */
    private static array $cache = [];

    /**
     * Fetch a single setting value by key.
     * Returns null if the key doesn't exist or on DB error.
     */
    public static function get(string $key): ?string
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        try {
            $row = Database::fetchOne(
                'SELECT value FROM site_settings WHERE setting_key = ? LIMIT 1',
                [$key]
            );
            $value = $row ? (string) $row['value'] : null;
        } catch (\Throwable) {
            $value = null;
        }

        self::$cache[$key] = $value;
        return $value;
    }

    /**
     * Update or insert a setting value (upsert).
     */
    public static function set(string $key, string $value): void
    {
        Database::query(
            'INSERT INTO site_settings (setting_key, value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)',
            [$key, $value]
        );

        // Invalidate cache entry so next get() reflects the new value.
        self::$cache[$key] = $value;
    }
}

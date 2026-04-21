<?php
require_once __DIR__ . '/../helpers/db.php';

class WeatherCache
{
    public static function clearExpired(): void
    {
        db()->exec('DELETE FROM weather_cache WHERE expires_at <= NOW()');
    }
}

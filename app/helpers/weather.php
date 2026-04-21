<?php
require_once __DIR__ . '/db.php';

function weather_condition_from_code(int $code): string
{
    $map = [
        0 => 'Clear sky', 1 => 'Mainly clear', 2 => 'Partly cloudy', 3 => 'Overcast',
        45 => 'Fog', 48 => 'Depositing rime fog', 51 => 'Light drizzle', 53 => 'Drizzle',
        55 => 'Dense drizzle', 61 => 'Slight rain', 63 => 'Rain', 65 => 'Heavy rain',
        71 => 'Slight snow', 73 => 'Snow', 75 => 'Heavy snow', 80 => 'Rain showers',
        81 => 'Rain showers', 82 => 'Violent rain showers', 95 => 'Thunderstorm'
    ];

    return $map[$code] ?? 'Weather update unavailable';
}

function fetch_json(string $url): ?array
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "User-Agent: WeatherHub/1.0\r\n"
        ]
    ]);

    $json = @file_get_contents($url, false, $context);
    if ($json === false) {
        return null;
    }

    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

function get_cached_weather(string $city): ?array
{
    $stmt = db()->prepare('SELECT payload_json FROM weather_cache WHERE city_name = :city AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
    $stmt->execute(['city' => $city]);
    $row = $stmt->fetch();

    return $row ? json_decode($row['payload_json'], true) : null;
}

function cache_weather(string $city, array $payload): void
{
    $minutes = (require __DIR__ . '/../config/app.php')['weather_cache_minutes'];
    $stmt = db()->prepare('INSERT INTO weather_cache (city_name, payload_json, expires_at) VALUES (:city, :payload, DATE_ADD(NOW(), INTERVAL :minutes MINUTE))');
    $stmt->bindValue(':city', $city);
    $stmt->bindValue(':payload', json_encode($payload));
    $stmt->bindValue(':minutes', $minutes, PDO::PARAM_INT);
    $stmt->execute();
}

function get_weather_for_city(string $city): ?array
{
    $city = trim($city);
    if ($city === '') {
        return null;
    }

    $cached = get_cached_weather($city);
    if ($cached) {
        return $cached;
    }

    $geo = fetch_json('https://geocoding-api.open-meteo.com/v1/search?count=1&name=' . urlencode($city));
    if (!$geo || empty($geo['results'][0])) {
        return null;
    }

    $place = $geo['results'][0];
    $forecast = fetch_json('https://api.open-meteo.com/v1/forecast?latitude=' . urlencode((string) $place['latitude']) . '&longitude=' . urlencode((string) $place['longitude']) . '&current=temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code&daily=weather_code,temperature_2m_max,temperature_2m_min&timezone=auto');
    if (!$forecast) {
        return null;
    }

    $payload = [
        'city' => $place['name'],
        'country' => $place['country'] ?? '',
        'latitude' => $place['latitude'],
        'longitude' => $place['longitude'],
        'current' => [
            'temperature' => $forecast['current']['temperature_2m'] ?? null,
            'humidity' => $forecast['current']['relative_humidity_2m'] ?? null,
            'wind' => $forecast['current']['wind_speed_10m'] ?? null,
            'code' => $forecast['current']['weather_code'] ?? null,
            'condition' => weather_condition_from_code((int) ($forecast['current']['weather_code'] ?? 0)),
        ],
        'daily' => [],
    ];

    $dates = $forecast['daily']['time'] ?? [];
    $maxs = $forecast['daily']['temperature_2m_max'] ?? [];
    $mins = $forecast['daily']['temperature_2m_min'] ?? [];
    $codes = $forecast['daily']['weather_code'] ?? [];

    foreach ($dates as $i => $date) {
        $payload['daily'][] = [
            'date' => $date,
            'max' => $maxs[$i] ?? null,
            'min' => $mins[$i] ?? null,
            'condition' => weather_condition_from_code((int) ($codes[$i] ?? 0)),
        ];
    }

    cache_weather($city, $payload);
    return $payload;
}

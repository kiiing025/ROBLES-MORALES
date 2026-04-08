<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../models/SavedLocation.php';
require_once __DIR__ . '/../models/SearchHistory.php';

class WeatherController
{
    private SavedLocation $savedLocationModel;
    private SearchHistory $searchHistoryModel;

    public function __construct(PDO $pdo)
    {
        $this->savedLocationModel = new SavedLocation($pdo);
        $this->searchHistoryModel = new SearchHistory($pdo);
    }

    public function search(int $userId, string $cityName): array
    {
        $cityName = trim($cityName);

        if ($cityName === '') {
            setFlash('danger', 'Please enter a city name.');
            redirect('weather.php');
        }

        $this->searchHistoryModel->create($userId, $cityName);

        return [
            'city' => $cityName,
            'condition' => 'Partly Cloudy',
            'temperature' => 29,
            'humidity' => 78,
            'wind' => 12,
            'source' => 'Sample midterm-ready output. Replace this with a real weather API in the final phase.',
        ];
    }

    public function saveLocation(int $userId, string $cityName): void
    {
        $cityName = trim($cityName);

        if ($cityName === '') {
            setFlash('danger', 'No city name was provided to save.');
            redirect('weather.php');
        }

        if ($this->savedLocationModel->existsForUser($userId, $cityName)) {
            setFlash('warning', 'That location is already saved.');
            redirect('weather.php?city=' . urlencode($cityName));
        }

        $this->savedLocationModel->create($userId, $cityName);
        setFlash('success', 'Location saved successfully.');
        redirect('weather.php?city=' . urlencode($cityName));
    }
}

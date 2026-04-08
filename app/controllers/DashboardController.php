<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/SavedLocation.php';
require_once __DIR__ . '/../models/SearchHistory.php';
require_once __DIR__ . '/../models/UserPreference.php';

class DashboardController
{
    private SavedLocation $savedLocationModel;
    private SearchHistory $searchHistoryModel;
    private UserPreference $preferenceModel;

    public function __construct(PDO $pdo)
    {
        $this->savedLocationModel = new SavedLocation($pdo);
        $this->searchHistoryModel = new SearchHistory($pdo);
        $this->preferenceModel = new UserPreference($pdo);
    }

    public function getDashboardData(int $userId): array
    {
        return [
            'saved_locations' => $this->savedLocationModel->allByUser($userId),
            'search_history' => $this->searchHistoryModel->allByUser($userId),
            'preferences' => $this->preferenceModel->getByUser($userId),
        ];
    }
}

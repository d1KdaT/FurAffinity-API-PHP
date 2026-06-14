<?php

declare(strict_types=1);

require_once file_exists(__DIR__ . '/config.php')
    ? __DIR__ . '/config.php'
    : __DIR__ . '/config.example.php';

require_once __DIR__ . '/../vendor/autoload.php';

use FurAffinity\Exchange;
use FurAffinity\FavoriteType;

$submissionId = 60800395;

try {
    $fa = new Exchange($settings);
    $result = $fa->toggleFavorite(FavoriteType::Fav, $submissionId);

    if ($result === 1) {
        echo "Submission {$submissionId} has been added to your favorites.\n";
    } elseif ($result === 2) {
        echo "Submission {$submissionId} is already in your favorites.\n";
    } elseif ($result === 3) {
        echo "Owner of {$submissionId} blocked '{$settings['username']}'.\n";
    } else {
        echo "Failed to add submission {$submissionId} to favorites.\n";
    }
} catch (Throwable $e) {
    echo "Error: " . get_class($e) . ": " . $e->getMessage() . "\n";
}

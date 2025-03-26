<?php

declare(strict_types=1);

require_once file_exists(__DIR__ . '/config.php')
    ? __DIR__ . '/config.php'
    : __DIR__ . '/config.example.php';

require_once __DIR__ . '/../vendor/autoload.php';

use FurAffinity\Exchange;

$lastKnownId = 0; // Set this to your last processed submission ID if needed

try {
    $fa = new Exchange($settings);
    $newIds = $fa->getNewMsgSubmissions($lastKnownId);

    if (!empty($newIds)) {
        echo "New submission IDs since #{$lastKnownId}:\n";
        foreach ($newIds as $id) {
            echo "- $id\n";
        }
    } else {
        echo "No new submissions found.\n";
    }
} catch (Throwable $e) {
    echo "Error: " . get_class($e) . ": " . $e->getMessage() . "\n";
}

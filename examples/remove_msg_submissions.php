<?php

declare(strict_types=1);

require_once file_exists(__DIR__ . '/config.php')
    ? __DIR__ . '/config.php'
    : __DIR__ . '/config.example.php';

require_once __DIR__ . '/../vendor/autoload.php';

use FurAffinity\Exchange;

$submissionIds = [60800395, 12345678]; // Replace with real submission IDs from your message center

try {
    $fa = new Exchange($settings);
    $success = $fa->removeMsgSubmissions($submissionIds);

    if ($success) {
        echo "Submissions have been successfully removed from your message center.\n";
    } else {
        echo "Failed to remove submissions. Wrong FurAffinity answer.\n";
    }
} catch (Throwable $e) {
    echo "Error: " . get_class($e) . ": " . $e->getMessage() . "\n";
}

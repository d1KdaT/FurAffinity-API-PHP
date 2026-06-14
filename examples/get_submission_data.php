<?php

declare(strict_types=1);

require_once file_exists(__DIR__ . '/config.php')
    ? __DIR__ . '/config.php'
    : __DIR__ . '/config.example.php';

require_once __DIR__ . '/../vendor/autoload.php';

use FurAffinity\Exchange;

$submissionId = 60800395;

try {
    $fa = new Exchange($settings);
    $data = $fa->getById($submissionId);

    if ($data === false) {
        echo "Submission not found or invalid.\n";
    } else {
        echo "Submission data:\n";
        print_r($data);
    }
} catch (Throwable $e) {
    echo "Error: " . get_class($e) . ": " . $e->getMessage() . "\n";
}

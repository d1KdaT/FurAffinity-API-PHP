<?php

declare(strict_types=1);

require_once file_exists(__DIR__ . '/config.php')
    ? __DIR__ . '/config.php'
    : __DIR__ . '/config.example.php';

require_once __DIR__ . '/../vendor/autoload.php';

use FurAffinity\Exchange;

// Replace with the username you want to check
$usernameToCheck = 'falvie';

try {
    $fa = new Exchange($settings);
    $exists = $fa->checkUserExists($usernameToCheck);

    if ($exists) {
        echo "User '{$usernameToCheck}' exists.\n";
    } else {
        echo "User '{$usernameToCheck}' does NOT exist.\n";
    }
} catch (Throwable $e) {
    echo "Error: " . get_class($e) . ": " . $e->getMessage() . "\n";
}

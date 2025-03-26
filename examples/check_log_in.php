<?php

declare(strict_types=1);

require_once file_exists(__DIR__ . '/config.php')
    ? __DIR__ . '/config.php'
    : __DIR__ . '/config.example.php';

require_once __DIR__ . '/../vendor/autoload.php';

use FurAffinity\Exchange;

try {
    $fa = new Exchange($settings);
    $loggedIn = $fa->checkLogIn();

    if ($loggedIn) {
        echo "Successfully logged in as '{$settings['username']}'.\n";
    } else {
        echo "Not logged in. Check your cookies.\n";
    }
} catch (Throwable $e) {
    echo "Error: " . get_class($e) . ": " . $e->getMessage() . "\n";
}

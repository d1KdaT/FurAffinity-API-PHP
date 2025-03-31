<?php

declare(strict_types=1);

require_once file_exists(__DIR__ . '/config.php')
    ? __DIR__ . '/config.php'
    : __DIR__ . '/config.example.php';

require_once __DIR__ . '/../vendor/autoload.php';

use FurAffinity\Exchange;
use FurAffinity\WatchType;

$username = 'falvie'; // The user you want to watch

try {
    $fa = new Exchange($settings);
    $result = $fa->toggleWatch(WatchType::Watch, $username);

    if ($result === 1) {
        echo "User '{$username}' has been added to your watchlist.\n";
    } elseif ($result === 2) {
        echo "User '{$username}' is already on your watchlist.\n";
    } elseif ($result === 3) {
        echo "User '{$username}' blocked '{$settings['username']}'.\n";
    } elseif ($result === 4) {
        echo "User '{$username}' has been permanently suspended.\n";
    } else {
        echo "Failed to add '{$username}' to your watchlist.\n";
    }
} catch (Throwable $e) {
    echo "Error: " . get_class($e) . ": " . $e->getMessage() . "\n";
}

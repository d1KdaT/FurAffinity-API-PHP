<?php

declare(strict_types=1);

require_once file_exists(__DIR__ . '/config.php')
    ? __DIR__ . '/config.php'
    : __DIR__ . '/config.example.php';

require_once __DIR__ . '/../vendor/autoload.php';

use FurAffinity\Exchange;

$username = "falvie"; // if calls getWatchlist() - return watchlist of session user

try {
    $fa = new Exchange($settings);
    $watchlist = $fa->getWatchlist($username);

    if ($watchlist === false) {
        echo "Watchlist is empty or could not be loaded.\n";
    } else {
        echo "Watchlist:\n";
        foreach ($watchlist as $entry) {
            echo "{$entry['display_name']} (~{$entry['username']})\n";
        }
    }
} catch (Throwable $e) {
    echo "Error: " . get_class($e) . ": " . $e->getMessage() . "\n";
}

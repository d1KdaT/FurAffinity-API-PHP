# FurAffinity API (Unofficial PHP Parser)

A lightweight and modern PHP library for interacting with FurAffinity through HTML parsing.  
Provides functionality for reading submissions, checking user activity, toggling favorites/watches, and working with the message center.

> ⚠️ This project is **unofficial** and not affiliated with FurAffinity.net. Use at your own discretion.

---

## 📦 Installation

This package is not yet available on Packagist, but you can install it via GitHub using Composer:

### 1. Add the repository to your project’s `composer.json`:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/d1KdaT/FurAffinity-API-PHP"
    }
  ],
  "require": {
    "d1kdat/furaffinity-api-php": "dev-main"
  }
}
```

### 2. Install with Composer:

```bash
composer install
```

---

## ✅ Requirements

- PHP 8.1 or higher
- PHP extensions:
  - `curl`
  - `iconv`
  - `json`
  - `pcre`

---

## 🚀 Quick Usage

```php
use FurAffinity\Exchange;

$settings = [
    'username' => 'your_username',
    'a' => 'cookie_a',
    'b' => 'cookie_b',
];

$fa = new Exchange($settings);

$submission = $fa->getById(22872063);

if ($submission !== false) {
    print_r($submission);
}
```

---

## 📚 Features

- 🔍 Get submission data by ID
- ⭐ Toggle favorites
- 👤 Toggle watch/unwatch
- ✅ Check login state
- 👁️ Check if a user exists
- 📥 Read your watchlist
- 📫 Read and remove new message center submissions

---

## 📂 Examples

See [`examples/`](examples/) for ready-to-run scripts:

- [`get_submission_data.php`](examples/get_submission_data.php)
- [`get_watchlist.php`](examples/get_watchlist.php)
- [`check_user_exists.php`](examples/check_user_exists.php)
- [`check_log_in.php`](examples/check_log_in.php)
- [`toggle_watch.php`](examples/toggle_watch.php)
- [`toggle_favorite.php`](examples/toggle_favorite.php)
- [`remove_msg_submissions.php`](examples/remove_msg_submissions.php)
- [`get_new_msg_submissions.php`](examples/get_new_msg_submissions.php)

> Each example uses `config.php` or falls back to `config.example.php` for authentication.

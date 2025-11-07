# CleanTrack CRM

Lightweight cleaning service CRM built with PHP 8.2, SQLite and Tailwind CSS.

## Requirements
- PHP 8.2 with PDO SQLite extension

## Setup
```bash
cp .env.example .env
php scripts/migrate.php
php scripts/seed.php
php -S localhost:8000 -t public
```

Login with `admin@cleantrack.test` / `password`.

# CleanTrack CRM

Yengil vaznli, to'liq PHP (Composer-siz) asosida yozilgan tozalash xizmati CRM tizimi. Ma'lumotlar bazasi sifatida SQLite (PDO) ishlatiladi, foydalanuvchi interfeysi Tailwind CSS bilan qurilgan.

## Talablar
- PHP 8.2 (PDO SQLite kengaytmasi yoqilgan)

## O'rnatish
```bash
cp .env.example .env
php scripts/migrate.php
php scripts/seed.php
php -S localhost:8000 -t public
```

Dastlabki kirish: `admin@cleantrack.test` / `password`.

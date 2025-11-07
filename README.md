# Tozalash boshqaruv tizimi

Bu loyiha oddiy PHP (frameworksiz), SQLite va TailwindCSS yordamida qurilgan tozalash kompaniyasi uchun boshqaruv panelidir.

## O'rnatish

1. PHP 8 va SQLite o'rnatilgan bo'lishi kerak.
2. Repodan keyin quyidagi buyruqni ishga tushiring:

```bash
php init_db.php
```

Bu buyruq `storage/database.sqlite` faylini yaratadi va boshlang'ich foydalanuvchini qo'shadi (`owner` / `owner123`).

3. PHP ichki serverini ishga tushiring:

```bash
php -S localhost:8000 -t public
```

4. Brauzerda `http://localhost:8000` manziliga o'ting.

## Funksiyalar

- Rolga asoslangan autentifikatsiya (owner, admin, accountant, dispatcher, cleaner)
- Mijozlar va CRM, hujjatlar, eslatmalar, aloqa loglari
- Mijoz kartasi, moliyaviy ko'rinish, loyallik ballari, QC ma'lumotlari
- Loyallik boshqaruvi, ballar, darajalar, kampaniyalar
- Taklif va hisob-faktura (Payme, Click, Uzum QR ma'lumotlari, CSV eksport)
- Ish rejalashtirish, rasmlar, geo ma'lumotlar, tozalovchilar uchun bildirishnomalar
- QC, checklist, qayta ishlash va mijoz fikrlari
- Inventar va xarid buyurtmalari
- HR va payroll moduli
- Xizmatlar katalogi va narxlash
- Mijoz portali (token asosida)
- Kommunikatsiya va marketing kampaniyalari
- Hisobotlar va grafiklar
- Sozlamalar, kompaniya ma'lumotlari va to'lov shlyuzlari

## Ma'lumotlar bazasi

Barcha ma'lumotlar `storage/database.sqlite` faylida saqlanadi. Tizim `lib/db.php` orqali PDO bilan bog'lanadi.

## Xavfsizlik

- CSRF tokenlari barcha POST so'rovlarida tekshiriladi
- Kirish ma'lumotlari sha256 asosidagi `password_hash` bilan saqlanadi
- Audit loglari foydalanuvchi harakatlarini yozib boradi

## Foydali sozlamalar

- `init_db.php` faylini kerakli paytda qayta ishga tushirib ma'lumotlar bazasini tozalash mumkin
- `storage/database.sqlite` fayli `.gitignore` ostida
- Yuklangan fayllar `uploads/` papkasida saqlanadi


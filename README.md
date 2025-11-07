# CleanTrack CRM (Oddiy PHP talqini)

Bu loyiha toza PHP 8 va SQLite asosida ishlaydi. Hech qanday framework yoki composer kutubxonalari talab qilinmaydi. Tailwind CSS fayli `public/assets/css/tailwind.css` orqali ulangan.

## Ishga tushirish

1. Loyihani serveringizdagi `public_html/cleaning` papkasiga joylashtiring.
2. PHP 8 va SQLite kengaytmasi yoqilganiga ishonch hosil qiling.
3. Brauzerda `https://domeningiz/public_html/cleaning/index.php` manzilini oching.
4. Dastlabki login maʼlumotlari: `admin@example.com` / `parol123`.
5. Birinchi kirish paytida SQLite bazasi avtomatik ravishda yaratiladi.

## Asosiy imkoniyatlar

- Mijozlarni qoʻshish va ularni roʻyxatda koʻrish
- Buyurtmalar yaratish, umumiy summani avtomatik hisoblash
- Toʻlovni buyurtma bilan birga qayd etish
- Bosh sahifada asosiy statistikalar: bugungi ishlar, mijozlar soni, buyurtma va toʻlovlar yigʻindisi

## Fayl tuzilmasi

```
public_html/cleaning/
├── public/
│   ├── assets/
│   │   └── css/
│   │       └── tailwind.css
│   └── index.php
├── database/
│   └── cleantrack.sqlite   # avtomatik yaratiladi
└── README.md
```

## Maʼlumotlar bazasi

- `users` jadvali (standart foydalanuvchi avtomatik qoʻshiladi)
- `clients` jadvali (mijozlar)
- `orders` jadvali (buyurtmalar)
- `payments` jadvali (qabul qilingan toʻlovlar)

## Litsenziya

Ushbu kodni oʻzgartirish va ichki loyihalarda foydalanish erkin.

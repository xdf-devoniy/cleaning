<?php
return [
    'env' => getenv('APP_ENV') ?: 'dev',
    'app_key' => getenv('APP_KEY') ?: 'base64:changeme',
    'db_path' => getenv('DB_PATH') ?: __DIR__ . '/../../database/cleantrack.sqlite',
    'timezone' => getenv('TIMEZONE') ?: 'Asia/Tashkent',
    'locale' => getenv('LOCALE') ?: 'uz',
    'currency' => getenv('CURRENCY') ?: 'UZS',
    'invoice_prefix' => getenv('INVOICE_PREFIX') ?: 'CLN',
    'default_commission_pct' => (int)(getenv('DEFAULT_COMMISSION_PCT') ?: 40),
];

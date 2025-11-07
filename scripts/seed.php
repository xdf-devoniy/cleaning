<?php
require __DIR__ . '/../bootstrap.php';

use App\Core\DB;

DB::migrate();
echo "Database migrated and demo data available.\n";

<?php
require __DIR__ . '/../bootstrap.php';

use App\Core\DB;

DB::migrate();
echo "Migrations applied\n";

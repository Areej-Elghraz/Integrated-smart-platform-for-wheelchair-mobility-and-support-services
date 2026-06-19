<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$db = Illuminate\Support\Facades\DB::connection()->getPdo();
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach($tables as $table) {
    echo "=== $table ===\n";
    print_r($db->query("SHOW CREATE TABLE `$table`")->fetch()[1]);
    echo "\n\n";
}

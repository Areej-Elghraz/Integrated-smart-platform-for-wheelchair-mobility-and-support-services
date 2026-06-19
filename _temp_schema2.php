<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$db = Illuminate\Support\Facades\DB::connection()->getPdo();
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$output = '';
foreach($tables as $table) {
    $output .= "=== $table ===\n";
    $output .= $db->query("SHOW CREATE TABLE `$table`")->fetch()[1] . "\n\n";
}
file_put_contents('_schema_utf8.txt', $output);

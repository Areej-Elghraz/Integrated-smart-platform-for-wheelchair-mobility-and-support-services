<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$db = Illuminate\Support\Facades\DB::connection()->getPdo();
$tables = ['users', 'events', 'trips', 'user_friends', 'disability_types', 'medical_conditions'];
foreach($tables as $table) {
    try {
        $stmt = $db->query("SHOW CREATE TABLE $table");
        echo "=== $table ===\n";
        echo $stmt->fetch()[1] . "\n\n";
    } catch(Exception $e) {
        echo "Table $table not found or error.\n\n";
    }
}

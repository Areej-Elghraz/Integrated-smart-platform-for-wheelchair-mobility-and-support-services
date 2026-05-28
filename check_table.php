<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$db = Illuminate\Support\Facades\DB::connection()->getPdo();
$stmt = $db->query("SHOW CREATE TABLE wheelchairs");
print_r($stmt->fetch());

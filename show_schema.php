<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$db = Illuminate\Support\Facades\DB::connection()->getPdo();
print_r($db->query("SHOW CREATE TABLE current_vital_states")->fetch()[1]);
echo "\n\n";
print_r($db->query("SHOW CREATE TABLE events")->fetch()[1]);

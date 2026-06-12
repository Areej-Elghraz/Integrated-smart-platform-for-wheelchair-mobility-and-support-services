<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$place = App\Models\Place::first();
echo "Model distribution keys: " . json_encode(array_keys($place->rating_distribution)) . "\n";
echo "Model distribution: " . json_encode($place->rating_distribution) . "\n";

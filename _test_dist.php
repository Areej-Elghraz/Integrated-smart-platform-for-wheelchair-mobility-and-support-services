<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$place = App\Models\Place::first();
if ($place) {
    echo "Rating distribution: " . json_encode($place->rating_distribution) . "\n";
} else {
    echo "No place found\n";
}

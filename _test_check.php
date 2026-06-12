<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = App\Models\User::first();
auth('sanctum')->setUser($user);

$request = Illuminate\Http\Request::create('/api/places/12', 'GET');
// simulate sanctum auth
$request->setUserResolver(function () use ($user) { return $user; });

try {
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Exception: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

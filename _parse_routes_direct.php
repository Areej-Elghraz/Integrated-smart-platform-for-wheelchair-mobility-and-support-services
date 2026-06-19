<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$routes = \Illuminate\Support\Facades\Route::getRoutes();

$output = "| Method | Endpoint | Route Name | Action |\n";
$output .= "|--------|----------|------------|--------|\n";

foreach ($routes as $route) {
    if (strpos($route->uri(), 'api/') === 0) {
        $method = implode('/', array_diff($route->methods(), ['HEAD']));
        $uri = "`/" . $route->uri() . "`";
        $name = $route->getName() ?: '';
        $actionName = $route->getActionName();
        if ($actionName == 'Closure') $actionName = 'Closure';
        else $actionName = class_basename(explode('@', $actionName)[0]) . '@' . (explode('@', $actionName)[1] ?? 'invoke');
        
        $output .= "| $method | $uri | $name | $actionName |\n";
    }
}
file_put_contents('parsed_routes_direct.md', $output);

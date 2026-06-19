<?php
$data = json_decode(file_get_contents('routes_list.json'), true);
if (!$data) {
    echo "No valid JSON found.\n";
    exit;
}
$routes = [];
foreach ($data as $route) {
    if (strpos($route['uri'], 'api/') === 0) {
        $routes[] = "| " . str_replace('|', '/', $route['method']) . " | `/" . $route['uri'] . "` | " . $route['name'] . " |";
    }
}
echo "| Method | Endpoint | Route Name |\n";
echo "|--------|----------|------------|\n";
foreach ($routes as $r) {
    echo $r . "\n";
}

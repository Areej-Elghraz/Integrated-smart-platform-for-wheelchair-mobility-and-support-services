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
$output = "| Method | Endpoint | Route Name |\n";
$output .= "|--------|----------|------------|\n";
foreach ($routes as $r) {
    $output .= $r . "\n";
}
file_put_contents('parsed_routes2.md', $output);

<?php
$schema = file_get_contents('_schema_utf8.txt');
$tables = explode("=== ", $schema);
$plantuml = "@startuml\n\nhide methods\nhide stereotypes\nskinparam linetype ortho\n\n";

$relations = [];

foreach($tables as $tableBlock) {
    if(trim($tableBlock) == '') continue;
    $lines = explode("\n", trim($tableBlock));
    $tableName = trim($lines[0], "=\r\n ");
    
    if(in_array($tableName, ['migrations', 'failed_jobs', 'jobs', 'job_batches', 'password_reset_tokens', 'personal_access_tokens', 'cache', 'cache_locks', 'sessions'])) continue;

    $plantuml .= "entity \"$tableName\" as $tableName {\n";
    
    foreach($lines as $line) {
        if(preg_match('/^\s*`([^`]+)`\s+([a-zA-Z0-9_]+(\(.*?\))?)/', $line, $matches)) {
            $colName = $matches[1];
            $colType = strtoupper($matches[2]);
            if(strpos($line, 'AUTO_INCREMENT') !== false || strpos($line, 'PRIMARY KEY') !== false) {
                $plantuml .= "    * $colName : $colType <<PK>>\n";
            } elseif(strpos($line, 'FOREIGN KEY') !== false) {
                // will capture below
            } else {
                $plantuml .= "    $colName : $colType\n";
            }
        }
        
        if(preg_match('/FOREIGN KEY \(`([^`]+)`\) REFERENCES `([^`]+)`/', $line, $matches)) {
            $colName = $matches[1];
            $refTable = $matches[2];
            $relations[] = "$tableName }o--|| $refTable : \"$colName\"";
            // Replace the normal column with FK indication if possible, or just let it be.
        }
    }
    $plantuml .= "}\n\n";
}

$plantuml .= "\n' Relationships\n";
foreach(array_unique($relations) as $rel) {
    $plantuml .= "$rel\n";
}

$plantuml .= "\n@enduml";
file_put_contents('erd.puml', $plantuml);
echo "Done";

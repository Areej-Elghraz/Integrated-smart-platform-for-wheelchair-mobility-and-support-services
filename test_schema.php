<?php
$r = DB::select('SHOW CREATE TABLE users')[0];
echo $r->{'Create Table'};

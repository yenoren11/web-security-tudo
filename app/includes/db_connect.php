<?php
    if (!isset($db)) {
        $host        = "host = tudo-db";
        $port        = "port = 5432";
        $dbname      = "dbname = tudo";
        $credentials = "user = postgres password = postgres";

        $db = pg_connect( "$host $port $dbname $credentials" );

        if (!$db) {
            echo "Error: Unable to connect to db.";
        }
    }
?>
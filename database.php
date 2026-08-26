<?php

function db(){
    $config = require __DIR__ . '/config/database.php';

    return mysqli_connect(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['database']
    );
}

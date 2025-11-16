<?php
// Bootstrap - DO NOT MODIFY
$CONFIG = require __DIR__ . '/config.php';

function cfg($key) {
    global $CONFIG;
    $keys = explode('.', $key);
    $value = $CONFIG;
    foreach ($keys as $k) {
        if (!isset($value[$k])) return null;
        $value = $value[$k];
    }
    return $value;
}

function db() {
    static $pdo;
    if (!$pdo) {
        $pdo = new PDO(
            cfg('db.dsn'),
            cfg('db.user'),
            cfg('db.pass'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}
?>
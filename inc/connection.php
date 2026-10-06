<?php
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'cornerstone_library';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPassword = getenv('DB_PASS');
$dbPort = getenv('DB_PORT') ?: '3306';

if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $dbHost)) {
    throw new PDOException('Invalid database host configuration.');
}

if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $dbName)) {
    throw new PDOException('Invalid database name configuration.');
}

$validatedPort = filter_var(
    $dbPort,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 65535]]
);
if ($validatedPort === false) {
    throw new PDOException('Invalid database port configuration.');
}

$pdo = new PDO(
    "mysql:host={$dbHost};port={$validatedPort};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPassword === false ? '' : $dbPassword,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
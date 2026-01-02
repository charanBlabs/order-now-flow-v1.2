<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'launc18175_directory';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$sql = file_get_contents(__DIR__ . '/pmp_logs_schema.sql');
$queries = explode(';', $sql);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if ($mysqli->query($query) === TRUE) {
            echo "Success: " . substr($query, 0, 50) . "...\n";
        } else {
            echo "Error: " . $mysqli->error . "\nQuery: " . $query . "\n";
        }
    }
}

$mysqli->close();

<?php

function getDBConnection() {
    $host = "localhost";
    $dbname = "YOUR_DATABASE_NAME";
    $username = "root";
    $password = "";

    try {
        $db = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8",
            $username,
            $password
        );

        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $db;

    } catch (PDOException $e) {
        die("DB Connection failed: " . $e->getMessage());
    }
}
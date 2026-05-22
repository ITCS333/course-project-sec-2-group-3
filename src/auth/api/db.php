<?php

function getDBConnection() {
    $host = "localhost";
    $dbname = "course";
    $username = "admin";
    $password = "password123";

    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $db;
}

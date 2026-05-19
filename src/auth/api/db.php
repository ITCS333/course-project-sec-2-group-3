<?php

function getDBConnection() {
    $host = "localhost";
    $dbname = "users_db";
    $username = "root";
    $password = "";

    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $db;
}
?>
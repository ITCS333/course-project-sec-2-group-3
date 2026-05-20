<?php
// src/weekly/helpers.php
require_once __DIR__ . '/../db.php'; // assume db.php returns PDO connection

function getWeeksList() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, week_number, title, created_at FROM weeks ORDER BY week_number ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getWeekById($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM weeks WHERE id = ?");
    $stmt->execute([$id]);
    $week = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($week && $week['resource_links']) {
        $week['resource_links'] = json_decode($week['resource_links'], true);
    }
    return $week;
}

function isAdmin() {
    session_start();
    return isset($_SESSION['user']) && $_SESSION['user']['is_admin'] == 1;
}

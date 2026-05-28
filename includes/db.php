<?php
// ============================================
// Database connectie (PDO)
// ============================================
// Pas de onderstaande gegevens aan indien nodig.

$db_host = 'db';
$db_name = 'wk_poule';
$db_user = 'root';
$db_pass = 'root';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connectie mislukt: ' . htmlspecialchars($e->getMessage()));
}

<?php
$host = 'localhost';
$db   = 'conferences';
$user = 'root';
$pass = '';

try {
    // строка подключения.
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;port=3306;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // показываем ошибки
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}
?>
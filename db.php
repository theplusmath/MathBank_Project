<?php
$host = 'localhost';
$db   = 'theplusmath';
$user = 'theplusmath';
$pass = 'wnstj1205+';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    global $pdo;  // ✅ 이 줄을 추가
} catch (\PDOException $e) {
    die('❌ DB 연결 실패: ' . $e->getMessage());
}
?>

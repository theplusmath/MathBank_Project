<?php
// 오류 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// DB 연결
require_once("db.php"); // db.php에 $pdo 변수가 있어야 합니다
global $pdo;

header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $pdo->prepare("SELECT id, parent_id, name, depth, sort_order FROM paths ORDER BY sort_order ASC, id ASC");
    $stmt->execute();
    $paths = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($paths, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>

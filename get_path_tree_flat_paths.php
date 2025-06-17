<?php
// 오류 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// DB 연결
require_once("db.php"); // $pdo 포함되어 있어야 함
global $pdo;

header('Content-Type: application/json; charset=utf-8');

// ✅ MySQL 문자셋 설정 (중복 시 생략 가능)
$pdo->exec("SET NAMES utf8mb4");

try {
    $stmt = $pdo->prepare("SELECT id, parent_id, name, depth, sort_order FROM paths ORDER BY sort_order ASC, id ASC");
    $stmt->execute();
    $paths = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $paths[] = [
            'id' => (int)$row['id'],
            'parent_id' => is_null($row['parent_id']) ? null : (int)$row['parent_id'],
            'name' => $row['name'],
            'depth' => (int)$row['depth'],
            'sort_order' => (int)$row['sort_order']
        ];
    }

    echo json_encode($paths, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ✅ DB 연결 함수 포함
require_once 'functions.php'; // 이 줄을 꼭 추가하세요

header('Content-Type: application/json; charset=utf-8');

try {
    $conn = connectDB();

    $stmt = $conn->prepare("SELECT id, name FROM teachers ORDER BY name ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    $teachers = [];
    while ($row = $result->fetch_assoc()) {
        $teachers[] = [
            'id' => $row['id'],
            'name' => $row['name']
        ];
    }

    echo json_encode($teachers, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode([
        'error' => true,
        'message' => '❌ 서버 오류: ' . $e->getMessage()
    ]);
}

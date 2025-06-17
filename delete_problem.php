<?php
require_once 'functions.php';

ob_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

$conn = connectDB();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => '문제 ID가 없습니다.']);
    exit;
}

// 원본 문제 조회
$result = $conn->query("SELECT * FROM problems WHERE id = $id");
if (!$result || $result->num_rows === 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => "ID $id: 문제를 찾을 수 없습니다."]);
    exit;
}

$row = $result->fetch_assoc();
$row['deleted_at'] = date('Y-m-d H:i:s');

$fields = array_keys($row);
$columns = implode(', ', $fields);
$values = implode(', ', array_map(fn($v) => "'" . $conn->real_escape_string($v) . "'", array_values($row)));

$insertSQL = "INSERT INTO deleted_problems ($columns) VALUES ($values)";
if (!$conn->query($insertSQL)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => "ID $id 백업 실패: " . $conn->error]);
    exit;
}

if (!$conn->query("DELETE FROM problems WHERE id = $id")) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => "ID $id 삭제 실패: " . $conn->error]);
    exit;
}

ob_end_clean();
echo json_encode(['success' => true, 'message' => "ID $id 문제 삭제 완료"]);

<?php
require_once("/var/www/config/db_connect.php"); // MySQLi

header('Content-Type: application/json; charset=utf-8');

$id = intval($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => '삭제할 ID가 전달되지 않았습니다.']);
    exit;
}

// 1. 하위 경로 존재 체크
$stmt = $conn->prepare("SELECT COUNT(*) FROM paths WHERE parent_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($childCount);
$stmt->fetch();
$stmt->close();

if ($childCount > 0) {
    echo json_encode(['success' => false, 'message' => '하위 경로가 있어 삭제할 수 없습니다.']);
    exit;
}

// 2. 실제 삭제
$stmt = $conn->prepare("DELETE FROM paths WHERE id = ?");
$stmt->bind_param("i", $id);
$success = $stmt->execute();

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();
$conn->close();

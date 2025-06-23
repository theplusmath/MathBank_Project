<?php
header('Content-Type: application/json');
require_once("/var/www/config/db_connect.php");

$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);
$new_name = trim($data['name'] ?? '');
$parent_id = isset($data['parent_id']) ? intval($data['parent_id']) : null;

if (!$id || !$new_name) {
    echo json_encode(["success" => false, "message" => "ID 또는 새 이름이 누락되었습니다."]);
    exit;
}

// 이름 중복 체크 (같은 부모 아래)
$stmt = $conn->prepare("SELECT COUNT(*) FROM paths WHERE parent_id <=> ? AND name = ? AND id != ?");
$stmt->bind_param("isi", $parent_id, $new_name, $id);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    echo json_encode(["success" => false, "message" => "이미 동일한 이름의 경로가 같은 폴더 내에 존재합니다."]);
    exit;
}

// 실제 이름 수정
$stmt = $conn->prepare("UPDATE paths SET name = ? WHERE id = ?");
$stmt->bind_param("si", $new_name, $id);
$success = $stmt->execute();

if ($success) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => $conn->error]);
}

$stmt->close();
$conn->close();

<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

// JSON 데이터 파싱
$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);
$new_name = trim($data['name'] ?? '');

if (!$id || !$new_name) {
    echo json_encode(["success" => false, "message" => "ID 또는 새 이름이 누락되었습니다."]);
    exit;
}

// 이름 중복 확인 (같은 부모 내에서 중복 방지 가능)
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
?>

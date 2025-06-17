<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

$data = json_decode(file_get_contents("php://input"), true);

$id = (int)($data['id'] ?? 0);
$newParentId = (int)($data['new_parent_id'] ?? 0);
$newOrder = (int)($data['new_order'] ?? 0);

if (!$id) {
  echo json_encode(['success' => false, 'message' => '경로 ID 누락']);
  exit;
}

$stmt = $conn->prepare("UPDATE path SET parent_id = ?, sort_order = ? WHERE id = ?");
$stmt->bind_param("iii", $newParentId, $newOrder, $id);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success]);
$conn->close();

<?php
require_once("/var/www/config/db_connect.php");
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents("php://input"), true);
$dragged_id = intval($input['dragged_id'] ?? 0);
$target_id = intval($input['target_id'] ?? 0);
$new_parent_id = isset($input['new_parent_id']) ? intval($input['new_parent_id']) : null;

if (!$dragged_id || $new_parent_id === null) {
    echo json_encode(['success' => false, 'message' => '필수 항목 누락']);
    exit;
}

// dragged_id의 현재 parent_id 조회
$stmt = $conn->prepare("SELECT parent_id FROM paths WHERE id = ?");
$stmt->bind_param("i", $dragged_id);
$stmt->execute();
$stmt->bind_result($old_parent_id);
$stmt->fetch();
$stmt->close();

// parent_id 변경 (이동)
$stmt = $conn->prepare("UPDATE paths SET parent_id = ? WHERE id = ?");
$stmt->bind_param("ii", $new_parent_id, $dragged_id);
$stmt->execute();
$stmt->close();

// 새 부모 하위의 형제 id 목록(자기 자신 제외, 정렬순서로)
$stmt = $conn->prepare("SELECT id FROM paths WHERE parent_id <=> ? AND id != ? ORDER BY sort_order ASC, id ASC");
$stmt->bind_param("ii", $new_parent_id, $dragged_id);
$stmt->execute();
$res = $stmt->get_result();
$siblings = [];
while ($row = $res->fetch_assoc()) {
    $siblings[] = $row['id'];
}
$stmt->close();

// 드래그된 항목을 마지막에 삽입
$new_order = $siblings;
$new_order[] = $dragged_id;

// 트랜잭션
$conn->begin_transaction();
try {
    foreach ($new_order as $i => $id) {
        $stmt = $conn->prepare("UPDATE paths SET sort_order = ? WHERE id = ?");
        $order = $i + 1;
        $stmt->bind_param("ii", $order, $id);
        $stmt->execute();
        $stmt->close();
    }
    $conn->commit();
    echo json_encode(['success' => true, 'dragged_id' => $dragged_id, 'new_parent_id' => $new_parent_id]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
$conn->close();

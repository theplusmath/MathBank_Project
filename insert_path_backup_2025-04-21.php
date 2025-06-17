<?php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents("php://input"), true);
    $parent_id = intval($input['parent_id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $depth = intval($input['depth'] ?? 0);
    $sort_order = intval($input['sort_order'] ?? 0);
    $id = isset($input['id']) ? intval($input['id']) : null;

    if (!$name) {
        echo json_encode(['success' => false, 'message' => '경로 이름 누락']);
        exit;
    }

    // 기존에 삭제된 path를 복원하는 경우
    if ($id) {
        $stmt = $pdo->prepare("INSERT INTO paths (id, parent_id, name, depth, sort_order) VALUES (?, ?, ?, ?, ?)");
        $success = $stmt->execute([$id, $parent_id, $name, $depth, $sort_order]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO paths (parent_id, name, depth, sort_order) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([$parent_id, $name, $depth, $sort_order]);
    }

    echo json_encode(['success' => $success]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

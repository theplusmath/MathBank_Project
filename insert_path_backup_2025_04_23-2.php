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

    if ($id) {
        // 복원용 삽입
        $stmt = $pdo->prepare("INSERT INTO paths (id, parent_id, name, depth, sort_order) VALUES (?, ?, ?, ?, ?)");
        $success = $stmt->execute([$id, $parent_id, $name, $depth, $sort_order]);
    } else {
        // 일반 삽입
        $stmt = $pdo->prepare("INSERT INTO paths (parent_id, name, depth, sort_order) VALUES (?, ?, ?, ?)");
        $success = $stmt->execute([$parent_id, $name, $depth, $sort_order]);
        $id = $success ? $pdo->lastInsertId() : null;
    }

    if ($success) {
        echo json_encode([
            'success' => true,
            'inserted' => [
                'id' => $id,
                'parent_id' => $parent_id,
                'name' => $name,
                'depth' => $depth,
                'sort_order' => $sort_order
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '삽입 실패']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

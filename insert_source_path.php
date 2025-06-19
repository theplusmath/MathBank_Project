<?php
require_once("/var/www/config/db_connect.php");
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
        // 복원용 삽입 (id 명시)
        $stmt = $conn->prepare("INSERT INTO source_paths (id, parent_id, name, depth, sort_order) VALUES (?, ?, ?, ?, ?)");
        $success = $stmt->bind_param('iisii', $id, $parent_id, $name, $depth, $sort_order)
            && $stmt->execute();
    } else {
        // 일반 삽입 (auto-increment id)
        $stmt = $conn->prepare("INSERT INTO source_paths (parent_id, name, depth, sort_order) VALUES (?, ?, ?, ?)");
        $success = $stmt->bind_param('isii', $parent_id, $name, $depth, $sort_order)
            && $stmt->execute();
        $id = $success ? $conn->insert_id : null;
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
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

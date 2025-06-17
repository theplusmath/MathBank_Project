<?php
require_once 'db.php'; // PDO 방식 DB 연결 포함

header('Content-Type: application/json; charset=utf-8');

try {
    $id = intval($_POST['id'] ?? 0);

    if (!$id) {
        echo json_encode(['success' => false, 'message' => '삭제할 ID가 전달되지 않았습니다.']);
        exit;
    }

    // 1. 하위 경로가 있는지 확인
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM paths WHERE parent_id = ?");
    $stmt->execute([$id]);
    $childCount = $stmt->fetchColumn();

    if ($childCount > 0) {
        echo json_encode(['success' => false, 'message' => '하위 경로가 있어 삭제할 수 없습니다.']);
        exit;
    }

    // 2. 실제 삭제
    $stmt = $pdo->prepare("DELETE FROM paths WHERE id = ?");
    $result = $stmt->execute([$id]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '삭제 실패']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

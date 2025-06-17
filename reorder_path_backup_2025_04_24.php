<?php
require_once 'db.php';
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents("php://input"), true);
    $dragged_id = intval($input['dragged_id'] ?? 0);
    $target_id = intval($input['target_id'] ?? 0);
    $new_parent_id = isset($input['new_parent_id']) ? intval($input['new_parent_id']) : null;

    if (!$dragged_id || $new_parent_id === null) {
        echo json_encode(['success' => false, 'message' => '필수 항목 누락']);
        exit;
    }

    // dragged 항목의 현재 부모를 먼저 조회
    $stmt = $pdo->prepare("SELECT parent_id FROM paths WHERE id = ?");
    $stmt->execute([$dragged_id]);
    $old_parent_id = $stmt->fetchColumn();

    // parent_id 변경 (드래그된 항목을 새 부모로 이동)
    $stmt = $pdo->prepare("UPDATE paths SET parent_id = ? WHERE id = ?");
    $stmt->execute([$new_parent_id, $dragged_id]);

    // 새 부모 하위 항목들 정렬 (자기 자신 제외)
    $stmt = $pdo->prepare("SELECT id FROM paths WHERE parent_id = ? AND id != ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$new_parent_id, $dragged_id]);
    $siblings = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 드래그된 항목을 마지막에 삽입
    $new_order = $siblings;
    $new_order[] = $dragged_id;

    // 정렬 순서 업데이트
    $pdo->beginTransaction();
    foreach ($new_order as $i => $id) {
        $stmt = $pdo->prepare("UPDATE paths SET sort_order = ? WHERE id = ?");
        $stmt->execute([$i + 1, $id]);
    }
    $pdo->commit();

    echo json_encode(['success' => true, 'dragged_id' => $dragged_id, 'new_parent_id' => $new_parent_id]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

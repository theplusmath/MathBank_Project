<?php
file_put_contents(__DIR__.'/php-error.log', date('c')." [reorder_source_path] 진입\n", FILE_APPEND);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("/var/www/config/db_connect.php"); // $conn (mysqli) 제공
header('Content-Type: application/json');

file_put_contents(__DIR__.'/php-error.log', date('c')." [start reorder_source_path] raw=".file_get_contents("php://input")."\n", FILE_APPEND);

$input = json_decode(file_get_contents("php://input"), true);
$dragged_id = intval($input['dragged_id'] ?? 0);
$new_parent_id = isset($input['new_parent_id']) ? intval($input['new_parent_id']) : null;
$target_id = isset($input['target_id']) ? intval($input['target_id']) : null;

file_put_contents(__DIR__.'/php-error.log', date('c')." [reorder_source_path] input=".var_export($input,true)."\n", FILE_APPEND);

if (!$dragged_id || $new_parent_id === null) {
    echo json_encode(['success' => false, 'message' => '필수 항목 누락']);
    exit;
}

try {
    $conn->begin_transaction();

    // 1. parent_id 변경
    $stmt = $conn->prepare("UPDATE source_path SET parent_id = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_parent_id, $dragged_id);
    if (!$stmt->execute()) throw new Exception("parent_id 업데이트 실패");
    $stmt->close();

    // 2. 새 부모의 depth 읽기 (최상위면 0)
    $parent_depth = 0;
    if ($new_parent_id) {
        $stmt = $conn->prepare("SELECT depth FROM source_path WHERE id = ?");
        $stmt->bind_param("i", $new_parent_id);
        $stmt->execute();
        $stmt->bind_result($parent_depth_val);
        $stmt->fetch();
        $parent_depth = intval($parent_depth_val);
        $stmt->close();
    }
    $new_depth = $parent_depth + 1;

    // 3. dragged_id의 depth 업데이트
    $stmt = $conn->prepare("UPDATE source_path SET depth = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_depth, $dragged_id);
    if (!$stmt->execute()) throw new Exception("depth 업데이트 실패");
    $stmt->close();

    // 4. 재귀적으로 하위 전체 depth 업데이트 함수 정의
    function updateChildrenDepths($conn, $parent_id, $parent_depth) {
        $stmt = $conn->prepare("SELECT id FROM source_path WHERE parent_id = ?");
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $child_depth = $parent_depth + 1;
        while ($row = $result->fetch_assoc()) {
            $child_id = $row['id'];
            $stmt2 = $conn->prepare("UPDATE source_path SET depth = ? WHERE id = ?");
            $stmt2->bind_param("ii", $child_depth, $child_id);
            if (!$stmt2->execute()) throw new Exception("child depth 업데이트 실패: id=$child_id");
            $stmt2->close();
            // 재귀 호출
            updateChildrenDepths($conn, $child_id, $child_depth);
        }
        $stmt->close();
    }
    // 5. dragged_id의 모든 하위 노드까지 반영
    updateChildrenDepths($conn, $dragged_id, $new_depth);

    // 6. 새 부모의 하위 정렬 (drag&drop 대상의 새 위치 지정)
    $stmt = $conn->prepare("SELECT id FROM source_path WHERE parent_id = ? AND id != ? ORDER BY sort_order ASC, id ASC");
    $stmt->bind_param("ii", $new_parent_id, $dragged_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $new_order = [];
    while ($row = $result->fetch_assoc()) {
        $new_order[] = $row['id'];
    }
    $stmt->close();

    if ($target_id && ($idx = array_search($target_id, $new_order)) !== false) {
        // dragged_id를 target_id 앞에 삽입
        array_splice($new_order, $idx, 0, [$dragged_id]);
    } else {
        // 기본: dragged_id를 마지막에 삽입
        $new_order[] = $dragged_id;
    }




    foreach ($new_order as $i => $id) {
        $stmt = $conn->prepare("UPDATE source_path SET sort_order = ? WHERE id = ?");
        $order = $i + 1;
        $stmt->bind_param("ii", $order, $id);
        if (!$stmt->execute()) throw new Exception("sort_order 업데이트 실패: id=$id");
        $stmt->close();
    }

    $conn->commit();

    echo json_encode(['success' => true, 'dragged_id' => $dragged_id, 'new_parent_id' => $new_parent_id, 'new_depth' => $new_depth]);
} catch (Exception $e) {
    if ($conn->errno) $conn->rollback();
    file_put_contents(__DIR__.'/php-error.log', date('c')." [reorder_source_path][ERROR] ".$e->getMessage()."\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

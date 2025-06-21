<?php
require_once("/var/www/config/db_connect.php");
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents("php://input"), true);
    $id = intval($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');

    if (!$id || !$name) {
        echo json_encode(['success' => false, 'message' => '데이터 누락']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE source_path SET name=? WHERE id=?");
    $success = $stmt->bind_param('si', $name, $id) && $stmt->execute();

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '이름 수정 실패']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

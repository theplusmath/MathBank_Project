<?php
require_once '/var/www/config/db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

file_put_contents(__DIR__.'/php-error.log', date('c')." [delete_path] 파일 진입\n", FILE_APPEND);

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
$id = null;
if ($data && isset($data['id'])) {
    $id = intval($data['id']);
} elseif (isset($_POST['id'])) {
    $id = intval($_POST['id']);
}
file_put_contents(__DIR__.'/php-error.log', date('c')." [delete_path] id=$id\n", FILE_APPEND);

header('Content-Type: application/json; charset=utf-8');

try {
    if (!$id) {
        file_put_contents(__DIR__.'/php-error.log', date('c')." [delete_path] ID 없음\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => '삭제할 ID가 전달되지 않았습니다.']);
        exit;
    }

    // 1. 하위 경로 확인
    $stmt = $conn->prepare("SELECT COUNT(*) FROM source_path WHERE parent_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($childCount);
    $stmt->fetch();
    $stmt->close();

    if ($childCount > 0) {
        file_put_contents(__DIR__.'/php-error.log', date('c')." [delete_path] 하위 있음\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => '하위 경로가 있어 삭제할 수 없습니다.']);
        exit;
    }

    // 2. 삭제
    $stmt = $conn->prepare("DELETE FROM source_path WHERE id = ?");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    $stmt->close();

    if ($result) {
        file_put_contents(__DIR__.'/php-error.log', date('c')." [delete_path] 삭제 성공: $id\n", FILE_APPEND);
        echo json_encode(['success' => true]);
    } else {
        file_put_contents(__DIR__.'/php-error.log', date('c')." [delete_path] 삭제 실패\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => '삭제 실패']);
    }
} catch (Exception $e) {
    file_put_contents(__DIR__.'/php-error.log', date('c')." [delete_path] EXCEPTION: ".$e->getMessage()."\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

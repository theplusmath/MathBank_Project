<?php
require_once 'functions.php';

header('Content-Type: application/json');
$conn = connectDB();

// 1?? 복원할 ID 받기
$id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => '? 유효하지 않은 문제 ID']);
    exit;
}

// 2?? deleted_problems에서 해당 문제 조회
$stmt = $conn->prepare("SELECT * FROM deleted_problems WHERE id = ?");
bindParams($stmt, 'i', [$id]);
$stmt->execute();
$result = $stmt->get_result();
$deletedProblem = $result->fetch_assoc();
$stmt->close();

if (!$deletedProblem) {
    echo json_encode(['success' => false, 'message' => '? 삭제된 문제를 찾을 수 없습니다']);
    exit;
}

// 3?? 복원: problems 테이블로 INSERT
// 단, id는 새로 부여되도록 unset
unset($deletedProblem['id']);  // 새 ID로 들어가도록
$columns = array_keys($deletedProblem);
$placeholders = implode(', ', array_fill(0, count($columns), '?'));
$colString = implode(', ', $columns);
$sqlInsert = "INSERT INTO problems ($colString) VALUES ($placeholders)";
$stmtInsert = $conn->prepare($sqlInsert);
$types = guessParamTypes($deletedProblem);
$params = array_values($deletedProblem);

if (!bindParams($stmtInsert, $types, $params) || !$stmtInsert->execute()) {
    echo json_encode(['success' => false, 'message' => '?? 복원 실패: ' . $stmtInsert->error]);
    exit;
}
$newId = $stmtInsert->insert_id;
$stmtInsert->close();

// 4?? deleted_problems에서 삭제
$stmtDelete = $conn->prepare("DELETE FROM deleted_problems WHERE id = ?");
bindParams($stmtDelete, 'i', [$id]);
$stmtDelete->execute();
$stmtDelete->close();

echo json_encode(['success' => true, 'message' => "? 문제 복원 완료! 새 ID: $newId"]);

<?php
// get_history_diff.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
    exit;
}

$historyId = intval($_GET['history_id'] ?? 0);
if (!$historyId) {
    echo json_encode(['success' => false, 'message' => 'history_id 입력 오류']);
    exit;
}

// ✅ 과거 이력 조회 (prepare 방식)
$stmt = $conn->prepare("SELECT * FROM history_problems WHERE id = ?");
$stmt->bind_param("i", $historyId);
$stmt->execute();
$historyResult = $stmt->get_result();
if (!$historyResult || $historyResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'history 데이터 없음']);
    exit;
}
$historyRow = $historyResult->fetch_assoc();
$problemId = intval($historyRow['problem_id']);

// ✅ 현재 문제 조회 (prepare 방식)
$stmt2 = $conn->prepare("SELECT * FROM problems WHERE id = ?");
$stmt2->bind_param("i", $problemId);
$stmt2->execute();
$currentResult = $stmt2->get_result();
if (!$currentResult || $currentResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'current problem 데이터 없음']);
    exit;
}
$currentRow = $currentResult->fetch_assoc();

// ✅ 변경된 필드 비교
$fieldsToCompare = [
    'title', 'question', 'answer', 'solution', 'hint', 'video',
    'difficulty', 'type', 'category', 'source', 'created_by', 'tags',
    'path_text', 'path_id'
];

$diff = [];
foreach ($fieldsToCompare as $field) {
    $old = $historyRow[$field] ?? '';
    $new = $currentRow[$field] ?? '';
    if ($old != $new) {
        $diff[] = [
            'field' => $field,
            'old' => $old,
            'new' => $new
        ];
    }
}

echo json_encode(['success' => true, 'diff' => $diff], JSON_UNESCAPED_UNICODE);
$conn->close();

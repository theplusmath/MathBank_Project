<?php
// restore_history.php

session_start(); // ✅ 세션 시작

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => '접근 권한이 없습니다. 관리자만 복원할 수 있습니다.']);
    exit;
}



ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
    exit;
}

$historyId = intval($_POST['history_id'] ?? 0);

if (!$historyId) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 이력 ID']);
    exit;
}

$result = $conn->query("SELECT * FROM history_problems WHERE id = $historyId");

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => '이력을 찾을 수 없습니다.']);
    exit;
}

$row = $result->fetch_assoc();

// 🔁 복원 전 현재 상태를 history_problems에 백업
$currentResult = $conn->query("SELECT * FROM problems WHERE id = $problemId");
if ($currentResult && $currentResult->num_rows > 0) {
    $current = $currentResult->fetch_assoc();

    $backupFields = [
        'problem_id', 'title', 'question', 'answer', 'solution', 'hint', 'video',
        'difficulty', 'type', 'category', 'source', 'created_by', 'tags',
        'path_text', 'path_id', 'copied_by', 'origin_id',
        'main_formula_latex', 'main_formula_tree', 'formula_keywords', 'all_formulas_tree',
        'updated_at'
    ];

    $backupValues = array_map(function($f) use ($conn, $current) {
        if ($f === 'problem_id') return intval($current['id']);
        if ($f === 'updated_at') return "'" . date('Y-m-d H:i:s') . "'";
        return "'" . $conn->real_escape_string($current[$f] ?? '') . "'";
    }, $backupFields);

    $columnList = implode(', ', $backupFields);
    $valueList = implode(', ', $backupValues);

    $conn->query("INSERT INTO history_problems ($columnList) VALUES ($valueList)");
}




$problemId = intval($row['problem_id']);

// 복원할 필드만 추려서 UPDATE
$fieldsToRestore = [
    'title', 'question', 'answer', 'solution', 'hint', 'video',
    'difficulty', 'type', 'category', 'source', 'created_by', 'tags',
    'path_text', 'path_id', 'copied_by', 'origin_id',
    'main_formula_latex', 'main_formula_tree', 'formula_keywords', 'all_formulas_tree'
];

$setClause = implode(", ", array_map(fn($field) => "$field = ?", $fieldsToRestore));
$stmt = $conn->prepare("UPDATE problems SET $setClause WHERE id = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => '문제 복원 쿼리 준비 실패: ' . $conn->error]);
    exit;
}

$values = array_map(fn($f) => $row[$f] ?? '', $fieldsToRestore);
$types = str_repeat('s', count($values)) . 'i'; // 마지막은 ID
$values[] = $problemId;

$stmt->bind_param($types, ...$values);

if ($stmt->execute()) {

// ✅ 복원 성공 시 복원 로그 기록
$adminName = $_SESSION['username'] ?? 'unknown';
$now = date('Y-m-d H:i:s');
$conn->query("INSERT INTO restore_log (history_id, problem_id, restored_by, restored_at) VALUES ($historyId, $problemId, '$adminName', '$now')");



    echo json_encode(['success' => true, 'message' => '문제가 이전 이력으로 복원되었습니다.']);
} else {
    echo json_encode(['success' => false, 'message' => '복원 실패: ' . $stmt->error]);
}
$stmt->close();
$conn->close();

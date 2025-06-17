<?php
// get_problem_diff.php

session_start();

header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
    exit;
}

$historyId = intval($_GET['history_id'] ?? 0);

if (!$historyId) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 이력 ID']);
    exit;
}

// 1. 해당 이력
$history = $conn->query("SELECT * FROM history_problems WHERE id = $historyId");
if (!$history || $history->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => '이력 데이터를 찾을 수 없습니다.']);
    exit;
}
$h = $history->fetch_assoc();

// 2. 현재 문제
$problem = $conn->query("SELECT * FROM problems WHERE id = {$h['problem_id']}");
if (!$problem || $problem->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => '현재 문제를 찾을 수 없습니다.']);
    exit;
}
$p = $problem->fetch_assoc();

// 3. 비교할 필드 목록
$fields = ['title', 'question', 'answer', 'solution', 'hint', 'video', 'tags'];
$diff = [];
foreach ($fields as $f) {
    $diff[$f] = [
        'current' => $p[$f] ?? '',
        'history' => $h[$f] ?? ''
    ];
}

echo json_encode(['success' => true, 'diff' => $diff]);
$conn->close();

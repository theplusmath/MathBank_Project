<?php
session_start();
require_once 'db_connect.php';
$user_id = $_SESSION['user_id'] ?? 0;
$problem_id = intval($_POST['problem_id'] ?? 0);
if (!$user_id || !$problem_id) exit(json_encode(['msg'=>'입력 오류']));

// "wrong_notes" 테이블에 저장(없으면 새로 만드세요)
$conn->query("REPLACE INTO wrong_notes (user_id, problem_id, saved_at) VALUES ($user_id, $problem_id, NOW())");

echo json_encode(['msg'=>'오답노트에 저장되었습니다!']);

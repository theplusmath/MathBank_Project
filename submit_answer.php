<?php
session_start();
require_once 'db_connect.php';
$user_id = $_SESSION['user_id'] ?? 0;
$problem_id = intval($_POST['problem_id'] ?? 0);
$answer = trim($_POST['answer'] ?? '');

if (!$user_id || !$problem_id || !$answer) exit(json_encode(['msg'=>'입력 오류','reload'=>false]));

// 정답 가져오기
$p = $conn->query("SELECT answer FROM problems WHERE id=$problem_id")->fetch_assoc();
$is_correct = ($answer == $p['answer']) ? 1 : 0; // 심플비교(나중엔 더 정교하게)

// submissions 테이블에 저장
$conn->query("REPLACE INTO submissions (user_id, problem_id, answer, is_correct, submitted_at) VALUES ($user_id, $problem_id, '".addslashes($answer)."', $is_correct, NOW())");

echo json_encode(['msg'=>'제출 완료!','reload'=>true]);

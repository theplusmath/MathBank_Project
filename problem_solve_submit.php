<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('/var/www/config/db_connect.php');

// POST로 값 받기
$problem_id = isset($_POST['problem_id']) ? intval($_POST['problem_id']) : 0;
$answer = $_POST['answer'] ?? '';
$user_id = 1; // 예시 (로그인 연동 안된 상태라면 임시로 1번)

// 쿼리 작성 (submissions 테이블에 맞게 조정!)
$sql = "INSERT INTO submissions (problem_id, user_id, answer, submitted_at) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("쿼리 준비 오류: " . $conn->error); // ★ 이 줄 추가!
}

$stmt->bind_param('iis', $problem_id, $user_id, $answer);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "제출이 완료되었습니다.";
} else {
    echo "저장에 실패했습니다: " . $stmt->error;
}

// 제출 성공 시 결과 페이지로 이동
if ($stmt->affected_rows > 0) {
    header("Location: problem_solve_result.php?problem_id=" . $problem_id);
    exit;
} else {
    echo "저장에 실패했습니다: " . $stmt->error;
}


?>

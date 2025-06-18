<?php
require_once('/var/www/config/db_connect.php');

// 1. 문제 id 받기
$problem_id = isset($_GET['problem_id']) ? intval($_GET['problem_id']) : 0;
if ($problem_id == 0) exit('잘못된 접근입니다.');

// 2. 문제 정보 불러오기
$stmt = $conn->prepare("SELECT * FROM problems WHERE id=?");
$stmt->bind_param('i', $problem_id);
$stmt->execute();
$result = $stmt->get_result();
$problem = $result->fetch_assoc();
if (!$problem) exit('문제가 존재하지 않습니다.');

// 3. 가장 최근 제출한 답안 불러오기 (로그인X이면 user_id=0)
$user_id = 0; // 로그인 연동 전
$stmt2 = $conn->prepare("SELECT * FROM submissions WHERE problem_id=? AND user_id=? ORDER BY submitted_at DESC LIMIT 1");
$stmt2->bind_param('ii', $problem_id, $user_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
$submission = $res2->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>문제 풀이 결과</title>
</head>
<body>
  <h2><?= htmlspecialchars($problem['title']) ?> - 풀이 결과</h2>
  <div>
    <b>문제:</b><br>
    <?= $problem['question'] ?>
  </div>
  <hr>
  <div>
    <b>내가 제출한 답:</b><br>
    <?= htmlspecialchars($submission['answer'] ?? '제출한 답이 없습니다.') ?>
  </div>
  <hr>
  <div>
    <b>정답:</b> <?= htmlspecialchars($problem['answer']) ?><br>
    <b>해설:</b> <?= $problem['solution'] ?><br>
    <b>풀이영상:</b> 
    <?php if ($problem['video']) { ?>
      <a href="<?= htmlspecialchars($problem['video']) ?>" target="_blank">영상보기</a>
    <?php } else { ?>
      (등록된 영상 없음)
    <?php } ?>
  </div>
</body>
</html>

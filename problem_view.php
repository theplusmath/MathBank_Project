<?php
// problem_view.php
session_start();
require_once 'db_connect.php'; // DB 연결 함수
$user_id = $_SESSION['user_id'] ?? 0;

$problem_id = intval($_GET['id'] ?? 0);
if (!$problem_id) exit('문제 ID 오류');

// 문제 정보 불러오기
$stmt = $conn->prepare("SELECT * FROM problems WHERE id = ?");
$stmt->bind_param("i", $problem_id);
$stmt->execute();
$problem = $stmt->get_result()->fetch_assoc();
if (!$problem) exit('문제 없음');

// 해당 학생의 제출/오답 여부 확인
$subm = $conn->query("SELECT * FROM submissions WHERE user_id=$user_id AND problem_id=$problem_id")->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>문제 풀이</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <style>
    .problem-box {max-width:650px; margin:40px auto; padding:20px; border:1px solid #aaa; border-radius:12px;}
    .title {font-weight:bold; font-size:20px; margin-bottom:10px;}
    .solution-box {background:#f5f5f5; border-radius:8px; padding:10px;}
    .video-box {margin:15px 0;}
    .submit-btn {background:#348dda; color:white; border:none; border-radius:6px; padding:8px 22px; font-size:17px; cursor:pointer;}
    .wrong {color:#e43e1c; font-weight:bold;}
    .correct {color:#189d3a; font-weight:bold;}
  </style>
</head>
<body>
<div class="problem-box">
  <div class="title"><?= htmlspecialchars($problem['title']) ?></div>
  <div class="question"><?= $problem['question'] ?></div>

  <!-- 답안 제출 -->
  <form id="answerForm">
    <input type="hidden" name="problem_id" value="<?= $problem_id ?>">
    <textarea name="answer" rows="3" placeholder="여기에 풀이 또는 정답을 입력하세요..." style="width:100%;margin:18px 0;" required><?= htmlspecialchars($subm['answer'] ?? '') ?></textarea>
    <button type="submit" class="submit-btn">답안 제출</button>
    <?php if ($subm): ?>
      <span class="<?= ($subm['is_correct'] ?? 0) ? 'correct' : 'wrong' ?>">
        <?= ($subm['is_correct'] ?? 0) ? '정답입니다!' : '오답입니다.' ?>
      </span>
    <?php endif; ?>
  </form>

  <!-- 풀이영상/정답/해설 -->
  <?php if ($problem['video']): ?>
    <div class="video-box">
      <b>풀이영상:</b>
      <a href="<?= htmlspecialchars($problem['video']) ?>" target="_blank">[영상 보기]</a>
    </div>
  <?php endif; ?>
  <div style="margin-top:14px;">
    <button id="show_solution" class="submit-btn" type="button">정답/해설 보기</button>
  </div>
  <div id="solution" class="solution-box" style="display:none;">
    <div><b>정답:</b> <?= htmlspecialchars($problem['answer']) ?></div>
    <div><b>해설:</b> <?= $problem['solution'] ?></div>
  </div>

  <!-- 오답노트 저장 -->
  <div style="margin-top:25px;">
    <button type="button" class="submit-btn" id="add_wrong">오답노트 저장</button>
  </div>
</div>

<script>
  // 답안 제출
  $('#answerForm').on('submit', function(e){
    e.preventDefault();
    $.post('submit_answer.php', $(this).serialize(), function(res){
      alert(res.msg);
      if (res.reload) location.reload();
    }, 'json');
  });

  // 해설 보기
  $('#show_solution').click(function() {
    $('#solution').slideToggle();
  });

  // 오답노트 저장
  $('#add_wrong').click(function() {
    $.post('add_wrong_note.php', {problem_id:<?= $problem_id ?>}, function(res){
      alert(res.msg);
    }, 'json');
  });
</script>
</body>
</html>

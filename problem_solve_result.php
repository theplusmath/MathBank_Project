<?php
require_once('/var/www/config/db_connect.php');

$problem_id = isset($_GET['problem_id']) ? intval($_GET['problem_id']) : 0;
$user_id = 1; // (로그인 미연동 상태, 임시)

if ($problem_id == 0) exit('잘못된 접근입니다.');

// 문제 정보 불러오기
$stmt = $conn->prepare("SELECT * FROM problems WHERE id=?");
$stmt->bind_param('i', $problem_id);
$stmt->execute();
$result = $stmt->get_result();
$problem = $result->fetch_assoc();

if (!$problem) exit('문제가 존재하지 않습니다.');

// 방금 제출한 내 답안 불러오기
$stmt2 = $conn->prepare("SELECT answer, submitted_at FROM submissions WHERE problem_id=? AND user_id=? ORDER BY submitted_at DESC LIMIT 1");
$stmt2->bind_param('ii', $problem_id, $user_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
$submission = $result2->fetch_assoc();

// 답안 변수가 비어있을 때를 안전하게 처리
$submitted_answer = $submission['answer'] ?? '-';
$submitted_time = $submission['submitted_at'] ?? '-';

?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($problem['title']) ?> - 제출 결과</title>
  <style>
    body {
      background: #f7f9fb;
      font-family: 'Malgun Gothic', sans-serif;
      margin: 0; padding: 0;
    }
    .result-container {
      max-width: 900px;
      margin: 40px auto;
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 20px #c7d6ea22;
      padding: 36px 40px 32px 40px;
    }
    .title {
      font-size: 2em;
      font-weight: bold;
      margin-bottom: 14px;
      color: #23386b;
    }
    .section {
      margin: 24px 0;
    }
    .label {
      font-weight: bold;
      color: #0e387b;
      margin-bottom: 6px;
      display: block;
    }
    .value {
      margin-bottom: 10px;
      padding-left: 8px;
      line-height: 1.5;
    }
    .problem-block {
      background: #f3f5fa;
      padding: 22px 18px;
      border-radius: 10px;
      margin-bottom: 18px;
      font-size: 1.15em;
    }
    .answer-block, .solution-block {
      background: #f7fafc;
      padding: 15px 13px;
      border-radius: 8px;
      margin-bottom: 12px;
    }
    .small {
      color: #666;
      font-size: 0.96em;
    }
    .button-row {
      margin-top: 32px;
      display: flex;
      gap: 16px;
    }
    .btn {
      background: #0e387b;
      color: #fff;
      border: none;
      padding: 10px 28px;
      border-radius: 6px;
      font-size: 1em;
      cursor: pointer;
      text-decoration: none;
      transition: background 0.18s;
      font-weight: bold;
    }
    .btn:hover {
      background: #166b8b;
    }
  </style>

  <script>
  window.MathJax = {
    tex: {
      inlineMath: [['$', '$'], ['\\(', '\\)']],
      displayMath: [['$$', '$$'], ['\\[', '\\]']]
    },
    svg: {
      fontCache: 'global'
    }
  };
  </script>


  <!-- MathJax -->
  <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</head>
<body>
<div class="result-container">
  <div class="problem-block"><?= $problem['question'] ?></div>
  <div class="section">
    <span class="label">내 답안</span>
    <?= htmlspecialchars($submitted_answer) ?>
    <span class="small">제출 시각: <?= $submitted_time ?></span>
  </div>
  <div class="section">
    <span class="label">정답</span>
    <div class="answer-block"><?= $problem['answer'] ?></div>
  </div>
  <div class="section">
    <span class="label">해설</span>
    <div class="solution-block"><?= $problem['solution'] ?></div>
  </div>
  <?php if (!empty($problem['video'])): ?>
    <div class="section">
      <span class="label">풀이영상</span>
      <div class="solution-block" style="text-align: center;">
        <?php
        $url = htmlspecialchars($problem['video']);
        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $m) || preg_match('/v=([a-zA-Z0-9_-]+)/', $url, $m)) {
          $videoId = $m[1];
          echo '<iframe width="420" height="236" src="https://www.youtube.com/embed/'.$videoId.'" frameborder="0" allowfullscreen style="border-radius: 12px;"></iframe>';
        } else {
          echo '<a class="btn" href="'.$url.'" target="_blank">영상 보기</a>';
        }
        ?>
      </div>
    </div>
  <?php endif; ?>
  <div class="button-row">
    <a class="btn" href="problem_solve.php?id=<?= $problem['id'] ?>">다시 풀기</a>
    <a class="btn" href="list_problems.html">문제 목록으로</a>
  </div>
</div>
<script>
  MathJax.typesetPromise();
</script>
</body>
</html>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>문제 보기</title>
  <style>
  body {
    font-family: 'Malgun Gothic', sans-serif;
    margin: 20px;
    line-height: 1.8;
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
    word-break: break-word;
  }
  .problem-box {
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 10px;
    margin-top: 20px;
    background-color: #fafafa;
  }
  .problem-box h3 {
    margin-top: 0;
  }
  /* 🔥 문제! p 태그는 줄바꿈 허용 & 스크롤X */
  .problem-box p {
    overflow-x: visible;
    white-space: normal;
  }
  button {
    margin-top: 20px;
    padding: 10px 20px;
    font-size: 16px;
    cursor: pointer;
  }
  details {
    background: #f9f9f9;
    border: 1px solid #ccc;
    padding: 10px;
    margin-top: 15px;
  }
  /* 수식 display(블록)/inline 모두 가로 스크롤X */
  .mjx-container, .katex-display, .math-display, .math-block {
    overflow-x: visible !important;
    white-space: normal !important;
    max-width: 100% !important;
    word-break: break-word !important;
  }
  </style>

  <!-- MathJax -->
  <script>
    window.MathJax = {
      tex: {
        inlineMath: [['$', '$'], ['\\(', '\\)']],
        displayMath: [['$$', '$$'], ['\\[', '\\]']],
        processEscapes: true
      }
    };
  </script>
  <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</head>
<body>

<h1>문제 보기</h1>

<?php
require_once 'functions.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
  echo "<p style='color:red;'>❌ 문제 ID가 없습니다.</p>";
  exit;
}

$conn = connectDB();
$problem = getProblemById($conn, $id);
if (!$problem) {
  echo "<p style='color:red;'>❌ 문제를 찾을 수 없습니다.</p>";
  exit;
}

$originProblem = !empty($problem['origin_id']) ? getOriginProblem($conn, $problem['origin_id']) : null;
$conn->close();
?>

<?php if ($originProblem): ?>
  <div style="background-color: #ffe4e1; padding: 10px; margin-bottom: 20px; border: 1px solid #f08080; border-radius: 5px;">
    이 문제는 복사본입니다. [원본 문제: <a href="view_problem.php?id=<?= $originProblem['id'] ?>"><?= htmlspecialchars($originProblem['title']) ?></a>]
  </div>
<?php endif; ?>

<div class="problem-box">
  <h3>제목: <?= htmlspecialchars($problem['title']) ?></h3>
  <p><strong>문제:</strong><br><?= $problem['question'] ?></p>
  <p><strong>정답:</strong><br><?= htmlspecialchars($problem['answer']) ?></p>
  <p><strong>해설:</strong><br><?= $problem['solution'] ?></p>
  <p><strong>힌트:</strong><br><?= htmlspecialchars($problem['hint']) ?></p>
  <p><strong>영상 링크:</strong><br>
    <?php if (!empty($problem['video'])): ?>
      <a href="<?= htmlspecialchars($problem['video']) ?>" target="_blank"><?= htmlspecialchars($problem['video']) ?></a>
    <?php else: ?>
      없음
    <?php endif; ?>
  </p>
  <p><strong>난이도:</strong> <?= htmlspecialchars($problem['difficulty']) ?></p>
  <p><strong>유형:</strong> <?= htmlspecialchars($problem['type']) ?></p>
  <p><strong>분류:</strong> <?= htmlspecialchars($problem['category']) ?></p>
  <p><strong>출처:</strong> <?= htmlspecialchars($problem['source']) ?></p>
  <p><strong>경로:</strong> <?= htmlspecialchars($problem['path_text'] ?? '없음') ?></p>
</div>

<details>
  <summary>🔍 수식 분석 정보 (관리자용)</summary>
  <p><strong>대표 수식:</strong><br><?= htmlspecialchars($problem['main_formula_latex'] ?? '') ?></p>
  <p><strong>대표 수식 구조(tree):</strong><br><code><?= htmlspecialchars($problem['main_formula_tree'] ?? '') ?></code></p>
  <p><strong>Formula Keywords:</strong><br><?= htmlspecialchars($problem['formula_keywords'] ?? '') ?></p>
</details>

<button onclick="location.href='list_problems.html'">목록으로 돌아가기</button>

</body>
</html>

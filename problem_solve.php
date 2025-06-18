<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

$id = $_GET['id'] ?? 0;
if (!$id) {
    echo "문제 ID가 없습니다.";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM problems WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$problem = $result->fetch_assoc();

if (!$problem) {
    echo "문제를 찾을 수 없습니다.";
    exit;
}
?>


<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($problem['title']) ?> - 문제풀이</title>
  <style>
    .container {
      display: flex;
      gap: 48px;
      max-width: 1200px;
      margin: 40px auto;
      font-family: 'Malgun Gothic', sans-serif;
    }
    .question-area {
      flex: 2;
      background: #fafbfc;
      padding: 36px 28px;
      border-radius: 16px;
      font-size: 1.2em;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
      overflow-x: auto;
    }
    .solve-area {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 24px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      padding: 36px 28px;
    }
    textarea {
      width: 100%;
      height: 120px;
      font-size: 1em;
      border-radius: 8px;
      border: 1px solid #bbb;
      padding: 10px;
    }
    button {
      padding: 10px 0;
      background: #166b8b;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 1em;
      cursor: pointer;
      margin-top: 10px;
    }
    button:hover {
      background: #1c82aa;
    }

    @media (max-width: 900px) {
      .container {
        flex-direction: column;
        gap: 16px;
        max-width: 98vw;
      }
      .question-area, .solve-area {
        padding: 20px 8px;
        font-size: 1.08em;
      }
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
  <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
  <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
  <script>
      MathJax.typesetPromise();
  </script>
</head>
<body>
<div class="container">
  <div class="question-area">
  <h2><?= htmlspecialchars($problem['title']) ?></h2>
  <div><?= $problem['question'] ?></div>
  <?php if (!empty($problem['hint'])): ?>
  <div style="margin-top:24px;">
    <button id="showHintBtn"
      style="background:#1976d2;color:#fff;border:none;padding:7px 18px;border-radius:6px;cursor:pointer;">
      힌트 보기
    </button>
    <div id="hintBox" style="display:none; margin-top:14px; padding:12px 18px; background:#f8fafc; border-radius:8px; color:#205080;">
      <?= htmlspecialchars($problem['hint']) ?>
    </div>
  </div>
<?php endif; ?>
</div>
  <div class="solve-area">
    <form method="POST" action="problem_solve_submit.php">
      <input type="hidden" name="problem_id" value="<?= $problem['id'] ?>">
      <label>내 풀이/답안:</label>
      <textarea name="answer" required></textarea>
      <button type="submit">제출</button>
    </form>
  </div>
</div>
<script>
  MathJax.typesetPromise();
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var btn = document.getElementById('showHintBtn');
  var box = document.getElementById('hintBox');
  if (btn && box) {
    btn.addEventListener('click', function() {
      if (box.style.display === 'none' || box.style.display === '') {
        box.style.display = 'block';
        btn.textContent = '힌트 닫기';
      } else {
        box.style.display = 'none';
        btn.textContent = '힌트 보기';
      }
    });
  }
});
</script>


</body>
</html>

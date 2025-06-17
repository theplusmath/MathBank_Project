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
    .problem-box p {
      overflow-x: auto;
      overflow-y: hidden;
      white-space: nowrap;
      -webkit-overflow-scrolling: touch; /* iOS 부드러운 스크롤 */
    }
    button {
      margin-top: 20px;
      padding: 10px 20px;
      font-size: 16px;
      cursor: pointer;
    }
  </style>

  <!-- MathJax 설정 추가 -->
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

$originProblem = null;
if (!empty($problem['origin_id'])) {
    $originStmt = $conn->prepare("SELECT id, title FROM problems WHERE id = ?");
    $originStmt->bind_param("i", $problem['origin_id']);
    $originStmt->execute();
    $originResult = $originStmt->get_result();
    $originProblem = $originResult->fetch_assoc();
    $originStmt->close();
}

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
</div>

<button onclick="location.href='list_problems.html'">목록으로 돌아가기</button>

</body>
</html>

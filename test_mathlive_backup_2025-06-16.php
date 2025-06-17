<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB에서 문제 1개 가져오기
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM problems WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$problem = $result->fetch_assoc();
$conn->close();
if (!$problem) exit("문제를 찾을 수 없습니다.");

// $...$로 감싼 수식만 추출
preg_match_all('/\$([^\$]+)\$/', $problem['question'], $matches);
$formulas = $matches[1];
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>문제 본문+수식+라텍스 입력 (MathLive)</title>
  <script src="https://cdn.jsdelivr.net/npm/mathlive/dist/mathlive.min.js"></script>
  <style>
    body { font-family: 'Malgun Gothic', sans-serif; margin: 40px; }
    .math-block { margin-bottom: 26px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    .formula-label { font-weight: bold; }
    .latex-label { display: inline-block; margin-top: 7px; font-size:0.97em; color:#555; }
    math-field { width: 80%; min-width: 220px; font-size: 1.2em; background: #f9f9ff; }
    .latex-input { width: 80%; min-width:220px; font-size: 1em; margin-top:5px; }
    #output { margin-top:32px; color:#333; background:#f5f5ff; padding:12px; }
    .problem-box { background: #f8f9ff; padding: 18px; margin-bottom: 30px; border-radius: 8px; }
  </style>
</head>
<body>
<h2>문제 본문 & 수식 MathLive & LaTeX 입력</h2>
<div class="problem-box">
  <?= $problem['question'] ?>
</div>

<?php if (empty($formulas)): ?>
  <div style="color:#a00;">문제에서 추출된 수식이 없습니다.</div>
<?php else: ?>
  <form id="formulaForm" method="post" action="update_formula.php">
    <input type="hidden" name="id" value="<?= $problem['id'] ?>">
    <?php foreach ($formulas as $i => $f): ?>
      <div class="math-block">
        <span class="formula-label">수식 <?= $i+1 ?> (MathLive):</span><br>
        <!-- MathLive 입력창 -->
        <math-field id="mf<?= $i ?>" virtual-keyboard-mode="manual"><?= htmlspecialchars($f) ?></math-field>
        <br>
        <span class="latex-label">Latex 코드 입력란:</span><br>
        <input type="text" id="latex<?= $i ?>" class="latex-input" value="<?= htmlspecialchars($f) ?>">
      </div>
    <?php endforeach; ?>
    <button type="button" onclick="showAllLatex()">수정된 LaTeX 코드 출력</button>
  </form>
  <div id="output"></div>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
  // MathLive -> 아래 input으로 실시간 반영 (각 쌍 연결)
  <?php foreach ($formulas as $i => $f): ?>
    let mf<?= $i ?> = document.getElementById('mf<?= $i ?>');
    let latex<?= $i ?> = document.getElementById('latex<?= $i ?>');
    // MathLive 값이 바뀌면 아래 input에 자동 반영
    mf<?= $i ?>.addEventListener('input', function() {
      latex<?= $i ?>.value = mf<?= $i ?>.value;
    });
    // input 값을 수정하면 위의 MathLive도 바뀜
    latex<?= $i ?>.addEventListener('input', function() {
      mf<?= $i ?>.value = latex<?= $i ?>.value;
    });
  <?php endforeach; ?>
});

// 모든 값 출력
function showAllLatex() {
    const fields = document.querySelectorAll('math-field');
    const inputs = document.querySelectorAll('.latex-input');
    let out = '';
    fields.forEach((f, idx) => {
        out += `수식${idx+1} (MathLive): <code>${f.value}</code><br>`;
        out += `수식${idx+1} (LaTeX 입력란): <code>${inputs[idx].value}</code><br><br>`;
    });
    document.getElementById('output').innerHTML = out;
}
</script>
</body>
</html>

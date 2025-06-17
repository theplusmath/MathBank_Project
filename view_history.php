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

$result = $conn->query("SELECT * FROM history_problems WHERE problem_id = $id ORDER BY updated_at DESC");
$histories = [];
while ($row = $result->fetch_assoc()) {
    $histories[] = $row;
}

$teachers = [];
$res = $conn->query("SELECT id, name FROM teachers");
while ($row = $res->fetch_assoc()) {
    $teachers[$row['id']] = $row['name'];
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>문제 수정 이력 보기</title>
  <style>
    body { font-family: 'Malgun Gothic', sans-serif; margin: 20px; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
    th { background-color: #f2f2f2; }
  </style>
</head>
<body>
  <h1>?? 문제 수정 이력 보기</h1>
  <form method="get">
    문제 ID: <input type="number" name="id" value="<?= htmlspecialchars($id) ?>">
    <button type="submit">이력 불러오기</button>
  </form>

<?php if (count($histories) >= 2): ?>
  <h3>🧪 이력 비교</h3>
  <p>두 개의 이력을 선택하고 아래 버튼을 클릭하세요.</p>

  <form onsubmit="return compareHistory();">
    <?php foreach ($histories as $i => $row): ?>
      <label>
        <input type="checkbox" name="compare_t[]" value="<?= $row['updated_at'] ?>">
        <?= $row['updated_at'] ?> (<?= htmlspecialchars($row['title']) ?>)
      </label><br>
    <?php endforeach; ?>
    <button type="submit">🔍 선택한 두 이력 비교</button>
  </form>

  <div id="diffResult" style="margin-top: 30px; padding: 15px; border: 1px solid #ccc;">
    <!-- 비교 결과 iframe 삽입됨 -->
  </div>
<?php endif; ?>




  <?php if (empty($histories)): ?>
    <p>?? 이력이 없습니다.</p>
  <?php else: ?>
    <h2>?? 문제 ID <?= $id ?>의 수정 이력 (<?= count($histories) ?>개)</h2>
    <table>
      <thead>
        <tr>
          <th>수정일</th><th>제목</th><th>질문</th><th>정답</th><th>해설</th>
          <th>난이도</th><th>유형</th><th>태그</th><th>경로</th>
          <th>작성자</th><th>복사자</th><th>원본ID</th><th>대표 수식</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($histories as $row): ?>
          <tr>
            <td><?= $row['updated_at'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= $row['question'] ?></td>
            <td><?= $row['answer'] ?></td>
            <td><?= $row['solution'] ?></td>
            <td><?= $row['difficulty'] ?></td>
            <td><?= $row['type'] ?></td>
            <td><?= htmlspecialchars($row['tags']) ?></td>
            <td><?= htmlspecialchars($row['path_text']) ?></td>
            <td><?= $teachers[$row['created_by']] ?? '' ?></td>
            <td><?= $teachers[$row['copied_by']] ?? '' ?></td>
            <td><?= $row['origin_id'] ?></td>
            <td>$$<?= htmlspecialchars($row['main_formula_latex']) ?>$$</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
  <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

<script>
function compareHistory() {
  const checked = document.querySelectorAll('input[name="compare_t[]"]:checked');
  if (checked.length !== 2) {
    alert('⚠️ 정확히 두 개를 선택해주세요.');
    return false;
  }

  const t1 = checked[0].value;
  const t2 = checked[1].value;
  const problem_id = <?= (int)$id ?>;

  const iframe = document.createElement('iframe');
  iframe.src = `compare_history.php?problem_id=${problem_id}&t1=${t1}&t2=${t2}`;
  iframe.width = "100%";
  iframe.height = "600";
  iframe.style.border = "1px solid #aaa";
  iframe.onload = () => window.scrollTo({ top: iframe.offsetTop, behavior: 'smooth' });

  const container = document.getElementById('diffResult');
  container.innerHTML = '';
  container.appendChild(iframe);

  return false; // 폼 제출 막기
}
</script>


</body>
</html>

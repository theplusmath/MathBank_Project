<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset("utf8mb4");

$problem_id = $_GET['problem_id'] ?? 0;
$t1 = $_GET['t1'] ?? '';
$t2 = $_GET['t2'] ?? '';

if (!$problem_id || !$t1 || !$t2) {
    echo "<p>?? 잘못된 요청입니다.</p>";
    exit;
}

// 최신 것이 first, 이전 것이 second 되도록 정렬
$timestamps = [$t1, $t2];
sort($timestamps);
[$firstTime, $secondTime] = $timestamps;

// 두 이력 조회
$rows = [];
foreach ([$firstTime, $secondTime] as $t) {
    $stmt = $conn->prepare("SELECT * FROM history_problems WHERE problem_id = ? AND updated_at = ?");
    $stmt->bind_param("is", $problem_id, $t);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows[] = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();
if (count($rows) !== 2) {
    echo "<p>?? 이력 정보를 불러오지 못했습니다.</p>";
    exit;
}

function highlightDiff($oldRaw, $newRaw) {
    if ($oldRaw === $newRaw) return nl2br(htmlspecialchars($newRaw));

    $maxLen = max(mb_strlen($oldRaw), mb_strlen($newRaw));
    $diff = '';

    for ($i = 0; $i < $maxLen; $i++) {
        $c1 = mb_substr($oldRaw, $i, 1);
        $c2 = mb_substr($newRaw, $i, 1);
        if ($c1 !== $c2) {
            $diff .= '<span class="diff-highlight">' . htmlspecialchars($c2) . '</span>';
        } else {
            $diff .= htmlspecialchars($c2);
        }
    }

    return nl2br($diff);
}

$fieldsToCompare = [
    'title' => '제목',
    'question' => '질문',
    'answer' => '정답',
    'solution' => '해설',
    'hint' => '힌트',
    'difficulty' => '난이도',
    'type' => '유형',
    'tags' => '태그',
    'path_text' => '경로',
    'main_formula_latex' => '대표 수식',
];

echo <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>문제 변경 이력 비교</title>
  <style>
    body {
      font-family: 'Malgun Gothic', sans-serif;
      padding: 20px;
    }
    .diff-highlight {
      background-color: #ffd54f;
      color: #000;
      font-weight: bold;
      padding: 1px 2px;
      border-radius: 2px;
    }
    table {
      border-collapse: collapse;
      width: 100%;
      margin-top: 20px;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 8px;
      vertical-align: top;
    }
    th {
      background-color: #f2f2f2;
    }
  </style>
</head>
<body>
HTML;



echo "<h2>📝 문제 ID $problem_id 변경 비교</h2>";

echo "<p>?? 비교: <strong>$secondTime</strong> → <strong>$firstTime</strong></p>";

echo '<label style="display:inline-block; margin: 10px 0;">
  <input type="checkbox" id="toggleChangedOnly" onchange="toggleChangedRows()"> 
  🔍 변경된 항목만 보기
</label>';


echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width:100%;'>";
echo "<tr><th>항목</th><th>변경 전</th><th>변경 후</th></tr>";

foreach ($fieldsToCompare as $key => $label) {
    $before = $rows[0][$key] ?? '';
    $after = $rows[1][$key] ?? '';
    $highlighted = highlightDiff($before, $after);
    $isChanged = $before !== $after;

    echo "<tr data-changed='" . ($isChanged ? "true" : "false") . "'>";

    echo "<td><strong>$label</strong></td>";
    $latexFields = ['question', 'solution', 'answer', 'main_formula_latex'];

    $beforeOutput = in_array($key, $latexFields)
        ? nl2br($before)  // 수식 필드는 이스케이프 없이 그대로 출력
        : nl2br(htmlspecialchars($before));

    echo "<td style='background:#f9f9f9'>$beforeOutput</td>";
    echo "<td style='" . ($isChanged ? "background:#fff6d5;" : "") . "'>$highlighted</td>";
    echo "</tr>";
}

echo "</table>";


// 👇 이 아래에 추가
echo <<<HTML
<form action="restore_problem.php" method="post" onsubmit="return confirm('정말 이 버전으로 복원하시겠습니까?')">
  <input type="hidden" name="problem_id" value="$problem_id">
  <input type="hidden" name="timestamp" value="$secondTime">
  <button type="submit" style="margin-top:20px; padding:10px 20px; background:#f44336; color:white; border:none; border-radius:4px; cursor:pointer;">
    🔄 변경 전 상태로 복원
  </button>
</form>
HTML;



?>

<script>
function toggleChangedRows() {
  const showOnlyChanged = document.getElementById('toggleChangedOnly').checked;
  const rows = document.querySelectorAll('tr[data-changed]');
  rows.forEach(row => {
    const isChanged = row.getAttribute('data-changed') === 'true';
    row.style.display = (!showOnlyChanged || isChanged) ? '' : 'none';
  });
}
</script>

<?php
echo <<<HTML
<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
<script id="MathJax-script" async
        src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</body>
</html>
HTML;
?>
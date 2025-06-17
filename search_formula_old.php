<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "db.php";

// 수식 정규화 함수
function normalize_formula($formula) {
    $formula = trim($formula);
    $formula = preg_replace('/[a-zA-Z]+/', 'VAR', $formula);
    $formula = preg_replace('/\d+/', 'NUM', $formula);
    return $formula;
}

$input_formula = $_GET['formula'] ?? '';
$normalized = '';
$similar_results = [];

if ($input_formula) {
    $normalized = normalize_formula($input_formula);
    $formula_hash = sha1($normalized);
    $prefix = substr($formula_hash, 0, 10);

    // 유사 수식 검색
    $stmt = $pdo->prepare("SELECT * FROM formula_index WHERE formula_skeleton_hash LIKE ?");
    $stmt->execute([$prefix . '%']);
    $similar_results = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>수식 검색</title>
</head>
<body>
    <h2>🔍 수식 검색기</h2>

    <form method="get" action="search_formula.php">
        <label for="formula">LaTeX 수식 입력:</label>
        <input type="text" name="formula" id="formula" style="width:400px;" value="<?= htmlspecialchars($input_formula) ?>">
        <button type="submit">검색</button>
    </form>

    <?php if ($input_formula): ?>
        <hr>
        <h3>🔍 검색 결과</h3>
        <p><strong>입력 수식:</strong> <?= htmlspecialchars($input_formula) ?></p>
        <p><strong>정규화된 수식:</strong> <?= htmlspecialchars($normalized) ?></p>

        <h4>🔍 유사한 수식을 가진 문제:</h4>
        <?php if (count($similar_results) > 0): ?>
            <ul>
                <?php foreach ($similar_results as $row): ?>
                    <li>
                        문제 ID: <?= htmlspecialchars($row['problem_id']) ?><br>
                        수식: <?= htmlspecialchars($row['original_formula']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>❌ 유사한 수식을 가진 문제가 없습니다.</p>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "db.php";

function normalize_formula($formula) {
    $formula = trim($formula);
    $formula = preg_replace('/[a-zA-Z]+/', 'VAR', $formula);
    $formula = preg_replace('/\d+/', 'NUM', $formula);
    return $formula;
}

$stmt = $pdo->query("SELECT id, question FROM problems");

while ($row = $stmt->fetch()) {
    $problem_id = $row['id'];
    $question = $row['question'] ?? '';

    if (!$question) continue;

    preg_match_all('/\\\\\((.*?)\\\\\)|\$\$(.*?)\$\$/s', $question, $matches);
    $formulas = array_filter(array_merge($matches[1], $matches[2]));

    foreach ($formulas as $formula) {
        $original_formula = trim($formula);
        $skeleton = normalize_formula($original_formula);
        $formula_hash = sha1($skeleton);  // 해시 생성

        // 중복 확인
        $check = $pdo->prepare("SELECT COUNT(*) FROM formula_index WHERE problem_id = ? AND original_formula = ?");
        $check->execute([$problem_id, $original_formula]);
        if ($check->fetchColumn() > 0) continue;

        // 삽입
        $insert = $pdo->prepare("INSERT INTO formula_index 
            (problem_id, original_formula, formula_skeleton, formula_skeleton_hash) 
            VALUES (?, ?, ?, ?)");
        $insert->execute([$problem_id, $original_formula, $skeleton, $formula_hash]);
    }
}

echo "✅ 수식 추출 및 저장 완료!";
?>

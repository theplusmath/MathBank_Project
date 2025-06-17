<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);

require_once 'functions.php';

echo "✅ functions.php 까지 성공";
exit;

require_once 'mathpix_analyzer.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>✅ update_problem.php 진단 도구</h2>";

$conn = connectDB();
echo "✅ DB 연결 성공<br>";

// 샘플 데이터
$id = 1;  // 수정할 실제 문제 ID를 넣으세요

$title = '디버그 테스트 제목';
$question = '수식: \\( x^2 + 2x + 1 \\)';
$answer = '정답은 -1';
$solution = '인수분해: \\( (x+1)^2 \\)';
$hint = '제곱식';
$video = '';
$difficulty = 2;
$type = '객관식';
$category = '이차방정식';
$source = '테스트용';
$created_by = 1;
$tags = '이차방정식, 인수분해';
$path_text = '2022개정/고등수학/이차방정식';
$path_id = 13;

$tagsArray = array_filter(array_map('trim', explode(',', $tags)));

$question_text = cleanTextForLatex($question);
$answer_text = cleanTextForLatex($answer);
$solution_text = cleanTextForLatex($solution);
$hint_text = cleanTextForLatex($hint);

$analyzed = analyzeFormulasFromQuestion($question_text);
echo "✅ 수식 분석 결과 수: " . count($analyzed) . "<br>";

$mainFormulaLatex = $mainHash = $mainFormulaTree = $mainSympy = $keywords = $allFormulasTree = '';

if (!empty($analyzed)) {
    usort($analyzed, fn($a, $b) => mb_strlen($b['latex']) - mb_strlen($a['latex']));
    $mainFormulaLatex = implode(', ', array_column(array_slice($analyzed, 0, 3), 'latex'));
    $mainHash = $analyzed[0]['hash'] ?? '';
    $mainFormulaTree = json_encode($analyzed[0]['tree'] ?? [], JSON_UNESCAPED_UNICODE);
    $mainSympy = $analyzed[0]['sympy_expr'] ?? '';
    $allFormulasTree = json_encode(array_map(fn($f) => [
        'latex' => $f['latex'], 'tree' => $f['tree'], 'hash' => $f['hash']
    ], $analyzed), JSON_UNESCAPED_UNICODE);
    $keywords = implode(',', array_unique(array_merge(...array_column($analyzed, 'keywords'))));
    echo "✅ 메인 수식: $mainFormulaLatex<br>";
} else {
    echo "⚠️ 수식 분석 결과 없음<br>";
}

// 데이터 집합
$data = [
    'title' => $title,
    'question' => $question,
    'question_text' => $question_text,
    'answer' => $answer,
    'answer_text' => $answer_text,
    'solution' => $solution,
    'solution_text' => $solution_text,
    'hint' => $hint,
    'hint_text' => $hint_text,
    'video' => $video,
    'difficulty' => $difficulty,
    'type' => $type,
    'category' => $category,
    'source' => $source,
    'created_by' => $created_by,
    'tags' => $tags,
    'path_text' => $path_text,
    'path_id' => $path_id,
    'main_formula_latex' => $mainFormulaLatex,
    'main_formula_tree' => $mainFormulaTree,
    'all_formulas_tree' => $allFormulasTree,
    'formula_keywords' => $keywords,
    'hash' => $mainHash,
    'sympy_expr' => $mainSympy
];

// SQL 생성
$setClause = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
$sql = "UPDATE problems SET $setClause, updated_at = NOW() WHERE id = ?";

echo "<hr><b>SQL:</b><br><code>$sql</code><br>";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "❌ SQL 준비 실패: " . $conn->error;
    exit;
}

$types = guessParamTypes($data) . 'i';
$params = array_values($data);
$params[] = $id;

echo "<b>타입 문자열:</b> $types<br>";
echo "<b>바인딩 변수 수:</b> " . count($params) . "<br>";

if (!bindParams($stmt, $types, $params)) {
    echo "❌ bindParams 실패";
    exit;
}

if (!$stmt->execute()) {
    echo "❌ 실행 실패: " . $stmt->error;
    exit;
}

echo "✅ UPDATE 성공: ID $id<br>";

// 태그 삭제 및 삽입
$conn->query("DELETE FROM problem_tags WHERE problem_id = $id");
echo "✅ 기존 태그 삭제<br>";

foreach ($tagsArray as $tag) {
    $conn->query("INSERT IGNORE INTO tags (name) VALUES ('" . $conn->real_escape_string($tag) . "')");
    $res = $conn->query("SELECT id FROM tags WHERE name = '" . $conn->real_escape_string($tag) . "'");
    if ($tagRow = $res->fetch_assoc()) {
        $tag_id = $tagRow['id'];
        $conn->query("INSERT IGNORE INTO problem_tags (problem_id, tag_id) VALUES ($id, $tag_id)");
    }
}
echo "✅ 태그 삽입 완료<br>";

// 후처리
processFormulasForProblem($id, $question, $solution, $answer, $hint, $conn);
echo "✅ 수식 후처리 완료<br>";

echo "<hr><b>🎉 모든 점검 완료!</b>";

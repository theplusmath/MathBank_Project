<?php
// 에러 출력 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 로그 파일 기록
$logFile = __DIR__ . '/debug_check.txt';
file_put_contents($logFile, date('c') . " \u2705 START\n", FILE_APPEND);

require_once __DIR__ . '/../functions.php';
file_put_contents($logFile, date('c') . " \u2705 functions.php \ud3ec\ud568\n", FILE_APPEND);

// DB \uc5f0\uacb0
$conn = connectDB();
file_put_contents($logFile, date('c') . " \u2705 DB \uc5f0\uacb0 OK\n", FILE_APPEND);

// \uc0d8\ud50c \ub370\uc774\ud130
$id = 1;
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

if (!file_exists(__DIR__ . '/../mathpix_analyzer.php')) {
    file_put_contents($logFile, date('c') . " ? mathpix_analyzer.php 파일 없음\n", FILE_APPEND);
    exit;
}
require_once __DIR__ . '/../mathpix_analyzer.php';
file_put_contents($logFile, date('c') . " ? mathpix_analyzer.php 포함 완료\n", FILE_APPEND);

file_put_contents($logFile, date('c') . " \u2705 mathpix_analyzer \ud3ec\ud568 \uc644\ub8cc\n", FILE_APPEND);

$question_text = cleanTextForLatex($question);
$answer_text = cleanTextForLatex($answer);
$solution_text = cleanTextForLatex($solution);
$hint_text = cleanTextForLatex($hint);

$analyzed = analyzeFormulasFromQuestion($question_text);
file_put_contents($logFile, date('c') . " \u2705 \uc218\uc2dd \ubd84\uc11d \uc218: " . count($analyzed) . "\n", FILE_APPEND);

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
}

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

$setClause = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
$sql = "UPDATE problems SET $setClause, updated_at = NOW() WHERE id = ?";

file_put_contents($logFile, date('c') . " \u2705 SQL \uc900\ube44 \uc644\ub8cc\n", FILE_APPEND);

$stmt = $conn->prepare($sql);
if (!$stmt) {
    file_put_contents($logFile, date('c') . " \u274c SQL \uc900\ube44 \uc2e4\ud328: " . $conn->error . "\n", FILE_APPEND);
    exit;
}

$types = guessParamTypes($data) . 'i';
$params = array_values($data);
$params[] = $id;

if (!bindParams($stmt, $types, $params)) {
    file_put_contents($logFile, date('c') . " \u274c bindParams \uc2e4\ud328\n", FILE_APPEND);
    exit;
}

if (!$stmt->execute()) {
    file_put_contents($logFile, date('c') . " \u274c UPDATE \uc2e4\ud328: " . $stmt->error . "\n", FILE_APPEND);
    exit;
}

file_put_contents($logFile, date('c') . " \u2705 UPDATE \uc131\uacf5\n", FILE_APPEND);

$conn->query("DELETE FROM problem_tags WHERE problem_id = $id");
foreach ($tagsArray as $tag) {
    $conn->query("INSERT IGNORE INTO tags (name) VALUES ('" . $conn->real_escape_string($tag) . "')");
    $res = $conn->query("SELECT id FROM tags WHERE name = '" . $conn->real_escape_string($tag) . "'");
    if ($row = $res->fetch_assoc()) {
        $tag_id = $row['id'];
        $conn->query("INSERT IGNORE INTO problem_tags (problem_id, tag_id) VALUES ($id, $tag_id)");
    }
}
file_put_contents($logFile, date('c') . " \u2705 \ud0dc\uadf8 \uc0bd\uc785 \uc644\ub8cc\n", FILE_APPEND);

processFormulasForProblem($id, $question, $solution, $answer, $hint, $conn);
file_put_contents($logFile, date('c') . " \u2705 \uc218\uc2dd \ud6c4\ucc98\ub9ac \uc644\ub8cc\n", FILE_APPEND);

file_put_contents($logFile, date('c') . " \u2705 \ubaa8\ub4e0 \uacfc\uc815 \uc644\ub8cc\n", FILE_APPEND);

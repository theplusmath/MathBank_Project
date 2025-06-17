<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);

file_put_contents(__DIR__ . '/debug_check.txt', date('c') . " ✅ update_problem_debug.php 시작됨\n", FILE_APPEND);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../functions.php';


file_put_contents(__DIR__ . '/php-error.log', date('c') . " ✅ functions.php 포함 완료\n", FILE_APPEND);

require_once __DIR__ . '/../mathpix_analyzer.php';
file_put_contents(__DIR__ . '/php-error.log', date('c') . " ✅ mathpix_analyzer.php 포함 완료\n", FILE_APPEND);

$conn = connectDB();
file_put_contents(__DIR__ . '/php-error.log', date('c') . " ✅ DB 연결 완료\n", FILE_APPEND);

// 입력 수집
$id         = (int)($_POST['id'] ?? 0);
$copyMode   = $_POST['copyMode'] ?? '0';
$title      = $_POST['title'] ?? '';
$question   = $_POST['question'] ?? '';
$answer     = $_POST['answer'] ?? '';
$solution   = $_POST['solution'] ?? '';
$hint       = $_POST['hint'] ?? '';
$video      = $_POST['video'] ?? '';
$difficulty = isset($_POST['difficulty']) && is_numeric($_POST['difficulty']) ? (int)$_POST['difficulty'] : null;
$type       = $_POST['type'] ?? '';
$category   = $_POST['category'] ?? '';
$source     = $_POST['source'] ?? '';
$created_by = isset($_POST['created_by']) && $_POST['created_by'] !== '' ? (int)$_POST['created_by'] : null;
$tags       = $_POST['tags'] ?? '';
$path_text  = $_POST['path_text'] ?? '';
$path_id    = isset($_POST['path_id']) && is_numeric($_POST['path_id']) ? (int)$_POST['path_id'] : null;

$tagsArray = parseTags($tags);
$question_text = cleanTextForLatex($question);
$answer_text   = cleanTextForLatex($answer);
$solution_text = cleanTextForLatex($solution);
$hint_text     = cleanTextForLatex($hint);

// 수식 분석
$analyzed = analyzeFormulasFromQuestion($question_text);
$mainFormulaLatex = $mainFormulaTree = $allFormulasTree = $keywords = $mainHash = $mainSympy = '';

if (!empty($analyzed)) {
    usort($analyzed, function ($a, $b) {
        return mb_strlen($b['latex']) - mb_strlen($a['latex']);
    });

    $mainFormulaLatex = implode(', ', array_map(function ($item) {
        return $item['latex'];
    }, array_slice($analyzed, 0, 3)));

    $mainHash = isset($analyzed[0]['hash']) ? $analyzed[0]['hash'] : '';
    $mainFormulaTree = isset($analyzed[0]['tree']) ? json_encode($analyzed[0]['tree'], JSON_UNESCAPED_UNICODE) : '';
    $mainSympy = isset($analyzed[0]['sympy_expr']) ? $analyzed[0]['sympy_expr'] : '';

    $allFormulasTree = json_encode(array_map(function ($f) {
        return array(
            'latex' => $f['latex'],
            'tree'  => $f['tree'],
            'hash'  => $f['hash']
        );
    }, $analyzed), JSON_UNESCAPED_UNICODE);

    $keywordArrays = array_map(function ($item) {
        return isset($item['keywords']) ? $item['keywords'] : [];
    }, $analyzed);
    $keywords = implode(',', array_unique(call_user_func_array('array_merge', $keywordArrays)));
}

// SQL 구성 및 실행
$sql = "UPDATE problems SET
    title = ?, question = ?, question_text = ?, answer = ?, answer_text = ?, solution = ?, solution_text = ?,
    hint = ?, hint_text = ?, video = ?, difficulty = ?, type = ?, category = ?, source = ?, created_by = ?,
    tags = ?, path_text = ?, path_id = ?, main_formula_latex = ?, main_formula_tree = ?, all_formulas_tree = ?,
    formula_keywords = ?, hash = ?, sympy_expr = ?, updated_at = NOW() WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => '쿼리 준비 실패', 'error' => $conn->error]);
    exit;
}

$params = [
    $title, $question, $question_text, $answer, $answer_text, $solution, $solution_text,
    $hint, $hint_text, $video, $difficulty, $type, $category, $source, $created_by,
    $tags, $path_text, $path_id, $mainFormulaLatex, $mainFormulaTree, $allFormulasTree,
    $keywords, $mainHash, $mainSympy, $id
];

$types = guessParamTypes($params);
bindParams($stmt, $types, $params);

if ($stmt->execute()) {
    $conn->query("DELETE FROM problem_tags WHERE problem_id = $id");
    foreach ($tagsArray as $tag) {
        $conn->query("INSERT IGNORE INTO tags (name) VALUES ('" . $conn->real_escape_string($tag) . "')");
        $res = $conn->query("SELECT id FROM tags WHERE name = '" . $conn->real_escape_string($tag) . "'");
        if ($tagRow = $res->fetch_assoc()) {
            $conn->query("INSERT IGNORE INTO problem_tags (problem_id, tag_id) VALUES ($id, {$tagRow['id']})");
        }
    }

    processFormulasForProblem($id, $question, $solution, $answer, $hint, $conn);

    echo json_encode(['status' => 'success', 'message' => '문제 수정 완료', 'id' => $id]);
} else {
    echo json_encode(['status' => 'error', 'message' => '문제 수정 실패', 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();

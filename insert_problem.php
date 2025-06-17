<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'functions.php';
require_once 'mathpix_analyzer.php';

$conn = connectDB();

// POST 데이터 안전하게 수집
$title    = $_POST['title']    ?? '';
$question = $_POST['question'] ?? '';
$answer   = $_POST['answer']   ?? '';
$solution = $_POST['solution'] ?? '';
$hint     = $_POST['hint']     ?? '';
$video    = $_POST['video']    ?? '';
$difficulty = isset($_POST['difficulty']) && is_numeric($_POST['difficulty']) ? (int)$_POST['difficulty'] : null;
$type     = $_POST['type']     ?? '';
$category = $_POST['category'] ?? '';
$source   = $_POST['source']   ?? '';
$created_by = isset($_POST['created_by']) && is_numeric($_POST['created_by']) ? (int)$_POST['created_by'] : null;
$tags     = $_POST['tags']     ?? '';
$path_text = $_POST['path_text'] ?? '';
$path_id  = isset($_POST['path_id']) && is_numeric($_POST['path_id']) ? (int)$_POST['path_id'] : null;

// 태그 처리
$tagsArray = array_filter(array_map('trim', explode(',', $tags)));

// --- 정제본 생성 ---
$question_text = cleanTextForLatex($question ?? '');
$answer_text   = cleanTextForLatex($answer ?? '');
$solution_text = cleanTextForLatex($solution ?? '');
$hint_text     = cleanTextForLatex($hint ?? '');

// --- 수식 분석 ---
$analyzed = analyzeFormulasFromQuestion($question);
$mainFormulaLatex = '';
$mainFormulaTree = '';
$allFormulasTree = '';
$keywords = '';
$mainHash = '';
$mainSympy = '';

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

// --- INSERT 쿼리 ---
$stmt = $conn->prepare("INSERT INTO problems
    (title, question, question_text, answer, answer_text, solution, solution_text, hint, hint_text, video, difficulty, type, category, source,
     created_by, tags, path_text, path_id,
     main_formula_latex, main_formula_tree, all_formulas_tree,
     formula_keywords, hash, sympy_expr, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

$stmt->bind_param("ssssssssssisssississsss",
    $title, $question, $question_text,
    $answer, $answer_text,
    $solution, $solution_text,
    $hint, $hint_text,
    $video, $difficulty, $type, $category, $source,
    $created_by, $tags, $path_text, $path_id,
    $mainFormulaLatex, $mainFormulaTree, $allFormulasTree,
    $keywords, $mainHash, $mainSympy
);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    // 태그 별도 연결 (problem_tags 테이블)
    if (!empty($tagsArray)) {
        foreach ($tagsArray as $tag) {
            // 태그 등록 (중복 무시)
            $stmtTag = $conn->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
            $stmtTag->bind_param("s", $tag);
            $stmtTag->execute();
            $stmtTag->close();

            // 태그 id 조회
            $stmtTagId = $conn->prepare("SELECT id FROM tags WHERE name = ?");
            $stmtTagId->bind_param("s", $tag);
            $stmtTagId->execute();
            $stmtTagId->bind_result($tag_id);
            $stmtTagId->fetch();
            $stmtTagId->close();

            // 문제-태그 연결 (중복 무시)
            if ($tag_id) {
                $stmtProbTag = $conn->prepare("INSERT IGNORE INTO problem_tags (problem_id, tag_id) VALUES (?, ?)");
                $stmtProbTag->bind_param("ii", $newId, $tag_id);
                $stmtProbTag->execute();
                $stmtProbTag->close();
            }
        }
    }
    // 수식 분석 기록 등 후처리 함수 호출
    processFormulasForProblem($newId, $question, $solution, $answer, $hint, $conn);

    echo "<script>alert('문제 등록 완료. ID: {$newId}'); window.location.href = 'edit_problem.php?id={$newId}';</script>";
} else {
    die("문제 등록 실패: " . $stmt->error);
}
$stmt->close();

?>

<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

file_put_contents(__DIR__ . '/debug_post_log.txt', "[1] PHP 시작\n", FILE_APPEND);
require_once 'functions.php';
file_put_contents(__DIR__ . '/debug_post_log.txt', "[2] functions.php 불러옴\n", FILE_APPEND);
require_once 'mathpix_analyzer.php';
file_put_contents(__DIR__ . '/debug_post_log.txt', "[3] mathpix_analyzer.php 불러옴\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug_post_log.txt', "\n\n--- [START LOG] " . date('Y-m-d H:i:s') . " ---\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug_post_log.txt', "[POST DATA]: " . print_r($_POST, true), FILE_APPEND);

$conn = connectDB();

$id = is_numeric($_POST['id'] ?? null) ? (int)$_POST['id'] : 0;
$copyMode = $_POST['copyMode'] ?? '0';
$title = $_POST['title'] ?? '';
$question = $_POST['question'] ?? '';
$answer = $_POST['answer'] ?? '';
$solution = $_POST['solution'] ?? '';
$hint = $_POST['hint'] ?? '';
$video = $_POST['video'] ?? '';
$difficulty = $_POST['difficulty'] ?? 0;
$type = $_POST['type'] ?? '';
$category = $_POST['category'] ?? '';
$source = $_POST['source'] ?? '';
$created_by = $_POST['created_by'] ?? 0;
$tags = $_POST['tags'] ?? '';
$tagsArray = array_filter(array_map('trim', explode(',', $tags)));
$tagReset = isset($_POST['tagReset']) ? $_POST['tagReset'] : '';
$path_text = $_POST['path_text'] ?? '';
$path_id = $_POST['path_id'] ?? 0;

// === 정제본 따로 생성 ===
$question_text = cleanTextForLatex($question ?? '');
$answer_text   = cleanTextForLatex($answer ?? '');
$solution_text = cleanTextForLatex($solution ?? '');
$hint_text     = cleanTextForLatex($hint ?? '');

// 디버그 로그
file_put_contents(__DIR__.'/debug_post_log.txt', "[디버그] question_text: $question_text\n", FILE_APPEND);
file_put_contents(__DIR__.'/debug_post_log.txt', "[디버그] answer_text: $answer_text\n", FILE_APPEND);
file_put_contents(__DIR__.'/debug_post_log.txt', "[디버그] solution_text: $solution_text\n", FILE_APPEND);
file_put_contents(__DIR__.'/debug_post_log.txt', "[디버그] hint_text: $hint_text\n", FILE_APPEND);

// 수식 분석
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

// ---------- (2) 복사모드(새 문제 생성) ----------
if ($copyMode === '1') {
    $created_at = date('Y-m-d H:i:s');
    $title = preg_match('/^\[복사본(?: (\d+))?\]\s*(.+)$/u', $title, $matches)
        ? "[복사본 " . ((int)($matches[1] ?? 1) + 1) . "] " . $matches[2]
        : "[복사본] $title";

    $stmt = $conn->prepare("INSERT INTO problems
        (title, question, question_text, answer, answer_text, solution, solution_text, hint, hint_text, video, difficulty, type, category, source,
         created_by, tags, path_text, path_id, copied_by, origin_id,
         main_formula_latex, main_formula_tree, all_formulas_tree,
         formula_keywords, hash, sympy_expr, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $copied_by = $_POST['copied_by'] ?? 0;
    $stmt->bind_param("ssssssssssisssissiiissssss",
        $title, $question, $question_text,
        $answer, $answer_text,
        $solution, $solution_text,
        $hint, $hint_text,
        $video, $difficulty, $type, $category, $source,
        $created_by, $tags, $path_text, $path_id,
        $copied_by, $id,
        $mainFormulaLatex, $mainFormulaTree, $allFormulasTree,
        $keywords, $mainHash, $mainSympy
    );

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        processFormulasForProblem($newId, $question, $solution, $answer, $hint, $conn);
        echo "<script>alert('복사 완료. 새 문제 ID: {$newId}'); window.open('edit_problem.php?id={$newId}', '_blank'); window.location.href = 'list_problems.html';</script>";
    } else {
        die("복사 실패: " . $stmt->error);
    }
    $stmt->close();
    exit;
}

// ---------- (3) 기존 수정 이력(history) 저장 ----------
$backup = $conn->query("SELECT * FROM problems WHERE id = $id");
if ($backup && $backup->num_rows > 0) {
    $old = $backup->fetch_assoc();
    $fields = ['problem_id', ...array_keys($old), 'updated_at'];
    $fields = array_filter($fields, fn($f) => $f !== 'id');
    $values = [intval($old['id'])];
    foreach ($old as $key => $val) {
        if ($key === 'id') continue;
        $values[] = is_null($val) ? "NULL" : ("'" . $conn->real_escape_string($val) . "'");
    }
    $values[] = "'" . date('Y-m-d H:i:s') . "'";
    $sql = "INSERT INTO history_problems (" . implode(',', $fields) . ") VALUES (" . implode(',', $values) . ")";
    $conn->query($sql);
}

// ---------- (4) 태그 테이블, 연결 테이블 업데이트 ----------
if ($tagReset === 'on') {
    $stmtDeleteTags = $conn->prepare("DELETE FROM problem_tags WHERE problem_id = ?");
    $stmtDeleteTags->bind_param("i", $id);
    $stmtDeleteTags->execute();
    $stmtDeleteTags->close();
}
if (!empty($tagsArray)) {
    foreach ($tagsArray as $tag) {
        $stmtTag = $conn->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
        $stmtTag->bind_param("s", $tag);
        $stmtTag->execute();
        $stmtTag->close();

        $stmtTagId = $conn->prepare("SELECT id FROM tags WHERE name = ?");
        $stmtTagId->bind_param("s", $tag);
        $stmtTagId->execute();
        $stmtTagId->bind_result($tag_id);
        $stmtTagId->fetch();
        $stmtTagId->close();

        if ($tag_id) {
            $stmtProbTag = $conn->prepare("INSERT IGNORE INTO problem_tags (problem_id, tag_id) VALUES (?, ?)");
            $stmtProbTag->bind_param("ii", $id, $tag_id);
            $stmtProbTag->execute();
            $stmtProbTag->close();
        }
    }
}

// ---------- (5) UPDATE ----------
$stmt = $conn->prepare("UPDATE problems SET
    title = ?, question = ?, question_text = ?, answer = ?, answer_text = ?, solution = ?, solution_text = ?, hint = ?, hint_text = ?, video = ?,
    difficulty = ?, type = ?, category = ?, source = ?,
    created_by = ?, tags = ?, path_text = ?, path_id = ?,
    main_formula_latex = ?, main_formula_tree = ?, all_formulas_tree = ?,
    formula_keywords = ?, hash = ?, sympy_expr = ?
    WHERE id = ?");
$stmt->bind_param("ssssssssssisssississssssi",
    $title, $question, $question_text,
    $answer, $answer_text,
    $solution, $solution_text,
    $hint, $hint_text,
    $video, $difficulty, $type, $category, $source,
    $created_by, $tags, $path_text, $path_id,
    $mainFormulaLatex, $mainFormulaTree, $allFormulasTree,
    $keywords, $mainHash, $mainSympy,
    $id
);

if ($stmt->execute()) {
    // 기존 수식 오류 삭제
    $stmt2 = $conn->prepare("DELETE FROM formula_errors WHERE problem_id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();

    processFormulasForProblem($id, $question, $solution, $answer, $hint, $conn);
    echo "<script>alert('문제 수정 완료'); location.href='edit_problem.php?id={$id}';</script>";
} else {
    die("문제 수정 실패: " . $stmt->error);
}
$stmt->close();

?>

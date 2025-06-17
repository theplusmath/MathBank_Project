<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB 연결
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

// POST 데이터 받기
$id = $_POST['id'] ?? 0;
$copyMode = $_POST['copyMode'] ?? '0';

$title = $_POST['title'] ?? '';
$question = $_POST['question'] ?? '';
$answer = $_POST['answer'] ?? '';
$solution = $_POST['solution'] ?? '';
$hint = $_POST['hint'] ?? '';
$video = $_POST['video'] ?? '';
$difficulty = $_POST['difficulty'] ?? null;
$type = $_POST['type'] ?? '';
$category = $_POST['category'] ?? '';
$source = $_POST['source'] ?? '';
$created_by = isset($_POST['created_by']) && is_numeric($_POST['created_by']) ? (int)$_POST['created_by'] : 0;
$tags = $_POST['tags'] ?? '';
$mainFormulaLatex = $_POST['main_formula_latex'] ?? '';
$mainFormulaTree = $_POST['main_formula_tree'] ?? '';
$allFormulasTree = $_POST['all_formulas_tree'] ?? '';
$formulasKeywords = $_POST['formula_keywords'] ?? '';
$copied_by = 'admin';

// 복사 저장 모드
if ($copyMode === '1') {
    $newTitle = '[복사본] ' . $title;
    $created_by = 1;

    $stmt = $conn->prepare("INSERT INTO problems (
        title, question, answer, solution, hint, video,
        difficulty, type, category, source, created_by, tags,
        origin_id, copied_by,
        main_formula_latex, main_formula_tree, all_formulas_tree, formula_keywords
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "ssssssissssiisssss",
        $newTitle, $question, $answer, $solution, $hint, $video,
        $difficulty, $type, $category, $source, $created_by, $tags,
        $id, $copied_by,
        $mainFormulaLatex, $mainFormulaTree, $allFormulasTree, $formulasKeywords
    );
} else {
    // 수정 모드
    $stmt = $conn->prepare("UPDATE problems SET
        title=?, question=?, answer=?, solution=?, hint=?, video=?,
        difficulty=?, type=?, category=?, source=?, created_by=?, tags=?,
        copied_by=?, main_formula_latex=?, main_formula_tree=?, all_formulas_tree=?, formula_keywords=?
        WHERE id=?");

    $stmt->bind_param(
        "ssssssissssissssssi",
        $title, $question, $answer, $solution, $hint, $video,
        $difficulty, $type, $category, $source, $created_by, $tags,
        $copied_by, $mainFormulaLatex, $mainFormulaTree, $allFormulasTree, $formulasKeywords,
        $id
    );
}

// 실행 및 결과 처리
if ($stmt->execute()) {
    $lastId = ($copyMode === '1') ? $conn->insert_id : $id;
    echo "<script>alert('저장되었습니다.'); location.href='edit_problem.php?id={$lastId}';</script>";
} else {
    echo "<script>alert('저장 실패: {$conn->error}'); history.back();</script>";
}

// 종료
$stmt->close();
$conn->close();
?>

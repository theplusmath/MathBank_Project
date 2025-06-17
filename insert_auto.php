<?php
require_once 'functions.php';  // 여기 bindParams 함수 포함돼야 함
$conn = connectDB();

// ?? INSERT할 데이터 (변수 → 값 매핑)
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

// ?? 추가로 자동 시간 처리
$data['created_at'] = date('Y-m-d H:i:s');

// ?? 타입 문자열 자동 생성 (i = int, s = string)
function guessParamTypes($arr) {
    $types = '';
    foreach ($arr as $val) {
        $types .= is_int($val) ? 'i' : 's';
    }
    return $types;
}

// ?? SQL 생성
$columns = implode(', ', array_keys($data));
$placeholders = implode(', ', array_fill(0, count($data), '?'));
$sql = "INSERT INTO problems ($columns) VALUES ($placeholders)";
$stmt = $conn->prepare($sql);

// ?? 바인딩 실행
$types = guessParamTypes($data);
$params = array_values($data);

if (!bindParams($stmt, $types, $params)) {
    die("bind_param 실패");
}

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    echo "? INSERT 성공: ID = $newId";
} else {
    die("INSERT 실패: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>

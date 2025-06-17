<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// POST 데이터 로그 (옵션)
file_put_contents(__DIR__ . '/debug_post_log.txt', "\n\n[update_problem] POST: " . print_r($_POST, true), FILE_APPEND);

// DB 연결
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

$id = $_POST['id'] ?? 0;
$copyMode = $_POST['copyMode'] ?? '0';

$title = $_POST['title'] ?? '';
$question = $_POST['question'] ?? '';
$answer = $_POST['answer'] ?? '';
$solution = $_POST['solution'] ?? '';
$hint = $_POST['hint'] ?? '';
$video = $_POST['video'] ?? '';
$difficulty = $_POST['difficulty'] ?? '';
$type = $_POST['type'] ?? '';
$category = $_POST['category'] ?? '';
$source = $_POST['source'] ?? '';
$created_by = $_POST['created_by'] !== '' ? (int)$_POST['created_by'] : null;
$tags = $_POST['tags'] ?? '';
$path_text = $_POST['path_text'] ?? '';
$path_id = $_POST['path_id'] !== '' ? (int)$_POST['path_id'] : null;

$copied_by = 'admin';

// FastAPI 호출 함수
function get_formula_data_from_api($latex) {
    $api_url = "https://math-api-83wx.onrender.com/normalize";
    $payload = json_encode(["latex" => $latex]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

if ($copyMode === '1') {
    $newTitle = '[복사본] ' . $title;
    $created_by = 1;

    $stmt = $conn->prepare("INSERT INTO problems (title, question, answer, solution, hint, video, difficulty, type, category, source, created_by, tags, path_text, path_id, copied_by, origin_id, main_formula_latex, main_formula_tree, all_formulas_tree, formulas_keywords) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssssissssissss", $newTitle, $question, $answer, $solution, $hint, $video, $difficulty, $type, $category, $source, $created_by, $tags, $path_text, $path_id, $copied_by, $id, $mainFormulaLatex, $mainFormulaTree, $allFormulasTree, $formulasKeywords);
} else {
    $stmt = $conn->prepare("UPDATE problems SET title=?, question=?, answer=?, solution=?, hint=?, video=?, difficulty=?, type=?, category=?, source=?, created_by=?, tags=?, path_text=?, path_id=?, copied_by=?, main_formula_latex=?, main_formula_tree=?, all_formulas_tree=?, formulas_keywords=? WHERE id=?");

$stmt->bind_param("ssssssssssisssssssssi",
    $title,
    $question,
    $answer,
    $solution,
    $hint,
    $video,
    $difficulty,
    $type,
    $category,
    $source,
    $created_by,
    $tags,
    $path_text,
    $path_id,
    $copied_by,
    $mainFormulaLatex,
    $mainFormulaTree,
    $allFormulasTree,
    $formulasKeywords,
    $id
);
}

if (!$stmt->execute()) {
    echo "<script>alert('저장 실패: {$conn->error}'); history.back();</script>";
    exit;
}

if ($copyMode === '1') {
    $id = $conn->insert_id; // 복사본 새 ID로 갱신
}
$stmt->close();

// ✅ formula_index 갱신 시작
$conn->query("DELETE FROM formula_index WHERE problem_id = $id");

preg_match_all('/\\\\\((.*?)\\\\\)|\\\\\[(.*?)\\\\\]|\$\$(.*?)\$\$|\$(.*?)\$/s', $question, $matches);
$formulas = array_filter(array_merge($matches[1], $matches[2], $matches[3], $matches[4]));

if (empty($formulas)) {
    // 수식이 전혀 없을 경우, 수식 관련 필드를 빈 값으로 초기화
    $stmt = $conn->prepare("UPDATE problems SET main_formula_latex = '', main_formula_tree = '', all_formulas_tree = '', formulas_keywords = '' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $conn->close();
    echo "<script>alert('저장되었습니다. (수식 없음)'); location.href='list_problems.html';</script>";
    exit;
}



$analyzedResults = []; // ✅ 수식 전체 결과 저장용
$allKeywords = [];

foreach ($formulas as $formula) {
    $original = trim($formula);
    $data = get_formula_data_from_api($original);

    $hash = $data['hash'] ?? sha1($original);
    $tree = $data['tree'] ?? '';
    $keywords = $data['keywords'] ?? [];

    // ✅ formula_index 저장
    $stmt = $conn->prepare("INSERT INTO formula_index (problem_id, original_formula, formula_skeleton, formula_skeleton_hash, main_formula_tree, formula_keywords, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("isssss", $id, $original, $original, $hash, $tree, implode(',', $keywords));
    $stmt->execute();
    $stmt->close();

    // ✅ 결과 누적
    $analyzedResults[] = [
        'latex' => $original,
        'tree' => $tree,
        'hash' => $hash,
        'keywords' => $keywords
    ];

    $allKeywords = array_merge($allKeywords, $keywords);
}

// ✅ 2단계: problems 테이블의 수식 관련 필드 업데이트

// 대표 수식 선택 (길이 기준, 상위 3개까지)
usort($analyzedResults, function($a, $b) {
    return mb_strlen($b['latex']) - mb_strlen($a['latex']);
});

$mainFormulas = array_column(array_slice($analyzedResults, 0, 3), 'latex');
$mainFormulaLatex = implode(', ', $mainFormulas);

// ✅ 너무 길면 잘라 저장 (500자 제한)
if (mb_strlen($mainFormulaLatex) > 500) {
    $mainFormulaLatex = mb_substr($mainFormulaLatex, 0, 500) . '...';
}



// 가장 긴 수식의 tree 선택
$mainFormulaTree = '';
if (!empty($analyzedResults)) {
    $mainFormulaTree = json_encode($analyzedResults[0]['tree'], JSON_UNESCAPED_UNICODE);
}

// 전체 트리 정보 JSON으로
$allFormulasTree = json_encode(array_map(function($item) {
    return [
        'latex' => $item['latex'],
        'tree' => $item['tree'],
        'hash' => $item['hash']
    ];
}, $analyzedResults), JSON_UNESCAPED_UNICODE);

// 키워드 중복 제거 후 CSV
$uniqueKeywords = array_values(array_unique($allKeywords));
sort($uniqueKeywords);
$formulasKeywords = implode(',', $uniqueKeywords);

// DB에 업데이트
$stmt = $conn->prepare("UPDATE problems SET main_formula_latex = ?, main_formula_tree = ?, all_formulas_tree = ?, formulas_keywords = ? WHERE id = ?");
$stmt->bind_param("ssssi", $mainFormulaLatex, $mainFormulaTree, $allFormulasTree, $formulasKeywords, $id);
$stmt->execute();
$stmt->close();



$conn->close();
echo "<script>alert('저장되었습니다.'); location.href='list_problems.html';</script>";
?>

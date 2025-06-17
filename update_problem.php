<?php
// 에러 출력 및 로그 기록
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);

// JSON 응답 헤더 (AJAX 등에서 활용)
header('Content-Type: application/json; charset=utf-8');
file_put_contents(__DIR__ . '/php-error.log', date('c') . " ✅ update_problem.php 진입\n", FILE_APPEND);

require_once 'functions.php';
file_put_contents(__DIR__ . '/php-error.log', date('c') . " ✅ functions.php 포함 완료\n", FILE_APPEND);

require_once 'mathpix_analyzer.php';
file_put_contents(__DIR__ . '/php-error.log', date('c') . " ✅ mathpix_analyzer.php 포함 완료\n", FILE_APPEND);

$conn = connectDB();
file_put_contents(__DIR__ . '/php-error.log', date('c') . " ✅ DB 연결 완료\n", FILE_APPEND);

// POST 데이터 수집
$id         = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$copyMode   = isset($_POST['copyMode']) ? $_POST['copyMode'] : '0';
$title      = isset($_POST['title']) ? $_POST['title'] : '';
$question   = isset($_POST['question']) ? $_POST['question'] : '';
$answer     = isset($_POST['answer']) ? $_POST['answer'] : '';
$solution   = isset($_POST['solution']) ? $_POST['solution'] : '';
$hint       = isset($_POST['hint']) ? $_POST['hint'] : '';
$video      = isset($_POST['video']) ? $_POST['video'] : '';
$difficulty = (isset($_POST['difficulty']) && is_numeric($_POST['difficulty'])) ? (int)$_POST['difficulty'] : null;
$type       = isset($_POST['type']) ? $_POST['type'] : '';
$category   = isset($_POST['category']) ? $_POST['category'] : '';
$source     = isset($_POST['source']) ? $_POST['source'] : '';
$created_by = (isset($_POST['created_by']) && $_POST['created_by'] !== '') ? (int)$_POST['created_by'] : null;
$tags       = isset($_POST['tags']) ? $_POST['tags'] : '';
$path_text  = isset($_POST['path_text']) ? $_POST['path_text'] : '';
$path_id    = (isset($_POST['path_id']) && is_numeric($_POST['path_id'])) ? (int)$_POST['path_id'] : null;
$return_url  = isset($_POST['return_url']) ? $_POST['return_url'] : '';

$tagsArray = parseTags($tags);

// 텍스트 파생 컬럼 생성
$question_text       = extractQuestionText($question);
$question_text_only  = extractQuestionTextOnly($question);

$answer_text   = extractCleanText($answer);
$solution_text = extractCleanText($solution);
$hint_text     = extractCleanText($hint);

// 경로 보정
//resolvePathConflict($conn, $path_id, $path_text);

// 경로 ID 자동 보정
if ((!$path_id || $path_id === 0) && $path_text) {
    $path_id = getPathIdFromText($conn, $path_text);
    if (!$path_id) {
        $stmt = $conn->prepare("SELECT id FROM paths WHERE name LIKE CONCAT('%', ?, '%')");
        $stmt->bind_param("s", $path_text);
        $stmt->execute();
        $stmt->bind_result($matched_id);
        $matches = array();
        while ($stmt->fetch()) $matches[] = $matched_id;
        $stmt->close();
        if (count($matches) === 1) {
            $path_id = $matches[0];
        } else {
            echo json_encode(array('success' => false, 'message' => "경로 인식 실패 또는 중복됨: $path_text"));
            exit;
        }
    }
}

// 수식 분석 (반드시 question에서!)
$analyzed = analyzeFormulasFromQuestion($question);
file_put_contents(__DIR__.'/debug_post_log.txt', "[analyzeFormulasFromQuestion 결과] " . print_r($analyzed, true) . "\n", FILE_APPEND);

$mainFormulaLatex = '';
$mainFormulaTree = '';
$allFormulasTree = '';
$keywords = '';
$mainHash = '';
$mainSympy = '';

if (!empty($analyzed)) {
    usort($analyzed, function($a, $b) { return mb_strlen($b['latex']) - mb_strlen($a['latex']); });
    $mainLatexArr = array();
    foreach (array_slice($analyzed, 0, 3) as $item) {
        $mainLatexArr[] = $item['latex'];
    }
    $mainFormulaLatex = implode(', ', $mainLatexArr);
    $mainHash         = isset($analyzed[0]['hash']) ? $analyzed[0]['hash'] : '';
    $mainFormulaTree  = json_encode(isset($analyzed[0]['tree']) ? $analyzed[0]['tree'] : array(), JSON_UNESCAPED_UNICODE);
    $mainSympy        = isset($analyzed[0]['sympy_expr']) ? $analyzed[0]['sympy_expr'] : '';
    $allFormulasTree  = json_encode(array_map(function($f) {
        return array('latex' => $f['latex'], 'tree' => $f['tree'], 'hash' => $f['hash']);
    }, $analyzed), JSON_UNESCAPED_UNICODE);

    // 키워드 합치기
    $keywordArrays = array();
    foreach ($analyzed as $item) {
        $keywordArrays[] = isset($item['keywords']) ? $item['keywords'] : array();
    }
    $keywords = implode(',', array_unique(call_user_func_array('array_merge', $keywordArrays)));
}

// 이력(history) 백업
$backup = $conn->query("SELECT * FROM problems WHERE id = $id");
if ($backup && $backup->num_rows > 0) {
    $old = $backup->fetch_assoc();
    if (empty($old['path_id']) || !is_numeric($old['path_id'])) $old['path_id'] = 105;
    if (empty($old['path_text'])) $old['path_text'] = '더플러스수학';
    $fields = array_merge(array('problem_id'), array_keys($old), array('updated_at'));
    $fields = array_filter($fields, function($f) { return $f !== 'id'; }); // id는 제외
    $values = array((int)$old['id']);
    foreach ($old as $key => $val) {
        if ($key === 'id') continue;
        if (in_array($key, array('path_id', 'created_by', 'copied_by', 'origin_id', 'difficulty')) && ($val === '' || is_null($val))) {
            $values[] = "NULL";
        } else {
            $values[] = "'" . $conn->real_escape_string($val === null ? '' : $val) . "'";
        }
    }
    $values[] = "'" . date('Y-m-d H:i:s') . "'";
    $sql = "INSERT INTO history_problems (" . implode(',', $fields) . ") VALUES (" . implode(',', $values) . ")";
    $conn->query($sql);
}

// 실제 문제 업데이트 실행
$sql = "UPDATE problems SET
    title = ?, question = ?, question_text = ?, question_text_only = ?,
    answer = ?, answer_text = ?, solution = ?, solution_text = ?,
    hint = ?, hint_text = ?, video = ?, difficulty = ?, type = ?, category = ?, source = ?,
    created_by = ?, tags = ?, path_text = ?, path_id = ?,
    main_formula_latex = ?, main_formula_tree = ?, all_formulas_tree = ?,
    formula_keywords = ?, hash = ?, sympy_expr = ?, updated_at = NOW()
    WHERE id = ?";

$params = array(
    $title, $question, $question_text, $question_text_only,
    $answer, $answer_text, $solution, $solution_text,
    $hint, $hint_text, $video, $difficulty, $type, $category, $source,
    $created_by, $tags, $path_text, $path_id,
    $mainFormulaLatex, $mainFormulaTree, $allFormulasTree,
    $keywords, $mainHash, $mainSympy, $id
);

$types = guessParamTypes($params);
bindParams($stmt = $conn->prepare($sql), $types, $params);


$success = $stmt->execute();


if ($success) {
    file_put_contents(__DIR__ . '/php-error.log', date('c') . " ✅ UPDATE 성공: 문제ID = $id\n", FILE_APPEND);
    // 태그 연동 처리 등... (생략)

    // 성공 후 이동
    if ($return_url && filter_var($return_url, FILTER_VALIDATE_URL)) {
        header('Location: ' . $return_url);
        exit;
    } elseif ($return_url) {
        // 상대경로인 경우 (예: list_problems.html)
        header('Location: ' . $return_url);
        exit;
    } else {
        // 기본 이동 (목록)
        header('Location: list_problems.html');
        exit;
    }
} else {
    // 실패 시 처리(기존처럼)
    echo "<script>alert('문제 수정 실패: " . addslashes($stmt->error) . "'); history.back();</script>";
    exit;
}

$stmt->close();
$conn->close();
?>

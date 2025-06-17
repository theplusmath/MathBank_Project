<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 🔍 디버깅용 POST 데이터 로그 출력 (웹화면)
/*echo "<pre>";
print_r($_POST);
echo "</pre>";*/

// ✅ JSON 응답 한글 깨짐 방지
header('Content-Type: application/json; charset=utf-8');

// 📌 수식 정규화 함수
function normalize_formula($formula) {
    $formula = trim($formula);
    $formula = preg_replace('/[a-zA-Z]+/', 'VAR', $formula);
    $formula = preg_replace('/\d+/', 'NUM', $formula);
    return $formula;
}

// 🔗 DB 연결
$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

// 📥 POST 데이터 수신
$title      = $_POST['title']      ?? '';
$path_text  = $_POST['path_text']  ?? '';
$question   = $_POST['question']   ?? '';
$solution   = $_POST['solution']   ?? '';
$answer     = $_POST['answer']     ?? '';
$difficulty = $_POST['difficulty'] ?? '';
$type       = $_POST['type']       ?? '';
$category   = $_POST['category']   ?? '';
$hint       = $_POST['hint']       ?? '';
$video      = $_POST['video']      ?? '';
$source     = $_POST['source']     ?? '미지정';
$created_by = $_POST['created_by'] ?? null;

// 필수 항목 체크
if (!$title || !$question) {
    echo json_encode(['success' => false, 'message' => '제목과 문제 내용은 필수입니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ✅ path_id 조회 또는 추가
$path_id = null;
if ($path_text) {
    $stmt = $conn->prepare("SELECT id FROM path WHERE name = ? LIMIT 1");
    $stmt->bind_param("s", $path_text);
    $stmt->execute();
    $stmt->bind_result($fetched_path_id);
    if ($stmt->fetch()) {
        $path_id = $fetched_path_id;
    }
    $stmt->close();

    if (!$path_id) {
        $stmt = $conn->prepare("INSERT INTO path (name, parent_id, depth, created_at) VALUES (?, NULL, 1, NOW())");
        $stmt->bind_param("s", $path_text);
        if ($stmt->execute()) {
            $path_id = $stmt->insert_id;
        }
        $stmt->close();
    }
}

// ✅ 문제 INSERT
$stmt = $conn->prepare(
    "INSERT INTO problems 
    (title, path_text, path_id, question, solution, answer, difficulty, type, category, hint, video, source, created_by, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'SQL 준비 실패: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param(
    "ssisisssssssi", 
    $title, $path_text, $path_id, $question, $solution, $answer, $difficulty, $type, $category, $hint, $video, $source, $created_by
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => '문제 저장 실패: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$problem_id = $stmt->insert_id;

// ✅ 수식 추출 및 인덱싱 ( \( \), \[ \], $$ $$, $ $ 모두 처리)
preg_match_all('/\\\\\((.*?)\\\\\)|\\\\\[(.*?)\\\\\]|\$\$(.*?)\$\$|\$(.*?)\$/s', $question, $matches);
$formulas = array_filter(array_merge($matches[1], $matches[2], $matches[3], $matches[4]));

foreach ($formulas as $formula) {
    $original = trim($formula);
    $skeleton = normalize_formula($original);
    $hash = sha1($skeleton);

    // 중복 방지
    $check = $conn->prepare("SELECT COUNT(*) FROM formula_index WHERE problem_id = ? AND original_formula = ?");
    $check->bind_param("is", $problem_id, $original);
    $check->execute();
    $check->bind_result($count);
    $check->fetch();
    $check->close();

    if ($count > 0) continue;

    // 저장
    $insert = $conn->prepare("INSERT INTO formula_index 
        (problem_id, original_formula, formula_skeleton, formula_skeleton_hash, created_at) 
        VALUES (?, ?, ?, ?, NOW())");
    $insert->bind_param("isss", $problem_id, $original, $skeleton, $hash);
    $insert->execute();
    $insert->close();
}

echo json_encode(['success' => true, 'message' => '문제가 성공적으로 저장되었습니다.'], JSON_UNESCAPED_UNICODE);

// 닫기
$stmt->close();
$conn->close();
?>

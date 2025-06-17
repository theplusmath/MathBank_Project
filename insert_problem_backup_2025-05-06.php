<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// DB 연결
$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

// POST 데이터 수신
$title = $_POST['title'] ?? '';
$path_text = $_POST['path_text'] ?? '';
$question = $_POST['question'] ?? '';
$solution = $_POST['solution'] ?? '';
$answer = $_POST['answer'] ?? '';
$difficulty = $_POST['difficulty'] ?? '';
$type = $_POST['type'] ?? '';
$category = $_POST['category'] ?? '';
$hint = $_POST['hint'] ?? '';
$video = $_POST['video'] ?? '';
$source = $_POST['source'] ?? '';
$created_by = $_POST['created_by'] ?? null;  // 선생님 ID
if (!$source) $source = '미지정';

if (!$title || !$question) {
    echo json_encode(['success' => false, 'message' => '제목과 문제 내용은 필수입니다.']);
    exit;
}

// path_id 조회 또는 없으면 추가
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

// 문제 INSERT
$stmt = $conn->prepare(
    "INSERT INTO problems 
    (title, path_text, path_id, question, solution, answer, difficulty, type, category, hint, video, source, created_by, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'SQL 준비 실패: ' . $conn->error]);
    exit;
}

$stmt->bind_param(
    "ssisisssssssi", 
    $title, $path_text, $path_id, $question, $solution, $answer, $difficulty, $type, $category, $hint, $video, $source, $created_by
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => '문제 저장 실패: ' . $stmt->error]);
} else {
    echo json_encode(['success' => true, 'message' => '문제가 성공적으로 저장되었습니다.']);
}

$stmt->close();
$conn->close();
?>

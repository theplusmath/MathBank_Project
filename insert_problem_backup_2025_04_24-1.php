<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

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

// 필수 유효성 검사
if (!$title || !$question) {
  echo json_encode(['success' => false, 'message' => '제목과 문제 내용은 필수입니다.']);
  exit;
}

// path_id 조회
$path_id = null;
$stmt = $conn->prepare("SELECT id FROM path WHERE path_text = ? LIMIT 1");
$stmt->bind_param("s", $path_text);
$stmt->execute();
$stmt->bind_result($fetched_path_id);
if ($stmt->fetch()) {
  $path_id = $fetched_path_id;
}
$stmt->close();

// INSERT 실행
$stmt = $conn->prepare("INSERT INTO problems (title, path, path_id, question, solution, answer, difficulty, type, category, hint, video, source, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'SQL 준비 실패: ' . $conn->error]);
  exit;
}

$stmt->bind_param("ssisssssssss", $title, $path_text, $path_id, $question, $solution, $answer, $difficulty, $type, $category, $hint, $video, $source);

if (!$stmt->execute()) {
  echo json_encode(['success' => false, 'message' => '실행 실패: ' . $stmt->error]);
} else {
  echo json_encode(['success' => true, 'message' => '문제가 성공적으로 저장되었습니다.']);
}

$stmt->close();
$conn->close();
?>

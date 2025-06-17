<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// MySQL 연결
$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

// POST 데이터 받기
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

// 유효성 검사
if (!$title || !$question) {
  echo json_encode(['success' => false, 'message' => '제목과 문제 내용은 필수입니다.']);
  exit;
}

// INSERT 실행
$stmt = $conn->prepare("INSERT INTO problems (title, path, question, solution, answer, difficulty, type, category, hint, video, source, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'SQL 준비 실패: ' . $conn->error]);
  exit;
}

$stmt->bind_param("sssssssssss", $title, $path_text, $question, $solution, $answer, $difficulty, $type, $category, $hint, $video, $source);

$success = $stmt->execute();

if (!$success) {
  echo json_encode(['success' => false, 'message' => '실행 실패: ' . $stmt->error]);
} else {
  echo json_encode(['success' => true]);
}

$stmt->close();
$conn->close();

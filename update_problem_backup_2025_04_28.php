<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
$difficulty = $_POST['difficulty'] ?? null;
$type = $_POST['type'] ?? '';
$category = $_POST['category'] ?? '';
$source = $_POST['source'] ?? '';
$created_by = $_POST['created_by'] ?? null;
$tags = $_POST['tags'] ?? '';   // ✅ 추가된 부분

if ($copyMode === '1') {
    // 복사 저장
    $stmt = $conn->prepare("INSERT INTO problems (title, question, answer, solution, hint, video, difficulty, type, category, source, created_by, tags, origin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssissssii", $title, $question, $answer, $solution, $hint, $video, $difficulty, $type, $category, $source, $created_by, $tags, $id);
} else {
    // 기존 문제 수정
    $stmt = $conn->prepare("UPDATE problems SET title=?, question=?, answer=?, solution=?, hint=?, video=?, difficulty=?, type=?, category=?, source=?, created_by=?, tags=? WHERE id=?");
    $stmt->bind_param("ssssssisssssi", $title, $question, $answer, $solution, $hint, $video, $difficulty, $type, $category, $source, $created_by, $tags, $id);
}

if ($stmt->execute()) {
    echo "<script>alert('저장되었습니다.'); location.href='list_problems.html';</script>";
} else {
    echo "<script>alert('저장 실패: {$conn->error}'); history.back();</script>";
}

$conn->close();
?>

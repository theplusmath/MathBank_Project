<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB 연결
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

// POST 데이터 수신
$id = $_POST['id'] ?? 0;
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
$created_by = $_POST['created_by'] ?? null;

if (!$id || !$title || !$question) {
    echo "<script>alert('필수 항목이 누락되었습니다.'); history.back();</script>";
    exit;
}

// 수정 쿼리 실행
$stmt = $conn->prepare("
    UPDATE problems 
    SET 
        title = ?, 
        question = ?, 
        answer = ?, 
        solution = ?, 
        hint = ?, 
        video = ?, 
        difficulty = ?, 
        type = ?, 
        category = ?, 
        source = ?, 
        created_by = ?, 
        updated_at = NOW()
    WHERE id = ?
");

$stmt->bind_param(
    "ssssssisssii", 
    $title, $question, $answer, $solution, $hint, $video, 
    $difficulty, $type, $category, $source, $created_by, $id
);

if ($stmt->execute()) {
    echo "<script>alert('문제가 성공적으로 수정되었습니다.'); location.href='list_problems.html';</script>";
} else {
    echo "<script>alert('수정 실패: " . $stmt->error . "'); history.back();</script>";
}

$stmt->close();
$conn->close();
?>

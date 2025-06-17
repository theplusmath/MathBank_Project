<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// POST 데이터 디버깅 로그
file_put_contents(__DIR__ . '/debug_post_log.txt', "📝 POST 데이터:\n" . print_r($_POST, true), FILE_APPEND);

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
$difficulty = $_POST['difficulty'] ?? '';
$type = $_POST['type'] ?? '';
$category = $_POST['category'] ?? '';
$source = $_POST['source'] ?? '';
$created_by = $_POST['created_by'] !== '' ? (int)$_POST['created_by'] : null;
$tags = $_POST['tags'] ?? '';
$path_text = $_POST['path_text'] ?? '';
$path_id = $_POST['path_id'] !== '' ? (int)$_POST['path_id'] : null;

$copied_by = 'admin';

if ($copyMode === '1') {
    $newTitle = '[복사본] ' . $title;
    $created_by = 1;

    $stmt = $conn->prepare("
        INSERT INTO problems (
            title, question, answer, solution, hint, video,
            difficulty, type, category, source,
            created_by, tags, path_text, path_id, copied_by, origin_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssssssssissssi",
        $newTitle, $question, $answer, $solution, $hint,
        $video, $difficulty, $type, $category, $source,
        $created_by, $tags, $path_text, $path_id, $copied_by, $id
    );

} else {
    // 수정 모드
    $stmt = $conn->prepare("
        UPDATE problems SET
            title=?, question=?, answer=?, solution=?, hint=?, video=?,
            difficulty=?, type=?, category=?, source=?,
            created_by=?, tags=?, path_text=?, path_id=?, copied_by=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "ssssssssssissssi",
        $title, $question, $answer, $solution, $hint,
        $video, $difficulty, $type, $category, $source,
        $created_by, $tags, $path_text, $path_id, $copied_by, $id
    );
}

// 실행
if ($stmt->execute()) {
    echo "<script>alert('저장되었습니다.'); location.href='list_problems.html';</script>";
} else {
    echo "<script>alert('저장 실패: {$conn->error}'); history.back();</script>";
}

$stmt->close();
$conn->close();
?>

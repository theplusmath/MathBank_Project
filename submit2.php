<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'] ?? "";
    $path = $_POST['path'] ?? "";
    $category = $_POST['category'] ?? "";
    $question = $_POST['question'] ?? "";
    $answer = $_POST['answer'] ?? "";
    $solution = $_POST['solution'] ?? "";
    $difficulty = $_POST['difficulty'] ?? "";
    $video = $_POST['video'] ?? "";
    $hint = $_POST['hint'] ?? "";
    $type = $_POST['type'] ?? "";

    $conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
    if ($conn->connect_error) {
        die("MySQL 연결 실패: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // 제목 또는 문제 중복 검사
    $stmt = $conn->prepare("SELECT COUNT(*) FROM problems WHERE title = ? OR question = ?");
    $stmt->bind_param("ss", $title, $question);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('같은 제목 또는 같은 문제가 이미 존재합니다.'); history.back();</script>";
        $conn->close();
        exit;
    }

    // 문제 저장
    $stmt = $conn->prepare("INSERT INTO problems (title, path, category, type, question, answer, solution, difficulty, video, hint)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $title, $path, $category, $type, $question, $answer, $solution, $difficulty, $video, $hint);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header("Location: index.php");
    exit;
}
?>

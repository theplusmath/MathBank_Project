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
    $source = $_POST['source'] ?? "";

    $conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
    if ($conn->connect_error) {
        die("MySQL 연결 실패: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

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

    $stmt = $conn->prepare("INSERT INTO problems (title, path, category, type, question, answer, solution, difficulty, video, hint, source)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssss", $title, $path, $category, $type, $question, $answer, $solution, $difficulty, $video, $hint, $source);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>문제 입력</title>
</head>
<body>
  <h2>문제 입력 폼</h2>
  <form method="POST" action="submit.php">
    <label>제목: <input type="text" name="title" required></label><br>
    <label>경로: <input type="text" name="path"></label><br>
    <label>평가항목: <input type="text" name="category"></label><br>
    <label>유형:
      <select name="type">
        <option value="">선택</option>
        <option value="선다형">선다형</option>
        <option value="단답형">단답형</option>
        <option value="서술형">서술형</option>
      </select>
    </label><br>
    <label>문제:<br><textarea name="question" rows="5" cols="80"></textarea></label><br>
    <label>정답:<br><textarea name="answer" rows="2" cols="80"></textarea></label><br>
    <label>풀이:<br><textarea name="solution" rows="5" cols="80"></textarea></label><br>
    <label>난이도:
      <select name="difficulty">
        <option value="">선택</option>
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
        <option value="4">4</option>
        <option value="5">5</option>
      </select>
    </label><br>
    <label>출처:
      <select name="source">
        <option value="">출처 선택</option>
        <option value="문제집">문제집</option>
        <option value="일반고내신">일반고내신</option>
        <option value="과고내신">과고내신</option>
        <option value="자사고내신">자사고내신</option>
        <option value="수능, 모의고사기출">수능, 모의고사기출</option>
        <option value="심층면접, 수리논술기출">심층면접, 수리논술기출</option>
        <option value="본고사기출">본고사기출</option>
        <option value="중등기출">중등기출</option>
      </select>
    </label><br>
    <label>동영상 링크: <input type="text" name="video"></label><br>
    <label>힌트:<br><textarea name="hint" rows="3" cols="80"></textarea></label><br>
    <button type="submit">저장</button>
  </form>
</body>
</html>

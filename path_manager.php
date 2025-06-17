<?php
// DB 연결
$mysqli = new mysqli("localhost", "DB_아이디", "DB_비밀번호", "DB_이름");
$mysqli->set_charset("utf8");

// 추가 요청 처리
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST["name"];
    $parent_id = $_POST["parent_id"];
    $depth = $_POST["depth"];

    $stmt = $mysqli->prepare("INSERT INTO path (parent_id, name, depth) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $parent_id, $name, $depth);
    $stmt->execute();
    $stmt->close();
    header("Location: path_manager.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>경로 관리자</title>
</head>
<body>
  <h2>경로 추가</h2>
  <form method="POST" action="path_manager.php">
    이름: <input type="text" name="name" required>
    상위 ID: <input type="number" name="parent_id" value="0" required>
    단계 (0~5): <input type="number" name="depth" min="0" max="5" required>
    <button type="submit">추가</button>
  </form>

  <h2>경로 목록</h2>
  <ul>
    <?php
    $result = $mysqli->query("SELECT * FROM path ORDER BY depth, parent_id, id");
    while ($row = $result->fetch_assoc()) {
        echo "<li>[" . $row['id'] . "] " . str_repeat("— ", $row['depth']) . $row['name'] . " (상위: " . $row['parent_id'] . ")</li>";
    }
    ?>
  </ul>
</body>
</html>

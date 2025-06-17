<?php
// DB에 저장
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');
$id = $_POST['id'] ?? 0;
$question = $_POST['question'] ?? '';
$stmt = $conn->prepare("UPDATE problems SET question=? WHERE id=?");
$stmt->bind_param("si", $question, $id);
$stmt->execute();
$stmt->close();
$conn->close();
header("Location: edit_formula.php?id=".$id); // 저장 후 다시 돌아가기

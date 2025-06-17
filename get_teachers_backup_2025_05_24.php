<?php
$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

// ✅ users 테이블에서 teacher만 가져오기
$sql = "SELECT id, name FROM users WHERE role = 'teacher' AND status = 'active' ORDER BY name ASC";
$result = $conn->query($sql);

$teachers = [];
while ($row = $result->fetch_assoc()) {
    $teachers[] = $row;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($teachers);
$conn->close();
?>

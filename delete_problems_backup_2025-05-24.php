<?php
header('Content-Type: application/json');
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset("utf8mb4");

$result = $conn->query("SELECT * FROM deleted_problems ORDER BY id DESC");

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(['success' => true, 'data' => $data]);
?>

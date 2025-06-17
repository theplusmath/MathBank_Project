<?php
header('Content-Type: application/json; charset=UTF-8');

$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

$parent_id = $_GET['parent_id'] ?? null;

if ($parent_id === '' || $parent_id === null) {
    $stmt = $conn->prepare("SELECT id, name FROM paths WHERE parent_id IS NULL ORDER BY sort_order, id");
} else {
    $stmt = $conn->prepare("SELECT id, name FROM paths WHERE parent_id = ? ORDER BY sort_order, id");
    $stmt->bind_param("i", $parent_id);
}

$stmt->execute();
$result = $stmt->get_result();

$paths = [];
while ($row = $result->fetch_assoc()) {
    $paths[] = $row;
}

echo json_encode($paths);

$stmt->close();
$conn->close();

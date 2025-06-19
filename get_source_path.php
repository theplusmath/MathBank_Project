<?php
// get_source_path.php

require_once '/var/www/config/db_connect.php'; // ← 실제 위치로 고쳐야 함!

$parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : null;

if ($parent_id === null) {
    $stmt = $conn->prepare("SELECT * FROM source_path WHERE parent_id IS NULL ORDER BY sort_order, name");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("SELECT * FROM source_path WHERE parent_id = ? ORDER BY sort_order, name");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
}
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = ['id' => $row['id'], 'name' => $row['name']];
}
echo json_encode($data);
?>

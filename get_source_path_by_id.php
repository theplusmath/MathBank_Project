<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '/var/www/config/db_connect.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { echo json_encode([]); exit; }

$arr = [];
while ($id) {
    $stmt = $conn->prepare("SELECT id, parent_id FROM source_path WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) break;
    array_unshift($arr, (int)$row['id']);
    $id = $row['parent_id'];
}
echo json_encode($arr, JSON_UNESCAPED_UNICODE);

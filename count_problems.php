<?php
require_once 'db.php';

$sql = "SELECT path, COUNT(*) as count FROM problems GROUP BY path";
$result = $conn->query($sql);

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[$row['path']] = intval($row['count']);
}

echo json_encode($data);
?>

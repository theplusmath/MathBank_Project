<?php
require_once('/var/www/config/db_connect.php');
header('Content-Type: application/json; charset=utf-8');

$id = intval($_GET['id'] ?? 0);

function getPathHierarchy($conn, $id) {
    $result = [];
    while ($id) {
        $stmt = $conn->prepare("SELECT id, parent_id, name FROM paths WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($cid, $pid, $name);
        if ($stmt->fetch()) {
            array_unshift($result, ['id' => $cid, 'name' => $name]);
            $id = $pid;
        } else {
            break;
        }
        $stmt->close();
    }
    return $result;
}

echo json_encode(getPathHierarchy($conn, $id), JSON_UNESCAPED_UNICODE);

<?php
require_once 'db.php';

try {
    $stmt = $pdo->query("SELECT * FROM paths ORDER BY depth ASC, parent_id ASC, name ASC");
    $paths = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $paths
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

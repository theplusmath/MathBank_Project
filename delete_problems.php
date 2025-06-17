<?php
require_once 'functions.php';

header('Content-Type: application/json');
$conn = connectDB();

// ID �ޱ�
$id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => '? ��ȿ���� ���� ���� ID']);
    exit;
}

// 1?? ���� ���� ��������
$stmt = $conn->prepare("SELECT * FROM problems WHERE id = ?");
bindParams($stmt, 'i', [$id]);
$stmt->execute();
$result = $stmt->get_result();
$problem = $result->fetch_assoc();
$stmt->close();

if (!$problem) {
    echo json_encode(['success' => false, 'message' => '? �ش� ID�� ������ ã�� �� �����ϴ�']);
    exit;
}

// 2?? ��� ���̺� INSERT
$columns = array_keys($problem);
$placeholders = implode(', ', array_fill(0, count($columns), '?'));
$colString = implode(', ', $columns);
$sqlInsert = "INSERT INTO deleted_problems ($colString) VALUES ($placeholders)";
$stmtInsert = $conn->prepare($sqlInsert);
$types = guessParamTypes($problem);
$params = array_values($problem);

if (!bindParams($stmtInsert, $types, $params) || !$stmtInsert->execute()) {
    echo json_encode(['success' => false, 'message' => '?? ���� �� ��� ����: ' . $stmtInsert->error]);
    exit;
}
$stmtInsert->close();

// 3?? ���� ���� ����
$stmtDelete = $conn->prepare("DELETE FROM problems WHERE id = ?");
bindParams($stmtDelete, 'i', [$id]);

if (!$stmtDelete->execute()) {
    echo json_encode(['success' => false, 'message' => '?? ���� ���� ����: ' . $stmtDelete->error]);
    exit;
}
$stmtDelete->close();

echo json_encode(['success' => true, 'message' => "? ���� ID {$id} ���� �Ϸ� �� �����"]);

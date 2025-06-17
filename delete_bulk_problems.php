<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB ���� ����: ' . $conn->connect_error]);
    exit;
}

// �Է� ������ �ޱ�
$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['ids'] ?? [];

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => '������ ������ ���õ��� �ʾҽ��ϴ�.']);
    exit;
}

$deletedCount = 0;
$skippedIds = [];

foreach ($ids as $id) {
    $id = intval($id);
    
    // 1. ������ ���� ��ȸ
    $result = $conn->query("SELECT * FROM problems WHERE id = $id");
    if (!$result || $result->num_rows === 0) {
        $skippedIds[] = $id;
        continue;
    }

    $row = $result->fetch_assoc();
    $row['deleted_at'] = date('Y-m-d H:i:s');

    // 2. �ʵ� �� �� ����
    $fields = array_keys($row);
    $columns = implode(", ", $fields);
    $values = implode(", ", array_map(function ($v) use ($conn) {
        if (is_null($v)) return "NULL";
        return "'" . $conn->real_escape_string($v) . "'";
    }, array_values($row)));

    // 3. ��� ���̺�� ����
    $insertSQL = "INSERT INTO deleted_problems ($columns) VALUES ($values)";
    if (!$conn->query($insertSQL)) {
        $skippedIds[] = $id;
        continue;
    }

    // 4. ���� ���� ����
    $deleteSQL = "DELETE FROM problems WHERE id = $id";
    if (!$conn->query($deleteSQL)) {
        $skippedIds[] = $id;
        continue;
    }

    $deletedCount++;
}

// ? ��� ���
echo json_encode([
    'success' => true,
    'deleted' => $deletedCount,
    'skipped' => $skippedIds
]);
?>

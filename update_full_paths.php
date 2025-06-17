<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1?? DB ����
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

// 2?? full_path ���� �Լ�
function buildFullPath(mysqli $conn, int $id): string {
    $names = [];
    while ($id) {
        $res = $conn->query("SELECT name, parent_id FROM paths WHERE id = $id LIMIT 1");
        if ($row = $res->fetch_assoc()) {
            array_unshift($names, $row['name']); // ���ʿ� ����
            $id = (int)$row['parent_id'];
        } else {
            break;
        }
    }
    return implode('/', $names);
}

// 3?? ��ü paths ���̺� ��� ����
$result = $conn->query("SELECT id FROM paths");
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $fullPath = buildFullPath($conn, $id);
    $escapedPath = $conn->real_escape_string($fullPath);
    $conn->query("UPDATE paths SET full_path = '$escapedPath' WHERE id = $id");
}

echo "? ��� full_path ���� �Ϸ�!";
$conn->close();

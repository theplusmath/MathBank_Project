<?php
header('Content-Type: text/html; charset=utf-8'); // �� �� ���� �� ���� �߰�
// ���� ǥ�� ����
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB ����
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("DB ���� ����: " . $conn->connect_error);
}

// ���� ������
$id = 143; // �̹� �����ϴ� ���� ID�� �����Ϸ��� �ش� ID�� ����
$formula_json = json_encode([
    "type" => "operator",
    "op" => "+",
    "left" => ["type" => "value", "val" => "x"],
    "right" => ["type" => "value", "val" => "1"]
]);

// ������Ʈ ���� ����
$sql = "UPDATE problems SET main_formula_tree = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $formula_json, $id);

if ($stmt->execute()) {
    echo "main_formula_tree ������Ʈ ����!";
} else {
    echo "���� �߻�: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>

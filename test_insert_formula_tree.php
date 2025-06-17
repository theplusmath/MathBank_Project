<?php
header('Content-Type: text/html; charset=utf-8'); // ← 이 줄을 맨 위에 추가
// 오류 표시 설정
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB 연결
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("DB 연결 실패: " . $conn->connect_error);
}

// 샘플 데이터
$id = 143; // 이미 존재하는 문제 ID를 수정하려면 해당 ID로 설정
$formula_json = json_encode([
    "type" => "operator",
    "op" => "+",
    "left" => ["type" => "value", "val" => "x"],
    "right" => ["type" => "value", "val" => "1"]
]);

// 업데이트 쿼리 실행
$sql = "UPDATE problems SET main_formula_tree = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $formula_json, $id);

if ($stmt->execute()) {
    echo "main_formula_tree 업데이트 성공!";
} else {
    echo "오류 발생: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>

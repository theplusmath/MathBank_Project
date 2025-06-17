<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1?? DB 연결
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

// 2?? full_path 생성 함수
function buildFullPath(mysqli $conn, int $id): string {
    $names = [];
    while ($id) {
        $res = $conn->query("SELECT name, parent_id FROM paths WHERE id = $id LIMIT 1");
        if ($row = $res->fetch_assoc()) {
            array_unshift($names, $row['name']); // 앞쪽에 누적
            $id = (int)$row['parent_id'];
        } else {
            break;
        }
    }
    return implode('/', $names);
}

// 3?? 전체 paths 테이블 경로 갱신
$result = $conn->query("SELECT id FROM paths");
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $fullPath = buildFullPath($conn, $id);
    $escapedPath = $conn->real_escape_string($fullPath);
    $conn->query("UPDATE paths SET full_path = '$escapedPath' WHERE id = $id");
}

echo "? 모든 full_path 갱신 완료!";
$conn->close();

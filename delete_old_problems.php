<?php
// delete_old_problems.php
// 삭제된 지 90일 지난 문제를 deleted_problems 테이블에서 자동 삭제하는 스크립트

$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("DB 연결 실패: " . $conn->connect_error);
}

// 삭제 쿼리 실행
$sql = "DELETE FROM deleted_problems WHERE deleted_at < NOW() - INTERVAL 90 DAY";
$result = $conn->query($sql);

if ($result) {
    echo date('Y-m-d H:i:s') . " - 90일 이상 지난 삭제된 문제를 성공적으로 정리했습니다.";
} else {
    echo "삭제 실패: " . $conn->error;
}

$conn->close();
?>

<?php
// auto_cleanup.php: 90일 이상 지난 삭제된 문제 자동 정리

$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

// 90일 이상 지난 삭제된 문제 제거
$conn->query("DELETE FROM deleted_problems WHERE deleted_at < NOW() - INTERVAL 90 DAY");

// 결과 출력 (수동 실행 시 확인용)
echo date("Y-m-d H:i:s") . " - 90일 이상 지난 삭제된 문제를 성공적으로 정리했습니다.";

$conn->close();
?>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("db.php");

if ($conn) {
    echo "✅ DB 연결 성공!";
} else {
    echo "❌ DB 연결 실패!";
}
?>

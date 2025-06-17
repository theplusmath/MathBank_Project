<?php
$source = 'restore.php';
$backupDir = 'backup';

if (!is_dir($backupDir)) {
    mkdir($backupDir);
}

$date = date('Ymd_His');
$backupFile = "$backupDir/restore_$date.php";

if (copy($source, $backupFile)) {
    echo "$date - restore.php 백업 완료: $backupFile";
} else {
    echo "$date - 백업 실패!";
}
?>

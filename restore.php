<?php
// 데이터베이스 연결
$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

// 복구 요청 처리 (30일 이내만 허용)
if (isset($_GET['restore'])) {
    $restore_id = (int)$_GET['restore'];
    $result = $conn->query("SELECT * FROM deleted_problems WHERE id = $restore_id AND deleted_at >= NOW() - INTERVAL 30 DAY");
    $row = $result->fetch_assoc();

    if ($row) {
        $stmt = $conn->prepare("INSERT INTO problems (title, path, category, type, question, answer, solution, difficulty, video, hint, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssss", $row['title'], $row['path'], $row['category'], $row['type'], $row['question'], $row['answer'], $row['solution'], $row['difficulty'], $row['video'], $row['hint'], $row['source']);
        $stmt->execute();
        $stmt->close();

        $user = 'admin';
        $log_stmt = $conn->prepare("INSERT INTO restored_log (problem_id, restored_by, restored_at) VALUES (?, ?, NOW())");
        $log_stmt->bind_param("is", $row['id'], $user);
        $log_stmt->execute();
        $log_stmt->close();

        $conn->query("DELETE FROM deleted_problems WHERE id = $restore_id");

        echo "<script>alert('복구가 완료되었습니다.'); location.href='restore.php';</script>";
        exit;
    } else {
        echo "<script>alert('30일이 지난 문제는 복구할 수 없습니다.'); location.href='restore.php';</script>";
        exit;
    }
}

// 삭제 요청 처리
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM deleted_problems WHERE id = $delete_id");
    echo "<script>alert('영구 삭제되었습니다.'); location.href='restore.php';</script>";
    exit;
}

$deleted = $conn->query("SELECT * FROM deleted_problems WHERE deleted_at >= NOW() - INTERVAL 30 DAY ORDER BY deleted_at DESC");
$logs = $conn->query("SELECT rl.problem_id, dp.title, rl.restored_at, rl.restored_by FROM restored_log rl LEFT JOIN deleted_problems dp ON rl.problem_id = dp.original_id ORDER BY rl.restored_at DESC");
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>문제 복구 시스템</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <h2>🗃️ 삭제된 문제 목록 (최근 30일 이내)</h2>
    <table>
        <tr>
            <th>ID</th><th>제목</th><th>출처</th><th>삭제일시</th><th>복구</th><th>영구삭제</th>
        </tr>
        <?php while ($row = $deleted->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['source'] ?? '') ?></td>
            <td><?= $row['deleted_at'] ?></td>
            <td><a href="?restore=<?= $row['id'] ?>" onclick="return confirm('복구하시겠습니까?')">복구</a></td>
            <td><a href="?delete=<?= $row['id'] ?>" onclick="return confirm('정말로 영구 삭제하시겠습니까?')">삭제</a></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <h2>📜 복구 로그</h2>
    <table>
        <tr>
            <th>원본 ID</th><th>제목</th><th>복구일시</th><th>복구자</th>
        </tr>
        <?php while ($row = $logs->fetch_assoc()): ?>
        <tr>
            <td><?= $row['problem_id'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= $row['restored_at'] ?></td>
            <td><?= htmlspecialchars($row['restored_by']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
<?php $conn->close(); ?>

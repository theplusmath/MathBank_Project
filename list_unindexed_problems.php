<?php
require_once "db.php";

// 쿼리: formula_index에 인덱스가 없는 문제 가져오기
$sql = "
    SELECT p.id, p.title, p.question
    FROM problems p
    LEFT JOIN formula_index f ON p.id = f.problem_id
    WHERE f.problem_id IS NULL
    ORDER BY p.id DESC
";
$stmt = $pdo->query($sql);
$results = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>수식 인덱스가 없는 문제</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background-color: #f0f0f0; }
        td pre { white-space: pre-wrap; word-break: break-all; }
    </style>
</head>
<body>
    <h2>❗ 수식 인덱스가 없는 문제 목록</h2>
    <p>총 <?= count($results) ?>건</p>
    <table>
        <tr>
            <th>ID</th>
            <th>제목</th>
            <th>문제 본문</th>
        </tr>
        <?php foreach ($results as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['id']) ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><pre><?= htmlspecialchars(strip_tags($row['question'])) ?></pre></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

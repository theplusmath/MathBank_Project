<?php
// 데이터베이스 연결
$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

// 삭제 요청 처리 시 백업 저장 후 삭제
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    // 기존 문제 가져오기
    $result = $conn->query("SELECT * FROM problems WHERE id = $delete_id");
    $row = $result->fetch_assoc();

    if ($row) {
        // deleted_problems 테이블에 백업
        $stmt = $conn->prepare("INSERT INTO deleted_problems (original_id, title, path, category, type, question, answer, solution, difficulty, video, hint, deleted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issssssssss", $row['id'], $row['title'], $row['path'], $row['category'], $row['type'], $row['question'], $row['answer'], $row['solution'], $row['difficulty'], $row['video'], $row['hint']);
        $stmt->execute();
        $stmt->close();

        // 원본 삭제
        $conn->query("DELETE FROM problems WHERE id = $delete_id");
    }

    echo "<script>alert('삭제 및 백업 완료되었습니다.'); location.href='list.php';</script>";
    exit;
}

$result = $conn->query("SELECT * FROM problems ORDER BY id DESC");

function formatted_math($text) {
    $text = str_replace(['\\(', '\\)'], '$', $text);
    return '<div>' . nl2br(htmlspecialchars($text)) . '</div>';  // 줄바꿈 유지, HTML 이스케이프 처리
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>수학 문제 목록</title>

    <!-- MathJax 설정 추가 -->
    <script>
    window.MathJax = {
        tex: {
            inlineMath: [['$', '$'], ['\\(', '\\)']]
        }
    };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #999;
            padding: 10px;
            vertical-align: top;
        }
        th {
            background-color: #eee;
        }
    </style>
</head>
<body>
    <h2>저장된 수학 문제 목록</h2>
    <p><strong>※ 경로:</strong> 중/고등 교육과정의 과목 → 대단락 → 중단락 → 소단락으로 구성 예정<br>
    <strong>※ 평가항목:</strong> 계산능력, 이해능력, 추론능력, 내적문제해결능력, 외적문제해결능력 중 선택<br>
    <strong>※ 유형:</strong> 선다형, 단답형, 서술형 중 선택<br>
    <strong>※ 난이도:</strong> 1~5 중 선택</p>

    <table>
        <tr>
            <th>ID</th>
            <th>제목</th>
            <th>경로</th>
            <th>평가항목</th>
            <th>유형</th>
            <th>문제</th>
            <th>정답</th>
            <th>동영상링크</th>
            <th>풀이</th>
            <th>난이도</th>
            <th>힌트</th>
            <th>수정</th>
            <th>삭제</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['path']) ?></td>
            <td><?= htmlspecialchars($row['category']) ?></td>
            <td><?= htmlspecialchars($row['type']) ?></td>
            <td><?= formatted_math($row['question']) ?></td>
            <td><?= formatted_math($row['answer']) ?></td>
            <td><a href="<?= htmlspecialchars($row['video']) ?>" target="_blank">링크</a></td>
            <td><?= formatted_math($row['solution']) ?></td>
            <td><?= htmlspecialchars($row['difficulty']) ?></td>
            <td><?= formatted_math($row['hint']) ?></td>
            <td><a href="edit.php?id=<?= $row['id'] ?>">수정</a></td>
            <td><a href="list.php?delete=<?= $row['id'] ?>" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
<?php $conn->close(); ?>

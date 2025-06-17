<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB 연결
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo "문제 ID가 없습니다.";
    exit;
}

// 문제 정보 불러오기
$stmt = $conn->prepare("SELECT * FROM problems WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$problem = $result->fetch_assoc();

if (!$problem) {
    echo "해당 문제를 찾을 수 없습니다.";
    exit;
}

// 선생님 목록 불러오기
$teachers = [];
$teacherResult = $conn->query("SELECT id, name FROM teachers ORDER BY name");
while ($row = $teacherResult->fetch_assoc()) {
    $teachers[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>문제 수정</title>
</head>
<body>
    <h2>✏️ 문제 수정</h2>

    <form action="update_problem.php" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($problem['id']) ?>">

        제목: <input type="text" name="title" value="<?= htmlspecialchars($problem['title']) ?>"><br><br>

        문제: <textarea name="question" rows="5"><?= htmlspecialchars($problem['question']) ?></textarea><br><br>

        정답: <textarea name="answer" rows="2"><?= htmlspecialchars($problem['answer']) ?></textarea><br><br>

        해설: <textarea name="solution" rows="5"><?= htmlspecialchars($problem['solution']) ?></textarea><br><br>

        힌트: <textarea name="hint" rows="2"><?= htmlspecialchars($problem['hint']) ?></textarea><br><br>

        영상 링크: <input type="text" name="video" value="<?= htmlspecialchars($problem['video']) ?>"><br><br>

        난이도: 
        <input type="number" name="difficulty" min="1" max="5" value="<?= htmlspecialchars($problem['difficulty']) ?>"><br><br>

        유형: <input type="text" name="type" value="<?= htmlspecialchars($problem['type']) ?>"><br><br>

        분류: <input type="text" name="category" value="<?= htmlspecialchars($problem['category']) ?>"><br><br>

        출처: <input type="text" name="source" value="<?= htmlspecialchars($problem['source']) ?>"><br><br>

        작성자:
        <select name="created_by">
            <option value="">-- 선택하세요 --</option>
            <?php foreach ($teachers as $teacher): ?>
                <option value="<?= $teacher['id'] ?>" <?= $teacher['id'] == $problem['created_by'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($teacher['name']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">수정 완료</button>
    </form>
</body>
</html>

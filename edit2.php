<?php
$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<script>alert('잘못된 접근입니다.'); history.back();</script>";
    exit;
}

$action = $_POST['action'] ?? '';

// 수정 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $title = $_POST['title'] ?? "";
    $path = $_POST['path'] ?? "";
    $category = $_POST['category'] ?? "";
    $type = $_POST['type'] ?? "";
    $question = $_POST['question'] ?? "";
    $answer = $_POST['answer'] ?? "";
    $solution = $_POST['solution'] ?? "";
    $difficulty = $_POST['difficulty'] ?? "";
    $video = $_POST['video'] ?? "";
    $hint = $_POST['hint'] ?? "";

    $stmt = $conn->prepare("UPDATE problems SET title=?, path=?, category=?, type=?, question=?, answer=?, solution=?, difficulty=?, video=?, hint=? WHERE id=?");
    $stmt->bind_param("ssssssssssi", $title, $path, $category, $type, $question, $answer, $solution, $difficulty, $video, $hint, $id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('수정 완료되었습니다.'); location.href='list.php';</script>";
    exit;
}

// 기존 데이터 가져오기 또는 미리보기용 데이터 유지
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'preview') {
    $problem = $_POST; // 미리보기용으로 폼 데이터를 그대로 사용
} else {
    $stmt = $conn->prepare("SELECT * FROM problems WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $problem = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>문제 수정</title>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <style>
        textarea, input, select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }
        .preview {
            border: 1px dashed #aaa;
            padding: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<h2>문제 수정</h2>
<form method="post">
    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

    <label>제목</label>
    <input type="text" name="title" value="<?= htmlspecialchars($problem['title']) ?>">

    <label>경로</label>
    <input type="text" name="path" value="<?= htmlspecialchars($problem['path']) ?>">

    <label>평가항목</label>
    <input type="text" name="category" value="<?= htmlspecialchars($problem['category']) ?>">

    <label>유형</label>
    <select name="type">
        <option value="선다형" <?= $problem['type'] === '선다형' ? 'selected' : '' ?>>선다형</option>
        <option value="단답형" <?= $problem['type'] === '단답형' ? 'selected' : '' ?>>단답형</option>
        <option value="서술형" <?= $problem['type'] === '서술형' ? 'selected' : '' ?>>서술형</option>
    </select>

    <label>문제</label>
    <textarea name="question" rows="5"><?= htmlspecialchars($problem['question']) ?></textarea>

    <label>정답</label>
    <textarea name="answer" rows="2"><?= htmlspecialchars($problem['answer']) ?></textarea>

    <label>풀이</label>
    <textarea name="solution" rows="5"><?= htmlspecialchars($problem['solution']) ?></textarea>

    <label>난이도 (1~5)</label>
    <input type="number" name="difficulty" value="<?= htmlspecialchars($problem['difficulty']) ?>" min="1" max="5">

    <label>동영상 링크</label>
    <input type="text" name="video" value="<?= htmlspecialchars($problem['video']) ?>">

    <label>힌트</label>
    <textarea name="hint" rows="3"><?= htmlspecialchars($problem['hint']) ?></textarea>

    <button type="submit" name="action" value="preview">미리보기</button>
    <button type="submit" name="action" value="update">최종 수정 완료</button>
</form>

<?php if ($action === 'preview'): ?>
    <div class="preview">
        <h3>📌 미리보기</h3>
        <p><strong>문제:</strong><br><?= nl2br($problem['question']) ?></p>
        <p><strong>정답:</strong><br><?= nl2br($problem['answer']) ?></p>
        <p><strong>풀이:</strong><br><?= nl2br($problem['solution']) ?></p>
        <p><strong>힌트:</strong><br><?= nl2br($problem['hint']) ?></p>
    </div>
<?php endif; ?>

</body>
</html>

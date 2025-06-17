<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>문제 수정</title>
  <style>
    body {
      font-family: 'Malgun Gothic', sans-serif;
      margin: 20px;
    }
    input, select, textarea {
      margin-bottom: 10px;
      padding: 5px;
      width: 100%;
    }
    textarea {
      height: 80px;
    }
    button {
      padding: 8px 12px;
      margin: 5px;
    }
  </style>
</head>
<body>

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo "문제 ID가 없습니다.";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM problems WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$problem = $result->fetch_assoc();

if (!$problem) {
    echo "문제를 찾을 수 없습니다.";
    exit;
}

$teachers = [];
$teacherResult = $conn->query("SELECT id, name FROM teachers ORDER BY name");
while ($row = $teacherResult->fetch_assoc()) {
    $teachers[] = $row;
}

$conn->close();
?>

<h1>문제 수정</h1>

<form id="problemForm" action="update_problem.php" method="POST">
    <input type="hidden" name="id" value="<?= htmlspecialchars($problem['id']) ?>">
    <input type="hidden" name="copyMode" id="copyMode" value="0">

    제목: <input type="text" name="title" value="<?= htmlspecialchars($problem['title']) ?>"><br>

    문제: <textarea name="question"><?= htmlspecialchars($problem['question']) ?></textarea><br>

    정답: <textarea name="answer"><?= htmlspecialchars($problem['answer']) ?></textarea><br>

    해설: <textarea name="solution"><?= htmlspecialchars($problem['solution']) ?></textarea><br>

    힌트: <textarea name="hint"><?= htmlspecialchars($problem['hint']) ?></textarea><br>

    영상 링크: <input type="text" name="video" value="<?= htmlspecialchars($problem['video']) ?>"><br>

    난이도:
    <select name="difficulty">
      <option value="">-- 난이도 선택 --</option>
      <?php for ($i = 1; $i <= 5; $i++): ?>
        <option value="<?= $i ?>" <?= $problem['difficulty'] == $i ? 'selected' : '' ?>><?= $i ?></option>
      <?php endfor; ?>
    </select><br>

    유형:
    <select name="type">
      <option value="">-- 유형 선택 --</option>
      <option value="선택형" <?= $problem['type'] == '선택형' ? 'selected' : '' ?>>선택형</option>
      <option value="단답형" <?= $problem['type'] == '단답형' ? 'selected' : '' ?>>단답형</option>
      <option value="서술형" <?= $problem['type'] == '서술형' ? 'selected' : '' ?>>서술형</option>
    </select><br>

    분류:
    <select name="category">
      <option value="">-- 분류 선택 --</option>
      <option value="계산능력" <?= $problem['category'] == '계산능력' ? 'selected' : '' ?>>계산능력</option>
      <option value="이해능력" <?= $problem['category'] == '이해능력' ? 'selected' : '' ?>>이해능력</option>
      <option value="추론능력" <?= $problem['category'] == '추론능력' ? 'selected' : '' ?>>추론능력</option>
      <option value="내적문제해결능력" <?= $problem['category'] == '내적문제해결능력' ? 'selected' : '' ?>>내적문제해결능력</option>
      <option value="외적문제해결능력" <?= $problem['category'] == '외적문제해결능력' ? 'selected' : '' ?>>외적문제해결능력</option>
    </select><br>

    출처:
    <select name="source">
      <option value="">-- 출처 선택 --</option>
      <option value="문제집" <?= $problem['source'] == '문제집' ? 'selected' : '' ?>>문제집</option>
      <option value="중등기출" <?= $problem['source'] == '중등기출' ? 'selected' : '' ?>>중등기출</option>
      <option value="일반고기출" <?= $problem['source'] == '일반고기출' ? 'selected' : '' ?>>일반고기출</option>
      <option value="과학고기출" <?= $problem['source'] == '과학고기출' ? 'selected' : '' ?>>과학고기출</option>
      <option value="자사고기출" <?= $problem['source'] == '자사고기출' ? 'selected' : '' ?>>자사고기출</option>
      <option value="수능모의고사기출" <?= $problem['source'] == '수능모의고사기출' ? 'selected' : '' ?>>수능모의고사기출</option>
      <option value="수리논술심층면접" <?= $problem['source'] == '수리논술심층면접' ? 'selected' : '' ?>>수리논술심층면접</option>
      <option value="AP미적분" <?= $problem['source'] == 'AP미적분' ? 'selected' : '' ?>>AP미적분</option>
    </select><br>

    작성자:
    <select name="created_by">
      <option value="">-- 작성자 선택 --</option>
      <?php foreach ($teachers as $teacher): ?>
        <option value="<?= $teacher['id'] ?>" <?= $teacher['id'] == $problem['created_by'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($teacher['name']) ?>
        </option>
      <?php endforeach; ?>
    </select><br>

    태그 (쉼표로 구분): <input type="text" name="tags" value="<?= htmlspecialchars($problem['tags'] ?? '') ?>"><br>

    <button type="submit" onclick="return confirm('정말 수정하시겠습니까? (원본이 변경됩니다.)')">수정 완료</button>
    <button type="button" onclick="confirmCopy()">복사 저장</button>
    <button type="button" onclick="previewProblem()">미리보기</button>
</form>

<script>
function confirmCopy() {
  if (confirm('수정한 내용을 복사하여 새 문제로 저장하시겠습니까?')) {
    document.getElementById('copyMode').value = '1';
    document.getElementById('problemForm').submit();
  }
}

function previewProblem() {
  const id = <?= (int)$problem['id'] ?>;
  window.open('view_problem.php?id=' + id, '_blank');
}
</script>

<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
  ClassicEditor
    .create( document.querySelector( 'textarea[name="question"]' ))
    .catch( error => console.error( error ));

  ClassicEditor
    .create( document.querySelector( 'textarea[name="solution"]' ))
    .catch( error => console.error( error ));
</script>

</body>
</html>

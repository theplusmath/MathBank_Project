<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'functions.php';

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
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>문제 수정</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
body { 
    font-family: 'Malgun Gothic', sans-serif; 
    margin: 40px; 
    padding: 16px; 
    background: #fcfcfe;
}
input, select, textarea { 
    margin-bottom: 10px; 
    padding: 5px; 
    width: 100%; 
    box-sizing: border-box;
}
textarea { 
    height: 80px; 
    resize: vertical;
}
button { 
    padding: 8px 12px; 
    margin: 5px; 
    border-radius: 6px;
    border: none;
    background: #2957af;
    color: #fff;
    font-size: 1em;
    cursor: pointer;
    transition: background 0.2s;
}
button.btn-outline-danger {
    background: #fff;
    color: #c3271d;
    border: 1.5px solid #c3271d;
}
button.btn-outline-danger:hover {
    background: #c3271d;
    color: #fff;
}
.edit-section {
    background: #f7f8fc;
    border: 1.5px solid #d0d3e6;
    border-radius: 11px;
    padding: 16px 18px;
    margin-bottom: 22px;
    box-shadow: 0 1px 8px #dde2ee50;
}
h1, h2, h3 { 
    margin-top: 28px; 
    margin-bottom: 16px; 
}
label, .formula-label { 
    margin-bottom: 4px; 
    display: inline-block; 
    font-weight: bold;
    color: #2d3187;
}
.math-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 28px;
    margin-bottom: 28px;
}
.math-block {
    background: #f4f7fc;
    border-radius: 9px;
    margin-bottom: 12px;
    box-shadow: 0 2px 7px #0001;
    padding: 15px 10px 12px 10px;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    min-height: 92px;
    height: 100%;
    position: relative;
    transition: opacity 0.3s, max-height 0.3s;
    animation: fadeIn 0.4s;
}
@keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }
math-field { 
    width: 100%; 
    min-width: 60px; 
    font-size: 1.16em; 
    background: #fff; 
}
.latex-label { 
    font-size:0.97em; 
    color:#555; 
    margin-top: 7px;
}
.latex-input { 
    width: 98%; 
    font-size: 1em; 
    margin-top:2px; 
}
.apply-btn { 
    margin-top: 10px; 
    padding:5px 18px; 
    background:#2957af; 
    color:#fff; 
    border:none; 
    border-radius:6px; 
    font-size:1em; 
    cursor:pointer;
}
@media (max-width: 900px) {
    .math-grid { grid-template-columns: 1fr; gap: 10px; }
    .edit-section { padding: 8px 5px; }
}
</style>

  <!-- Bootstrap 5 (팝업/모달용) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- CKEditor 5 (문제/정답 입력란) -->
  <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
  <!-- MathLive (수식 편집기) -->
  <script src="https://cdn.jsdelivr.net/npm/mathlive/dist/mathlive.min.js"></script>
</head>

<body>
<script>window.problem_id = <?= (int)$problem['id'] ?>;</script>

<h1>문제 수정</h1>
<!-- 🔁 수정 이력 보기 버튼 -->
<div style="margin-bottom: 15px;">
  <a href="view_history.html?problem_id=<?= $problem['id'] ?>" target="_blank" style="padding: 6px 10px; background-color: #555; color: white; text-decoration: none; border-radius: 4px;">
    🕘 수정 이력 보기
  </a>
</div>
<form id="problemForm" action="update_problem.php" method="POST" onsubmit="return handleSubmit()">
    <input type="hidden" name="id" value="<?= htmlspecialchars($problem['id']) ?>">
    <input type="hidden" name="copyMode" id="copyMode" value="0">
    <input type="hidden" name="return_url" id="return_url" value="">


    제목: <input type="text" name="title" value="<?= htmlspecialchars($problem['title']) ?>"><br>

    <!-- 문제 -->
    문제:
    <textarea name="question" id="questionArea"><?= htmlspecialchars($problem['question']) ?></textarea>
    <button type="button" class="btn btn-outline-danger" onclick="toggleFormulaPanel('question')">[문제] 수식 오류 검사 및 수정</button>
    <div id="formulaPanel_question"></div>

    정답: <textarea name="answer" id="answerArea"><?= htmlspecialchars($problem['answer']) ?></textarea>
    <button type="button" class="btn btn-outline-danger" onclick="toggleFormulaPanel('answer')">
        [정답] 수식 오류 검사 및 수정
    </button>
    <div id="formulaPanel_answer"></div>

    해설: <textarea name="solution" id="solutionArea"><?= htmlspecialchars($problem['solution']) ?></textarea>
    <button type="button" class="btn btn-outline-danger" onclick="toggleFormulaPanel('solution')">
        [해설] 수식 오류 검사 및 수정
    </button>
    <div id="formulaPanel_solution"></div>


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
      <?php
        $sources = ['문제집', '중등기출', '일반고기출', '과학고기출', '자사고기출', '수능모의고사기출', '수리논술심층면접', 'AP미적분'];
        foreach ($sources as $src): ?>
          <option value="<?= $src ?>" <?= $problem['source'] == $src ? 'selected' : '' ?>><?= $src ?></option>
      <?php endforeach; ?>
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
<div style="margin:10px 0;">
  <label>path_id로 직접 이동:&nbsp;</label>
  <input type="number" id="manual_path_id" placeholder="경로 ID 입력" style="width: 120px;">
  <button type="button" onclick="setPathById()">이동</button>
</div>
<div class="form-group">
  <label>경로 선택 (교육과정 ~ 소단원):</label><br />
  <select id="depth1" onchange="loadNextDepth(1)"></select>
  <select id="depth2" onchange="loadNextDepth(2)"></select>
  <select id="depth3" onchange="loadNextDepth(3)"></select>
  <select id="depth4" onchange="loadNextDepth(4)"></select>
  <select id="depth5" onchange="loadNextDepth(5)"></select>
  <select id="depth6" onchange="updatePathTextAndId()"></select>
  <input type="hidden" name="path_text" id="path_text" value="<?= htmlspecialchars($problem['path_text'] ?? '') ?>">
  <input type="hidden" name="path_id" id="path_id" value="<?= (int)($problem['path_id'] ?? 0) ?>">
  </div>

<div class="form-group">
  <label>출처 경로 선택:</label><br />
  <select id="source_path1" onchange="loadSourceNextDepth(1)"></select>
  <select id="source_path2" onchange="loadSourceNextDepth(2)"></select>
  <select id="source_path3" onchange="loadSourceNextDepth(3)"></select>
  <select id="source_path4" onchange="loadSourceNextDepth(4)"></select>
  <select id="source_path5" onchange="loadSourceNextDepth(5)"></select>
  <select id="source_path6" onchange="loadSourceNextDepth(6)"></select>
  <input type="hidden" name="source_path_id" id="source_path_id" value="<?= (int)($problem['source_path_id'] ?? 0) ?>">
</div>



    <button type="submit">수정 완료</button>
    <button type="button" onclick="confirmCopy()" style="background-color: orange;">복사 저장</button>
    <button type="button" onclick="previewProblem()">미리보기</button>
</form>


<h2>🕘 수정 이력</h2>
<table border="1" cellpadding="8" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>제목</th>
            <th>수정일</th>
            <th>복원</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $historyConn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
        $historyConn->set_charset('utf8mb4');
        $id = intval($problem['id']);
        $result = $historyConn->query("SELECT id, title, updated_at FROM history_problems WHERE problem_id = $id ORDER BY updated_at DESC");

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>{$row['id']}</td>
                <td>" . htmlspecialchars($row['title']) . "</td>
                <td>{$row['updated_at']}</td>
                <td>
                    <button onclick=\"compareHistory({$row['id']})\">비교</button>
                    <button onclick=\"restoreHistory({$row['id']})\">복원</button>
                    <button onclick=\"deleteHistory({$row['id']})\" style=\"color:red;\">삭제</button>
                </td>
            </tr>";
        }
        $historyConn->close();
        ?>
    </tbody>
</table>

<div id="diffResult" style="margin-top: 30px; padding: 15px; border: 1px solid #ccc; background-color: #f9f9f9; display: none;">
    <h3>🔍 변경된 필드</h3>
    <ul id="diffList"></ul>
</div>

<h2>📝 복원 로그</h2>
<table border="1" cellpadding="8" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th>ID</th>
            <th>복원된 이력 ID</th>
            <th>복원자</th>
            <th>복원 일시</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $logConn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
        $logConn->set_charset('utf8mb4');
        $id = intval($problem['id']);
        $logResult = $logConn->query("SELECT id, history_id, restored_by, restored_at FROM restore_log WHERE problem_id = $id ORDER BY restored_at DESC");

        while ($row = $logResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['history_id']}</td>";
            echo "<td>" . htmlspecialchars($row['restored_by']) . "</td>";
            echo "<td>{$row['restored_at']}</td>";
            echo "</tr>";
        }

        $logConn->close();
        ?>
    </tbody>
</table>




<!-- ================================ -->
<!-- [수식 오류 검사 및 수정 모달] -->
<!-- ================================ -->
<div class="modal fade" id="formulaModal" tabindex="-1" aria-labelledby="formulaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="formulaModalLabel">수식 오류 검사 및 수정 (모든 $...$ 수식)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="formulaEditGrid"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" onclick="applyAllFormulaEdits()">모든 적용</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
      </div>
    </div>
  </div>
</div>
<!-- ================================ -->



<!-- 이하 이력/복원 표 및 기타 UI 코드 (생략, 위의 너의 코드와 동일하게 두면 됨) -->

<!-- ================================ -->
<!-- [수식 오류 검사 및 수정 모달] -->
<!-- ================================ -->
<div class="modal fade" id="formulaModal" tabindex="-1" aria-labelledby="formulaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="formulaModalLabel">수식 오류 검사 및 수정 (모든 $...$ 수식)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="formulaEditGrid"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" onclick="applyAllFormulaEdits()">모든 적용</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
      </div>
    </div>
  </div>
</div>
<script>
  window.problem_id = <?= (int)$problem['id'] ?>;
</script>

<script>window.problem_id = <?= (int)$problem['id'] ?>;</script>
<script src="/js/edit_problem.js?v=202406"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    setTimeout(() => {
        const select = document.getElementById('source_path1');
        if (select) {
            // 반드시 옵션 초기화
            loadSourcePathOptions(1, null);
        } else {
            console.warn('source_path1 select 요소가 아직 없음!');
        }
    }, 500); // 충분히 여유 있는 시간
});
</script>


</body>
</html>

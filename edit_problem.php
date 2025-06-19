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
<script>

const DEPTH_COUNT = 6;   // 필요하면 7, 8로 변경
let questionEditor, solutionEditor;
ClassicEditor.create(document.querySelector('textarea[name="question"]')).then(editor => questionEditor = editor);
ClassicEditor.create(document.querySelector('textarea[name="solution"]')).then(editor => solutionEditor = editor);



// -------------------------------------------------
// [이하 기존의 경로/이력/복원/미리보기/저장 등 JS 유지]
// -------------------------------------------------
function confirmCopy() {
  if (confirm('수정한 내용을 복사하여 새 문제로 저장하시겠습니까?')) {
    document.querySelector('textarea[name="question"]').value = questionEditor.getData();
    document.querySelector('textarea[name="solution"]').value = solutionEditor.getData();
    document.getElementById('copyMode').value = '1';
    document.getElementById('problemForm').submit();
  }
}
function previewProblem() {
  const id = <?= (int)$problem['id'] ?>;
  window.open('view_problem.php?id=' + id, '_blank');
}
function handleSubmit() {
  document.querySelector('textarea[name="question"]').value = questionEditor.getData();
  document.querySelector('textarea[name="solution"]').value = solutionEditor.getData();
  document.getElementById('copyMode').value = '0';
  return confirm('정말 수정하시겠습니까? (원본이 변경됩니다.)');
}
// [이하 경로 드롭다운 및 이력 복원 로직 기존대로 복사, 그대로 사용]


let formulaPanelOpen = {};

function toggleFormulaPanel(field) {
    // 열려있으면 닫기
    if (formulaPanelOpen[field]) {
        document.getElementById('formulaPanel_' + field).innerHTML = '';
        formulaPanelOpen[field] = false;
        return;
    }

    // 문제 본문 데이터 읽기
    let text;
    if (field === 'question' && typeof questionEditor !== 'undefined') {
        text = questionEditor.getData();
    } else {
        text = document.getElementById(field + 'Area') ? document.getElementById(field + 'Area').value : '';
    }
    // HTML 태그 제거
    let htmlTagRegex = /(<([^>]+)>)/gi;
    text = text.replace(htmlTagRegex, '');

    // $...$ 수식 추출
    let regex = /\$([^\$]+)\$/g, m, arr = [];
    let idx = 0;
    while ((m = regex.exec(text)) !== null) {
        arr.push({
            index: idx,
            formula: m[1],
            raw: m[0],
            pos: m.index
        });
        idx++;
    }
    let edits = arr.map(f => ({ ...f, edited: f.formula }));

    // 그리드 HTML 만들기
    let grid = `<div class="math-grid" style="grid-template-columns: 1fr 1fr 1fr 0.7fr;">`;
    edits.forEach((item, i) => {
        // 정리된 LaTeX
        let cleanedLatex = item.edited.replace(/\s+/g, ' ').trim();
        grid += `
        <div class="math-block" style="display:flex;align-items:center;">
            <math-field id="mf_${field}_${i}" virtual-keyboard-mode="manual" style="margin-right:8px;">${item.edited}</math-field>
            <input type="text" value="${cleanedLatex.replace(/"/g,"&quot;")}" class="latex-input" readonly style="background:#eef;margin:0 8px;width:99%;">
            <input type="text" id="latex_${field}_${i}" class="latex-input" value="${item.edited.replace(/"/g,"&quot;")}" style="margin:0 8px;width:99%;">
            <button type="button" class="apply-btn" onclick="applyFormulaEditPanel('${field}', ${i})">확인/적용</button>
        </div>
        `;
    });
    grid += '</div>';
    grid += `<button type="button" class="btn btn-success" onclick="applyAllFormulaEditsPanel('${field}', ${edits.length})">모든 적용</button>`;

    // **렌더 먼저!**
    document.getElementById('formulaPanel_' + field).innerHTML = grid;
    formulaPanelOpen[field] = true;

    // **이벤트 바인딩은 렌더 이후에!!**
    edits.forEach((item, i) => {
        let mf = document.getElementById('mf_' + field + '_' + i);
        let latex = document.getElementById('latex_' + field + '_' + i);
        if (!mf || !latex) return;
        let cleaned = mf.parentElement ? mf.parentElement.querySelector('input[readonly]') : null;
        mf.addEventListener('input', () => {
            latex.value = mf.value;
            if (cleaned) cleaned.value = mf.value.replace(/\s+/g, ' ').trim();
        });
        latex.addEventListener('input', () => {
            mf.value = latex.value;
            if (cleaned) cleaned.value = latex.value.replace(/\s+/g, ' ').trim();
        });
    });

    // 상태 전역 저장
    window['formulaEdits_' + field] = edits;
}


// 수식별 적용
function applyFormulaEditPanel(field, idx) {
    let edits = window['formulaEdits_' + field];
    let mf = document.getElementById('mf_' + field + '_' + idx);
    let latex = document.getElementById('latex_' + field + '_' + idx).value;
    edits[idx].edited = latex;

    // 1. 본문 데이터 읽기
    let text;
    if (field === 'question' && typeof questionEditor !== 'undefined') {
        text = questionEditor.getData();
    } else {
        text = document.getElementById(field + 'Area') ? document.getElementById(field + 'Area').value : '';
    }
    // 2. 해당 수식만 원본 → 수정본으로 치환
    let plain = text.replace(edits[idx].raw, '$' + latex + '$');
    // 3. 본문 반영
    if (field === 'question' && typeof questionEditor !== 'undefined') {
        questionEditor.setData(plain);
    }
    if (document.getElementById(field + 'Area')) {
        document.getElementById(field + 'Area').value = plain;
    }

    // 4. 해당 math-block만 삭제
    const block = mf.closest('.math-block');
    if (block) block.remove();

    // 5. edits 배열의 해당 인덱스는 undefined 처리
    edits[idx] = null;

    // 6. math-block이 더 이상 없으면 패널 전체 닫기
    const remainingBlocks = document.querySelectorAll('#formulaPanel_' + field + ' .math-block');
    if (remainingBlocks.length === 0) {
        document.getElementById('formulaPanel_' + field).innerHTML = '';
        formulaPanelOpen[field] = false;
    }
}


// 모두 적용
function applyAllFormulaEditsPanel(field, total) {
    let edits = window['formulaEdits_' + field];
    let text;
    if (field === 'question' && typeof questionEditor !== 'undefined') {
        text = questionEditor.getData();
    } else {
        text = document.getElementById(field + 'Area') ? document.getElementById(field + 'Area').value : '';
    }
    let htmlTagRegex = /(<([^>]+)>)/gi;
    let plain = text.replace(htmlTagRegex, '');
    // 순서대로 원본 → 수정
    edits.forEach(f => {
    if (!f) return;
    plain = plain.replace(f.raw, '$' + f.edited + '$');
    });    // 적용
    if (field === 'question' && typeof questionEditor !== 'undefined') {
        questionEditor.setData(plain);
    }
    if (document.getElementById(field + 'Area')) {
        document.getElementById(field + 'Area').value = plain;
    }
    // 패널 닫기
    document.getElementById('formulaPanel_' + field).innerHTML = '';
    formulaPanelOpen[field] = false;
}


function setPathById() {
  const targetId = parseInt(document.getElementById('manual_path_id').value);
  if (!targetId) {
    alert('경로 ID를 입력하세요.');
    return;
  }
  // 모든 드롭다운 먼저 초기화
  for (let i = 1; i <= DEPTH_COUNT; i++) {
  document.getElementById(`depth${i}`).innerHTML = `<option value="">- ${i}단계 선택 -</option>`;
  }



  fetch('get_path_tree_flat_paths.php')
    .then(res => res.json())
    .then(flatPaths => {
      const pathMap = new Map();
      flatPaths.forEach(p => pathMap.set(p.id, p));
      let current = pathMap.get(targetId);
      if (!current) {
        alert('해당 경로 ID를 찾을 수 없습니다.');
        return;
      }
      const pathIds = [];
      while (current) {
        pathIds.unshift(current.id);
        current = pathMap.get(current.parent_id);
      }
      let promise = Promise.resolve();
      pathIds.forEach((id, index) => {
        promise = promise.then(() => {
          const parentId = index === 0 ? null : pathIds[index - 1];
          return fetch(`get_paths_by_parent.php?parent_id=${parentId ?? ''}`)
            .then(res => res.json())
            .then(options => {
              const sel = document.getElementById(`depth${index + 1}`);
              sel.innerHTML = `<option value="">- ${index + 1}단계 선택 -</option>`;
              options.forEach(opt => {
                const o = document.createElement('option');
                o.value = opt.id;
                o.textContent = opt.name;
                if (opt.id == id) o.selected = true;
                sel.appendChild(o);
              });
            });
        });
      });
      // 마지막에 path_id, path_text 동기화!
      promise.then(() => {
        document.getElementById('path_id').value = targetId;
        updatePathTextAndId(); // 동기화!
      });
    });
}

function loadDepthOptions(depth, parentId) {
  fetch(`get_paths_by_parent.php?parent_id=${parentId ?? ''}`)
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById(`depth${depth}`);
      select.innerHTML = `<option value="">- ${depth}단계 선택 -</option>`;
      data.forEach(row => {
        const opt = document.createElement("option");
        opt.value = row.id;
        opt.textContent = row.name;
        select.appendChild(opt);
      });
    });
}

// 반드시 아래 코드를 추가하세요!
function loadNextDepth(depth) {
  // 1. 선택된 단계(depth) 아래의 모든 드롭다운 초기화
  for (let i = depth + 1; i <= DEPTH_COUNT; i++) {
  document.getElementById(`depth${i}`).innerHTML = `<option value="">- ${i}단계 선택 -</option>`;
  }

  // 2. 선택된 값이 있으면 하위 옵션 로드
  const selectedId = document.getElementById(`depth${depth}`).value;
  if (selectedId) loadDepthOptions(depth + 1, selectedId);
  // 3. path_text, path_id 동기화
  updatePathTextAndId();
}

function updatePathTextAndId() {
  const names = [];
  let lastId = null;
  for (let i = 1; i <= DEPTH_COUNT; i++) {
  const sel = document.getElementById(`depth${i}`);
  if (sel && sel.value) {
    names.push(sel.options[sel.selectedIndex].text);
    lastId = sel.value;
    }
  }
  document.getElementById('path_text').value = names.join('/');
  document.getElementById('path_id').value = lastId ?? '';
}



// path_id 값이 있으면 드롭다운을 세팅하는 함수
function setPathByIdFromValue(pathId) {
  document.getElementById('manual_path_id').value = pathId;
  setPathById();
}


const SOURCE_DEPTH_COUNT = 6;
function loadSourceNextDepth(depth) {
  for (let i = depth + 1; i <= SOURCE_DEPTH_COUNT; i++) {
    document.getElementById(`source_path${i}`).innerHTML = `<option value="">- ${i}단계 선택 -</option>`;
  }
  const selectedId = document.getElementById(`source_path${depth}`).value;
  if (selectedId) loadSourcePathOptions(depth + 1, selectedId);
  updateSourcePathTextAndId();
}
function loadSourcePathOptions(depth, parentId) {
  fetch(`get_source_path.php?parent_id=${parentId ?? ''}`)
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById(`source_path${depth}`);
      select.innerHTML = `<option value="">- ${depth}단계 선택 -</option>`;
      data.forEach(row => {
        const opt = document.createElement("option");
        opt.value = row.id;
        opt.textContent = row.name;
        select.appendChild(opt);
      });
    });
}
function updateSourcePathTextAndId() {
  let lastId = null;
  for (let i = 1; i <= SOURCE_DEPTH_COUNT; i++) {
    const sel = document.getElementById(`source_path${i}`);
    if (sel && sel.value) lastId = sel.value;
  }
  document.getElementById('source_path_id').value = lastId ?? '';
}
// 최초 실행 시 최상위만 호출

window.addEventListener('DOMContentLoaded', function() {
  // 메인 경로
  const initialPathId = document.getElementById('path_id').value;
  if (initialPathId && !isNaN(parseInt(initialPathId))) {
    setPathByIdFromValue(initialPathId);
  } else {
    loadDepthOptions(1, null);
  }
  // 출처 경로
  const initialSourcePathId = document.getElementById('source_path_id').value;
  if (initialSourcePathId && !isNaN(parseInt(initialSourcePathId))) {
    setSourcePathByIdFromValue(initialSourcePathId);
  } else {
    loadSourcePathOptions(1, null);
  }
  // return_url 자동
  var referrer = document.referrer;
  var returnInput = document.getElementById('return_url');
  if (returnInput && !returnInput.value && referrer) {
      returnInput.value = referrer;
  }
});





function setSourcePathByIdFromValue(pathId) {
  fetch('get_source_path_tree_flat_paths.php')
    .then(res => res.json())
    .then(flatPaths => {
      const pathMap = new Map();
      flatPaths.forEach(p => pathMap.set(p.id, p));
      let current = pathMap.get(Number(pathId));
      if (!current) return;
      const pathIds = [];
      while (current) {
        pathIds.unshift(current.id);
        current = pathMap.get(current.parent_id);
      }
      let promise = Promise.resolve();
      pathIds.forEach((id, index) => {
        promise = promise.then(() => {
          const parentId = index === 0 ? null : pathIds[index - 1];
          return fetch(`get_source_path.php?parent_id=${parentId ?? ''}`)
            .then(res => res.json())
            .then(options => {
              const sel = document.getElementById(`source_path${index + 1}`);
              sel.innerHTML = `<option value="">- ${index + 1}단계 선택 -</option>`;
              options.forEach(opt => {
                const o = document.createElement('option');
                o.value = opt.id;
                o.textContent = opt.name;
                if (opt.id == id) o.selected = true;
                sel.appendChild(o);
              });
            });
        });
      });
      promise.then(() => {
        document.getElementById('source_path_id').value = pathId;
      });
    });
}


</script>
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


</body>
</html>

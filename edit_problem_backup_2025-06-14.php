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

  <!-- Bootstrap 5 (팝업/모달용) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- CKEditor 5 (문제/정답 입력란) -->
  <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

  <!-- MathJax (수식 미리보기용) -->
  <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
  <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

  <link rel="stylesheet" href="https://unpkg.com/mathlive/dist/mathlive.core.css">
  <link rel="stylesheet" href="https://unpkg.com/mathlive/dist/mathlive.css">
  <script src="https://unpkg.com/mathlive/dist/mathlive.min.js"></script>


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

    제목: <input type="text" name="title" value="<?= htmlspecialchars($problem['title']) ?>"><br>
    문제: <textarea name="question"><?= htmlspecialchars($problem['question']) ?></textarea><br>
    <!-- ↓↓↓ 이 아래에 버튼 추가 ↓↓↓ -->
    <button type="button" onclick="extractAndCheckFormulas()" class="btn btn-outline-danger" style="margin-bottom: 10px;">
      수식 오류 검사 및 수정
    </button>
    <!-- ... 위에서 버튼을 추가한 직후 ... -->
    <!-- ↓↓↓ 이 아래에 모달 코드를 추가 ↓↓↓ -->

    <div class="modal fade" id="formulaErrorModal" tabindex="-1" aria-labelledby="formulaErrorModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="formulaErrorModalLabel">수식 오류 검사 및 수정</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="formulaErrorModalBody">
            <!-- 여기에 동적으로 수식 리스트 및 Mathlive 에디터가 생성됨 -->
            <!-- 수식 수정용 Mathlive 입력창(초기에는 숨김) -->
            <div id="mathliveEditContainer" style="margin-top: 18px; display:none;">
                <h6>수식 수정(Mathlive)</h6>
                <math-field id="mathliveEditField" virtual-keyboard-mode="manual" style="width:100%; min-height:44px; font-size:1.2em; border:1px solid #bbb; margin-bottom:12px; background:#fafaff"></math-field>
                <button type="button" class="btn btn-success btn-sm" id="applyMathliveEditBtn">적용</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeMathliveEdit()">취소</button>
                <div id="mathliveEditError" style="color:crimson; min-height:24px;"></div>
            </div>


          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="applyAllFormulaFixes()">모든 수정 사항 반영</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
          </div>
        </div>
      </div>
    </div>


    <!-- Mathlive 입력(실험) -->
    <div>
      <label>Mathlive 수식 입력(테스트):</label>
      <math-field id="mathliveTest" virtual-keyboard-mode="manual" style="width:100%; min-height:40px; border:1px solid #ccc; padding:6px; margin-bottom:10px;"></math-field>
      <button type="button" onclick="copyMathliveToQuestion()">⬅️ 위 문제란에 복사</button>
    </div>

    <div id="mathlivePreview" style="background:#eef; min-height:32px; margin-bottom:8px; padding:5px 10px;"></div>
    <div id="mathliveError" style="color:crimson; min-height:20px;"></div>


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

    <!-- ✅ edit_problem.php 중 경로 선택 부분 수정 (6단계 드롭다운 + 기존값 세팅)-->
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



<script>
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
      // 하위 단계 초기화 👇
      for (let i = depth + 1; i <= 6; i++) {
        document.getElementById(`depth${i}`).innerHTML = `<option value="">- ${i}단계 선택 -</option>`;
      }
    });
}

function loadNextDepth(depth) {
  const selectedId = document.getElementById(`depth${depth}`).value;
  if (selectedId) loadDepthOptions(depth + 1, selectedId);
  updatePathTextAndId();
}

function updatePathTextAndId() {
  const names = [];
  let lastId = null;
  for (let i = 1; i <= 6; i++) {
    const sel = document.getElementById(`depth${i}`);
    if (sel.value) {
      names.push(sel.options[sel.selectedIndex].text);
      lastId = sel.value;
    }
  }
  document.getElementById('path_text').value = names.join('/');
  document.getElementById('path_id').value = lastId ?? '';
}

window.addEventListener('DOMContentLoaded', () => {
  const initialPathId = document.getElementById('path_id').value;
  if (initialPathId) {
    fetch(`get_path_tree_flat_paths.php`)
      .then(res => res.json())
      .then(flatPaths => {
        const pathMap = new Map();
        flatPaths.forEach(p => pathMap.set(p.id, p));
        const target = pathMap.get(parseInt(initialPathId));
        const pathIds = [];
        let current = target;
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
                  const o = document.createElement("option");
                  o.value = opt.id;
                  o.textContent = opt.name;
                  if (opt.id == id) o.selected = true;
                  sel.appendChild(o);
                });
              });
          });
        });
        promise.then(updatePathTextAndId);
      });
  } else {
    loadDepthOptions(1, null);
  }
});
</script>
   




    <button type="submit">수정 완료</button>
    <button type="button" onclick="confirmCopy()" style="background-color: orange;">복사 저장</button>
    <button type="button" onclick="previewProblem()">미리보기</button>
</form>

<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
let questionEditor, solutionEditor;
ClassicEditor.create(document.querySelector('textarea[name="question"]')).then(editor => questionEditor = editor);
ClassicEditor.create(document.querySelector('textarea[name="solution"]')).then(editor => solutionEditor = editor);


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
</script>

<script>
function handleSubmit() {
  // 1. CKEditor 데이터 반영
  document.querySelector('textarea[name="question"]').value = questionEditor.getData();
  document.querySelector('textarea[name="solution"]').value = solutionEditor.getData();

  // 2. 복사 아님 (수정 모드)
  document.getElementById('copyMode').value = '0';

  // 3. 사용자 확인
  return confirm('정말 수정하시겠습니까? (원본이 변경됩니다.)');
}
</script>

<!-- ✅ 이력 복원 테이블 UI -->
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
    $historyStmt = $conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
    $conn->set_charset('utf8mb4');
    $id = intval($problem['id']);
    $result = $conn->query("SELECT id, title, updated_at FROM history_problems WHERE problem_id = $id ORDER BY updated_at DESC");

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

    $conn->close();
    ?>
  </tbody>
</table>

<!-- 🔍 비교 결과 출력 영역 -->
<div id="diffResult" style="margin-top: 30px; padding: 15px; border: 1px solid #ccc; background-color: #f9f9f9; display: none;">
  <h3>🔍 변경된 필드</h3>
  <ul id="diffList"></ul>
</div>



<!-- ✅ 복원 로그 테이블 UI -->
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
    $conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
    $conn->set_charset('utf8mb4');
    $id = intval($problem['id']);
    $logResult = $conn->query("SELECT id, history_id, restored_by, restored_at FROM restore_log WHERE problem_id = $id ORDER BY restored_at DESC");

    while ($row = $logResult->fetch_assoc()) {
      echo "<tr>";
      echo "<td>{$row['id']}</td>";
      echo "<td>{$row['history_id']}</td>";
      echo "<td>" . htmlspecialchars($row['restored_by']) . "</td>";
      echo "<td>{$row['restored_at']}</td>";
      echo "</tr>";
    }

    $conn->close();
    ?>
  </tbody>
</table>




<script>
function restoreHistory(historyId) {
  if (!confirm("해당 시점으로 문제를 되돌리시겠습니까? (현재 내용은 이력으로 저장됩니다)")) return;

  fetch("restore_history.php", {
    method: "POST",
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ history_id: historyId })
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
    if (data.success) {
      location.reload();
    }
  })
  .catch(err => {
    alert("복원 요청 실패: " + err);
  });
}
</script>

<script>
function compareHistory(historyId) {
  fetch('get_history_diff.php?history_id=' + historyId)
    .then(res => res.json())
    .then(data => {
      const diffBox = document.getElementById('diffResult');
      const list = document.getElementById('diffList');
      list.innerHTML = '';

      if (!data.success || data.diff.length === 0) {
        list.innerHTML = '<li>차이가 없습니다. 동일한 내용입니다.</li>';
      } else {
        data.diff.forEach(d => {
          const li = document.createElement('li');
          li.innerHTML = `<strong>${d.field}</strong><br>
            <span style="color: red;">이전:</span> ${d.old}<br>
            <span style="color: green;">현재:</span> ${d.new}<br><br>`;
          list.appendChild(li);
        });
      }
      diffBox.style.display = 'block';
      diffBox.scrollIntoView({ behavior: 'smooth' });
    })
    .catch(err => {
      alert('비교 중 오류 발생: ' + err);
    });
}
</script>

<script>
function deleteHistory(historyId) {
  if (!confirm("정말 이 이력을 삭제하시겠습니까? 복원할 수 없습니다.")) return;

  fetch('delete_history.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ history_id: historyId })
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
    if (data.success) {
      location.reload();
    }
  })
  .catch(err => {
    alert("삭제 중 오류 발생: " + err);
  });
}

// path_id 입력 -> 드롭다운 자동 선택 (경로 트리 불러와서 자동 세팅)
function setPathById() {
  const targetId = parseInt(document.getElementById('manual_path_id').value);
  if (!targetId) {
    alert('경로 ID를 입력하세요.');
    return;
  }
  // 모든 드롭다운 먼저 초기화! (이 부분 추가)
  for (let i = 1; i <= 6; i++) {
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
      promise.then(() => {
        document.getElementById('path_id').value = targetId;
        updatePathTextAndId(); // 무조건 동기화
      });
    });
}


function copyMathliveToQuestion() {
    const math = document.getElementById('mathliveTest').value;
    // textarea에 LaTeX 코드로 입력
    document.querySelector('textarea[name="question"]').value = math;
    // CKEditor 쓰는 경우
    if (window.questionEditor) questionEditor.setData(math);
}

// Mathlive 입력 → 실시간 미리보기 & 간단 오류 감지
document.getElementById('mathliveTest').addEventListener('input', function(e) {
    const latex = e.target.value;
    // 미리보기
    document.getElementById('mathlivePreview').innerHTML = '$$' + latex + '$$';
    if (window.MathJax) MathJax.typesetPromise([document.getElementById('mathlivePreview')]);
    // 간단 오류 감지 (예시: \frac, 괄호 쌍 검사 등. 복잡한 건 나중에!)
    let errMsg = '';
    // 기본: 빈 값 오류 X
    if (latex.trim()) {
        // 예시: 중괄호 갯수 간단체크
        const left = (latex.match(/{/g) || []).length;
        const right = (latex.match(/}/g) || []).length;
        if (left !== right) errMsg = '중괄호 수가 맞지 않습니다.';
        // 기타 오류(추가 가능)
        if (/\\frac[^}]*$/.test(latex)) errMsg = '분수 명령의 인자가 부족합니다.';
    }
    document.getElementById('mathliveError').innerText = errMsg;
});

function extractAndCheckFormulas() {
  console.log('extractAndCheckFormulas 실행됨!');
  // 문제/해설 본문 추출
  const questionHTML = questionEditor.getData();
  const solutionHTML = solutionEditor.getData();
}
  // HTML에서 수식 추출 (예: \( ... \), $$ ... $$)
  // 1. 태그 제거
  function stripHtmlTags(html) {
    const div = document.createElement('div');
    div.innerHTML = html;
    return div.textContent || div.innerText || "";
  }

  // 2. 수식 추출 (정규식)
  function extractLatexAll(str, from = "") {
    let out = [];
    // $...$
    let reg1 = /\$([^\$]+)\$/g, m;
    while ((m = reg1.exec(str))) {
      out.push({ from, raw: m[0], latex: m[1], index: m.index });
    }
    // \( ... \)
    let reg2 = /\\\((.+?)\\\)/g;
    while ((m = reg2.exec(str))) {
      out.push({ from, raw: m[0], latex: m[1], index: m.index });
    }
    // \[ ... \]
    let reg3 = /\\\[(.+?)\\\]/g;
    while ((m = reg3.exec(str))) {
      out.push({ from, raw: m[0], latex: m[1], index: m.index });
    }
    // $$ ... $$
    let reg4 = /\$\$([^\$]+)\$\$/g;
    while ((m = reg4.exec(str))) {
      out.push({ from, raw: m[0], latex: m[1], index: m.index });
    }
    return out;
  }

  // 수식 모두 추출 (문제 + 해설)
  const questionText = stripHtmlTags(questionHTML);
  const solutionText = stripHtmlTags(solutionHTML);

  const questionFormulas = extractLatexAll(questionText, '문제');
  const solutionFormulas = extractLatexAll(solutionText, '해설');
  const formulas = [...questionFormulas, ...solutionFormulas];

  // 여기부터 하이라이트 부분
  let html = '';
  if (formulas.length === 0) {
    html = '<div style="color:gray;">수식을 찾지 못했습니다.</div>';
  } else {
    html = formulas.map((f, i) => {
      let nth = 1;
      for (let j = 0; j < i; j++) {
        if (formulas[j].from === f.from) nth++;
      }
      return `
        <div style="border-bottom:1px solid #eee; padding:8px 0;">
          <b>[${f.from} ${nth}]</b>
          <span style="color:navy;">${f.latex.replace(/</g,"&lt;")}</span>
          <button type="button" class="btn btn-sm btn-outline-success" style="margin-left:12px;"
            onclick="editFormulaWithMathlive('${encodeURIComponent(f.latex)}', '${f.from}', ${nth - 1})">수정</button>
        </div>
      `;
    }).join('');
  }

  document.getElementById('formulaErrorModalBody').innerHTML = html;

  // 팝업 띄우기
  const modalEl = document.getElementById('formulaErrorModal');
  const modal = new bootstrap.Modal(modalEl);
  modal.show();
}


let currentFormulaEdit = { index: null, latex: '', from: '' };

function editFormulaWithMathlive(latex, from, index) {
  // 1. 값 저장 (전역)
  currentFormulaEdit = {
    index: index,
    latex: decodeURIComponent(latex),
    from: from
  };
  // 2. Mathlive 에디터에 값 설정
  document.getElementById('mathliveEditField').value = currentFormulaEdit.latex;
  document.getElementById('mathliveEditError').innerText = '';
  // 3. 팝업 보이기
  document.getElementById('mathliveEditContainer').style.display = 'block';
  // 4. 스크롤 이동 (사용자 친화)
  setTimeout(() => {
    document.getElementById('mathliveEditField').focus();
  }, 200);
}


// 팝업 열기: 어떤 textarea(문제/해설)에서 호출했는지 기억
let currentTargetTextarea = null;

// 팝업 띄우기 함수
function openMathliveModalForTextarea(textareaName) {
    // textareaName: 'question' 또는 'solution'
    currentTargetTextarea = textareaName;
    // textarea 값 → mathlive popup에 복사
    document.getElementById('mathlivePopupField').value =
        document.querySelector('textarea[name="' + textareaName + '"]').value;
    // 팝업 보이기
    document.getElementById('mathliveModalOverlay').style.display = 'block';
    // 오류 안내 초기화
    document.getElementById('mathlivePopupError').innerText = '';
}

// 팝업 닫기 함수
function closeMathliveModal() {
    document.getElementById('mathliveModalOverlay').style.display = 'none';
    currentTargetTextarea = null;
}

// "수정 내용 적용" 버튼 → 수식 옮기기
// "수정 내용 적용" 버튼 클릭 시
const applyBtn = document.getElementById('applyMathlivePopupBtn');
if (applyBtn) {
    applyBtn.onclick = function() {
        const latex = document.getElementById('mathlivePopupField').value;
        let err = '';
        const left = (latex.match(/{/g) || []).length;
        const right = (latex.match(/}/g) || []).length;
        if (left !== right) err = '중괄호 수가 맞지 않습니다.';
        if (err) {
            document.getElementById('mathlivePopupError').innerText = err;
            return;
        }
  // 팝업에서 기억한 정보
  const from = currentFormula.from; // '문제' 또는 '해설'
  const nth = currentFormula.index; // 0,1,2... 번째(해당 영역 기준)

  // 바꿀 본문 추출
  let html = from === '문제' ? questionEditor.getData() : solutionEditor.getData();

  // nth번째 수식만 바꾼다!
  html = replaceNthLatex(html, from, nth, latex);

  // 반영 (에디터/textarea 동기화)
  if (from === '문제') {
    questionEditor.setData(html);
    document.querySelector('textarea[name="question"]').value = html;
  } else {
    solutionEditor.setData(html);
    document.querySelector('textarea[name="solution"]').value = html;
  }
  closeMathliveModal();
};





function closeMathliveEdit() {
  document.getElementById('mathliveEditContainer').style.display = 'none';
  currentFormulaEdit = { index: null, latex: '', from: '' };
}

document.getElementById('applyMathliveEditBtn').onclick = function() {
  const latex = document.getElementById('mathliveEditField').value;
  // 간단 오류 감지(예시: 중괄호)
  let err = '';
  const left = (latex.match(/{/g) || []).length;
  const right = (latex.match(/}/g) || []).length;
  if (left !== right) err = '중괄호 수가 맞지 않습니다.';
  if (err) {
    document.getElementById('mathliveEditError').innerText = err;
    return;
  }
  // 해당 리스트의 수식 텍스트 교체
  if (currentFormulaEdit.index !== null) {
    // 리스트에서 해당 인덱스의 span/텍스트를 찾아 변경
    // 수식 리스트는 formulaErrorModalBody 내에 있음
    const allItems = document.querySelectorAll('#formulaErrorModalBody > div');
    if (allItems[currentFormulaEdit.index]) {
      // 실제로는 <span> 태그가 들어가 있으니, 첫 <span> 찾아서 textContent만 교체
      const span = allItems[currentFormulaEdit.index].querySelector('span');
      if (span) span.textContent = latex;
    }
    // 값도 메모리에 저장
    currentFormulaEdit.latex = latex;
  }
  // 팝업 닫기
  closeMathliveEdit();
};

function applyAllFormulaFixes() {
  // 1. 모달 안의 모든 수식 항목(문제/해설) 수집
  const allItems = document.querySelectorAll('#formulaErrorModalBody > div');
  if (!allItems.length) {
    // 수식 없음 → 닫기만
    bootstrap.Modal.getInstance(document.getElementById('formulaErrorModal')).hide();
    return;
  }

  // 2. 기존 에디터 데이터(HTML)를 가져와 텍스트로 변환
  let questionHtml = questionEditor.getData();
  let solutionHtml = solutionEditor.getData();

  // 3. 각각의 리스트 항목을 반복하며, 원본 수식 부분을 교체
  allItems.forEach((div, i) => {
    const span = div.querySelector('span');
    const label = div.querySelector('b');
    if (!span || !label) return;
    const latex = span.textContent;
    // 어디(문제/해설)에서 온 수식인지 판별
    const from = label.textContent.replace(/[\[\]]/g, '').trim();
    // 정규식으로 교체 (조금 단순하게: 기존 수식 전체를 새로운 latex로 교체)
    if (from === '문제') {
      // questionHtml에서 해당 수식을 교체
      questionHtml = replaceNthLatex(questionHtml, i, latex, 'question');
    } else if (from === '해설') {
      solutionHtml = replaceNthLatex(solutionHtml, i, latex, 'solution');
    }
  });

  // 4. CKEditor, textarea 모두 반영
  questionEditor.setData(questionHtml);
  solutionEditor.setData(solutionHtml);
  document.querySelector('textarea[name="question"]').value = questionHtml;
  document.querySelector('textarea[name="solution"]').value = solutionHtml;

  // 5. 모달 닫기
  bootstrap.Modal.getInstance(document.getElementById('formulaErrorModal')).hide();
}

/**
 * HTML 내 n번째 LaTeX(\(...\), $$...$$, \[...\])를 새로운 latex로 교체
 * fieldType: 'question' | 'solution'
 */
function replaceNthLatex(html, from, nth, newLatex) {
  let idx = -1;
  // 정규식: \( ... \), \[ ... \], $$...$$, $...$
  const regex = /((\\\(|\\\[|\$\$|\$)(.*?)(\\\)|\\\]|\$\$|\$))/gs;
  return html.replace(regex, function(match, p1, start, latex, end) {
    idx++;
    if (idx === nth) {
      // latex 부분만 교체
      return start + newLatex + end;
    }
    return match;
  });
}

</script>


<!-- ✅ Mathlive 수식 편집 팝업 -->
<div id="mathliveModalOverlay" style="display:none; position:fixed; z-index:9999; left:0;top:0;width:100vw;height:100vh; background:rgba(0,0,0,0.3);">
  <div style="background:white; max-width:550px; margin:80px auto; padding:24px; border-radius:12px; box-shadow:0 4px 32px #0002; position:relative;">
    <h4>수식 수정(Mathlive)</h4>
    <math-field id="mathlivePopupField" virtual-keyboard-mode="manual" style="width:100%; min-height:44px; font-size:1.2em; border:1px solid #bbb; margin-bottom:18px; background:#fafaff"></math-field>
    <div style="margin-bottom:12px;">
      <button type="button" class="btn btn-primary" id="applyMathlivePopupBtn">수정 내용 적용</button>
      <button type="button" class="btn btn-secondary" onclick="closeMathliveModal()">닫기</button>
    </div>
    <div id="mathlivePopupError" style="color:crimson; min-height:24px;"></div>
  </div>
</div>


</body>
</html>

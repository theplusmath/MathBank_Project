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
      // 하위 단계 초기화
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
   
    <!-- 변경 전 -->
    

    <!-- 변경 후 -->
    <label>경로 ID: <input type="number" name="path_id" id="path_id" value="<?= (int)($problem['path_id'] ?? 0) ?>"></label><br>
    <br>




    <button type="button" onclick="confirmSubmit()">수정 완료</button>
    <button type="button" onclick="confirmCopy()" style="background-color: orange;">복사 저장</button>
    <button type="button" onclick="previewProblem()">미리보기</button>
</form>

<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
let questionEditor, solutionEditor;
ClassicEditor.create(document.querySelector('textarea[name="question"]')).then(editor => questionEditor = editor);
ClassicEditor.create(document.querySelector('textarea[name="solution"]')).then(editor => solutionEditor = editor);

function confirmSubmit() {
  if (confirm('정말 수정하시겠습니까? (원본이 변경됩니다.)')) {
    document.querySelector('textarea[name="question"]').value = questionEditor.getData();
    document.querySelector('textarea[name="solution"]').value = solutionEditor.getData();
    document.getElementById('copyMode').value = '0';
    document.getElementById('problemForm').submit();
  }
}

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
fetch('get_path_tree_flat_paths.php')
  .then(res => res.json())
  .then(data => {
    const map = {};
    data.forEach(item => {
      if (!map[item.depth]) map[item.depth] = [];
      map[item.depth].push(item);
    });

    function populate(select, items) {
      select.innerHTML = '<option value="">선택</option>';
      items.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.name;
        select.appendChild(opt);
      });
    }

    const curriculum = document.getElementById('curriculumSelect');
    const mainUnit = document.getElementById('mainUnitSelect');
    const midUnit = document.getElementById('midUnitSelect');
    const subUnit = document.getElementById('subUnitSelect');

    populate(curriculum, map[1] || []);

    curriculum.addEventListener('change', () => {
      const val = curriculum.value;
      const children = data.filter(p => p.parent_id == val);
      populate(mainUnit, children);
      populate(midUnit, []);
      populate(subUnit, []);
      updatePath();
    });

    mainUnit.addEventListener('change', () => {
      const val = mainUnit.value;
      const children = data.filter(p => p.parent_id == val);
      populate(midUnit, children);
      populate(subUnit, []);
      updatePath();
    });

    midUnit.addEventListener('change', () => {
      const val = midUnit.value;
      const children = data.filter(p => p.parent_id == val);
      populate(subUnit, children);
      updatePath();
    });

    subUnit.addEventListener('change', updatePath);

    function updatePath() {
      const id = subUnit.value || midUnit.value || mainUnit.value || curriculum.value;
      const names = [curriculum, mainUnit, midUnit, subUnit]
        .map(s => s.options[s.selectedIndex]?.textContent || '')
        .filter(n => n && n !== '선택');
      document.getElementById('path_id').value = id || '';
      document.getElementById('path_text').value = names.join('/');
    }

    // ✅ 기존 값 자동 설정
    const currentPathId = <?= (int)($problem['path_id'] ?? 0) ?>;
    if (currentPathId) {
      const current = data.find(p => p.id == currentPathId);
      if (current) {
        const pathChain = [];
        let temp = current;
        while (temp) {
          pathChain.unshift(temp);
          temp = data.find(p => p.id == temp.parent_id);
        }

        populate(curriculum, map[1] || []);
        curriculum.value = pathChain[0]?.id || '';
        const second = data.filter(p => p.parent_id == curriculum.value);
        populate(mainUnit, second);
        mainUnit.value = pathChain[1]?.id || '';
        const third = data.filter(p => p.parent_id == mainUnit.value);
        populate(midUnit, third);
        midUnit.value = pathChain[2]?.id || '';
        const fourth = data.filter(p => p.parent_id == midUnit.value);
        populate(subUnit, fourth);
        subUnit.value = pathChain[3]?.id || '';
        updatePath();
      }
    }
  });
</script>

</body>
</html>

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

$id = $_GET['id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM problems WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$problem = $result->fetch_assoc();
$conn->close();
if (!$problem) exit("문제를 찾을 수 없습니다.");

preg_match_all('/\$([^\$]+)\$/', $problem['question'], $matches, PREG_OFFSET_CAPTURE);
$formulas = $matches[1];      // [ [수식, offset], ... ]
$formulaRaw = $matches[0];    // [ ['$수식$', offset], ... ]
$originalText = $problem['question'];
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>수식 2열 편집기(실시간 그리드 재배치, 완전작동)</title>
  <script src="https://cdn.jsdelivr.net/npm/mathlive/dist/mathlive.min.js"></script>
  <style>
    body { font-family: 'Malgun Gothic', sans-serif; margin: 40px; background: #f8f9ff;}
    .problem-box { background: #fff; padding: 18px; margin-bottom: 16px; border-radius: 8px; }
    .problem-ta { width:100%; min-height:120px; font-size:1.05em; border-radius:8px; border:1px solid #ccc; padding:14px;}
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
    .formula-label { font-weight: bold; color: #2d3187; margin-bottom: 3px;}
    math-field { width: 100%; min-width: 60px; font-size: 1.16em; background: #fff; }
    .latex-label { font-size:0.97em; color:#555; margin-top: 7px;}
    .latex-input { width: 98%; font-size: 1em; margin-top:2px; }
    .apply-btn { margin-top: 10px; padding:5px 18px; background:#2957af; color:#fff; border:none; border-radius:6px; font-size:1em; cursor:pointer;}
    #saveBtn { margin:22px 0 0 0; padding:9px 26px; font-size:1.15em; border-radius:7px; border:none; background:#22a45e; color:#fff; font-weight: bold;}
    #saveBtn:disabled { background:#aaa; }
    @media (max-width: 900px) {
      .math-grid { grid-template-columns: 1fr; gap: 10px; }
    }
  </style>
</head>
<body>
<h2>모든 수식 직접 확인/수정 (2열, 실시간 재배치)</h2>
<div class="problem-box">
  <b>문제 본문 직접 수정</b><br>
  <textarea id="problemText" name="question" class="problem-ta"><?= htmlspecialchars($originalText) ?></textarea>
</div>

<form id="editForm" method="post" action="update_formula.php" onsubmit="return saveCheck();">
  <input type="hidden" name="id" value="<?= $problem['id'] ?>">
  <div class="math-grid" id="gridArea"></div>
  <input type="hidden" id="changedFlag" name="changedFlag" value="0">
  <button id="saveBtn" type="submit" disabled>최종 저장</button>
</form>

<script>
let originalText = `<?= str_replace(["\r","\n"], ["\\r", "\\n"], addslashes($originalText)) ?>`;
let formulas = <?= json_encode($formulas) ?>;
let formulaRaw = <?= json_encode($formulaRaw) ?>;
let total = formulas.length;
let changed = false;
let state = [];
for (let i = 0; i < total; i++) {
    state.push({
        index: i,
        formula: formulas[i][0],
        raw: formulaRaw[i][0],
        done: false
    });
}

// =============================
// [1] 동적으로 전체 그리드 렌더링
// =============================
function renderGrid() {
    const grid = document.getElementById('gridArea');
    grid.innerHTML = '';
    let visibleList = state.filter(f => !f.done);
    visibleList.forEach((item, idx) => {
        let html = `
        <div class="math-block" id="mathBlock${item.index}">
          <span class="formula-label">수식 ${item.index+1} 확인/수정</span>
          <math-field id="mf${item.index}" virtual-keyboard-mode="manual">${item.formula.replace(/</g,"&lt;").replace(/>/g,"&gt;")}</math-field>
          <span class="latex-label">LaTeX 코드 입력란:</span>
          <input type="text" id="latex${item.index}" class="latex-input" value="${item.formula.replace(/"/g,"&quot;")}">
          <button type="button" class="apply-btn" data-globalidx="${item.index}" data-nthvisible="${idx}">확인/적용</button>
        </div>`;
        grid.insertAdjacentHTML('beforeend', html);
    });

    // 동적 요소에 이벤트 바인딩(이벤트 위임)
    visibleList.forEach((item, idx) => {
        let mf = document.getElementById('mf'+item.index);
        let latex = document.getElementById('latex'+item.index);
        mf.addEventListener('input', function() { latex.value = mf.value; });
        latex.addEventListener('input', function() { mf.value = latex.value; });
    });

    // 모든 버튼에 이벤트 연결 (여기서 이벤트 위임도 가능)
    document.querySelectorAll('.apply-btn').forEach(btn => {
        btn.onclick = function() {
            let globalIdx = parseInt(this.getAttribute('data-globalidx'));
            let nthVisible = parseInt(this.getAttribute('data-nthvisible'));
            applyFix(globalIdx, nthVisible);
        }
    });

    updateSaveBtn();
}

// =============================
// [2] 수식 확인/적용
// =============================
// nthVisible: 현재 화면에 남아있는 편집창 중 몇 번째인지(0부터)
function applyFix(globalIdx, nthVisible) {
    let mf = document.getElementById('mf'+globalIdx);
    let latex = document.getElementById('latex'+globalIdx).value;
    let textarea = document.getElementById('problemText');
    let text = textarea.value;

    // 실제 $...$ 치환은 본문 전체에서, N번째(전체 수식 기준)만 교체
    let regex = /\$([^\$]+)\$/g, m, arr=[], lastIndex=0, resultText="", curIdx=0;
    while ((m = regex.exec(text)) !== null) {
        if (curIdx === globalIdx) {
            resultText += text.substring(lastIndex, m.index) + '$' + latex + '$';
            lastIndex = regex.lastIndex;
            changed = (m[0] !== ('$' + latex + '$')) ? true : changed;
        } else {
            resultText += text.substring(lastIndex, regex.lastIndex);
            lastIndex = regex.lastIndex;
        }
        curIdx++;
    }
    resultText += text.substring(lastIndex);
    textarea.value = resultText;

    // 해당 수식 상태 done 처리, 다음 렌더링
    state[globalIdx].done = true;
    renderGrid();
}

// =============================
// [3] 저장 버튼 활성화
// =============================
function updateSaveBtn() {
    let saveBtn = document.getElementById('saveBtn');
    let currentText = document.getElementById('problemText').value;
    let allDone = state.filter(f=>!f.done).length === 0;
    if(allDone && currentText !== originalText) {
      saveBtn.disabled = false;
      document.getElementById('changedFlag').value = "1";
    } else {
      saveBtn.disabled = true;
      document.getElementById('changedFlag').value = "0";
    }
}

// =============================
// [4] 저장 submit시, 변경 없으면 저장 막기
// =============================
function saveCheck() {
    if(document.getElementById('changedFlag').value === "1") {
      return true;
    } else {
      alert("수정된 내용이 없습니다.");
      return false;
    }
}

// =============================
// [5] 첫 진입시 전체 그리드 렌더링
// =============================
renderGrid();
</script>
</body>
</html>

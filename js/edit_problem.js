
let questionEditor, solutionEditor;
const DEPTH_COUNT = 6;
const SOURCE_DEPTH_COUNT = 6;


// edit_problem.js

function previewProblem() {
  window.open('view_problem.php?id=' + window.problem_id, '_blank');
}


window.addEventListener('DOMContentLoaded', function () {
   console.log('DOMContentLoaded 진입!');  // ← 추가
  // 1. 에디터 초기화
  ClassicEditor.create(document.querySelector('textarea[name="question"]'))
    .then(editor => { questionEditor = editor; });

  ClassicEditor.create(document.querySelector('textarea[name="solution"]'))
    .then(editor => { solutionEditor = editor; });

  // 2. 경로 드롭다운 초기화
  const initialPathId = document.getElementById('path_id').value;
  if (initialPathId && !isNaN(parseInt(initialPathId))) {
    setPathByIdFromValue(initialPathId);
  } else {
    loadDepthOptions(1, null);
  }

  // 3. 출처 경로 드롭다운 초기화
   console.log("출처 경로 드롭다운 초기화");  // ← 이 줄 추가
  const initialSourcePathId = document.getElementById('source_path_id').value;
  if (initialSourcePathId && !isNaN(parseInt(initialSourcePathId))) {
    setSourcePathByIdFromValue(initialSourcePathId);
  } else {
    setTimeout(() => loadSourcePathOptions(1, null), 300);
  }

  // 4. return_url 자동 세팅
  var referrer = document.referrer;
  var returnInput = document.getElementById('return_url');
  if (returnInput && !returnInput.value && referrer) {
      returnInput.value = referrer;
  }
  for (let i = 1; i <= DEPTH_COUNT; i++) showPathId(i);
  for (let i = 1; i <= SOURCE_DEPTH_COUNT; i++) showSourcePathId(i);
});



 
function confirmCopy() {
  if (confirm('수정한 내용을 복사하여 새 문제로 저장하시겠습니까?')) {
    document.querySelector('textarea[name="question"]').value = questionEditor.getData();
    document.querySelector('textarea[name="solution"]').value = solutionEditor.getData();
    document.getElementById('copyMode').value = '1';
    document.getElementById('problemForm').submit();
  }
}



function handleSubmit() {
  document.querySelector('textarea[name="question"]').value = questionEditor.getData();
  document.querySelector('textarea[name="solution"]').value = solutionEditor.getData();
  document.getElementById('copyMode').value = '0';
  return confirm('정말 수정하시겠습니까? (원본이 변경됩니다.)');
}


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



function applyFormulaEditPanel(field, idx) {
    let edits = window['formulaEdits_' + field];
    let mf = document.getElementById('mf_' + field + '_' + idx);
    let latex = document.getElementById('latex_' + field + '_' + idx).value;
    edits[idx].edited = latex;

    // 1. 본문 데이터 읽기
    let text;
    if (field === 'question' && typeof questionEditor !== 'undefined') {
        text = questionEditor.getData();
    } else if (field === 'solution' && typeof solutionEditor !== 'undefined') {
        text = solutionEditor.getData();
    } else if (document.getElementById(field + 'Area')) {
        text = document.getElementById(field + 'Area').value;
    } else {
        text = ''; // 예외 처리
    }

    // 2. 해당 수식만 원본 → 수정본으로 치환
    let plain = text.replace(edits[idx].raw, '$' + latex + '$');

    // 3. 본문 반영
    if (field === 'question' && typeof questionEditor !== 'undefined') {
        questionEditor.setData(plain);
    } else if (field === 'solution' && typeof solutionEditor !== 'undefined') {
        solutionEditor.setData(plain);
    } else if (document.getElementById(field + 'Area')) {
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
    } else if (field === 'solution' && typeof solutionEditor !== 'undefined') {
        text = solutionEditor.getData();
    } else if (document.getElementById(field + 'Area')) {
        text = document.getElementById(field + 'Area').value;
    } else {
        text = '';
    }

    // (기존) htmlTagRegex는 필요 없으면 빼도 됨
    let plain = text;
    edits.forEach(f => {
        if (!f) return;
        plain = plain.replace(f.raw, '$' + f.edited + '$');
    });

    // CKEditor 적용
    if (field === 'question' && typeof questionEditor !== 'undefined') {
        questionEditor.setData(plain);
    } else if (field === 'solution' && typeof solutionEditor !== 'undefined') {
        solutionEditor.setData(plain);
    } else if (document.getElementById(field + 'Area')) {
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

        // 경로 드롭다운 자동 펼침!
        const pathArea = document.getElementById('pathDropdownArea');
        const pathBtn = document.getElementById('togglePathDropdownBtn');
        if (pathArea && pathArea.style.display === 'none') {
            pathArea.style.display = 'block';
            if (pathBtn) pathBtn.innerText = '경로 드롭다운 닫기 ▲';
        }
      for (let i = 1; i <= DEPTH_COUNT; i++) showPathId(i);
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


function loadSourceNextDepth(depth) {
  for (let i = depth + 1; i <= SOURCE_DEPTH_COUNT; i++) {
    document.getElementById(`source_path${i}`).innerHTML = `<option value="">- ${i}단계 선택 -</option>`;
  }
  const selectedId = document.getElementById(`source_path${depth}`).value;
  if (selectedId) loadSourcePathOptions(depth + 1, selectedId);
  updateSourcePathTextAndId();
}


function loadSourcePathOptions(depth, parentId) {
  console.log('[디버그] loadSourcePathOptions 실행:', depth, parentId);
  fetch(`get_source_path.php?parent_id=${parentId ?? ''}`)
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById(`source_path${depth}`);
      console.log('select:', select, data);  // 이 줄 추가!
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

function setSourcePathById() {
    const id = parseInt(document.getElementById('manual_source_id').value);
    if (!id) {
        alert('출처 경로 ID를 입력하세요.');
        return;
    }

    // ↓↓↓ 이 부분이 핵심! 드롭다운 영역을 자동으로 엽니다.
    const area = document.getElementById('sourceDropdownArea');
    const btn = document.getElementById('toggleSourceDropdownBtn');
    if (area && area.style.display === 'none') {
        area.style.display = 'block';
        if (btn) btn.innerText = '출처 경로 드롭다운 닫기 ▲';
    }

    fetch('/get_source_path_by_id.php?id=' + id)
        .then(res => res.json())
        .then(pathArr => {
            if (!pathArr || !pathArr.length) {
                alert('경로를 찾을 수 없습니다.');
                return;
            }
            let promise = Promise.resolve();
            pathArr.forEach((pid, idx) => {
                promise = promise.then(() => {
                    const parentId = idx === 0 ? null : pathArr[idx - 1];
                    return fetch(`/get_source_path.php?parent_id=${parentId ?? ''}`)
                        .then(res => res.json())
                        .then(options => {
                            const sel = document.getElementById('source_path' + (idx + 1));
                            sel.innerHTML = `<option value="">- ${idx + 1}단계 선택 -</option>`;
                            options.forEach(opt => {
                                const o = document.createElement('option');
                                o.value = opt.id;
                                o.textContent = opt.name;
                                if (opt.id == pid) o.selected = true;
                                sel.appendChild(o);
                            });
                        });
                });
            });
            promise.then(() => {
                document.getElementById('source_path_id').value = id;
            });
        });
        for (let i = 1; i <= SOURCE_DEPTH_COUNT; i++) showSourcePathId(i);
}



// 실제 트리 자동 세팅 함수(이미 구현되어 있다면 이름만 맞추면 됨)
function setSourcePathByIdFromValue(sourceId) {
    // 이미 구현된 로직을 재사용: 
    // 1. source_id로부터 전체 트리 경로를 조회 (API/fetch로 배열 반환)
    // 2. 각 단계별 <select> value를 자동 세팅
    fetch('get_source_path_tree.php?id=' + sourceId)
      .then(res => res.json())
      .then(treeArr => {
        // treeArr: [1, 12, 35, 52] 등
        for (let d = 0; d < treeArr.length; d++) {
          const sel = document.getElementById('source_path' + (d + 1));
          if (sel) sel.value = treeArr[d];
          // 하위 옵션 자동 로드 등 필요한 부분 구현
        }
        // 마지막 단계 세팅 후, hidden 값도 동기화
        document.getElementById('source_path_id').value = sourceId;
      });
}


function toggleSourceDropdown() {
    const area = document.getElementById('sourceDropdownArea');
    const btn = document.getElementById('toggleSourceDropdownBtn');
    if (area.style.display === 'none') {
        area.style.display = 'block';
        btn.innerText = '출처 경로 드롭다운 닫기 ▲';
    } else {
        area.style.display = 'none';
        btn.innerText = '출처 경로 드롭다운으로 선택 ▼';
    }
}


function toggleSourceDropdown() {
    const area = document.getElementById('sourceDropdownArea');
    const btn = document.getElementById('toggleSourceDropdownBtn');
    if (area.style.display === 'none' || area.style.display === '') {
        area.style.display = 'block';
        btn.innerText = '출처 경로 드롭다운 닫기 ▲';
    } else {
        area.style.display = 'none';
        btn.innerText = '출처 경로 드롭다운으로 선택 ▼';
    }
}


function togglePathDropdown() {
    const area = document.getElementById('pathDropdownArea');
    const btn = document.getElementById('togglePathDropdownBtn');
    if (area.style.display === 'none' || area.style.display === '') {
        area.style.display = 'block';
        btn.innerText = '경로 드롭다운 닫기 ▲';
    } else {
        area.style.display = 'none';
        btn.innerText = '경로 드롭다운으로 선택 ▼';
    }
}

function showPathId(depth) {
    const sel = document.getElementById('depth' + depth);
    const span = document.getElementById('depth' + depth + '_id');
    if (sel && span) {
        const val = sel.value;
        span.textContent = val ? 'ID: ' + val : '';
    }
}

function showSourcePathId(depth) {
    const sel = document.getElementById('source_path' + depth);
    const span = document.getElementById('source_path' + depth + '_id');
    if (sel && span) {
        const val = sel.value;
        span.textContent = val ? 'ID: ' + val : '';
    }
}

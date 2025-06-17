// functions.js

// === 전역 데이터 ===
let flatPaths = [];  // 경로 트리 정보 저장용

// ? 메시지 출력
function showMessage(text, type = 'success') {
  const msg = document.getElementById('message');
  if (msg) {
    msg.textContent = text;
    msg.className = type;
  }
}

// ? 경로 트리 불러오기
function fetchPathTreeFlat() {
  return fetch('get_path_tree_flat_paths.php')
    .then(res => res.json())
    .then(data => {
      flatPaths = data;
      populateDropdowns();
    })
    .catch(err => {
      showMessage('? 경로 불러오기 실패: ' + err, 'error');
    });
}

// ? 경로 드롭다운 초기화
function populateDropdowns() {
  for (let i = 1; i <= 6; i++) {
    const select = document.getElementById('path' + i);
    if (select) {
      select.innerHTML = '<option value="">선택</option>';
      select.addEventListener('change', () => updateChildDropdowns(i));
    }
  }
  updateChildDropdowns(0);
}

// ? 하위 드롭다운 갱신
function updateChildDropdowns(level) {
  const parentId = level === 0 ? 0 : getSelectedPathId(level);
  const children = flatPaths.filter(p =>
    (parentId == 0 || parentId == null) ? p.parent_id == null : p.parent_id == parentId
  );

  if (level >= 6) return;

  const target = document.getElementById('path' + (level + 1));
  if (target) {
    target.innerHTML = '<option value="">선택</option>';
    children.forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.innerText = p.name;
      target.appendChild(opt);
    });
  }

  // 이후 레벨 드롭다운 초기화
  for (let i = level + 2; i <= 6; i++) {
    const sel = document.getElementById('path' + i);
    if (sel) sel.innerHTML = '<option value="">선택</option>';
  }

  updatePathText();
}

// ? 선택된 경로 ID 가져오기
function getSelectedPathId(level) {
  return document.getElementById('path' + level)?.value || 0;
}

// ? 경로 텍스트 자동 생성
function updatePathText() {
  const names = [];
  for (let i = 1; i <= 6; i++) {
    const sel = document.getElementById('path' + i);
    const txt = sel?.options[sel.selectedIndex]?.text;
    if (txt && txt !== '선택') names.push(txt);
  }
  const pathInput = document.getElementById('path_text');
  if (pathInput) {
    pathInput.value = names.join('~');
  }
}

// ? 선생님 목록 불러오기 (복사자 select)
function loadTeacherOptions() {
  fetch('get_teachers.php')
    .then(res => res.json())
    .then(data => {
      const sel = document.getElementById('copied_by_select');
      if (sel) {
        sel.innerHTML = '<option value="">선택</option>';
        data.forEach(t => {
          const opt = document.createElement('option');
          opt.value = t.id;
          opt.textContent = t.name;
          sel.appendChild(opt);
        });
      }
    })
    .catch(err => {
      console.error('선생님 목록 불러오기 실패:', err);
    });
}

// ? 문제 수정 시: 폼 자동 채우기 (데이터 바인딩용)
function populateFormFields(data) {
  for (const key in data) {
    const el = document.getElementById(key);
    if (el) {
      el.value = data[key];
    }
  }
  updatePathText(); // 경로 텍스트 갱신
}

// 사용자가 path_id를 직접 입력하면 드롭다운 자동 반영
function applyPathIdToDropdowns(pathId) {
  const pathMap = new Map(flatPaths.map(p => [p.id, p]));
  const selected = [];

  // pathId를 따라 위로 올라가며 경로 구성
  let current = pathMap.get(parseInt(pathId));
  while (current) {
    selected.unshift(current.id); // 앞에 추가
    current = pathMap.get(current.parent_id);
  }

  // 드롭다운 선택 반영
  selected.forEach((id, i) => {
    const sel = document.getElementById('path' + (i + 1));
    if (sel) {
      sel.value = id;
      updateChildDropdowns(i + 1); // 다음 레벨 채우기
    }
  });

  updatePathText(); // path_text 갱신
}


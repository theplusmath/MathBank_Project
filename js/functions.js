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




// ✅ 반드시 함수 밖에서 독립적으로 선언!
function updateSourcePathText() {
  const names = [];
  for (let i = 1; i <= 6; i++) {
    const sel = document.getElementById('source_path' + i);
    const txt = sel?.options[sel.selectedIndex]?.text;
    if (txt && txt !== '선택') names.push(txt);
  }
  const pathInput = document.getElementById('source_path_text');
  if (pathInput) {
    pathInput.value = names.join('~');
  }
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

  async function applyPathIdToDropdowns(pathId) {
      if (!pathId) return;
      const pathMap = new Map(flatPaths.map(p => [p.id, p]));
      const selected = [];
      let current = pathMap.get(parseInt(pathId));
      while (current) {
        selected.unshift(current.id);
        current = pathMap.get(current.parent_id);
      }
      // 각 단계별로 select 값 설정, 하위 드롭다운 로딩
      for (let i = 0; i < selected.length; i++) {
        const sel = document.getElementById('path' + (i + 1));
        if (sel) {
          sel.value = selected[i];
          // 하위 드롭다운 채우기
          await new Promise(resolve => {
            updateChildDropdowns(i + 1);
            setTimeout(resolve, 100); // DOM 반영을 위해 약간 대기
          });
        }
      }
      updateManualPathId();
      updatePathText();
    }



// 2. manual_path_id input에 값이 입력되면, 드롭다운을 해당 id에 맞게 자동 선택
function syncDropdownsToManualPathId() {
  const pathId = document.getElementById('manual_path_id')?.value;
  if (!pathId) return;
  applyPathIdToDropdowns(pathId);
  setTimeout(() => {
    updateManualPathId();
    updatePathText();
  }, 300); // 비동기 처리 보장
}

// 3. 드롭다운 바뀔 때마다 manual_path_id, path_text 모두 동기화
function onPathDropdownChange(level) {
  updateChildDropdowns(level);
  updateManualPathId();
  updatePathText();
}


function updateManualPathId() {
  let lastId = '';
  for (let i = 6; i >= 1; i--) {
    const sel = document.getElementById('path' + i);
    if (sel && sel.value) {
      lastId = sel.value;
      break;
    }
  }
  // manual_path_id input에 값 자동 입력
  const input = document.getElementById('manual_path_id');
  if (input) input.value = lastId;

  // source_path_id hidden input에도 값 자동 입력
  const sourcePathInput = document.getElementById('source_path_id');
  if (sourcePathInput) sourcePathInput.value = lastId;
}





// source_path 드롭다운 초기화
function populateSourceDropdowns() {
  for (let i = 1; i <= 6; i++) {
    const select = document.getElementById('source_path' + i);
    if (select) {
      select.innerHTML = '<option value="">선택</option>';
      select.addEventListener('change', () => updateSourceChildDropdowns(i));
    }
  }
  updateSourceChildDropdowns(0);
}







// source_path 하위 드롭다운 갱신 (반드시 AJAX 사용)
function updateSourceChildDropdowns(level) {
  const parentId = level === 0 ? '' : getSelectedSourcePathId(level);
  fetch('get_source_path.php?parent_id=' + parentId)
    .then(res => res.json())
    .then(children => {
      console.log('받아온 children:', children);   // <-- 추가
      if (level >= 6) return;
      const target = document.getElementById('source_path' + (level + 1));
      if (target) {
        target.innerHTML = '<option value="">선택</option>';
        children.forEach(p => {
          console.log('옵션 추가:', p.id, p.name); // <-- 추가
          const opt = document.createElement('option');
          opt.value = p.id;
          opt.innerText = p.name;
          target.appendChild(opt);
        });
      }
      // 이후 레벨 드롭다운 초기화
      for (let i = level + 2; i <= 6; i++) {
        const sel = document.getElementById('source_path' + i);
        if (sel) sel.innerHTML = '<option value="">선택</option>';
      }
      updateSourcePathId();
      updateSourcePathText && updateSourcePathText(); // (선택) source_path_text도 자동 업데이트
    });
}



// source_path 선택된 id 반환
function getSelectedSourcePathId(level) {
  return document.getElementById('source_path' + level)?.value || '';
}

// source_path_id 자동 입력
function updateSourcePathId() {
  let lastId = '';
  for (let i = 6; i >= 1; i--) {
    const sel = document.getElementById('source_path' + i);
    if (sel && sel.value) {
      lastId = sel.value;
      break;
    }
  }
  const input = document.getElementById('source_path_id');
  if (input) input.value = lastId;
}




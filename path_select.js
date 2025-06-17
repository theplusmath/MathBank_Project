// path_select.js

document.addEventListener("DOMContentLoaded", async () => {
  const pathSelectorsDiv = document.getElementById('pathSelectors');
  const pathTextInput = document.getElementById('path_text');

  let allPaths = [];

  try {
    const res = await fetch('get_path_tree_flat_paths.php');
    allPaths = await res.json();
  } catch (error) {
    console.error('경로 데이터를 불러오지 못했습니다.', error);
    return;
  }

  const depthLabels = ['교육과정', '수학 구분', '교과서', '대단원', '중단원', '소단원'];
  const selectors = [];

  for (let i = 0; i < 6; i++) {
    const select = document.createElement('select');
    select.dataset.depth = i;
    select.innerHTML = `<option value="">-- ${depthLabels[i]} 선택 --</option>`;
    pathSelectorsDiv.appendChild(select);
    selectors.push(select);

    select.addEventListener('change', () => handleChange(i));
  }

  // ✔ 수정된 루트 경로 필터: parent_id가 null 또는 undefined인 것
  const roots = allPaths.filter(p => p.parent_id == null);
  roots.sort((a, b) => a.sort_order - b.sort_order);
  roots.forEach(root => {
    const option = document.createElement('option');
    option.value = root.id;
    option.textContent = root.name;
    selectors[0].appendChild(option);
  });

  function handleChange(depth) {
    const selectedId = selectors[depth].value;

    // 하위 셀렉트 초기화
    for (let i = depth + 1; i < 6; i++) {
      selectors[i].innerHTML = `<option value="">-- ${depthLabels[i]} 선택 --</option>`;
    }

    if (!selectedId) {
      updatePathText();
      return;
    }

    const children = allPaths.filter(p => p.parent_id == selectedId);
    children.sort((a, b) => a.sort_order - b.sort_order);

    if (children.length > 0 && depth + 1 < 6) {
      children.forEach(child => {
        const option = document.createElement('option');
        option.value = child.id;
        option.textContent = child.name;
        selectors[depth + 1].appendChild(option);
      });
    }

    updatePathText();
  }

  function updatePathText() {
    const names = [];

    selectors.forEach(sel => {
      const selectedOption = sel.options[sel.selectedIndex];
      if (sel.value && selectedOption) {
        names.push(selectedOption.textContent);
      }
    });

    pathTextInput.value = names.join('/');
  }
});

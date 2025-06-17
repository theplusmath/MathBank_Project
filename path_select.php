<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$curriculums = [
  '2022개정' => [
    '고등수학' => [
      '수학(상)' => [
        '다항식' => [
          '항과 계수',
          '다항식의 연산'
        ],
        '방정식과 부등식' => [
          '일차방정식',
          '이차방정식'
        ]
      ]
    ]
  ],
  '2015개정' => [
    '고등수학' => [
      '수학 I' => [
        '지수함수',
        '로그함수'
      ],
      '수학 II' => [
        '삼각함수',
        '수열'
      ]
    ]
  ]
];
?>

<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>6단계 경로 선택</title>
  <style>
    select { margin: 5px; }
  </style>
</head>
<body>
  <h2>경로 선택</h2>
  <div>
    <?php
    $depthLabels = ["교육과정", "고등수학/중등수학", "교과서", "대단원", "중단원", "소단원"];
    for ($i = 0; $i < 6; $i++) {
      echo "<select id='depth" . ($i+1) . "' onchange='updateDepth(" . ($i+1) . ")'>";
      echo "<option value=' '>" . $depthLabels[$i] . "</option>";
      if ($i === 0) {
        foreach ($curriculums as $key => $val) {
          echo "<option value='$key'>$key</option>";
        }
      }
      echo "</select>";
    }
    ?>
  </div>

  <div style="margin-top: 20px">
    <select id="targetDepth">
      <?php foreach ($depthLabels as $index => $label): ?>
        <option value="<?= $index ?>"><?= $label ?></option>
      <?php endforeach; ?>
    </select>
    <button onclick="addOption()">추가</button>
    <button onclick="deleteOption()">삭제</button>
    <button onclick="renameOption()">이름 바꾸기</button>
  </div>

  <script>
    const data = <?php echo json_encode($curriculums, JSON_UNESCAPED_UNICODE); ?>;

    function updateDepth(level) {
      const depthIds = ["depth1", "depth2", "depth3", "depth4", "depth5", "depth6"];
      const depthLabels = ["교육과정", "고등수학/중등수학", "교과서", "대단원", "중단원", "소단원"];
      const values = depthIds.map(id => document.getElementById(id).value);

      for (let i = level; i < depthIds.length; i++) {
        document.getElementById(depthIds[i]).innerHTML = '<option value="">' + depthLabels[i] + '</option>';
      }

      let ref = data;
      for (let i = 0; i < level; i++) {
        if (values[i] && ref[values[i]]) {
          ref = ref[values[i]];
        } else {
          ref = null;
          break;
        }
      }

      if (ref && typeof ref === 'object' && !Array.isArray(ref)) {
        const nextSelect = document.getElementById(depthIds[level]);
        nextSelect.innerHTML = '<option value="">' + depthLabels[level] + '</option>';
        for (let key in ref) {
          nextSelect.innerHTML += `<option value="${key}">${key}</option>`;
        }
      } else if (Array.isArray(ref)) {
        const nextSelect = document.getElementById(depthIds[level]);
        nextSelect.innerHTML = '<option value="">' + depthLabels[level] + '</option>';
        for (let item of ref) {
          nextSelect.innerHTML += `<option value="${item}">${item}</option>`;
        }
      }
    }

    function addOption() {
      const index = parseInt(document.getElementById("targetDepth").value);
      const select = document.getElementById("depth" + (index + 1));
      const newItem = prompt("추가할 항목 이름을 입력하세요:");
      if (newItem) {
        const option = document.createElement("option");
        option.value = newItem;
        option.text = newItem;
        select.add(option);
        alert("추가되었습니다.");
      }
    }

    function deleteOption() {
      const index = parseInt(document.getElementById("targetDepth").value);
      const select = document.getElementById("depth" + (index + 1));
      if (select.selectedIndex > 0) {
        const removed = select.options[select.selectedIndex].text;
        select.remove(select.selectedIndex);
        alert("삭제되었습니다: " + removed);
      } else {
        alert("삭제할 항목을 선택하세요.");
      }
    }

    function renameOption() {
      const index = parseInt(document.getElementById("targetDepth").value);
      const select = document.getElementById("depth" + (index + 1));
      if (select.selectedIndex > 0) {
        const newName = prompt("새 이름을 입력하세요:");
        if (newName) {
          select.options[select.selectedIndex].text = newName;
          select.options[select.selectedIndex].value = newName;
          alert("이름이 변경되었습니다.");
        }
      } else {
        alert("이름을 바꿀 항목을 선택하세요.");
      }
    }
  </script>
</body>
</html>

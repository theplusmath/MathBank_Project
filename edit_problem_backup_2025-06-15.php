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

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/mathlive/dist/mathlive.core.css">
    <link rel="stylesheet" href="https://unpkg.com/mathlive/dist/mathlive.css">
    <script src="https://unpkg.com/mathlive/dist/mathlive.min.js"></script>


</head>
<body>

<h1>문제 수정</h1>

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
    <button type="button" onclick="extractAndCheckFormulas()" class="btn btn-outline-danger" style="margin-bottom: 10px;">
        수식 오류 검사 및 수정
    </button>
    <div class="modal fade" id="formulaErrorModal" tabindex="-1" aria-labelledby="formulaErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formulaErrorModalLabel">수식 오류 검사 및 수정</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="formulaErrorModalBody">
                    <div id="dynamicFormulaListArea"></div> <div id="mathliveEditContainer" style="margin-top: 18px; display:none;">
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
        $historyConn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath'); // 변수명 변경
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

        $historyConn->close(); // 닫는 변수명도 변경
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
        $logConn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath'); // 변수명 변경
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

        $logConn->close(); // 닫는 변수명도 변경
        ?>
    </tbody>
</table>


<script>

window.addEventListener('DOMContentLoaded', function() {

    let questionEditor, solutionEditor;
    // CKEditor 초기화
    ClassicEditor.create(document.querySelector('textarea[name="question"]'))
        .then(editor => {
            questionEditor = editor;
        })
        .catch(error => {
            console.error('There was an error initializing the question editor:', error);
        });

    ClassicEditor.create(document.querySelector('textarea[name="solution"]'))
        .then(editor => {
            solutionEditor = editor;
        })
        .catch(error => {
            console.error('There was an error initializing the solution editor:', error);
        });

    // MathLive 에디터에서 현재 편집 중인 수식 정보를 저장하는 전역 변수
    let currentFormulaEdit = { index: null, latex: '', from: '' };


    // MathLive 에디터 열기 함수 (모달 내 "수정" 버튼 클릭 시 호출됨)
    window.editFormulaWithMathlive = function(latex, from, index) {
        currentFormulaEdit = { index: index, latex: decodeURIComponent(latex), from: from };
        document.getElementById('mathliveEditError').innerText = ''; // 오류 메시지 초기화

        // MathLive 에디터 컨테이너를 보이게 설정
        document.getElementById('mathliveEditContainer').style.display = 'block';

        // MathLive 에디터 필드에 수식 입력 (비동기 처리)
        // MathLive 컴포넌트가 DOM에 완전히 렌더링된 후 값을 설정하도록 setTimeout 사용
        setTimeout(function() {
            const mf = document.getElementById('mathliveEditField');
            if (mf) {
                // MathLive 필드의 setValue 메서드가 있다면 사용 (MathLive 최신 버전 호환)
                if (typeof mf.setValue === "function") {
                    mf.setValue(currentFormulaEdit.latex);
                } else {
                    // 없다면 일반 input/textarea처럼 value 속성 사용 (구식 MathLive 또는 다른 컴포넌트)
                    mf.value = currentFormulaEdit.latex;
                }
                mf.focus(); // 에디터에 포커스
            } else {
                console.error('Error: mathliveEditField element not found!');
                alert('수식 편집 필드를 찾을 수 없습니다.');
            }
        }, 100); // 짧은 지연을 주어 DOM 렌더링을 기다림
    };


    // 수식 오류 검사 및 수정 모달 열기 함수
window.extractAndCheckFormulas = function() {
    console.log('extractAndCheckFormulas 실행됨!');
    if (!questionEditor || !solutionEditor) {
        alert("CKEditor가 아직 로드되지 않았습니다. 잠시 후 다시 시도해주세요.");
        return;
    }

    const questionHTML = questionEditor.getData();
    const solutionHTML = solutionEditor.getData();
    const questionText = stripHtmlTags(questionHTML);
    const solutionText = stripHtmlTags(solutionHTML);

    const questionFormulas = extractLatexAll(questionText, '문제');
    const solutionFormulas = extractLatexAll(solutionText, '해설');
    const formulas = [...questionFormulas, ...solutionFormulas];

    let html = '';
    if (formulas.length === 0) {
        html = '<div style="color:gray;">수식을 찾지 못했습니다.</div>';
    } else {
        html = formulas.map((f, i) => {
            let nth = 1;
            // 해당 영역(문제/해설) 내에서 몇 번째 수식인지 계산 (이전 수식 중에서 동일 `from`을 가진 수식의 개수)
            for (let j = 0; j < i; j++) {
                if (formulas[j].from === f.from) nth++;
            }
            return `
                <div style="border-bottom:1px solid #eee; padding:8px 0;">
                    <b>[${f.from} ${nth}]</b>
                    <span data-original-latex="${encodeURIComponent(f.latex)}"
                          data-formula-index-in-list="${i}"
                          style="color:navy;">${f.latex.replace(/</g,"&lt;")}</span>
                    <button type="button" class="btn btn-sm btn-outline-success" style="margin-left:12px;"
                        onclick="window.editFormulaWithMathlive('${encodeURIComponent(f.latex)}', '${f.from}', ${i})">수정</button>
                </div>
            `;
        }).join('');
    }

    // ★★★ 이 줄을 수정합니다. formulaErrorModalBody 대신 dynamicFormulaListArea에만 내용을 넣습니다. ★★★
    document.getElementById('dynamicFormulaListArea').innerHTML = html;

    // MathLive 에디터 컨테이너는 기본적으로 숨김
    const mathliveEditContainer = document.getElementById('mathliveEditContainer');
    if (mathliveEditContainer) {
        mathliveEditContainer.style.display = 'none';
    }
    document.getElementById('mathliveEditError').innerText = ''; // 모달 열 때 에러 메시지 초기화

    // Bootstrap 모달 객체를 생성하고 표시
    const modalEl = document.getElementById('formulaErrorModal');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
};
    // MathLive 수식 편집 취소 함수
    window.closeMathliveEdit = function() { // 전역으로 노출
        document.getElementById('mathliveEditContainer').style.display = 'none';
        currentFormulaEdit = { index: null, latex: '', from: '' };
        document.getElementById('mathliveEditError').innerText = ''; // 취소 시 오류 메시지 초기화
    }

    // MathLive 에디터 "적용" 버튼 클릭 시
    document.getElementById('applyMathliveEditBtn').onclick = function() {
        const mathliveEditField = document.getElementById('mathliveEditField');
        // MathLive 컴포넌트의 값 가져오기 (setValue/getValue 메서드 우선, 그 다음 value 속성)
        const latex = typeof mathliveEditField.getValue === "function" ? mathliveEditField.getValue() : mathliveEditField.value;

        let err = '';
        const left = (latex.match(/{/g) || []).length;
        const right = (latex.match(/}/g) || []).length;
        if (left !== right) err = '중괄호 수가 맞지 않습니다.';
        if (err) {
            document.getElementById('mathliveEditError').innerText = err;
            return;
        }

        // 현재 편집 중인 수식 정보 (currentFormulaEdit)가 있다면
        if (currentFormulaEdit.index !== null) {
            const allItems = document.querySelectorAll('#dynamicFormulaListArea > div');
            if (allItems[currentFormulaEdit.index]) {
                const span = allItems[currentFormulaEdit.index].querySelector('span');
                if (span) span.textContent = latex; // span 태그의 텍스트 내용을 새 수식으로 교체
            }
            // 현재 편집 중인 수식 객체의 'latex' 값도 업데이트
            currentFormulaEdit.latex = latex;
        }
        closeMathliveEdit();
    };

    // 모든 수식 수정 사항을 실제 문제/해설 내용에 반영하는 함수
    window.applyAllFormulaFixes = function() { // 전역으로 노출
        const allItems = document.querySelectorAll('#dynamicFormulaListArea > div');
        if (!allItems.length) {
            bootstrap.Modal.getInstance(document.getElementById('formulaErrorModal')).hide();
            return;
        }

        let questionHtml = questionEditor.getData();
        let solutionHtml = solutionEditor.getData();

        // **수식 교체 로직 개선:**
        // 원본 HTML에서 LaTeX 수식 패턴을 찾고, 해당 패턴을 수정된 LaTeX로 교체합니다.
        // CKEditor는 내부적으로 HTML을 구성하기 때문에 단순히 텍스트를 교체하는 것이 아니라,
        // MathJax/CKEditor가 인식하는 LaTeX 구문 자체를 찾아 바꾸는 방식이 더 안전합니다.
        // 여기서는 `extractLatexAll`을 통해 원본 수식과 인덱스를 다시 매칭하여 교체합니다.

        let originalQuestionContent = stripHtmlTags(questionEditor.getData());
        let originalSolutionContent = stripHtmlTags(solutionEditor.getData());

        // 문제 및 해설 본문의 원본 수식들을 다시 추출하여 배열에 저장 (매칭용)
        const originalQuestionFormulas = extractLatexAll(originalQuestionContent, '문제');
        const originalSolutionFormulas = extractLatexAll(originalSolutionContent, '해설');

        allItems.forEach((div, i) => {
            const span = div.querySelector('span');
            const label = div.querySelector('b');
            if (!span || !label) return;

            const newLatex = span.textContent; // 모달에서 수정된 새 수식
            const fromType = label.textContent.replace(/[\[\]]/g, '').split(' ')[0].trim(); // "문제" 또는 "해설"

            if (fromType === '문제') {
                // `i` 인덱스는 모달 리스트에서의 순서. 이를 이용해 원래 문제의 수식을 찾아 교체
                // 이 인덱스가 정확히 원본 텍스트의 몇 번째 수식인지를 가리킨다고 가정합니다.
                // (extractAndCheckFormulas에서 생성될 때 인덱스 `i`를 사용했으므로)
                const originalFormula = originalQuestionFormulas[i]; // i는 `formulas` 배열의 전체 인덱스이므로, fromType이 '문제'일 때만 해당하도록 로직 변경 필요.
                // **개선된 매칭 로직:** fromType과 nth 값을 이용해 정확한 원본 수식을 찾아야 합니다.
                // 모달의 `editFormulaWithMathlive` 호출 시 `i` (전체 formulas 배열의 인덱스)를 넘겼으므로,
                // `applyAllFormulaFixes`에서는 이 `i`를 사용하여 `formulas` 배열에 접근해야 합니다.
                const originalRawText = `\$${originalFormula.latex}\$`; // 예시: $...$ 형태로 가정

                // 실제 교체는 원본 `questionHtml`에서 진행
                // 정규식으로 안전하게 교체
                 questionHtml = questionHtml.split(originalFormula.raw).join(originalFormula.raw.replace(originalFormula.latex, newLatex));

            } else if (fromType === '해설') {
                 // 마찬가지로 originalSolutionFormulas에서 해당 수식을 찾아서 교체
                 const originalFormula = originalSolutionFormulas[i - originalQuestionFormulas.length]; // '해설' 수식은 '문제' 수식 개수만큼 인덱스에서 빼줘야 함.
                 solutionHtml = solutionHtml.split(originalFormula.raw).join(originalFormula.raw.replace(originalFormula.latex, newLatex));
            }
        });
        
        // CKEditor와 textarea 모두에 반영
        questionEditor.setData(questionHtml);
        solutionEditor.setData(solutionHtml);
        document.querySelector('textarea[name="question"]').value = questionHtml;
        document.querySelector('textarea[name="solution"]').value = solutionHtml;

        bootstrap.Modal.getInstance(document.getElementById('formulaErrorModal')).hide(); // 모달 닫기
    }

    // `replaceNthLatex` 함수는 이제 직접 사용되지 않으므로 제거하거나 주석 처리합니다.
    /*
    function replaceNthLatex(html, from, nth, newLatex) {
        let idx = -1;
        const regex = /((\\\(|\\\[|\$\$|\$)(.*?)(\\\)|\\\]|\$\$|\$))/gs;
        return html.replace(regex, function(match, fullMatch, startDelimiter, latexContent, endDelimiter) {
            idx++;
            if (idx === nth) {
                return startDelimiter + newLatex + endDelimiter;
            }
            return match;
        });
    }
    */


    // 초기 Path 선택 드롭다운 로드
    loadDepthOptions(1, null);


    // 기타 함수들 (위치 경로 선택, CKEditor 동기화, 미리보기 등)
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

    // 페이지 로드 시 기존 path_id에 따라 드롭다운 자동 선택
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

    function stripHtmlTags(html) {
        const div = document.createElement('div');
        div.innerHTML = html;
        return div.textContent || div.innerText || "";
    }

    function extractLatexAll(str, from = "") {
        let out = [];
        // $...$ (인라인 수식)
        let reg1 = /\$([^\$]+)\$/g, m;
        while ((m = reg1.exec(str))) {
            out.push({ from, raw: m[0], latex: m[1], index: m.index });
        }
        // \( ... \) (인라인 수식)
        let reg2 = /\\\((.+?)\\\)/g;
        while ((m = reg2.exec(str))) {
            out.push({ from, raw: m[0], latex: m[1], index: m.index });
        }
        // \[ ... \] (블록 수식)
        let reg3 = /\\\[(.+?)\\\]/g;
        while ((m = reg3.exec(str))) {
            out.push({ from, raw: m[0], latex: m[1], index: m.index });
        }
        // $$ ... $$ (블록 수식)
        let reg4 = /\$\$([^\$]+)\$\$/g;
        while ((m = reg4.exec(str))) {
            out.push({ from, raw: m[0], latex: m[1], index: m.index });
        }
        return out;
    }

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

    function setPathById() {
        const targetId = parseInt(document.getElementById('manual_path_id').value);
        if (!targetId) {
            alert('경로 ID를 입력하세요.');
            return;
        }
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
                    updatePathTextAndId();
                });
            });
    }


    function copyMathliveToQuestion() {
        const math = document.getElementById('mathliveTest').value;
        document.querySelector('textarea[name="question"]').value = math;
        if (window.questionEditor) questionEditor.setData(math);
    }

    document.getElementById('mathliveTest').addEventListener('input', function(e) {
        const latex = e.target.value;
        document.getElementById('mathlivePreview').innerHTML = '$$' + latex + '$$';
        if (window.MathJax) MathJax.typesetPromise([document.getElementById('mathlivePreview')]);
        let errMsg = '';
        if (latex.trim()) {
            const left = (latex.match(/{/g) || []).length;
            const right = (latex.match(/}/g) || []).length;
            if (left !== right) errMsg = '중괄호 수가 맞지 않습니다.';
            if (/\\frac[^}]*$/.test(latex)) errMsg = '분수 명령의 인자가 부족합니다.';
        }
        document.getElementById('mathliveError').innerText = errMsg;
    });

    // 팝업 열기: 어떤 textarea(문제/해설)에서 호출했는지 기억 (현재 사용되지 않음)
    let currentTargetTextarea = null;
    function openMathliveModalForTextarea(textareaName) {
        currentTargetTextarea = textareaName;
        document.getElementById('mathlivePopupField').value =
            document.querySelector('textarea[name="' + textareaName + '"]').value;
        document.getElementById('mathliveModalOverlay').style.display = 'block';
        document.getElementById('mathlivePopupError').innerText = '';
    }

    // 팝업 닫기 함수 (현재 사용되지 않음)
    function closeMathliveModal() {
        document.getElementById('mathliveModalOverlay').style.display = 'none';
        currentTargetTextarea = null;
    }

    // "수정 내용 적용" 버튼 → 수식 옮기기 (현재 사용되지 않음)
    const applyPopupBtn = document.getElementById('applyMathlivePopupBtn');
    if (applyPopupBtn) {
        applyPopupBtn.onclick = function() {
            const latex = document.getElementById('mathlivePopupField').value;
            let err = '';
            const left = (latex.match(/{/g) || []).length;
            const right = (latex.match(/}/g) || []).length;
            if (left !== right) err = '중괄호 수가 맞지 않습니다.';
            if (err) {
                document.getElementById('mathlivePopupError').innerText = err;
                return;
            }
            alert("이 '수정 내용 적용' 버튼은 현재 문제 수정 페이지의 '수식 오류 검사 및 수정' 모달과는 별개입니다. 실제 작동하지 않을 수 있습니다.");
            closeMathliveModal();
        };
    }

}); // DOMContentLoaded 이벤트 리스너 끝

</script>


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
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 가장 먼저 위치해야 함
file_put_contents(__DIR__ . '/debug_post_log.txt', "[1] PHP 시작\n", FILE_APPEND);

require_once 'functions.php';
file_put_contents(__DIR__ . '/debug_post_log.txt', "[2] functions.php 불러옴\n", FILE_APPEND);

require_once 'mathpix_analyzer.php';
file_put_contents(__DIR__ . '/debug_post_log.txt', "[3] mathpix_analyzer.php 불러옴\n", FILE_APPEND);

// 로그 초기화 및 기록
file_put_contents(__DIR__ . '/debug_post_log.txt', "\n\n--- [START LOG] " . date('Y-m-d H:i:s') . " ---\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug_post_log.txt', "[POST DATA]: " . print_r($_POST, true), FILE_APPEND);

// DB 연결
$conn = connectDB();

// 필드 수집
// 필드 수집 (수정 후)
$id = is_numeric($_POST['id'] ?? null) ? (int)$_POST['id'] : 0;
$copyMode = $_POST['copyMode'] ?? '0';
$title = $_POST['title'] ?? '';
$question = $_POST['question'] ?? '';
$answer = $_POST['answer'] ?? '';
$solution = $_POST['solution'] ?? '';
$hint = $_POST['hint'] ?? '';
$video = $_POST['video'] ?? '';
$difficulty = is_numeric($_POST['difficulty'] ?? null) ? (int)$_POST['difficulty'] : null;
$type = $_POST['type'] ?? '';
$category = $_POST['category'] ?? '';
$source = $_POST['source'] ?? '';
$created_by = is_numeric($_POST['created_by'] ?? null) ? (int)$_POST['created_by'] : null;
$tags = $_POST['tags'] ?? '';
$path_text = $_POST['path_text'] ?? '';
$path_id = is_numeric($_POST['path_id'] ?? null) ? (int)$_POST['path_id'] : null;

// ✅ 경로 충돌 자동 보정
resolvePathConflict($conn, $path_id, $path_text);



// 경로 ID 보정
if ((!$path_id || $path_id === 0) && $path_text) {
    $path_id = getPathIdFromText($conn, $path_text);
    if (!$path_id) {
        $stmt = $conn->prepare("SELECT id FROM paths WHERE name LIKE CONCAT('%', ?, '%')");
        $stmt->bind_param("s", $path_text);
        $stmt->execute();
        $stmt->bind_result($matched_id);

        $matches = [];
        while ($stmt->fetch()) $matches[] = $matched_id;
        $stmt->close();

        if (count($matches) === 1) {
            $path_id = $matches[0];
        } else {
            echo json_encode(['success' => false, 'message' => "경로 인식 실패 또는 중복됨: $path_text"]);
            exit;
        }
    }
}

// 수식 분석
$analyzed = analyzeFormulasFromQuestion($question);
$mainFormulaLatex = '';
$mainFormulaTree = '';
$allFormulasTree = '';
$keywords = '';
$mainHash = '';
$mainSympy = '';

if (!empty($analyzed)) {
    usort($analyzed, fn($a, $b) => mb_strlen($b['latex']) - mb_strlen($a['latex']));
    $mainFormulaLatex = implode(', ', array_column(array_slice($analyzed, 0, 3), 'latex'));
    $mainHash = $analyzed[0]['hash'] ?? '';
    $mainFormulaTree = json_encode($analyzed[0]['tree'] ?? [], JSON_UNESCAPED_UNICODE);
    $mainSympy = $analyzed[0]['sympy_expr'] ?? '';
    $allFormulasTree = json_encode(array_map(fn($f) => [
        'latex' => $f['latex'], 'tree' => $f['tree'], 'hash' => $f['hash']
    ], $analyzed), JSON_UNESCAPED_UNICODE);
    $keywords = implode(',', array_unique(array_merge(...array_column($analyzed, 'keywords'))));
}

// 복사 모드
if ($copyMode === '1') {
    $created_at = date('Y-m-d H:i:s');
    $title = preg_match('/^\[복사본(?: (\d+))?\]\s*(.+)$/u', $title, $matches)
        ? "[복사본 " . ((int)($matches[1] ?? 1) + 1) . "] " . $matches[2]
        : "[복사본] $title";

    $stmt = $conn->prepare("INSERT INTO problems
        (title, question, answer, solution, hint, video, difficulty, type, category, source,
         created_by, tags, path_text, path_id, copied_by, origin_id,
         main_formula_latex, main_formula_tree, all_formulas_tree,
         formula_keywords, hash, sympy_expr, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $copied_by = $_POST['copied_by'] ?? 0;
    $stmt->bind_param("ssssssisssissiiisssssss",
        $title, $question, $answer, $solution, $hint, $video,
        $difficulty, $type, $category, $source,
        $created_by, $tags, $path_text, $path_id,
        $copied_by, $id,
        $mainFormulaLatex, $mainFormulaTree, $allFormulasTree,
        $keywords, $mainHash, $mainSympy, $created_at
    );

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        echo "<script>alert('복사 완료. 새 문제 ID: {$newId}'); window.open('edit_problem.php?id={$newId}', '_blank'); window.location.href = 'list_problems.html';</script>";
    } else {
        die("복사 실패: " . $stmt->error);
    }
    $stmt->close();
    exit;
}

// 수정 전 백업
$backup = $conn->query("SELECT * FROM problems WHERE id = $id");
if ($backup && $backup->num_rows > 0) {
    $old = $backup->fetch_assoc();
    // ✏️ path_id와 path_text가 비어 있으면 기본값 설정
    if (empty($old['path_id']) || !is_numeric($old['path_id'])) {
        $old['path_id'] = 105;  // 더플러스수학의 ID
    }
    if (empty($old['path_text'])) {
        $old['path_text'] = '더플러스수학';
    }


    // ⚠️ 기존 'id' 필드는 problem_id로 별도 저장하므로 제거
    $fields = ['problem_id', ...array_keys($old), 'updated_at'];
    $fields = array_filter($fields, fn($f) => $f !== 'id'); // 'id'는 제외

    // ⚠️ 값 배열: problem_id 먼저 추가
    $values = [intval($old['id'])]; // ← 현재 문제의 ID를 problem_id로 사용

    foreach ($old as $key => $val) {
    if ($key === 'id') continue; // id는 제외

    // 정수형 필드가 비어 있으면 NULL로 설정
    if (in_array($key, ['path_id', 'created_by', 'copied_by', 'origin_id', 'difficulty']) && ($val === '' || is_null($val))) {
        $values[] = "NULL";
    } else {
        $values[] = "'" . $conn->real_escape_string($val ?? '') . "'";
    }
    }

    $values[] = "'" . date('Y-m-d H:i:s') . "'"; // updated_at 추가

    // ⚠️ SQL 실행
    $sql = "INSERT INTO history_problems (" . implode(',', $fields) . ") VALUES (" . implode(',', $values) . ")";
    $conn->query($sql);
}

// 업데이트 실행
$stmt = $conn->prepare("UPDATE problems SET
    title = ?, question = ?, answer = ?, solution = ?, hint = ?, video = ?,
    difficulty = ?, type = ?, category = ?, source = ?,
    created_by = ?, tags = ?, path_text = ?, path_id = ?,
    main_formula_latex = ?, main_formula_tree = ?, all_formulas_tree = ?,
    formula_keywords = ?, hash = ?, sympy_expr = ?
    WHERE id = ?");

$stmt->bind_param("ssssssisssssissssssssi",
    $title, $question, $answer, $solution, $hint, $video,
    $difficulty, $type, $category, $source,
    $created_by, $tags, $path_text, $path_id,
    $mainFormulaLatex, $mainFormulaTree, $allFormulasTree,
    $keywords, $mainHash, $mainSympy,
    $id
);

if ($stmt->execute()) {
    echo "<script>alert('문제 수정 완료'); location.href='edit_problem.php?id={$id}';</script>";
} else {
    die("문제 수정 실패: " . $stmt->error);
}
$stmt->close();

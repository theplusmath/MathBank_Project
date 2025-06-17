<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

file_put_contents(__DIR__ . '/version_check.txt', "\n\n[update_problem] POST: " . print_r($_POST, true), FILE_APPEND);
file_put_contents(__DIR__ . '/debug_post_log.txt', "\n\n[update_problem] POST: " . print_r($_POST, true), FILE_APPEND);

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

// ✅ problems 테이블의 컬럼 목록을 debug_post_log.txt에 기록
$columnsResult = $conn->query("SHOW COLUMNS FROM problems");
if ($columnsResult) {
    $columnNames = [];
    while ($row = $columnsResult->fetch_assoc()) {
        $columnNames[] = $row['Field'];
    }
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[📋 problems 테이블 컬럼 목록]\n" . implode(', ', $columnNames) . "\n", FILE_APPEND);
} else {
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[⚠️ 컬럼 조회 실패] " . $conn->error . "\n", FILE_APPEND);
}
exit; // ← 실제 처리 전에 여기서 종료시켜 테스트합니다.





// 🟡 관리자 이메일을 여기에 정확히 기입하세요
$adminEmail = 'admin@example.com';

// ✅ copied_by용 admin ID 조회
$adminQuery = $conn->query("SELECT id FROM users WHERE email = '$adminEmail' LIMIT 1");
$adminRow = $adminQuery->fetch_assoc();
if (!$adminRow) {
    die("⚠️ 관리자 계정을 찾을 수 없습니다. 이메일 확인 필요: $adminEmail");
}
$copied_by = (int)$adminRow['id'];

$id = $_POST['id'] ?? 0;
$copyMode = $_POST['copyMode'] ?? '0';
$title = $_POST['title'] ?? '';
$question = $_POST['question'] ?? '';

require_once 'mathpix_analyzer.php';  // 수식 분석 함수 포함된 파일

$analyzedResults = analyzeFormulasFromQuestion($question);

$answer = $_POST['answer'] ?? '';
$solution = $_POST['solution'] ?? '';
$hint = $_POST['hint'] ?? '';
$video = $_POST['video'] ?? '';
$difficulty = $_POST['difficulty'] ?? '';
$type = $_POST['type'] ?? '';
$category = $_POST['category'] ?? '';
$source = $_POST['source'] ?? '';
$created_by = isset($_POST['created_by']) ? (int)$_POST['created_by'] : 0;
$tags = $_POST['tags'] ?? '';
$path_text = $_POST['path_text'] ?? '';
$path_id = $_POST['path_id'] !== '' ? (int)$_POST['path_id'] : null;

// path_id가 비어 있고, path_text가 있다면 자동으로 찾아서 설정
if ((!$path_id || $path_id === 0) && $path_text) {
    $stmt = $conn->prepare("SELECT id FROM paths WHERE full_path = ? LIMIT 1");
    $stmt->bind_param("s", $path_text);
    $stmt->execute();
    $stmt->bind_result($foundPathId);
    if ($stmt->fetch()) {
        $path_id = $foundPathId;
    } else {
        $path_id = null; // 또는 기본값 설정 가능
    }
    $stmt->close();
}



function get_formula_data_from_api($latex) {
    $api_url = "https://math-api-83wx.onrender.com/normalize";
    $payload = json_encode(["latex" => $latex]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    curl_close($ch);
    file_put_contents(__DIR__ . "/debug_post_log.txt", $response . PHP_EOL, FILE_APPEND);
    return json_decode($response, true);
}

preg_match_all('/\\\((.*?)\\\)|\\\[(.*?)\\\]|\$\$(.*?)\$\$|\$(.*?)\$|\\\(\s*\\displaystyle\s*(.*?)\\\)/s', $question, $matches);
$formulas = array_filter(array_merge($matches[1], $matches[2], $matches[3], $matches[4], $matches[5]));

$mainFormulaLatex = '';
$mainFormulaTree = '';
$allFormulasTree = '';
$formulasKeywords = '';
$mainFormulaHash = '';
$mainFormulaSympy = '';
$analyzedResults = [];
$allKeywords = [];

foreach ($formulas as $formula) {
    $original = trim(preg_replace('/<[^>]*>/', '', $formula));
    try {
        $response = null; // 초기화
        $response = get_formula_data_from_api($original);
        $hash = $response['hash'] ?? sha1($original);
        $tree = $response['main_formula_tree'] ?? 'no_tree';
        $keywords = $response['formula_keywords'] ?? ['none'];
        $sympy = $response['sympy_expr'] ?? '';
    } catch (Exception $e) {
        $response = null;
        $hash = sha1($original);
        $tree = 'no_tree';
        $keywords = ['none'];
        $sympy = '';
    }

    $analyzedResults[] = [
        'latex' => $original,
        'tree' => $tree,
        'hash' => $hash,
        'keywords' => $keywords,
        'sympy_expr' => $sympy
    ];

    $allKeywords = array_merge($allKeywords, $keywords);

    // ✅ 여기에서 바로 response 로깅 가능
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[API 요청] $original\n[응답] " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
}


if (!empty($analyzedResults)) {
    $filteredResults = array_filter($analyzedResults, function($item) {
        $latex = $item['latex'];
        $keywords = $item['keywords'] ?? [];

        // 너무 짧은 수식 제외
        if (mb_strlen($latex) < 5) return false;

        // 불필요한 단순 표현 제외
        if (preg_match('/^\\displaystyle\s*\d+(\s*\\sqrt\s*\{?\d+\}?)?$/u', $latex)) return false;

        // 핵심 연산자가 하나라도 있는 경우 허용
        $essential = ['Symbol', 'Equality', 'Add', 'Mul', 'Pow'];
        return count(array_intersect($keywords, $essential)) >= 1;
    });

    // ✅ 분석된 것이 없으면 무조건 첫 수식을 사용
    if (empty($filteredResults) && !empty($analyzedResults)) {
        $filteredResults = [$analyzedResults[0]];
    }

    usort($filteredResults, fn($a, $b) => mb_strlen($b['latex']) - mb_strlen($a['latex']));

    $mainFormulas = array_column(array_slice($filteredResults, 0, 3), 'latex');

    if (!empty($mainFormulas)) {
        $mainFormulaLatex = implode(', ', $mainFormulas);
        if (mb_strlen($mainFormulaLatex) > 500) {
            $mainFormulaLatex = mb_substr($mainFormulaLatex, 0, 500) . '...';
        }
    } else {
        $mainFormulaLatex = '';
    }



    $mainFormulaHash = $filteredResults[0]['hash'] ?? '';
    $mainFormulaTree = isset($filteredResults[0]['tree']) ? json_encode($filteredResults[0]['tree'], JSON_UNESCAPED_UNICODE) : '"no_tree"';
    $mainFormulaSympy = $filteredResults[0]['sympy_expr'] ?? '';


    file_put_contents(__DIR__ . '/debug_post_log.txt', "[MainFormulaLatex] $mainFormulaLatex\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[MainFormulaHash] $mainFormulaHash\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[SymPyExpr] $mainFormulaSympy\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[API 요청] $original\n[응답] " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);



    $allFormulasTree = json_encode(array_map(fn($item) => [
        'latex' => $item['latex'],
        'tree' => $item['tree'],
        'hash' => $item['hash']
    ], $analyzedResults), JSON_UNESCAPED_UNICODE);

    $uniqueKeywords = array_values(array_unique($allKeywords));
    sort($uniqueKeywords);
    $formulasKeywords = implode(',', $uniqueKeywords);
} else {
    // 분석된 수식 자체가 없을 때
    $mainFormulaLatex = '';
    $mainFormulaTree = 'no_tree';
    $allFormulasTree = '[]';
    $formulasKeywords = '';
    $mainFormulaHash = '';
    $mainFormulaSympy = '';
}

if ($copyMode === '1') {
    $newTitle = '[복사본] ' . $title;
    $created_by = $copied_by;  // 같은 관리자 ID 사용
    $stmt = $conn->prepare("INSERT INTO problems (
        title, question, answer, solution, hint, video,
        difficulty, type, category, source, created_by, tags,
        path_text, path_id, copied_by, origin_id,
        main_formula_latex, main_formula_tree, all_formulas_tree, formula_keywords, hash, sympy_expr
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssisssissiiissssss",
        $newTitle, $question, $answer, $solution, $hint, $video,
        $difficulty, $type, $category, $source, $created_by, $tags,
        $path_text, $path_id, $copied_by, $id,
        $mainFormulaLatex, $mainFormulaTree, $allFormulasTree, $formulasKeywords,
        $mainFormulaHash, $mainFormulaSympy
    );
} else {
    $stmt = $conn->prepare("UPDATE problems SET
        title = ?, question = ?, answer = ?, solution = ?, hint = ?, video = ?,
        difficulty = ?, type = ?, category = ?, source = ?, created_by = ?, tags = ?,
        path_text = ?, path_id = ?, copied_by = ?,
        main_formula_latex = ?, main_formula_tree = ?, all_formulas_tree = ?, formula_keywords = ?,
        hash = ?, sympy_expr = ?
        WHERE id = ?");
    $stmt->bind_param("ssssssississssissssssi",                       
        $title, $question, $answer, $solution, $hint, $video,
        $difficulty, $type, $category, $source, $created_by, $tags,
        $path_text, $path_id, $copied_by,
        $mainFormulaLatex, $mainFormulaTree, $allFormulasTree, $formulasKeywords,
        $mainFormulaHash, $mainFormulaSympy, $id
    );
}

if (!$stmt->execute()) {
    echo "<script>alert('저장 실패: {$conn->error}'); history.back();</script>";
    exit;
}

if ($copyMode === '1') {
    $id = $conn->insert_id;
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[INSERT 후 ID] $id\n", FILE_APPEND);
}
$stmt->close();

$stmt = $conn->prepare("DELETE FROM formula_index WHERE problem_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// 새로운 분석 결과 저장
foreach ($analyzedResults as $item) {
    $stmt = $conn->prepare("
        INSERT INTO formula_index 
        (problem_id, original_formula, formula_skeleton, formula_skeleton_hash, main_formula_tree, formula_keywords, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param(
        "isssss",
        $id,
        $item['latex'],
        $item['latex'],
        $item['hash'],
        $item['tree'],
        implode(',', $item['keywords'])
    );
    $stmt->execute();
    $stmt->close();
}

$conn->close();
echo "<script>alert('저장 완료!'); location.href='edit_problem.php?id={$id}';</script>"; 


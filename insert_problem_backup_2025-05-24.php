<?php
require_once 'functions.php';  // ✅ 가장 첫 줄에 추가

ini_set('display_errors', 1);
error_reporting(E_ALL);


file_put_contents('debug_post_log.txt', print_r($_POST, true), FILE_APPEND);


// ✅ DB 연결
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die("DB 연결 실패: " . $conn->connect_error);
}

$title = $_POST['title'] ?? '';
$question = $_POST['question'] ?? '';
$answer = $_POST['answer'] ?? '';
$solution = $_POST['solution'] ?? '';
$hint = $_POST['hint'] ?? '';
$copied_by = $_POST['copied_by'] ?? '';
$main_formula_latex = $_POST['main_formula_latex'] ?? '';
$formula_keywords = $_POST['formula_keywords'] ?? '';
$path_text = $_POST['path_text'] ?? '';
$video = $_POST['video'] ?? '';
$difficulty = $_POST['difficulty'] ?? '';
$type = $_POST['type'] ?? '';
$category = $_POST['category'] ?? '';
$source = $_POST['source'] ?? '';






// ✅ 문제 본문 등록
$stmt = $conn->prepare("INSERT INTO problems 
    (title, question, answer, solution, hint, copied_by, video, difficulty, type, category, source, created_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

$stmt->bind_param("sssssssssss", 
    $title, $question, $answer, $solution, $hint, $copied_by, $video, $difficulty, $type, $category, $source);

$stmt->execute();

$problem_id = $stmt->insert_id; // 방금 삽입된 문제의 ID

if ($path_text) {
    $path_id = getPathIdFromText($conn, $path_text);

    if (!$path_id) {
        // 🔄 자동 보정 시도: '등차수열' 같은 단어만 입력되었을 경우
        $stmt = $conn->prepare("SELECT id FROM path WHERE name LIKE CONCAT('%', ?, '%')");
        $stmt->bind_param("s", $path_text);
        $stmt->execute();
        $stmt->bind_result($matched_id);

        $matches = [];
        while ($stmt->fetch()) {
            $matches[] = $matched_id;
        }
        $stmt->close();

        if (count($matches) === 1) {
            $path_id = $matches[0];
        } else {
            echo json_encode(['success' => false, 'message' => '❌ 경로 인식 실패 또는 중복됨: ' . $path_text]);
            exit;
        }
    }

    if ($path_id) {
        $stmt = $conn->prepare("UPDATE problems SET path_id = ?, path_text = ? WHERE id = ?");
        $stmt->bind_param("isi", $path_id, $path_text, $problem_id);
        $stmt->execute();
        $stmt->close();
    }
}



$stmt->close();

if ($main_formula_latex || $formula_keywords) {
    $stmt = $conn->prepare("UPDATE problems SET main_formula_latex = ?, formula_keywords = ? WHERE id = ?");
    $stmt->bind_param("ssi", $main_formula_latex, $formula_keywords, $problem_id);
    $stmt->execute();
    $stmt->close();
}
// ✅ 그 다음에 자동 분석 로직
processFormulasForProblem($problem_id, $question, $solution, $answer, $hint, $conn);



function getPathIdFromText($conn, $pathText) {
    $names = preg_split('/[\/~]/u', $pathText); // / 또는 ~ 모두 허용
    $parentId = null;
    $pathId = null;

    foreach ($names as $name) {
        $stmt = $conn->prepare("SELECT id FROM path WHERE name = ? AND parent_id " . ($parentId === null ? "IS NULL" : "= ?"));
        if ($parentId === null) {
            $stmt->bind_param("s", $name);
        } else {
            $stmt->bind_param("si", $name, $parentId);
        }
        $stmt->execute();
        $stmt->bind_result($pathId);
        if ($stmt->fetch()) {
            $parentId = $pathId;
        } else {
            return null;
        }
        $stmt->close();
    }

    return $pathId;
}


// ✅ 수식 추출 함수 ( \( \), \[ \], $$, $ 포함 + 필터링)
function extractLatexFormulas($text) {
    $patterns = [
        '/\$\$(.*?)\$\$/s',
        '/\\\\\[(.*?)\\\\\]/s',
        '/\\\\\((.*?)\\\\\)/s',
        '/\\\\displaystyle\s+(.*?)(?=(\\\\|$))/s',
        '/\$(.*?)\$/s'
    ];

    $matches = [];
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $text, $found)) {
            foreach ($found[1] as $match) {
                $clean = trim($match);
                if (isMeaningfulFormula($clean)) {
                    $matches[] = $clean;
                }
            }
        }
    }

    return array_values(array_unique($matches));
}

// ✅ 무의미한 수식 필터링 (한 글자, 나열형 등 제외)
function isMeaningfulFormula($formula) {
    $trimmed = trim($formula);

    if (preg_match('/^[a-zA-Z]$/', $trimmed)) return false;
    if (preg_match('/^([a-zA-Z0-9_]+, *)+[a-zA-Z0-9_]+$/', $trimmed)) return false;
    if (preg_match('/^\\\\?[=+\-*/\\^\\., ]+$/', $trimmed)) return false;

    return true;
}

// ✅ 대표 수식 선택 (80% 이상 길이 기준)
function selectMainFormulas(array $formulas): array {
    $lengths = array_map('mb_strlen', $formulas);
    $maxLength = max($lengths);
    $threshold = $maxLength * 0.8;

    $mainFormulas = [];
    foreach ($formulas as $formula) {
        if (mb_strlen($formula) >= $threshold) {
            $mainFormulas[] = $formula;
        }
    }

    return array_values(array_unique($mainFormulas));
}

// ✅ 대표 트리 대상 수식 1개 선택 (가장 긴 수식)
function selectMainFormulaForTree(array $formulas): string {
    $main = '';
    $max = 0;
    foreach ($formulas as $formula) {
        $len = mb_strlen($formula);
        if ($len > $max) {
            $main = $formula;
            $max = $len;
        }
    }
    return $main;
}

// ✅ FastAPI 호출 → hash, tree, keywords 수신
function analyzeFormulasWithAPI(array $formulas): array {
    $apiUrl = 'https://math-api-83wx.onrender.com/normalize';
    $postData = json_encode(['latex_list' => $formulas]);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) return [];

    $result = json_decode($response, true);
    return is_array($result) ? $result : [];
}

// ✅ keywords 병합
function extractUniqueKeywords(array $analyzedResults): array {
    $allKeywords = [];
    foreach ($analyzedResults as $item) {
        if (isset($item['keywords']) && is_array($item['keywords'])) {
            $allKeywords = array_merge($allKeywords, $item['keywords']);
        }
    }

    $uniqueKeywords = array_values(array_unique($allKeywords));
    sort($uniqueKeywords);
    return $uniqueKeywords;
}

// ✅ 전체 수식 처리 메인 함수
function processFormulasForProblem($problem_id, $question, $solution, $answer, $hint, $conn) {
    $fullText = $question . "\n" . $solution . "\n" . $answer . "\n" . $hint;
    $formulas = extractLatexFormulas($fullText);
    file_put_contents(__DIR__ . '/debug_formulas_log.txt', print_r($formulas, true), FILE_APPEND);

    if (empty($formulas)) return;

    $mainFormulas = selectMainFormulas($formulas);
    $mainFormulaForTree = selectMainFormulaForTree($formulas);
    $analyzed = analyzeFormulasWithAPI($formulas);
    if (empty($analyzed)) return;

    // 대표 수식 tree 선택
    $mainTree = '';
    foreach ($analyzed as $item) {
        if ($item['latex'] === $mainFormulaForTree) {
            $mainTree = json_encode($item['tree'], JSON_UNESCAPED_UNICODE);
            break;
        }
    }

    // 전체 트리 저장용
    $allFormulasTree = [];
    foreach ($analyzed as $item) {
        $allFormulasTree[] = [
            'latex' => $item['latex'],
            'tree' => $item['tree'],
            'hash' => $item['hash']
        ];
    }

    $keywords = extractUniqueKeywords($analyzed);
    $keywordsStr = implode(',', $keywords);
    $mainFormulasStr = implode(', ', $mainFormulas);

    // ✅ DB 업데이트
    $stmt = $conn->prepare("UPDATE problems SET main_formula_latex = ?, main_formula_tree = ?, all_formulas_tree = ?, formula_keywords = ? WHERE id = ?");
    $jsonAllFormulasTree = json_encode($allFormulasTree, JSON_UNESCAPED_UNICODE);
    $stmt->bind_param("ssssi", $mainFormulasStr, $mainTree, $jsonAllFormulasTree, $keywordsStr, $problem_id);
    $stmt->execute();
    $stmt->close();
}





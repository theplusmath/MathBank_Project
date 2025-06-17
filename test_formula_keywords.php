<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 로그 파일 초기화 (테스트를 위해 매번 초기화)
file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', '--- [START LOG] ' . date('Y-m-d H:i:s') . " ---\n");

// --- 1. 테스트용 POST 데이터 정의 ---
// 실제 문제 ID를 입력하세요. (예: 1)
$_POST['id'] = 1;
// 테스트할 질문 내용 (수식이 포함된 HTML)
$_POST['question'] = '<p>이것은 테스트 문제입니다. &nbsp;ㅇㅁㄹㅇ<br><br>&lt;p&gt;이차함수의 일반형은 다음과 같다:&lt;/p&gt;<br>$y = ax^2 + bx + c$<br>&nbsp;</p>';

// POST 데이터 로그
file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', "\n[TEST POST DATA]: " . print_r($_POST, true), FILE_APPEND);

// --- 2. 데이터베이스 연결 ---
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');

if ($conn->connect_errno) {
    $error_msg = "[? DB 연결 실패] " . $conn->connect_error . "\n";
    file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', $error_msg, FILE_APPEND);
    die($error_msg);
}
$conn->set_charset('utf8mb4');
file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', "[? DB 연결 성공]\n", FILE_APPEND);

// ? 현재 연결된 데이터베이스 이름 확인
$currentDbResult = $conn->query("SELECT DATABASE()");
if ($currentDbResult) {
    $currentDbName = $currentDbResult->fetch_row()[0];
    file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', "[?? 현재 연결된 데이터베이스] " . $currentDbName . "\n", FILE_APPEND);
    $currentDbResult->free();
}

// ?? 문제 테이블 컬럼 목록 로그
$columnsResult = $conn->query("SHOW COLUMNS FROM problems");
if ($columnsResult) {
    $columnNames = [];
    while ($row = $columnsResult->fetch_assoc()) {
        $columnNames[] = $row['Field'];
    }
    file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', "[?? problems 테이블 컬럼 목록]\n" . implode(', ', $columnNames) . "\n", FILE_APPEND);
    $columnsResult->free();
} else {
    file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', "[?? 컬럼 조회 실패] " . $conn->error . "\n", FILE_APPEND);
}


// --- 3. 필요한 변수 초기화 및 추출 ---
$id = $_POST['id'] ?? 0;
$question = $_POST['question'] ?? '';

// --- 4. 수식 분석 (mathpix_analyzer.php 필요) ---
// 이 파일이 이 스크립트와 같은 디렉토리에 있는지 확인하세요.
require_once 'mathpix_analyzer.php';

$analyzedResults = analyzeFormulasFromQuestion($question);
$formulasKeywords = '';
$allKeywords = [];

if (!empty($analyzedResults)) {
    foreach ($analyzedResults as $r) {
        $allKeywords = array_merge($allKeywords, $r['keywords']);
    }
    $formulasKeywords = implode(',', array_unique($allKeywords));
}

file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', "\n[Analyzed Keywords]: " . $formulasKeywords . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', "[Problem ID to update]: " . $id . "\n", FILE_APPEND);


// --- 5. formula_keywords만 업데이트하는 SQL 쿼리 ---
$update_sql = "UPDATE problems SET formula_keywords = ? WHERE id = ?";

file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', "\n[?? UPDATE 쿼리]: " . $update_sql . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', "[?? UPDATE 쿼리 Hex Dump]: " . bin2hex($update_sql) . "\n", FILE_APPEND);


$stmt = $conn->prepare($update_sql);

if (!$stmt) {
    $error_msg = "[?UPDATE prepare 실패] " . $conn->error . "\n";
    file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', $error_msg, FILE_APPEND);
    die($error_msg);
}
file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', "[? UPDATE prepare 성공]\n", FILE_APPEND);


// 바인딩 파라미터 (string, integer)
if (!$stmt->bind_param("si", $formulasKeywords, $id)) {
    $error_msg = "[?UPDATE bind_param 실패] " . $stmt->error . "\n";
    file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', $error_msg, FILE_APPEND);
    die($error_msg);
}
file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', "[? UPDATE bind_param 성공]\n", FILE_APPEND);


if (!$stmt->execute()) {
    $error_msg = "[? UPDATE 쿼리 실행 오류] " . $stmt->error . "\n";
    file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', $error_msg, FILE_APPEND);
    die($error_msg);
}

file_put_contents(__DIR__ . '/test_formula_keywords_log.txt', "[? UPDATE 쿼리 성공적으로 실행됨]\n", FILE_APPEND);

$stmt->close();
$conn->close();

echo "테스트 스크립트 실행 완료! 'test_formula_keywords_log.txt' 파일을 확인하세요.";
?>
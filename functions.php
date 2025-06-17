<?php
/**
 * functions.php
 * - 공통 유틸리티 함수 모음
 *   - DB 연결
 *   - 경로/태그 처리
 *   - 수식 정제, LaTeX 추출, 텍스트 정제
 *   - 문제/풀이/해설 관련 유틸 함수
 */

file_put_contents(__DIR__ . '/debug_post_log.txt', date('c')." functions.php loaded\n", FILE_APPEND);

// ====================
// DB 연결 함수
// ====================
function connectDB() {
    $host = 'localhost';
    $user = 'theplusmath';
    $pass = 'wnstj1205+';
    $dbname = 'theplusmath';
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset('utf8mb4');
    if ($conn->connect_errno) {
        die('DB 연결 오류: '.$conn->connect_error);
    }
    return $conn;
}

// ====================
// HTML/텍스트/수식 정제 함수
// ====================

/**
 * LaTeX 수식 추출 전 텍스트 정제용
 * - html 태그/엔티티 제거
 * - 컨트롤 문자 (\x00~\x08, \x0B, \x0C, \x0E~\x1F, \x7F) 제거
 * - 여러 공백을 한 칸으로
 */
function cleanTextForLatex($text) {
    // HTML 태그/엔티티 제거
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // 컨트롤 문자(\x00~\x08, \x0B, \x0C, \x0E~\x1F, \x7F) 제거
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
    // 여러 공백을 한 칸으로
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

// ====================
// 경로 관련 함수 (path 테이블)
// ====================

/**
 * 경로 텍스트("2022개정/고등수학/수학1/수열/등차수열")에서 path_id 찾기
 */
function getPathIdFromText($conn, $pathText) {
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[getPathIdFromText] 입력: $pathText\n", FILE_APPEND);

    $pathText = trim($pathText);
    if ($pathText === '') {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[getPathIdFromText] 빈 문자열\n", FILE_APPEND);
        return null;
    }

    $names = preg_split('/[\/~]/u', $pathText);
    $parentId = null;
    $pathId = null;

    foreach ($names as $name) {
        $stmt = $conn->prepare("SELECT id FROM paths WHERE name = ? AND " . ($parentId === null ? "parent_id IS NULL" : "parent_id = ?"));
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
            // 경로가 없으면 중단
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[getPathIdFromText] 경로 없음: $name\n", FILE_APPEND);
            $stmt->close();
            return null;
        }
        $stmt->close();
    }
    return $pathId;
}

// ====================
// 태그 처리 관련 함수
// ====================

/**
 * 콤마로 연결된 태그 문자열을 배열로 변환 (중복, 공백 제거)
 */
function parseTags($tagString) {
    $tagsArray = array_filter(array_map('trim', explode(',', $tagString)));
    $tagsArray = array_unique($tagsArray);
    return $tagsArray;
}

// ====================
// 문제/해설 기타 유틸 함수 (예시)
// ====================

/**
 * 예: 특정 문제ID의 문제/풀이/해설/힌트 등 정보 반환
 */
function getProblemById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM problems WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row;
}

/**
 * 주어진 SQL 쿼리에서 ? 바인딩 파라미터 개수를 센다.
 * @param string $sql
 * @return int
 */
function count_sql_placeholders($sql) {
    return substr_count($sql, '?');
}





/**
 * 배열의 값을 기반으로 bind_param 타입 문자열 반환
 * - int → i, float → d, string → s
 */
function guessParamTypes($data) {
    $types = '';
    foreach ($data as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    return $types;
}

/**
 * prepare된 stmt에 타입과 값을 동적으로 바인딩
 */
function bindParams($stmt, $types, $params) {
    // PHP 8 이상에서는 reference를 명시적으로 만들어야 함
    $bindNames = [];
    $bindNames[] = $types;
    foreach ($params as $key => $value) {
        $bindNames[] = &$params[$key];
    }
    return call_user_func_array([$stmt, 'bind_param'], $bindNames);
}

/**
 * 프린트/미리보기/검색/AI용 (HTML 등만 제거, 수식+이미지 포함)
 */
function extractQuestionText($html) {
    // <img> 태그만 남기고 나머지 HTML 태그 제거
    $text = strip_tags($html, '<img>');
    // 엔티티(&nbsp;, &lt; 등) 디코딩
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // 여러 공백을 하나로
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

/**
 * 순수 텍스트 전용 (HTML+수식+이미지 등 모두 제거)
 */
function extractQuestionTextOnly($html) {
    // 모든 태그 제거
    $text = strip_tags($html);
    // LaTeX 수식 패턴도 모두 제거
    $text = preg_replace('/\\\[(.*?)\\\]/us', '', $text);  // \[ ... \]
    $text = preg_replace('/\\\((.*?)\\\)/us', '', $text);  // \( ... \)
    $text = preg_replace('/\$\$(.*?)\$\$/us', '', $text);  // $$ ... $$
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

/**
 * solution, hint, answer 등 프린트/미리보기용 (HTML만 제거, 수식+이미지 포함)
 */
function extractCleanText($html) {
    $text = strip_tags($html, '<img>');
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

/**
 * solution, hint, answer 등 순수 텍스트 전용 (HTML+수식+이미지 등 모두 제거)
 */
function extractCleanTextOnly($html) {
    $text = strip_tags($html);
    $text = preg_replace('/\\\[(.*?)\\\]/us', '', $text);  // \[ ... \]
    $text = preg_replace('/\\\((.*?)\\\)/us', '', $text);  // \( ... \)
    $text = preg_replace('/\$\$(.*?)\$\$/us', '', $text);  // $$ ... $$
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}




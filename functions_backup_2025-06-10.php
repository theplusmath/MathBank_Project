<?php
/**
 * functions.php
 * 공통 유틸리티 함수 모음
 * - 경로 처리
 * - 수식 분석
 * - 문제 조회 및 출력
 * - DB 연결
 */

file_put_contents(__DIR__ . '/debug_post_log.txt', date('c')." functions.php loaded\n", FILE_APPEND);


// ===============================
// ?? PATH 관련 함수
// ===============================

function getPathIdFromText($conn, $pathText) {
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[getPathIdFromText] 입력: $pathText\n", FILE_APPEND);

    $pathText = trim($pathText);
    if ($pathText === '') {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[getPathIdFromText] 빈 문자열\n", FILE_APPEND);
        return null;
    }

    // ? 단일 경로 처리
    if (strpos($pathText, '/') === false && strpos($pathText, '~') === false) {
        $stmt = $conn->prepare("SELECT id FROM paths WHERE name = ? AND parent_id IS NULL");
        $stmt->bind_param("s", $pathText);
        $stmt->execute();
        $stmt->bind_result($pathId);
        if ($stmt->fetch()) {
            $stmt->close();
            return $pathId;
        }
        $stmt->close();
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[getPathIdFromText] 단일 경로 검색 실패: $pathText\n", FILE_APPEND);
        return null;
    }

    // 다단계 경로 처리
    $names = preg_split('/[\/~]/u', $pathText);
    $parentId = null;
    $pathId = null;

    foreach ($names as $name) {
        $name = trim($name);
        if ($name === '') continue;

        $stmt = $conn->prepare("SELECT id FROM paths WHERE name = ? AND parent_id " . ($parentId === null ? "IS NULL" : "= ?"));
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
            $stmt->close();
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[getPathIdFromText] 하위 경로 검색 실패: $name (parent_id: $parentId)\n", FILE_APPEND);
            return null;
        }
        $stmt->close();
    }

    return $pathId;
}


// ===============================
// HTML 정리용 함수
// ===============================

/**
 * LaTeX 수식 추출 전 텍스트 정제용
 * - html 태그/엔티티 제거
 * - 컨트롤 문자 (\x00~\x08, \x0B, \x0C, \x0E~\x1F, \x7F) 제거
 * - 여러 공백을 한 칸으로
 */

function cleanTextForLatex($text) {
    file_put_contents(__DIR__.'/latex_debug.txt', "--- ".date('Y-m-d H:i:s')." ---\n", FILE_APPEND);
    file_put_contents(__DIR__.'/latex_debug.txt', "[원본]\n". $text ."\n", FILE_APPEND);

    // 1. HTML 엔티티 먼저 복원
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // 2. <br>, <br/> 태그를 줄바꿈으로 변환 (원하는 경우)
    $text = str_ireplace(['<br>', '<br/>', '<br />'], "\n", $text);
    // 3. 모든 태그 제거
    $text = strip_tags($text);
    // 4. 컨트롤 문자 제거
    $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
    // 5. 여러 공백을 한 칸으로
    $text = preg_replace('/\s+/u', ' ', $text);

    file_put_contents(__DIR__.'/latex_debug.txt', "[최종 정제]\n". $text ."\n\n", FILE_APPEND);
    return trim($text);
}






// ===============================
// ?? 수식 추출 및 분석
// ===============================

function extractLatexFormulas($text) {
    //var_dump($text);
    //exit;
    $text = cleanTextForLatex($text);
    //$patterns = [
    //    '/\$\$(.*?)\$\$/s',
    //    '/\\\\\[(.*?)\\\\\]/s',
    //    '/\\\\\((.*?)\\\\\)/s',
    //    '/\$(.*?)\$/s'
    //];
    //$matches = [];
    //foreach ($patterns as $pattern) {
    //    if (preg_match_all($pattern, $text, $found)) {
    //        foreach ($found[1] as $match) {
    //            $clean = trim($match);
    //            if (mb_strlen($clean) < 3) continue;
    //            $matches[] = $clean;
    //        }
    //    }
 //   }
 //   return array_values(array_unique($matches));
}



function isMeaningfulFormula($formula) {
    $trimmed = trim($formula);

    if (preg_match('/^[a-zA-Z]$/', $trimmed)) return false;
    if (preg_match('/^([a-zA-Z0-9_]+, *)+[a-zA-Z0-9_]+$/', $trimmed)) return false;
    if (preg_match('/^\\\\?[=+\-\*\/\\^\\., ]+$/', $trimmed)) return false;



    return true;
}

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

    if ($httpCode !== 200 || !$response) {
        // 로그 파일에 시간, 코드, 요청 내용 남기기
        $logMessage = "[" . date("Y-m-d H:i:s") . "] API 오류 - HTTP $httpCode\n";
        $logMessage .= "요청 수식: " . json_encode($formulas, JSON_UNESCAPED_UNICODE) . "\n";
        $logMessage .= "응답: " . $response . "\n\n";
        file_put_contents(__DIR__ . '/error_api_log.txt', $logMessage, FILE_APPEND);
        return [];
    }


    if ($httpCode !== 200 || !$response) return [];

    $result = json_decode($response, true);
    return is_array($result) ? $result : [];
}

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

function processFormulasForProblem($problem_id, $question, $solution, $answer, $hint, $conn) {
    $fullText = $question . "\n" . $solution . "\n" . $answer . "\n" . $hint;
    $formulas = extractLatexFormulas($fullText);
    file_put_contents(__DIR__ . '/debug_formulas_log.txt', print_r($formulas, true), FILE_APPEND);

    if (empty($formulas)) return;

    // ? 대표 수식: 수동 입력값 우선 사용
    $mainFormulasStr = '';
    $mainFormulaForTree = '';
    if (!empty($_POST['main_formula_latex'])) {
        $mainFormulasStr = trim($_POST['main_formula_latex']);
        $mainFormulaForTree = trim($_POST['main_formula_latex']);
    } else {
        $mainFormulas = selectMainFormulas($formulas);
        $mainFormulasStr = implode(', ', $mainFormulas);
        $mainFormulaForTree = selectMainFormulaForTree($formulas);
    }

    // ? Render API 호출 (항상 실행)
    $analyzed = analyzeFormulasWithAPI($formulas);
    if (empty($analyzed)) return;

    // ? 대표 수식 트리 찾기
    $mainTree = '';
    foreach ($analyzed as $item) {
        if ($item['latex'] === $mainFormulaForTree) {
            $mainTree = json_encode($item['tree'], JSON_UNESCAPED_UNICODE);
            break;
        }
    }

    // ? 전체 트리 목록 저장
    $allFormulasTree = [];
    foreach ($analyzed as $item) {
        $allFormulasTree[] = [
            'latex' => $item['latex'],
            'tree' => $item['tree'],
            'hash' => $item['hash']
        ];
    }

    // ? 키워드 추출
    $keywords = extractUniqueKeywords($analyzed);
    $keywordsStr = implode(',', $keywords);

    // ? DB에 저장
    $stmt = $conn->prepare("UPDATE problems 
        SET main_formula_latex = ?, main_formula_tree = ?, all_formulas_tree = ?, formula_keywords = ? 
        WHERE id = ?");
    $jsonAllFormulasTree = json_encode($allFormulasTree, JSON_UNESCAPED_UNICODE);
    $stmt->bind_param("ssssi", $mainFormulasStr, $mainTree, $jsonAllFormulasTree, $keywordsStr, $problem_id);
    $stmt->execute();
    $stmt->close();
}


// ===============================
// ?? 문제 조회 및 출력
// ===============================

function getProblemById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM problems WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getOriginProblem($conn, $origin_id) {
    $stmt = $conn->prepare("SELECT id, title FROM problems WHERE id = ?");
    $stmt->bind_param("i", $origin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function printField($label, $value, $isHtml = false) {
    echo '<p><strong>' . htmlspecialchars($label) . ':</strong><br>';
    echo $isHtml ? $value : nl2br(htmlspecialchars($value));
    echo '</p>';
}

// ? DB 연결 도우미
function connectDB() {
    $conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
    $conn->set_charset('utf8mb4');
    mysqli_query($conn, "SET NAMES utf8mb4");
    mysqli_query($conn, "SET CHARACTER SET utf8mb4");

    if ($conn->connect_error) {
        die('DB 연결 실패: ' . $conn->connect_error);
    }

    return $conn;
}

function resolvePathConflict($conn, &$path_id, &$path_text) {
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[resolvePathConflict] 실행 시작 - path_id: $path_id / path_text: $path_text\n", FILE_APPEND);

    if ($path_id && $path_text) {
        // 둘 다 있는 경우 → 일치 여부 확인
        $stmt = $conn->prepare("SELECT name FROM paths WHERE id = ?");
        $stmt->bind_param("i", $path_id);
        $stmt->execute();
        $stmt->bind_result($realPathText);
        if ($stmt->fetch() && $realPathText !== $path_text) {
            $path_text = $realPathText; // DB 기준으로 정정
        }
        $stmt->close();
    } elseif ($path_text && !$path_id) {
        // path_text만 있음 → path_id 유추
        $path_id = getPathIdFromText($conn, $path_text);
    } elseif ($path_id && !$path_text) {
        // path_id만 있음 → path_text 유추
        $stmt = $conn->prepare("SELECT name FROM paths WHERE id = ?");
        $stmt->bind_param("i", $path_id);
        $stmt->execute();
        $stmt->bind_result($name);
        if ($stmt->fetch()) $path_text = $name;
        $stmt->close();
    } else {
        // ? 이 부분이 수정 대상
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[resolvePathConflict] ? 둘 다 없음. path_id=105(더플러스수학)로 설정\n", FILE_APPEND);
        $path_id = 105;
        $path_text = '더플러스수학';
    }
}


// ===============================
// [테스트/신버전] LaTeX 수식 추출 및 정규화 함수 모음
// 기존 함수와 이름 중복 없음!
// ===============================

/**
 * (1) 가장 단순한 $...$ 수식만 추출하는 테스트 함수
 */
function extractLatexFormulasSimple_Test($text) {
    $pattern = '/\$(.*?)\$/s';
    $matches = [];
    if (preg_match_all($pattern, $text, $found)) {
        foreach ($found[1] as $formula) {
            $matches[] = trim($formula);
        }
    }
    return array_unique($matches);
}

/**
 * (2) 유사 역슬래시(￦, ₩ 등)를 진짜 역슬래시(\)로 변환 (테스트용)
 */
function normalizeBackslash_Test($text) {
    // ￦(U+FFE6), ₩(U+20A9)를 모두 역슬래시(\)로 변환
    return preg_replace('/[\x{20A9}\x{FFE6}]/u', '\\', $text);
}

/**
 * (3) HTML 태그/엔티티 제거, 유사 역슬래시 정규화 등 포함 (테스트용)
 */
function cleanTextForLatex_v2($text) {
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = normalizeBackslash_Test($text);
    $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim($text);
}

/**
 * (4) 여러 종류($$, \[ \], \( \), $...$)의 LaTeX 수식 추출 (테스트/신버전)
 */
/**
 * (최종 권장) 여러 종류($$, \[ \], \( \), $...$) LaTeX 수식 추출 + 스타일명령 완전제거
 */
// 최종 복잡한 수식을 바꾸는 함수
function extractLatexFormulas_v2($text) {

    // 2. HTML 엔티티 복원
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');


    // 1. HTML 태그 제거
    $text = strip_tags($text);
    // 3. \displaystyle, &nbsp; 등 스타일/공백 명령 제거
    $text = str_replace(['\\displaystyle', '&nbsp;', "\xc2\xa0"], ' ', $text);

    // 4. 패턴별 LaTeX 추출 (줄바꿈 포함)
    $patterns = [
        '/\$\$(.*?)\$\$/s',                   // $$ ... $$
        '/\\\\\[(.*?)\\\\\]/s',               // \[ ... \]
        '/\\\\\((.*?)\\\\\)/s',               // \( ... \)
        '/\$(.*?)\$/s',                       // $ ... $
        '/\\\\begin\{.*?\}(.*?)\\\\end\{.*?\}/s', // \begin{...} ... \end{...}
    ];
    $matches = [];
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $text, $found)) {
            foreach ($found[1] as $formula) {
                $f = trim($formula);
                if (mb_strlen($f) > 0 && !in_array($f, $matches)) {
                    $matches[] = $f;
                }
            }
        }
    }
    return $matches;
}


function extractLatexFormulas_v3($text) {
    // 1. HTML/엔티티/제어문자/공백 정리
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = strip_tags($text);
    $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = trim($text);

    // 2. 모든 수식 패턴을 한 번에
    $patterns = [
        // 1) 블록 수식 환경 (예: aligned, matrix 등)
        '/\\\\begin\{([a-zA-Z*]+)\}(.*?)\\\\end\{\1\}/su',
        // 2) $$ ... $$
        '/\$\$(.*?)\$\$/su',
        // 3) \[ ... \]
        '/\\\\\[(.*?)\\\\\]/su',
        // 4) \( ... \)
        '/\\\\\((.*?)\\\\\)/su',
        // 5) $...$
        '/\$(.*?)\$/su',
    ];
    $matches = [];
    foreach ($patterns as $i => $pattern) {
        if (preg_match_all($pattern, $text, $found)) {
            // 블록환경은 $found[0], 나머지는 $found[1]
            foreach (($i === 0 ? $found[0] : $found[1]) as $formula) {
                // 유사역슬래시(￦, ₩, W 등) 처리
                $formula = preg_replace('/[\x{20A9}\x{FFE6}W]/u', '\\', $formula);
                // \displaystyle 제거
                $formula = str_replace('\displaystyle', '', $formula);
                // \ (역슬래시+공백) 제거
                $formula = str_replace('\ ', '', $formula);
                $clean = trim($formula);
                if (mb_strlen($clean) > 0) $matches[] = $clean;
            }
        }
    }
    // 중복제거 및 정렬
    return array_values(array_unique($matches));
}



// 3. 전체 수식 추출 (임시 치환 → 추출 → 복원)
function extractLatexFormulas_fracProxy($text) {
    $text = preprocessFracProxy($text);

    $patterns = [
        '/\$\$(.*?)\$\$/s',
        '/\\\\\[(.*?)\\\\\]/s',
        '/\\\\\((.*?)\\\\\)/s',
        '/\$(.*?)\$/s',
    ];
    $matches = [];
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $text, $found)) {
            foreach ($found[1] as $formula) {
                $clean = trim($formula);
                if (mb_strlen($clean) > 0) {
                    // 추출된 수식에서 프록시 복원
                    $matches[] = postprocessFracProxy($clean);
                }
            }
        }
    }
    return array_values(array_unique($matches));
}

function preprocessFracProxy($text) {
    $text = preg_replace('/(\\\\|₩|￦)frac/', '@@FRAC@@', $text);
    return $text;
}
function postprocessFracProxy($text) {
    return str_replace('@@FRAC@@', '\\frac', $text);
}

?>
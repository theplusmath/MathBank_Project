<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'functions.php';  // ✅ 가장 첫 줄에 추가

// 로그 파일 초기화 (새로운 요청마다 이전 로그를 덮어쓰려면 아래 주석을 해제)
// file_put_contents(__DIR__ . '/debug_post_log.txt', '');
file_put_contents(__DIR__ . '/debug_post_log.txt', "\n\n--- [START LOG] " . date('Y-m-d H:i:s') . " ---\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug_post_log.txt', "[ℹ️ update_problem.php 실행]\n", FILE_APPEND);
file_put_contents(__DIR__ . '/debug_post_log.txt', "[ℹ️ POST DATA]: " . print_r($_POST, true), FILE_APPEND);


// 데이터베이스 연결
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');

// 데이터베이스 연결 확인
if ($conn->connect_errno) {
    $error_msg = "[❌ DB 연결 실패] " . $conn->connect_error . "\n";
    file_put_contents(__DIR__ . '/debug_post_log.txt', $error_msg, FILE_APPEND);
    die($error_msg);
}
file_put_contents(__DIR__ . '/debug_post_log.txt', "[✅ DB 연결 성공]\n", FILE_APPEND);

$conn->set_charset('utf8mb4');

// 🎯 현재 연결된 데이터베이스 이름 확인
$currentDbResult = $conn->query("SELECT DATABASE()");
if ($currentDbResult) {
    $currentDbName = $currentDbResult->fetch_row()[0];
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[🎯 현재 연결된 데이터베이스] " . $currentDbName . "\n", FILE_APPEND);
    $currentDbResult->free();
} else {
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[⚠️ 현재 DB 이름 조회 실패] " . $conn->error . "\n", FILE_APPEND);
}

// 📋 문제 테이블 컬럼 목록 로그 (DB 연결 직후)
$columnsResult = $conn->query("SHOW COLUMNS FROM problems");
if ($columnsResult) {
    $columnNames = [];
    while ($row = $columnsResult->fetch_assoc()) {
        $columnNames[] = $row['Field'];
    }
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[📋 problems 테이블 컬럼 목록 - 연결 직후]\n" . implode(', ', $columnNames) . "\n", FILE_APPEND);
    $columnsResult->free();
} else {
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[⚠️ 컬럼 조회 실패 - 연결 직후] " . $conn->error . "\n", FILE_APPEND);
}

// ℹ️ 10초 대기 후 컬럼 상태 재확인 (이전 대화에서 필요 없다고 판단되었으나, 현재 문제 진단을 위해 포함)
// sleep(10); // 이 부분은 실제 운영에서는 필요 없으며, 디버깅 목적입니다.

// $columnsResultAfterSleep = $conn->query("SHOW COLUMNS FROM problems");
// if ($columnsResultAfterSleep) {
//     $columnNamesAfter = [];
//     while ($row = $columnsResultAfterSleep->fetch_assoc()) {
//         $columnNamesAfter[] = $row['Field'];
//     }
//     file_put_contents(__DIR__ . '/debug_post_log.txt', "[📋 sleep(10) 이후 컬럼 목록]\n" . implode(', ', $columnNamesAfter) . "\n", FILE_APPEND);
//     $columnsResultAfterSleep->free();
// } else {
//     file_put_contents(__DIR__ . '/debug_post_log.txt', "[⚠️ sleep 후 컬럼 조회 실패] " . $conn->error . "\n", FILE_APPEND);
// }

// ℹ️ 관리자 이메일 (copied_by 설정을 위함)
$adminEmail = 'admin@example.com'; // 실제 관리자 이메일로 변경하세요.
$adminQuery = $conn->query("SELECT id FROM users WHERE email = '$adminEmail' LIMIT 1");
if (!$adminQuery) {
    file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ admin 쿼리 실패] " . $conn->error . "\n", FILE_APPEND);
    die("[❌ admin 쿼리 실패] " . $conn->error);
}
$adminRow = $adminQuery->fetch_assoc();
if (!$adminRow) {
    file_put_contents(__DIR__ . '/debug_post_log.txt', "⚠️ 관리자 계정을 찾을 수 없습니다. 이메일 확인 필요: $adminEmail\n", FILE_APPEND);
    die("⚠️ 관리자 계정을 찾을 수 없습니다. 이메일 확인 필요: $adminEmail");
}
$copied_by = (int)$adminRow['id'];

// POST 데이터 추출 및 기본값 설정
$id = $_POST['id'] ?? 0;
$copyMode = $_POST['copyMode'] ?? '0'; // '1'이면 복사 모드, '0'이면 수정 모드
$title = $_POST['title'] ?? '';
$question = $_POST['question'] ?? '';
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
// 기존 코드: $path_id = $_POST['path_id'] !== '' ? (int)$_POST['path_id'] : null;
// 이 코드로 변경:
$path_id = isset($_POST['path_id']) && $_POST['path_id'] !== '' ? (int)$_POST['path_id'] : null;


// 경로 자동 처리
if ((!$path_id || $path_id === 0) && $path_text) {
    // 우선 정확한 경로 구조로 찾기
    $path_id = getPathIdFromText($conn, $path_text);

    // 정확히 못 찾았으면 자동 보정 시도
    if (!$path_id) {
        $stmt = $conn->prepare("SELECT id FROM paths WHERE name LIKE CONCAT('%', ?, '%')");
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
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ 경로 인식 실패 또는 중복됨: $path_text]\n", FILE_APPEND);
            echo "<script>alert('❌ 경로 인식 실패 또는 중복됨: $path_text'); history.back();</script>";
            exit;
        }
    }

    file_put_contents(__DIR__ . '/debug_post_log.txt', "[✅ path_id 설정됨: $path_id]\n", FILE_APPEND);
}

// ℹ️ 수식 분석 (외부 파일 `mathpix_analyzer.php` 필요)
require_once 'mathpix_analyzer.php'; // 이 파일이 존재하는지 확인하세요.
$analyzedResults = analyzeFormulasFromQuestion($question);
$mainFormulaLatex = '';
$mainFormulaTree = '';
$allFormulasTree = '';
$formulasKeywords = '';
$mainFormulaHash = '';
$mainFormulaSympy = '';
$allKeywords = [];

if (!empty($analyzedResults)) {
    // 수식 길이 기준으로 정렬 (가장 긴 수식이 메인으로)
    usort($analyzedResults, fn($a, $b) => mb_strlen($b['latex']) - mb_strlen($a['latex']));

    // 메인 수식 관련 정보 추출 (이전 답변과 동일하게 유지)
    $mainFormulaLatex = implode(', ', array_column(array_slice($analyzedResults, 0, 3), 'latex'));
    $mainFormulaLatex = mb_substr($mainFormulaLatex, 0, 500); // 500자 제한
    $mainFormulaHash = $analyzedResults[0]['hash'];
    $mainFormulaTree = json_encode($analyzedResults[0]['tree'], JSON_UNESCAPED_UNICODE);
    $mainFormulaSympy = $analyzedResults[0]['sympy_expr'];

    // 모든 수식 트리 및 키워드 추출 (이전 답변과 동일하게 유지)
    $allFormulasTree = json_encode(array_map(fn($f) => [
        'latex' => $f['latex'], 'tree' => $f['tree'], 'hash' => $f['hash']
    ], $analyzedResults), JSON_UNESCAPED_UNICODE);

    foreach ($analyzedResults as $r) {
        $allKeywords = array_merge($allKeywords, $r['keywords']);
    }
    $formulasKeywords = implode(',', array_unique($allKeywords));
}
file_put_contents(__DIR__ . '/debug_post_log.txt', "[ℹ️ Analyzed Results]: mainFormulaLatex='" . $mainFormulaLatex . "', mainFormulaHash='" . $mainFormulaHash . "', formulasKeywords='" . $formulasKeywords . "'\n", FILE_APPEND);
// ✅ formula_keywords 컬럼이 존재하면 업데이트 실행
if (in_array('formula_keywords', $columnNames)) {
    $stmt = $conn->prepare("UPDATE problems SET formula_keywords = ? WHERE id = ?");
    if (!$stmt) {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ formula_keywords prepare 실패] " . $conn->error . "\n", FILE_APPEND);
    } else {
        $stmt->bind_param("si", $formulasKeywords, $id);
        if ($stmt->execute()) {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[✅ formula_keywords UPDATE 성공]\n", FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ formula_keywords UPDATE 실패] " . $stmt->error . "\n", FILE_APPEND);
        }
        $stmt->close();
    }
}

// ✅ main_formula_latex 컬럼이 존재하면 업데이트 실행
if (in_array('main_formula_latex', $columnNames)) {
    $stmt = $conn->prepare("UPDATE problems SET main_formula_latex = ? WHERE id = ?");
    if (!$stmt) {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ main_formula_latex prepare 실패] " . $conn->error . "\n", FILE_APPEND);
    } else {
        $stmt->bind_param("si", $mainFormulaLatex, $id);
        if ($stmt->execute()) {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[✅ main_formula_latex UPDATE 성공]\n", FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ main_formula_latex UPDATE 실패] " . $stmt->error . "\n", FILE_APPEND);
        }
        $stmt->close();
    }
}


// ✅ main_formula_tree 컬럼이 존재하면 업데이트 실행
if (in_array('main_formula_tree', $columnNames)) {
    $stmt = $conn->prepare("UPDATE problems SET main_formula_tree = ? WHERE id = ?");
    if (!$stmt) {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ main_formula_tree prepare 실패] " . $conn->error . "\n", FILE_APPEND);
    } else {
        $stmt->bind_param("si", $mainFormulaTree, $id);
        if ($stmt->execute()) {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[✅ main_formula_tree UPDATE 성공]\n", FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ main_formula_tree UPDATE 실패] " . $stmt->error . "\n", FILE_APPEND);
        }
        $stmt->close();
    }
}


// ✅ all_formulas_tree 컬럼이 존재하면 업데이트 실행
if (in_array('all_formulas_tree', $columnNames)) {
    $stmt = $conn->prepare("UPDATE problems SET all_formulas_tree = ? WHERE id = ?");
    if (!$stmt) {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ all_formulas_tree prepare 실패] " . $conn->error . "\n", FILE_APPEND);
    } else {
        $stmt->bind_param("si", $allFormulasTree, $id);
        if ($stmt->execute()) {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[✅ all_formulas_tree UPDATE 성공]\n", FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ all_formulas_tree UPDATE 실패] " . $stmt->error . "\n", FILE_APPEND);
        }
        $stmt->close();
    }
}


// ✅ main_formula_hash 컬럼이 존재하면 업데이트 실행
if (in_array('main_formula_hash', $columnNames)) {
    $stmt = $conn->prepare("UPDATE problems SET main_formula_hash = ? WHERE id = ?");
    if (!$stmt) {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ main_formula_hash prepare 실패] " . $conn->error . "\n", FILE_APPEND);
    } else {
        $stmt->bind_param("si", $mainFormulaHash, $id);
        if ($stmt->execute()) {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[✅ main_formula_hash UPDATE 성공]\n", FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ main_formula_hash UPDATE 실패] " . $stmt->error . "\n", FILE_APPEND);
        }
        $stmt->close();
    }
}

// ✅ main_formula_sympy 컬럼이 존재하면 업데이트 실행
if (in_array('main_formula_sympy', $columnNames)) {
    $stmt = $conn->prepare("UPDATE problems SET main_formula_sympy = ? WHERE id = ?");
    if (!$stmt) {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ main_formula_sympy prepare 실패] " . $conn->error . "\n", FILE_APPEND);
    } else {
        $stmt->bind_param("si", $mainFormulaSympy, $id);
        if ($stmt->execute()) {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[✅ main_formula_sympy UPDATE 성공]\n", FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ main_formula_sympy UPDATE 실패] " . $stmt->error . "\n", FILE_APPEND);
        }
        $stmt->close();
    }
}

// ✅ hash 컬럼이 존재하면 업데이트 실행
if (in_array('hash', $columnNames)) {
    $stmt = $conn->prepare("UPDATE problems SET hash = ? WHERE id = ?");
    if (!$stmt) {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ hash prepare 실패] " . $conn->error . "\n", FILE_APPEND);
    } else {
        $stmt->bind_param("si", $mainFormulaHash, $id);
        if ($stmt->execute()) {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[✅ hash UPDATE 성공]\n", FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ hash UPDATE 실패] " . $stmt->error . "\n", FILE_APPEND);
        }
        $stmt->close();
    }
}


// ✅ sympy_expr 컬럼이 존재하면 업데이트 실행
if (in_array('sympy_expr', $columnNames)) {
    $stmt = $conn->prepare("UPDATE problems SET sympy_expr = ? WHERE id = ?");
    if (!$stmt) {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ sympy_expr prepare 실패] " . $conn->error . "\n", FILE_APPEND);
    } else {
        $stmt->bind_param("si", $mainFormulaSympy, $id);
        if ($stmt->execute()) {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[✅ sympy_expr UPDATE 성공]\n", FILE_APPEND);
        } else {
            file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ sympy_expr UPDATE 실패] " . $stmt->error . "\n", FILE_APPEND);
        }
        $stmt->close();
    }
}


// --- 🔽 INSERT/UPDATE 로직 시작 🔽 ---
// 이 아래 부분이 단계별로 교체될 코드입니다.





// INSERT 모드 (복사) 처리
if ($copyMode === '1') {
    // ✅ 복사본 제목 처리
    if (preg_match('/^\[복사본(?: (\d+))?\]\s*(.+)$/u', $title, $matches)) {
        $baseTitle = $matches[2];
        $copyNumber = isset($matches[1]) ? intval($matches[1]) + 1 : 2;
        $title = "[복사본 {$copyNumber}] $baseTitle";
    } else {
        $title = "[복사본] $title";
    }

    // 나머지 복사 INSERT 처리 계속...

    // 현재 시간
    $created_at = date('Y-m-d H:i:s');

    // INSERT 쿼리 실행
    $stmt = $conn->prepare("
        INSERT INTO problems (
            title, question, answer, solution, hint, video, difficulty, type, category, source,
            created_by, tags, path_text, path_id,
            copied_by, origin_id, main_formula_latex, main_formula_tree, all_formulas_tree,
            formula_keywords, hash, sympy_expr, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ 복사 prepare 실패] " . $conn->error . "\n", FILE_APPEND);
        die("복사 prepare 실패: " . $conn->error);
    }

    $stmt->bind_param("ssssssisssissiiisssssss",
        $title, $question, $answer, $solution, $hint, $video,
        $difficulty, $type, $category, $source,
        $created_by, $tags, $path_text, $path_id,
        $copied_by, $id, // origin_id는 현재 문제 ID
        $mainFormulaLatex, $mainFormulaTree, $allFormulasTree,
        $formulasKeywords, $mainFormulaHash, $mainFormulaSympy, $created_at
    );

    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[✅ 복사 INSERT 성공 - new_id = $newId]\n", FILE_APPEND);
        echo "<script>
        alert('복사 완료. 새 문제 ID: {$newId}');
        window.open('edit_problem.php?id={$newId}', '_blank');
        window.location.href = 'list_problems.html';
    </script>";
    } else {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ 복사 INSERT 실패] " . $stmt->error . "\n", FILE_APPEND);
        die("복사 실패: " . $stmt->error);
    }

    $stmt->close();
    exit;
}

else {

    // 🔁 기존 문제 내용을 history_problems에 백업
    $backupResult = $conn->query("SELECT * FROM problems WHERE id = $id");
    if ($backupResult && $backupResult->num_rows > 0) {
        $old = $backupResult->fetch_assoc();

        $fields = [
            'problem_id', 'title', 'question', 'answer', 'solution', 'hint', 'video',
            'difficulty', 'type', 'category', 'source', 'created_by', 'tags',
            'path_text', 'path_id', 'copied_by', 'origin_id',
            'main_formula_latex', 'main_formula_tree', 'formula_keywords', 'all_formulas_tree',
            'updated_at'
        ];

        $values = array_map(function($f) use ($conn, $old) {
            if ($f === 'problem_id') return intval($old['id']);
            if ($f === 'updated_at') return "'" . date('Y-m-d H:i:s') . "'";
            return "'" . $conn->real_escape_string($old[$f] ?? '') . "'";
        }, $fields);

        $columnList = implode(', ', $fields);
        $valueList = implode(', ', $values);

        $conn->query("INSERT INTO history_problems ($columnList) VALUES ($valueList)");
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[📦 수정 전 문제 백업 완료]\n", FILE_APPEND);
    } else {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[⚠️ 수정 전 문제 백업 실패 or 없음]\n", FILE_APPEND);
    }


    $stmt = $conn->prepare("
        UPDATE problems SET
            title = ?, question = ?, answer = ?, solution = ?, hint = ?, video = ?,
            difficulty = ?, type = ?, category = ?, source = ?,
            created_by = ?, tags = ?, path_text = ?, path_id = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ 일반 필드 prepare 실패] " . $conn->error . "\n", FILE_APPEND);
        die("문제 수정 실패 (prepare): " . $conn->error);
    }

    $stmt->bind_param("ssssssisssssiii",
        $title, $question, $answer, $solution, $hint, $video,
        $difficulty, $type, $category, $source,
        $created_by, $tags, $path_text, $path_id,
        $id
    );

    if ($stmt->execute()) {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[✅ 일반 필드 UPDATE 성공]\n", FILE_APPEND);
        echo "<script>alert('문제 수정 완료'); location.href='edit_problem.php?id={$id}';</script>";
    } else {
        file_put_contents(__DIR__ . '/debug_post_log.txt', "[❌ 일반 필드 UPDATE 실패] " . $stmt->error . "\n", FILE_APPEND);
        die("문제 수정 실패 (execute): " . $stmt->error);
    }

    $stmt->close();
}



?>
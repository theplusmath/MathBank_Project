<?php
/**
 * functions.php
 * ���� ��ƿ��Ƽ �Լ� ����
 * - ��� ó��
 * - ���� �м�
 * - ���� ��ȸ �� ���
 * - DB ����
 */



// ===============================
// ?? PATH ���� �Լ�
// ===============================

function getPathIdFromText($conn, $pathText) {
    $names = preg_split('/[\/~]/u', $pathText);
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

// ===============================
// ?? ���� ���� �� �м�
// ===============================

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

function isMeaningfulFormula($formula) {
    $trimmed = trim($formula);

    if (preg_match('/^[a-zA-Z]$/', $trimmed)) return false;
    if (preg_match('/^([a-zA-Z0-9_]+, *)+[a-zA-Z0-9_]+$/', $trimmed)) return false;
    if (preg_match('/^\\\\?[=+\-*/\\^\\., ]+$/', $trimmed)) return false;

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
        // �α� ���Ͽ� �ð�, �ڵ�, ��û ���� �����
        $logMessage = "[" . date("Y-m-d H:i:s") . "] API ���� - HTTP $httpCode\n";
        $logMessage .= "��û ����: " . json_encode($formulas, JSON_UNESCAPED_UNICODE) . "\n";
        $logMessage .= "����: " . $response . "\n\n";
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

    // ? ��ǥ ����: ���� �Է��� ������ �װ��� ����ϰ�, ������ �ڵ� ����
    $mainFormulasStr = '';
    if (!empty(trim($_POST['main_formula_latex']))) {
        $mainFormulasStr = trim($_POST['main_formula_latex']);
    }
    } else {
        $mainFormulas = selectMainFormulas($formulas);
        $mainFormulasStr = implode(', ', $mainFormulas);
    }

    // ? ��ǥ ���� �߿��� ���� �� �� �� tree �����
    $mainFormulaForTree = selectMainFormulaForTree($formulas);

    // ? Render API ȣ��
    $analyzed = analyzeFormulasWithAPI($formulas);
    if (empty($analyzed)) return;

    // ? ��ǥ ���� Ʈ��
    $mainTree = '';
    foreach ($analyzed as $item) {
        if ($item['latex'] === $mainFormulaForTree) {
            $mainTree = json_encode($item['tree'], JSON_UNESCAPED_UNICODE);
            break;
        }
    }

    // ? ��ü ���� Ʈ��
    $allFormulasTree = [];
    foreach ($analyzed as $item) {
        $allFormulasTree[] = [
            'latex' => $item['latex'],
            'tree' => $item['tree'],
            'hash' => $item['hash']
        ];
    }

    // ? Ű���� ����
    $keywords = extractUniqueKeywords($analyzed);
    $keywordsStr = implode(',', $keywords);

    // ? DB ������Ʈ
    $stmt = $conn->prepare("UPDATE problems 
        SET main_formula_latex = ?, main_formula_tree = ?, all_formulas_tree = ?, formula_keywords = ? 
        WHERE id = ?");
    $jsonAllFormulasTree = json_encode($allFormulasTree, JSON_UNESCAPED_UNICODE);
    $stmt->bind_param("ssssi", $mainFormulasStr, $mainTree, $jsonAllFormulasTree, $keywordsStr, $problem_id);
    $stmt->execute();
    $stmt->close();
}

function analyzeFormulasFromQuestion($question): array {
    $formulas = extractLatexFormulas($question);
    if (empty($formulas)) return [];
    return analyzeFormulasWithAPI($formulas);
}

// ===============================
// ?? ���� ��ȸ �� ���
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

// ? DB ���� �����
function connectDB() {
    $conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
    $conn->set_charset('utf8mb4');

    if ($conn->connect_error) {
        die('DB ���� ����: ' . $conn->connect_error);
    }

    return $conn;
}

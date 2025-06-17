<?php
// mathpix_analyzer.php

/**
 * HTML 엔티티를 LaTeX로 변환
 * @param string $text
 * @return string
 */
function convertHtmlEntitiesToLatex($text) {
    // HTML 엔티티 디코딩
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // 특수 문자 처리
    $replacements = [
        '&nbsp;' => ' ',
        '&lt;' => '<',
        '&gt;' => '>',
        '&amp;' => '&',
        '&quot;' => '"',
        '&#39;' => "'"
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $text);
}

/**
 * LaTeX 수식 추출 및 API로 분석
 * @param string $question 문제 텍스트(HTML 태그 포함)
 * @return array 결과 [latex, tree, hash, keywords, sympy_expr] 배열
 */
function analyzeFormulasFromQuestion($question) {
    // (1) HTML 엔티티 변환
    $cleaned_question = convertHtmlEntitiesToLatex($question);

    // (2) 수식 추출 패턴 (MathLive 호환)
    $patterns = [
        '/\\\\\((.*?)\\\\\)/s',        // \( ... \)
        '/\\\\\[(.*?)\\\\\]/s',        // \[ ... \]
        '/\$\$(.*?)\$\$/s',            // $$ ... $$
        '/\$(.*?)\$/s',                // $ ... $
        '/\\\\\(\s*\\displaystyle\s*(.*?)\\\\\)/s',  // \( \displaystyle ... \)
        '/<math-field[^>]*>(.*?)<\/math-field>/s',   // MathLive 필드
        '/<math-field[^>]*value="(.*?)"[^>]*>/s'     // MathLive value 속성
    ];
    
    $formulas = array();
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $cleaned_question, $matches)) {
            foreach ($matches[1] as $f) {
                $latex = convertHtmlEntitiesToLatex($f);
                if ($latex !== '') {
                    $formulas[] = $latex;
                }
            }
        }
    }
    $formulas = array_unique($formulas);

    // 의미 있는 수식만 필터링
    $formulas = array_filter($formulas, function($f) {
        return (mb_strlen($f) > 2 && !preg_match('/^[a-zA-Z0-9\s]+$/u', $f));
    });

    $results = array();
    foreach ($formulas as $latex) {
        try {
            $response = fetch_formula_analysis_from_api($latex);
            if ($response === false) {
                error_log("API call failed for formula: " . $latex);
                continue;
            }
            
            $results[] = array(
                'latex' => $latex,
                'tree' => $response['main_formula_tree'] ?? '',
                'hash' => $response['hash'] ?? '',
                'keywords' => $response['formula_keywords'] ?? array(),
                'sympy_expr' => $response['sympy_expr'] ?? '',
            );
        } catch (Exception $e) {
            error_log("Error processing formula: " . $latex . " - " . $e->getMessage());
            continue;
        }
    }
    return $results;
}

/**
 * 수식 하나를 외부 API로 분석 요청
 * @param string $latex
 * @return array|false
 */
function fetch_formula_analysis_from_api($latex) {
    $api_url = "https://math-api-83wx.onrender.com/normalize";
    $payload = json_encode(["latex" => $latex]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 타임아웃 (10초)
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("CURL Error: " . $error);
        return false;
    }

    if ($httpCode !== 200) {
        error_log("API returned non-200 status code: " . $httpCode);
        return false;
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        return false;
    }

    return $data;
}

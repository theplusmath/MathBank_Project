<?php
// mathpix_analyzer.php

/**
 * HTML 엔티티를 LaTeX로 변환 (실전용, 더플러스수학 강화 버전)
 * @param string $str
 * @return string
 */
function convertHtmlEntitiesToLatex($str) {
    $map = array(
        // 기본 수학 부등호/연산자
        '&le;'        => '\\leq ',
        '&leq;'       => '\\leq ',
        '&ge;'        => '\\geq ',
        '&geq;'       => '\\geq ',
        '&ne;'        => '\\neq ',
        '&lt;'        => '<',
        '&gt;'        => '>',
        '&plusmn;'    => '\\pm ',
        '&pm;'        => '\\pm ',
        '&minus;'     => '-',
        '&times;'     => '\\times ',
        '&ast;'       => '*',
        '&star;'      => '\\star ',
        '&div;'       => '\\div ',
        '&frasl;'     => '/',
        '&sol;'       => '/',
        '&sum;'       => '\\sum ',
        '&prod;'      => '\\prod ',
        '&cap;'       => '\\cap ',
        '&cup;'       => '\\cup ',
        '&sim;'       => '\\sim ',
        '&asymp;'     => '\\asymp ',
        '&approx;'    => '\\approx ',
        '&equiv;'     => '\\equiv ',
        '&cong;'      => '\\cong ',
        '&simeq;'     => '\\simeq ',
        '&nequiv;'    => '\\not\\equiv ',
        '&ncong;'     => '\\not\\cong ',
        '&prop;'      => '\\propto ',
        '&infin;'     => '\\infty ',
        '&infty;'     => '\\infty ',

        // 집합, 포함
        '&in;'        => '\\in ',
        '&isin;'      => '\\in ',
        '&notin;'     => '\\notin ',
        '&ni;'        => '\\ni ',
        '&notni;'     => '\\not\\ni ',
        '&sub;'       => '\\subset ',
        '&sup;'       => '\\supset ',
        '&sube;'      => '\\subseteq ',
        '&supe;'      => '\\supseteq ',
        '&subset;'    => '\\subset ',
        '&supset;'    => '\\supset ',
        '&subseteq;'  => '\\subseteq ',
        '&supseteq;'  => '\\supseteq ',

        // 논리 기호
        '&forall;'    => '\\forall ',
        '&exist;'     => '\\exists ',
        '&nexists;'   => '\\nexists ',
        '&there4;'    => '\\therefore ',
        '&because;'   => '\\because ',
        '&and;'       => '\\land ',
        '&or;'        => '\\lor ',
        '&not;'       => '\\neg ',
        '&implies;'   => '\\implies ',
        '&iff;'       => '\\iff ',

        // 함수/미분/적분/연산
        '&int;'       => '\\int ',
        '&sum;'       => '\\sum ',
        '&prod;'      => '\\prod ',
        '&partial;'   => '\\partial ',
        '&nabla;'     => '\\nabla ',
        '&darr;'      => '\\downarrow ',
        '&uarr;'      => '\\uparrow ',
        '&rarr;'      => '\\rightarrow ',
        '&larr;'      => '\\leftarrow ',
        '&harr;'      => '\\leftrightarrow ',

        // 기하/도형
        '&perp;'      => '\\perp ',
        '&angle;'     => '\\angle ',
        '&measuredangle;' => '\\measuredangle ',
        '&sphericalangle;' => '\\sphericalangle ',
        '&triangle;'  => '\\triangle ',

        // 점,곱
        '&middot;'    => '\\cdot ',
        '&sdot;'      => '\\cdot ',

        // 기호/기타
        '&deg;'       => '^{\\circ}',
        '&prime;'     => "'",
        '&Prime;'     => "''",
        '&hellip;'    => '\\ldots ',
        '&ellipsis;'  => '\\ldots ',
        '&bull;'      => '\\bullet ',

        // 기타 유니코드/문자
        '&nbsp;'      => ' ',
        '&#160;'      => ' ',

        // 특수 문자(조합 가능)
        '&alpha;'     => '\\alpha ',
        '&beta;'      => '\\beta ',
        '&gamma;'     => '\\gamma ',
        '&delta;'     => '\\delta ',
        '&epsilon;'   => '\\epsilon ',
        '&zeta;'      => '\\zeta ',
        '&eta;'       => '\\eta ',
        '&theta;'     => '\\theta ',
        '&iota;'      => '\\iota ',
        '&kappa;'     => '\\kappa ',
        '&lambda;'    => '\\lambda ',
        '&mu;'        => '\\mu ',
        '&nu;'        => '\\nu ',
        '&xi;'        => '\\xi ',
        '&omicron;'   => 'o',
        '&pi;'        => '\\pi ',
        '&rho;'       => '\\rho ',
        '&sigma;'     => '\\sigma ',
        '&tau;'       => '\\tau ',
        '&upsilon;'   => '\\upsilon ',
        '&phi;'       => '\\phi ',
        '&chi;'       => '\\chi ',
        '&psi;'       => '\\psi ',
        '&omega;'     => '\\omega ',
        '&Gamma;'     => '\\Gamma ',
        '&Delta;'     => '\\Delta ',
        '&Theta;'     => '\\Theta ',
        '&Lambda;'    => '\\Lambda ',
        '&Xi;'        => '\\Xi ',
        '&Pi;'        => '\\Pi ',
        '&Sigma;'     => '\\Sigma ',
        '&Upsilon;'   => '\\Upsilon ',
        '&Phi;'       => '\\Phi ',
        '&Psi;'       => '\\Psi ',
        '&Omega;'     => '\\Omega ',
    );
    foreach ($map as $entity => $latex) {
        $str = str_replace($entity, $latex, $str);
    }
    // 나머지 엔티티 일반 문자로 변환
    $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $str = str_replace("\xC2\xA0", ' ', $str); // non-breaking space
    $str = str_replace('&nbsp;', ' ', $str);
    $str = strip_tags($str);
    $str = preg_replace('/\s+/u', ' ', $str);
    return trim($str);
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

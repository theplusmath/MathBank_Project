<?php
function analyzeFormulasFromQuestion($question) {
    // 수식 추출 정규식
    preg_match_all('/\\\((.*?)\\\)|\\\[(.*?)\\\]|\$\$(.*?)\$\$|\$(.*?)\$|\\\(\s*\\displaystyle\s*(.*?)\\\)/s', $question, $matches);
    $formulas = array_filter(array_merge($matches[1], $matches[2], $matches[3], $matches[4], $matches[5]));

    $results = [];
    foreach ($formulas as $latex) {
        $latex = trim($latex);
        $response = fetch_formula_analysis_from_api($latex);

        $results[] = [
            'latex' => $latex,
            'tree' => $response['main_formula_tree'] ?? '',
            'hash' => $response['hash'] ?? '',
            'keywords' => $response['formula_keywords'] ?? [],
            'sympy_expr' => $response['sympy_expr'] ?? '',
        ];
    }
    return $results;
}

function fetch_formula_analysis_from_api($latex) {
    $api_url = "https://math-api-83wx.onrender.com/normalize";
    $payload = json_encode(["latex" => $latex]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

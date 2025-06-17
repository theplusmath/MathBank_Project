<?php
function get_formula_data_from_api($latex) {
    $api_url = "https://math-api-83wx.onrender.com/normalize";
    $payload = json_encode(["latex" => "\\displaystyle " . $latex]); // Áß¿ä!

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);

    echo json_encode([
        "input_latex" => $latex,
        "api_response" => $decoded
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

get_formula_data_from_api("\\displaystyle x^2 + y^2 + 2x - 6y + 1 = 0");

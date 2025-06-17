<?php
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

$sql = "SELECT id, question FROM problems WHERE main_formula_tree IS NULL LIMIT 5";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $question = strip_tags($row['question']);

    $payload = json_encode(["latex" => $question]);
    $ch = curl_init('http://localhost:8000/latex/parse'); // FastAPI 주소
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);

    if ($response === false) {
        echo "? CURL 오류 (ID $id): " . curl_error($ch) . "<br>";
        curl_close($ch);
        continue;
    }

    curl_close($ch);
    $data = json_decode($response, true);

    if (isset($data['main_formula_latex'])) {
        $keywords = isset($data['formula_keywords']) ? implode(',', $data['formula_keywords']) : '';

        $stmt = $conn->prepare("UPDATE problems SET 
            main_formula_latex = ?, 
            main_formula_tree = ?, 
            all_formulas_tree = ?, 
            formula_keywords = ? 
            WHERE id = ?");

        $stmt->bind_param("ssssi", 
            $data['main_formula_latex'], 
            $data['main_formula_tree'], 
            json_encode($data['all_formulas_tree'], JSON_UNESCAPED_UNICODE),
            $keywords,
            $id
        );

        $stmt->execute();
        echo "? 문제 ID $id 분석 완료<br>";
    } else {
        echo "? 문제 ID $id 분석 실패<br>";
    }
}
?>

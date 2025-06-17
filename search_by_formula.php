<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=utf-8');

// ? FastAPI 서버에 수식 전송 → hash 받기
function get_formula_hash($latex) {
    $api_url = "https://math-api-83wx.onrender.com/normalize";
    $payload = json_encode(["latex" => $latex]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['hash'] ?? null;
}

// ? DB 연결
$conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
$conn->set_charset("utf8mb4");

// ? 사용자 입력 수식
$input_latex = trim($_POST['latex'] ?? '');
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>수식으로 문제 검색</title>
</head>
<body>
    <h2>?? 수식으로 문제 검색</h2>

    <form method="POST">
        <label>LaTeX 수식:</label><br>
        <input type="text" name="latex" size="80" placeholder="예: \frac{2}{3}+x^2" value="<?= htmlspecialchars($input_latex) ?>"><br><br>
        <button type="submit">검색</button>
    </form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$input_latex) {
        echo "<p>?? 수식을 입력해주세요.</p>";
        exit;
    }

    $hash = get_formula_hash($input_latex);
    if (!$hash) {
        echo "<p>? FastAPI 해시 요청 실패</p>";
        exit;
    }

    $stmt = $conn->prepare("
        SELECT 
            p.id, p.title, p.question, p.created_at,
            f.original_formula, f.main_formula_tree, f.formula_keywords
        FROM formula_index f
        JOIN problems p ON f.problem_id = p.id
        WHERE f.formula_skeleton_hash = ?
    ");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<h2>? 유사 수식이 포함된 문제 결과</h2>";
    echo "<table border='1' cellpadding='6'>";
    echo "<tr>
            <th>ID</th>
            <th>제목</th>
            <th>문제</th>
            <th>원래 수식</th>
            <th>수식 트리</th>
            <th>키워드</th>
            <th>등록일</th>
          </tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['question']) . "</td>";
        echo "<td>" . htmlspecialchars($row['original_formula']) . "</td>";
        echo "<td>" . htmlspecialchars($row['main_formula_tree']) . "</td>";
        echo "<td>" . htmlspecialchars($row['formula_keywords']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    $stmt->close();
    $conn->close();
}
?>
</body>
</html>

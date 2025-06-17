<?php
header('Content-Type: text/html; charset=utf-8');  // ? alert로 HTML을 직접 출력하는 경우 반드시 이걸로


// restore_problem.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $problem_id = $_POST['problem_id'] ?? 0;
    $timestamp = $_POST['timestamp'] ?? '';

    if (!$problem_id || !$timestamp) {
        echo "? 유효하지 않은 요청입니다.";
        exit;
    }

    // 이력에서 문제 데이터 불러오기
    $stmt = $conn->prepare("SELECT * FROM history_problems WHERE problem_id = ? AND updated_at = ?");
    $stmt->bind_param("is", $problem_id, $timestamp);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = $result->fetch_assoc();
    $stmt->close();

    if (!$history) {
        echo "? 이력 데이터를 찾을 수 없습니다.";
        exit;
    }

    // 덮어쓸 필드만 선택
    $fields = ['title', 'question', 'answer', 'solution', 'hint', 'difficulty', 'type', 'tags', 'path_text', 'main_formula_latex'];

    $setClause = implode(', ', array_map(fn($f) => "$f = ?", $fields));
    $types = str_repeat('s', count($fields)) . 'i'; // string * n, 마지막 id는 int
    $values = array_map(fn($f) => $history[$f] ?? '', $fields);
    $values[] = $problem_id;

    $stmt = $conn->prepare("UPDATE problems SET $setClause WHERE id = ?");
    $stmt->bind_param($types, ...$values);
    $stmt->execute();
    $stmt->close();

    print <<<HTML
<script>
    alert("✅ 문제 복원 성공!");
    history.back();
</script>
HTML;
}
?>

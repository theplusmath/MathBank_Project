<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
    exit;
}

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['ids'] ?? [];

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => '복원할 문제가 선택되지 않았습니다.']);
    exit;
}

$restored = 0;

foreach ($ids as $id) {
    $id = intval($id);

    // 삭제된 문제 가져오기
    $result = $conn->query("SELECT * FROM deleted_problems WHERE id = $id");
    if ($result && $row = $result->fetch_assoc()) {
        // 복사할 필드만 추려서 INSERT
        $fields = [
            'title', 'question', 'answer', 'solution', 'hint', 'video',
            'difficulty', 'type', 'category', 'source', 'created_by', 'tags',
            'path_text', 'path_id', 'copied_by', 'origin_id',
            'main_formula_latex', 'main_formula_tree', 'formula_keywords', 'all_formulas_tree'
        ];

        $columns = implode(", ", $fields);
        $values = implode(", ", array_map(function($field) use ($conn, $row) {
            return "'" . $conn->real_escape_string($row[$field]) . "'";
        }, $fields));

        $insertSQL = "INSERT INTO problems ($columns) VALUES ($values)";
        if ($conn->query($insertSQL)) {
            // 원본에서 삭제
            $conn->query("DELETE FROM deleted_problems WHERE id = $id");
            $restored++;
        }
    }
}

echo json_encode(['success' => true, 'restored' => $restored]);
?>

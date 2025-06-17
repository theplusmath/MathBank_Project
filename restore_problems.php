<?php
require_once 'functions.php';

header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = connectDB();

$input = json_decode(file_get_contents('php://input'), true);
$ids = $input['ids'] ?? [];
$overwrite = $input['overwrite'] ?? false;

if (empty($ids)) {
    echo json_encode(['success' => false, 'message' => '복원할 문제가 선택되지 않았습니다.']);
    exit;
}

$restored = 0;
$skipped = [];
$overwritten = [];

// 복원 대상 필드 정의
$fields = [
    'id', 'title', 'question', 'answer', 'solution', 'hint', 'video',
    'difficulty', 'type', 'category', 'source', 'created_by', 'tags',
    'path_text', 'path_id', 'copied_by', 'origin_id',
    'main_formula_latex', 'main_formula_tree', 'formula_keywords', 'all_formulas_tree'
];
$columns = implode(', ', $fields);

foreach ($ids as $id) {
    $id = intval($id);

    $result = $conn->query("SELECT * FROM deleted_problems WHERE id = $id");
    if (!$result || $result->num_rows === 0) {
        $skipped[] = $id;
        continue;
    }

    $row = $result->fetch_assoc();

    // 값 구성
    $values = implode(', ', array_map(function($field) use ($conn, $row) {
        return "'" . $conn->real_escape_string($row[$field] ?? '') . "'";
    }, $fields));

    $exists = $conn->query("SELECT id FROM problems WHERE id = $id");

    if ($exists && $exists->num_rows > 0) {
        if ($overwrite) {
            $conn->query("DELETE FROM problems WHERE id = $id");

            $insert = $conn->query("INSERT INTO problems ($columns) VALUES ($values)");
            if ($insert) {
                $conn->query("DELETE FROM deleted_problems WHERE id = $id");
                $restored++;
                $overwritten[] = $id;
            } else {
                $skipped[] = $id;
            }
        } else {
            $skipped[] = $id;
        }
    } else {
        if ($conn->query("INSERT INTO problems ($columns) VALUES ($values)")) {
            $conn->query("DELETE FROM deleted_problems WHERE id = $id");
            $restored++;
        } else {
            $skipped[] = $id;
        }
    }
}

echo json_encode([
    'success' => true,
    'restored' => $restored,
    'skipped' => $skipped,
    'overwritten' => $overwritten
]);

<?php
header('Content-Type: application/json; charset=utf-8');

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$file = $_FILES['upload'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => '업로드 실패']]);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $ext;
$filepath = $uploadDir . $filename;

move_uploaded_file($file['tmp_name'], $filepath);

echo json_encode([
    'url' => '/upload/uploads/' . $filename  // 클라이언트가 불러올 수 있는 URL
]);

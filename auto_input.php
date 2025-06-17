<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 사용자가 입력한 텍스트를 문제 단위로 나누고 각각을 처리
function parse_all_inputs($inputText) {
    $lines = explode("\n", trim($inputText));
    $blocks = [];
    $current = [];
    $title_candidate = null;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        // *경로:를 만나면 그 바로 위 줄을 제목으로 사용
        if (str_starts_with($trimmed, '*경로:')) {
            if ($title_candidate !== null) {
                $current[] = '*제목:' . $title_candidate;
                $title_candidate = null;
            }
        }

        // 제목 후보 저장 (*이 없는 일반 텍스트 줄)
        if ($trimmed !== '' && !str_starts_with($trimmed, '*')) {
            $title_candidate = $trimmed;
        }

        if (str_starts_with($trimmed, '*경로:') && !empty($current)) {
            $blocks[] = implode("\n", $current);
            $current = [];
        }

        $current[] = $line;
    }
    if (!empty($current)) {
        $blocks[] = implode("\n", $current);
    }

    $parsed_all = [];
    foreach ($blocks as $block) {
        $parsed = parse_input(trim($block));
        if (!empty($parsed['title']) && !empty($parsed['question'])) {
            $parsed_all[] = $parsed;
        }
    }
    return $parsed_all;
}

function parse_input($text) {
    $lines = explode("\n", trim($text));
    $data = [
        'title' => '', 'path' => '', 'category' => '', 'type' => '',
        'question' => '', 'answer' => '', 'solution' => '', 'difficulty' => '',
        'video' => '', 'hint' => '', 'source' => ''
    ];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        if (str_starts_with($line, '*제목:')) {
            $data['title'] = trim(substr($line, strlen('*제목:')));
        } elseif (str_starts_with($line, '*경로:')) {
            $data['path'] = trim(substr($line, strlen('*경로:')));
        } elseif (str_starts_with($line, '*유형분류:')) {
            $data['category'] = trim(substr($line, strlen('*유형분류:')));
        } elseif (str_starts_with($line, '*유형:')) {
            $data['type'] = trim(substr($line, strlen('*유형:')));
        } elseif (str_starts_with($line, '*문제:')) {
            $field = 'question';
            $data[$field] = trim(substr($line, strlen('*문제:')));
        } elseif (str_starts_with($line, '*정답:')) {
            $data['answer'] = trim(substr($line, strlen('*정답:')));
        } elseif (str_starts_with($line, '*해설:')) {
            $field = 'solution';
            $data[$field] = trim(substr($line, strlen('*해설:')));
        } elseif (str_starts_with($line, '*난이도:')) {
            $data['difficulty'] = trim(substr($line, strlen('*난이도:')));
        } elseif (str_starts_with($line, '*동영상링크:')) {
            $data['video'] = trim(substr($line, strlen('*동영상링크:')));
        } elseif (str_starts_with($line, '*힌트:')) {
            $field = 'hint';
            $data[$field] = trim(substr($line, strlen('*힌트:')));
        } else {
            if (!empty($field)) {
                $data[$field] .= ($data[$field] ? "\n" : '') . $line;
            }
        }
    }

    $data['source'] = '';
    return $data;
}

$all_parsed = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = $_POST['raw'] ?? '';
    $all_parsed = parse_all_inputs($raw);

    if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
        $conn = new mysqli("localhost", "theplusmath", "wnstj1205+", "theplusmath");
        $conn->set_charset("utf8mb4");

        foreach ($all_parsed as $parsed) {
            $stmt = $conn->prepare("INSERT INTO problems (title, path, category, type, question, answer, solution, difficulty, video, hint, source)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss",
                $parsed['title'], $parsed['path'], $parsed['category'], $parsed['type'],
                $parsed['question'], $parsed['answer'], $parsed['solution'],
                $parsed['difficulty'], $parsed['video'], $parsed['hint'], $parsed['source']);
            $stmt->execute();
            $stmt->close();
        }

        $conn->close();
        echo "<script>alert('모든 문제가 성공적으로 저장되었습니다.'); location.href='index.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>여러 문제 자동 입력</title>
    <style>
        textarea { width: 100%; height: 300px; margin-bottom: 10px; }
        button { padding: 10px 20px; font-size: 16px; }
        .preview { border: 1px solid #ccc; padding: 10px; margin-top: 20px; background: #f9f9f9; }
    </style>
</head>
<body>
<h2>여러 문제 자동 입력</h2>
<form method="post">
    <textarea name="raw" placeholder="여기에 여러 문제를 붙여넣으세요. 문제는 제목 바로 아래에 *경로:가 있어야 합니다."><?= htmlspecialchars($_POST['raw'] ?? '') ?></textarea>
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['confirm'])): ?>
        <div class="preview">
            <h3>미리보기 (총 <?= count($all_parsed) ?>문제)</h3>
            <?php foreach ($all_parsed as $index => $p): ?>
                <div style="margin-bottom:20px">
                    <p><strong>[<?= $index + 1 ?>] 제목:</strong> <?= htmlspecialchars($p['title']) ?></p>
                    <p><strong>경로:</strong> <?= htmlspecialchars($p['path']) ?></p>
                    <p><strong>유형분류:</strong> <?= htmlspecialchars($p['category']) ?></p>
                    <p><strong>유형:</strong> <?= htmlspecialchars($p['type']) ?></p>
                    <p><strong>문제:</strong><br><?= nl2br(htmlspecialchars($p['question'])) ?></p>
                    <p><strong>정답:</strong> <?= htmlspecialchars($p['answer']) ?></p>
                    <p><strong>해설:</strong><br><?= nl2br(htmlspecialchars($p['solution'])) ?></p>
                    <p><strong>난이도:</strong> <?= htmlspecialchars($p['difficulty']) ?></p>
                    <p><strong>동영상 링크:</strong> <?= htmlspecialchars($p['video']) ?></p>
                    <p><strong>힌트:</strong><br><?= nl2br(htmlspecialchars($p['hint'])) ?></p>
                </div>
            <?php endforeach; ?>
            <button type="submit" name="confirm" value="yes">모두 저장하기</button>
            <button type="button" onclick="history.back();">다시 입력하기</button>
        </div>
    <?php else: ?>
        <button type="submit">미리보기</button>
    <?php endif; ?>
</form>
</body>
</html>

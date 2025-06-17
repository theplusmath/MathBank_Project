<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>수학 문제 입력</title>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <style>
        textarea, input[type="text"], select {
            width: 100%;
            margin-bottom: 10px;
        }
        textarea {
            height: 100px;
        }
        input[type="submit"] {
            padding: 10px 20px;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <h2>수학 문제 입력</h2>
    <form method="post" action="submit.php">
        <label>문제 제목:</label><br>
        <input type="text" name="title"><br>

        <label>경로:</label><br>
        <input type="text" name="path"><br>

        <label>유형 분류:</label><br>
        <select name="category">
            <option value="">-- 선택하세요 --</option>
            <option value="계산능력">계산능력</option>
            <option value="이해능력">이해능력</option>
            <option value="추론능력">추론능력</option>
            <option value="문제해결능력">문제해결능력</option>
        </select><br>

        <label>문제 유형:</label><br>
        <select name="type">
            <option value="">-- 선택하세요 --</option>
            <option value="선다형">선다형</option>
            <option value="단답형">단답형</option>
            <option value="서술형">서술형</option>
        </select><br>

        <label>문제 (LaTeX 포함):</label><br>
        <textarea name="question" required></textarea><br>

        <label>정답:</label><br>
        <textarea name="answer"></textarea><br>

        <label>풀이:</label><br>
        <textarea name="solution"></textarea><br>

        <label>난이도 (숫자):</label><br>
        <input type="text" name="difficulty"><br>

        <label>동영상 링크:</label><br>
        <input type="text" name="video"><br>

        <label>힌트:</label><br>
        <textarea name="hint"></textarea><br>

        <input type="submit" value="저장">
    </form>
</body>
</html>

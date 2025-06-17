<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'functions.php';

if (!function_exists('extractLatexFormulas')) {
    die("extractLatexFormulas 없음. functions.php에 함수가 맞게 들어갔는지 확인하세요.");
}

// HTML 시작
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>수식 추출 테스트</title>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</head>
<body>
<?php

$testCases = [
    '케이스1' => <<<EOT
<p>\\(\\displaystyle 0  <  x \\leq \\frac{\\pi}{2}\\) 일 때, 무한급수</p>

<p style="text-align:center">\\(\\displaystyle (1-\\cos ^4 x)+(\\cos ^2 x-\\cos ^6 x)+\\cdots+(\\cos ^{2 n-2} x-\\cos ^{2 n+2} x)+\\cdots\\)</p>

<p>의 합을 \\(\\displaystyle f(x)\\) 라 하고, \\(\\displaystyle x=0\\) 일 때 \\(\\displaystyle f(x)=2\\) 라 정한다.</p>

<p>(1) \\(\\displaystyle f(x)\\) 를 구하여라.</p>

<p>(2) \\(\\displaystyle \\int_0^{\\frac{\\pi}{2}} e^{-x} f(x) d x\\) 를 구하여라.<br>&nbsp;</p>
EOT,
    '케이스2' => '아래 수식을 살펴보자: $$\int_0^1 x dx$$',
    '케이스3' => '문제: \\[ \\frac{a}{b} + \\displaystyle x^2 \\]',
    '케이스4' => '<p>복잡한 수식: $\\displaystyle \\sum_{n=1}^{\\infty} \\frac{1}{n^2}$과 $g(x)$</p>',
    '케이스5' => '공백테스트: $   x   +  y   $',
    '케이스6' => '엔티티테스트: $&nbsp;f(0)=0$'
];

foreach ($testCases as $desc => $text) {
    echo "<div style='margin-bottom:2em;'>";
    echo "<strong>[$desc] 입력:</strong><br>";
    echo $text . "<br><br>";
    $result = extractLatexFormulas($text);
    echo "<strong>→ 추출 결과 (렌더링):</strong><br>";
    foreach ($result as $latex) {
        // 수식을 MathJax에서 렌더링
        // \( ... \) 또는 \[ ... \]로 감싸기
        echo "<div style='margin:0.5em 0; font-size:1.2em;'>\\(" . htmlspecialchars($latex, ENT_QUOTES, 'UTF-8') . "\\)</div>";
    }
    echo "</div><hr>";
}
?>
</body>
</html>

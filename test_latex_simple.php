<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once 'functions.php';
header("Content-Type: text/html; charset=UTF-8");

$text = <<<EOT
<p>자연수 $\displaystyle &amp;nbsp;n \geq 5$ 에 대하여 등식 $\displaystyle &amp;nbsp;\frac{1}{1-t^2}=1+t^2+t^4+\cdots+t^{2 n-2}+\frac{t^{2 n}}{1-t^2}$ 의 양변을 구간</p>

<p>$\displaystyle &amp;nbsp;0&amp;lt;x&amp;lt;1$ 에서 정적분하면,</p>

<p style="text-align:center">$\displaystyle &amp;nbsp;\int_0^x \frac{1}{1-t^2} d t=x+\frac{x^3}{3}+\frac{x^5}{5}+\cdots+\frac{x^{2 n-1}}{2 n-1}+\int_0^x \frac{t^{2 n}}{1-t^2} d t$</p>

<p>이다. 이 때, 다음 물음에 답하여라.</p>

<p>(1) 구간 $\displaystyle &amp;nbsp;0&amp;lt;x&amp;lt;1$ 에서</p>
	
<p>$\displaystyle &amp;nbsp;0&amp;lt;\int_0^x \frac{t^{2 n}}{1-t^2} d t&amp;lt;\frac{1}{1-x^2} \frac{x^{2 n+1}}{2 n+1}$</p>

<p>이 성립함을 보여라.</p>

<p>(2) $\displaystyle &amp;nbsp;0&amp;lt;x&amp;lt;1$ 에 대하여 무한급수</p>

<p style="text-align:center">$\displaystyle &amp;nbsp;x+\frac{x^3}{3}+\frac{x^5}{5}+\cdots$</p>

<p>의 합을 구하여라.</p>
(1) $\displaystyle &amp;nbsp;t \in(0,~1)$ 이므로 $\displaystyle &amp;nbsp;0&amp;lt;t \leq x&amp;lt;1$ 이면</p>

<p>$\displaystyle &amp;nbsp;<br />
\begin{aligned}<br />
&amp;amp; 0&amp;lt;\frac{1}{1-t^2} \leq \frac{1}{1-x^2} \Rightarrow 0&amp;lt;\frac{t^{2 n}}{1-t^2} \leq \frac{t^{2 n}}{1-x^2} \\<br />
&amp;amp; \Rightarrow 0&amp;lt;\int_0^x \frac{t^{2 n}}{1-t^2} d x \leq \int_0^x \frac{t^{2 n}}{1-x^2} d x=\frac{1}{1-x^2} \cdot \frac{x^{2 n+1}}{2 n+1}<br />
\end{aligned}<br />
$</p>

<p>이다.</p>

<p>(2) $\displaystyle &amp;nbsp;0&amp;lt;x&amp;lt;1$ 일 때, (1)에서 $\displaystyle &amp;nbsp;\lim _{n \rightarrow \infty} \frac{1}{1-x^2} \cdot \frac{x^{2 n+1}}{2 n+1}=0$ 이므로</p>

<p>$\displaystyle &amp;nbsp;\lim _{n \rightarrow \infty} \int_0^x \frac{t^{2 n}}{1-t^2} d t=0$ 이다. 따라서<br />
$\displaystyle &amp;nbsp;<br />
\begin{aligned}<br />
&amp;amp; \left|\int_0^x \frac{t^{2 n}}{1-t^2} d t\right|=\left|x+\frac{x^3}{3}+\frac{x^5}{5}+\cdots+\frac{x^{2 n-1}}{2 n-1}-\int_0^x \frac{1}{1-t^2} d t\right| \\<br />
&amp;amp; =\left|x+\frac{x^3}{3}+\frac{x^5}{5}+\cdots+\frac{x^{2 n-1}}{2 n-1}-\frac{1}{2} \ln \frac{1+x}{1-x}\right| \rightarrow 0 \\<br />
&amp;amp;<br />
\end{aligned}<br />
$</p>

<p>이므로</p>

<p>$\displaystyle &amp;nbsp;x+\frac{x^3}{3}+\frac{x^5}{5}+\cdots=\frac{1}{2} \ln \frac{1+x}{1-x}$</p>

<p>이다.</p>

EOT;

// 추출 함수 실행
$formulas = extractLatexFormulas_v2($text);

//$formulas = extractLatexFormulas_fracProxy($text);
echo "<pre>";
print_r($formulas);

// (1) 추출된 첫 번째 수식에서 첫 번째 문자 확인
if (isset($formulas[0])) {
    $formula = $formulas[0];
    // \frac의 첫 문자(역슬래시) 추출
    $firstChar = mb_substr($formula, 0, 1, "UTF-8");
    $codePoint = strtoupper(bin2hex($firstChar));

    // U+005C가 진짜 역슬래시
    if ($codePoint === '5C') {
        echo "\n? 정상: 진짜 역슬래시(\\, U+005C)입니다!";
    } else {
        echo "\n? 실패: 역슬래시가 아닙니다! (코드: U+" . $codePoint . ")";
    }
}
echo "</pre>";
?>

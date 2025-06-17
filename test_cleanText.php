<?php
require_once 'functions.php'; // cleanTextForLatex가 여기 있으면

$test = '<p>함수 $\displaystyle f(x) + 5$의 해를 구하시오.</p>';
echo cleanTextForLatex($test);
?>

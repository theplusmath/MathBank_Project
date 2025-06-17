<?php
$conn = new mysqli('localhost', 'theplusmath', 'wnstj1205+', 'theplusmath');
$conn->set_charset('utf8mb4');

$result = $conn->query("SHOW COLUMNS FROM problems");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "<br>";
}
$conn->close();

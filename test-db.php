<?php

require_once 'config/database.php';

echo "Database Connected Successfully!<br>";

$stmt = $pdo->query("SELECT * FROM repair_category");

$categories = $stmt->fetchAll();

echo "Total Categories: " . count($categories) . "<br><br>";

foreach ($categories as $category) {
    echo $category['category_name'] . "<br>";
}

?>

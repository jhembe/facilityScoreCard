<?php
include "db_config.php";

$categories = array();

$categoriesQuery = "SELECT * FROM Categories";
$categoriesResult = $conn->query($categoriesQuery);

while ($category = $categoriesResult->fetch_assoc()) {
    $questionsQuery = "SELECT * FROM Questions WHERE CategoryID = {$category['CategoryID']}";
    $questionsResult = $conn->query($questionsQuery);
    $questions = array();

    while ($question = $questionsResult->fetch_assoc()) {
        $questions[] = $question;
    }

    $categories[$category['CategoryName']] = $questions;
}

$conn->close();

header("Content-Type: application/json");
echo json_encode($categories);
?>

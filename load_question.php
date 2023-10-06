<?php
include "db_config.php";

$categories = array();

$categoriesQuery = "SELECT c.CategoryID, c.CategoryName, q.QuestionID, q.QuestionText 
                    FROM Categories c
                    LEFT JOIN Questions q ON c.CategoryID = q.CategoryID AND q.FacilityID = ?";

$stmt = $conn->prepare($categoriesQuery);
$stmt->bind_param("i", $facilityID);
$facilityID = $_GET["facilityID"];
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $categoryID = $row["CategoryID"];
    $categoryName = $row["CategoryName"];

    if (!isset($categories[$categoryName])) {
        $categories[$categoryName] = array();
    }

    $categories[$categoryName][] = array(
        "QuestionID" => $row["QuestionID"],
        "QuestionText" => $row["QuestionText"]
    );
}

$stmt->close();
$conn->close();

header("Content-Type: application/json");
echo json_encode($categories);
?>

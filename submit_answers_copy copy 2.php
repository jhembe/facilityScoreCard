<?php
include "db_config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["facilityID"])) {
    $facilityID = $_POST["facilityID"];
    $responses = array();

    foreach ($_POST as $key => $value) {
        if (substr($key, 0, 1) === "q") {
            $questionID = intval(substr($key, 1));
            $responseValue = intval($value);

            $responses[] = "($facilityID, $questionID, $responseValue)";
        }
    }

    if (!empty($responses)) {
        // Insert responses into Responses table
        $sql = "INSERT INTO Responses (FacilityID, QuestionID, ResponseValue) VALUES " . implode(', ', $responses);
        if ($conn->multi_query($sql) !== TRUE) {
            echo "Error: " . $sql . "<br>" . $conn->error;
            exit();
        }

        // Update FacilitySummary based on new responses
        $sqlUpdateSummary = "
            INSERT INTO FacilitySummary (FacilityID, TotalResponses, AdolescentFriendlyResponses, ClientFriendlyResponses)
            VALUES ($facilityID, 1, 0, 0)
            ON DUPLICATE KEY UPDATE
                TotalResponses = TotalResponses + 1,
                AdolescentFriendlyResponses = (
                    SELECT COUNT(*) FROM Questions q
                    JOIN Responses r ON q.QuestionID = r.QuestionID
                    WHERE q.IsAdolescentFriendly = 1 AND r.FacilityID = $facilityID
                ),
                ClientFriendlyResponses = (
                    SELECT COUNT(*) FROM Questions q
                    JOIN Responses r ON q.QuestionID = r.QuestionID
                    WHERE q.IsClientFriendly = 1 AND r.FacilityID = $facilityID
                )";
        
        if ($conn->query($sqlUpdateSummary) !== TRUE) {
            echo "Error updating summary: " . $conn->error;
            exit();
        }

        echo "Answers submitted successfully!";
    } else {
        echo "No answers submitted.";
    }

    $conn->close();
} else {
    header("HTTP/1.0 400 Bad Request");
}
?>

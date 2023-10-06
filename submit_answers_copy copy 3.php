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
        $values = implode(", ", $responses);

        // Insert responses into Responses table
        $sql = "INSERT INTO Responses (FacilityID, QuestionID, ResponseValue) VALUES $values";

        if ($conn->multi_query($sql) === TRUE) {
            // Calculate summary scores using the modified query
            $summaryQuery = "
                INSERT INTO FacilitySummary (FacilityID, TotalScore, AdolescentFriendlyScore, ClientFriendlyScore)
                SELECT
                    F.FacilityID,
                    SUM(CASE WHEN R.ResponseValue = 1 THEN 1 ELSE 0 END) AS TotalScore,
                    SUM(CASE WHEN R.ResponseValue = 1 AND Q.IsAdolescentFriendly = 1 THEN 1 ELSE 0 END) AS AdolescentFriendlyScore,
                    SUM(CASE WHEN R.ResponseValue = 1 AND Q.IsClientFriendly = 1 THEN 1 ELSE 0 END) AS ClientFriendlyScore
                FROM
                    Facilities F
                JOIN
                    Responses R ON F.FacilityID = R.FacilityID
                JOIN
                    Questions Q ON R.QuestionID = Q.QuestionID
                WHERE
                    F.FacilityID = $facilityID
                GROUP BY
                    F.FacilityID, F.FacilityName
            ";

            if ($conn->query($summaryQuery) === TRUE) {
                echo "Answers submitted successfully!";
            } else {
                echo "Error updating summary: " . $conn->error;
            }
        } else {
            echo "Error inserting responses: " . $conn->error;
        }
    } else {
        echo "No answers submitted.";
    }

    $conn->close();
} else {
    header("HTTP/1.0 400 Bad Request");
}
?>

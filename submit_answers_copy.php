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
            // Update or insert summary scores
            $summaryQuery = "
                INSERT INTO FacilitySummary (FacilityID, EntryCount, TotalScore, AdolescentFriendlyScore, ClientFriendlyScore)
                VALUES ($facilityID, 1, (SELECT SUM(R.ResponseValue) FROM Responses R WHERE R.FacilityID = $facilityID), 
                                    (SELECT SUM(Q.IsAdolescentFriendly = 1) FROM Responses R JOIN Questions Q ON R.QuestionID = Q.QuestionID WHERE R.FacilityID = $facilityID), 
                                    (SELECT SUM(Q.IsClientFriendly = 1) FROM Responses R JOIN Questions Q ON R.QuestionID = Q.QuestionID WHERE R.FacilityID = $facilityID))
                ON DUPLICATE KEY UPDATE
                EntryCount = EntryCount + 1,
                TotalScore = TotalScore + (SELECT SUM(R.ResponseValue) FROM Responses R WHERE R.FacilityID = $facilityID),
                AdolescentFriendlyScore = AdolescentFriendlyScore + (SELECT SUM(Q.IsAdolescentFriendly = 1) FROM Responses R JOIN Questions Q ON R.QuestionID = Q.QuestionID WHERE R.FacilityID = $facilityID),
                ClientFriendlyScore = ClientFriendlyScore + (SELECT SUM(Q.IsClientFriendly = 1) FROM Responses R JOIN Questions Q ON R.QuestionID = Q.QuestionID WHERE R.FacilityID = $facilityID)
            ";

            if ($conn->query($summaryQuery) === TRUE) {
                // Calculate average for scores
                $averageQuery = "
                    UPDATE FacilitySummary
                    SET     
                        TotalScore = TotalScore / EntryCount,
                        AdolescentFriendlyScore = AdolescentFriendlyScore / EntryCount,
                        ClientFriendlyScore = ClientFriendlyScore / EntryCount
                    WHERE FacilityID = $facilityID
                ";

                if ($conn->query($averageQuery) === TRUE) {
                    echo "Answers submitted successfully!";
                } else {
                    echo "Error calculating average: " . $conn->error;
                }
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

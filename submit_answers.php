<?php
include "db_config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["facilityID"])) {
    $facilityID = $_POST["facilityID"];
    
    // Extract year and quarter values
    $year = $_POST["year"];
    $quarter = $_POST["quarter"];

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
            // Insert or update summary scores with year and quarter
            $summaryQuery = "
                INSERT INTO FacilitySummary (FacilityID, EvaluationYear, EvaluationQuarter, EntryCount, TotalScore, AdolescentFriendlyScore, ClientFriendlyScore)
                VALUES (?, ?, ?, 1, (SELECT SUM(R.ResponseValue) FROM Responses R WHERE R.FacilityID = ?), 
                                    (SELECT SUM(Q.IsAdolescentFriendly = 1) FROM Responses R JOIN Questions Q ON R.QuestionID = Q.QuestionID WHERE R.FacilityID = ?), 
                                    (SELECT SUM(Q.IsClientFriendly = 1) FROM Responses R JOIN Questions Q ON R.QuestionID = Q.QuestionID WHERE R.FacilityID = ?))
                ON DUPLICATE KEY UPDATE
                EntryCount = EntryCount + 1,
                TotalScore = TotalScore + (SELECT SUM(R.ResponseValue) FROM Responses R WHERE R.FacilityID = ?),
                AdolescentFriendlyScore = AdolescentFriendlyScore + (SELECT SUM(Q.IsAdolescentFriendly = 1) FROM Responses R JOIN Questions Q ON R.QuestionID = Q.QuestionID WHERE R.FacilityID = ?),
                ClientFriendlyScore = ClientFriendlyScore + (SELECT SUM(Q.IsClientFriendly = 1) FROM Responses R JOIN Questions Q ON R.QuestionID = Q.QuestionID WHERE R.FacilityID = ?)
            ";

            // Prepare the statement
            $stmt = $conn->prepare($summaryQuery);

            // Bind parameters
            $stmt->bind_param("iiiiiiiiii", $facilityID, $year, $quarter, $facilityID, $facilityID, $facilityID, $facilityID, $facilityID, $facilityID);

            if ($stmt->execute() === TRUE) {
                // Calculate average for scores
                $averageQuery = "
                    UPDATE FacilitySummary
                    SET     
                        TotalScore = TotalScore / EntryCount,
                        AdolescentFriendlyScore = AdolescentFriendlyScore / EntryCount,
                        ClientFriendlyScore = ClientFriendlyScore / EntryCount
                    WHERE FacilityID = ?
                ";

                // Prepare the statement
                $stmt = $conn->prepare($averageQuery);

                // Bind parameter
                $stmt->bind_param("i", $facilityID);

                if ($stmt->execute() === TRUE) {
                    echo "Answers submitted successfully!";
                } else {
                    echo "Error calculating average: " . $conn->error;
                }
            } else {
                echo "Error updating summary: " . $conn->error;
            }
            
            // Close the statement
            $stmt->close();
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

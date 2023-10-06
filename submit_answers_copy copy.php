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

        $sql = "INSERT INTO Responses (FacilityID, QuestionID, ResponseValue) VALUES $values";

        if ($conn->multi_query($sql) === TRUE) {
            echo "Answers submitted successfully!";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } else {
        echo "No answers submitted.";
    }

    $conn->close();
} else {
    header("HTTP/1.0 400 Bad Request");
}
?>

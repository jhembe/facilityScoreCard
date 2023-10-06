<?php
include "db_config.php";

$sql = "SELECT FS.*, F.FacilityName FROM FacilitySummary FS
        JOIN Facilities F ON FS.FacilityID = F.FacilityID";

$result = $conn->query($sql);

$summaryData = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $summaryData[] = $row;
    }
}

$conn->close();

header("Content-Type: application/json");
echo json_encode($summaryData);
?>

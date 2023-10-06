<?php
include "db_config.php";

$facilities = array();

$facilitiesQuery = "SELECT * FROM Facilities";
$facilitiesResult = $conn->query($facilitiesQuery);

while ($facility = $facilitiesResult->fetch_assoc()) {
    $facilities[] = $facility;
}

$conn->close();

header("Content-Type: application/json");
echo json_encode($facilities);
?>

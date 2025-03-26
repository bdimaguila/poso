<?php
function updateDiscountTable($violation, $ticket_number, $conn) {
    // Mapping of violations to column names
    $violationMap = [
        "FAILURE TO WEAR HELMET" => "FTWH",
        "OPEN MUFFLER/NUISANCE" => "OMN",
        "ARROGANT" => "ARG",
        "ONEWAY" => "ONEWAY",
        "ILLEGAL PARKING" => "ILP",
        "DRIVING WITHOUT LICENSE/INVALID LICENSE" => "DWL",
        "NO OR/CR WHILE DRIVING" => "NORCR",
        "DRIVING UNREGISTERED VEHICLE" => "DUV",
        "UNREGISTERED MOTOR VEHICLE" => "UMV",
        "OBSTRUCTION" => "OBS",
        "DISREGARDING TRAFFIC SIGNS" => "DTS",
        "DISREGARDING TRAFFIC OFFICER" => "DTO",
        "TRUCK BAN" => "TRB",
        "STALLED VEHICLE" => "STV",
        "RECKLESS DRIVING" => "RCD",
        "DRIVING UNDER THE INFLUENCE OF LIQUOR" => "DUL",
        "INVALID OR NO FRANCHISE/COLORUM" => "INF",
        "OPERATING OUT OF LINE" => "OOL",
        "TRIP - CUTTING" => "TCT",
        "OVERLOADING" => "OVL",
        "LOADING/UNLOADING IN PROHIBITED ZONE" => "LUZ",
        "INVOLVE IN ACCIDENT" => "IVA",
        "SMOKE BELCHING" => "SMB",
        "NO SIDE MIRROR" => "NSM",
        "JAY WALKING" => "JWK",
        "WEARING SLIPPERS/SHORTS/SANDO" => "WSS",
        "ILLEGAL VENDING" => "ILV",
        "IMPOUNDED" => "IMP"
    ];

    // Check if violation exists in the map
    if (isset($violationMap[$violation])) {
        $column = $violationMap[$violation];
        // Prepare and execute the update query
        $query = "UPDATE discount SET $column = 1 WHERE ticket_number = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $ticket_number);

        if ($stmt->execute()) {
            echo "Discount table updated successfully for violation: $violation<br>";
        } else {
            echo "Error updating discount table: " . $stmt->error . "<br>";
        }
        $stmt->close();
    } else {
        echo "Invalid violation: $violation<br>";
    }
}
?>

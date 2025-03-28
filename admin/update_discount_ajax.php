<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    exit();
}
include 'connection.php';

$ticket_number = $_POST['ticket_number'];
$status = $_POST['status'];
$receipt_num = $_POST['receipt_num'];
$others_violation_text = $_POST['others_violation_text'];
$others_violation_amount = $_POST['others_violation_amount'];
$new_violations = json_decode($_POST['violations']);

// Define available violations with respective column names and penalties
$violation_map =  [
    'FAILURE TO WEAR HELMET' => ['column' => 'FTWH', 'penalty' => 200],
    'OPEN MUFFLER/NUISANCE' => ['column' => 'OMN', 'penalty' => 1000],
    'ARROGANT' => ['column' => 'ARG', 'penalty' => 1000],
    'ONEWAY' => ['column' => 'ONEWAY', 'penalty' => 200],
    'ILLEGAL PARKING' => ['column' => 'ILP', 'penalty' => 200],
    'DRIVING WITHOUT LICENSE/INVALID LICENSE' => ['column' => 'DWL', 'penalty' => 1000],
    'NO OR/CR WHILE DRIVING' => ['column' => 'NORCR', 'penalty' => 500],
    'DRIVING UNREGISTERED VEHICLE' => ['column' => 'DUV', 'penalty' => 500],
    'UNREGISTERED MOTOR VEHICLE' => ['column' => 'UMV', 'penalty' => 500],
    'OBSTRUCTION' => ['column' => 'OBS', 'penalty' => 200],
    'DISREGARDING TRAFFIC SIGNS' => ['column' => 'DTS', 'penalty' => 200],
    'DISREGARDING TRAFFIC OFFICER' => ['column' => 'DTO', 'penalty' => 200],
    'TRUCK BAN' => ['column' => 'TRB', 'penalty' => 200],
    'STALLED VEHICLE' => ['column' => 'STV', 'penalty' => 200],
    'RECKLESS DRIVING' => ['column' => 'RCD', 'penalty' => 100],
    'DRIVING UNDER THE INFLUENCE OF LIQUOR' => ['column' => 'DUL', 'penalty' => 200],
    'INVALID OR NO FRANCHISE/COLORUM' => ['column' => 'INF', 'penalty' => 2000],
    'OPERATING OUT OF LINE' => ['column' => 'OOL', 'penalty' => 2000],
    'TRIP - CUTTING' => ['column' => 'TCT', 'penalty' => 200],
    'OVERLOADING' => ['column' => 'OVL', 'penalty' => 200],
    'LOADING/UNLOADING IN PROHIBITED ZONE' => ['column' => 'LUZ', 'penalty' => 200],
    'INVOLVE IN ACCIDENT' => ['column' => 'IVA', 'penalty' => 200],
    'SMOKE BELCHING' => ['column' => 'SMB', 'penalty' => 500],
    'NO SIDE MIRROR' => ['column' => 'NSM', 'penalty' => 200],
    'JAY WALKING' => ['column' => 'JWK', 'penalty' => 200],
    'WEARING SLIPPERS/SHORTS/SANDO' => ['column' => 'WSS', 'penalty' => 300],
    'ILLEGAL VENDING' => ['column' => 'ILV', 'penalty' => 200],
    'IMPOUNDED' => ['column' => 'IMP', 'penalty' => 800]
];

// Prepare the update query for discount table
$update_fields = [];
$params = [':ticket_number' => $ticket_number];

foreach ($violation_map as $violation => $data) {
    $column = $data['column'];
    $penalty = $data['penalty'];

    if(in_array($violation, $new_violations)) {
        $update_fields[] = "$column = :$column";
        $params[":$column"] = $penalty;
    } else {
        $update_fields[] = "$column = NULL";
    }
}

// Update OTHERS and OTHERS_P
$update_fields[] = "OTHERS = :others_violation_text";
$update_fields[] = "OTHERS_P = :others_violation_amount";
$params[':others_violation_text'] = $others_violation_text;
$params[':others_violation_amount'] = $others_violation_amount;

//Update receipt number
$update_fields[] = "receipt_num = :receipt_num";
$params[':receipt_num'] = $receipt_num;

if (!empty($update_fields)) {
    $sql_update_discount = "UPDATE discount SET " . implode(", ", $update_fields) . " WHERE ticket_number = :ticket_number";
    $stmt_update = $conn->prepare($sql_update_discount);
    $stmt_update->execute($params);
}

// Update the STATUS in the discount table
$stmt_status = $conn->prepare("UPDATE discount SET STATUS = :status WHERE ticket_number = :ticket_number");
$stmt_status->bindParam(':status', $status);
$stmt_status->bindParam(':ticket_number', $ticket_number);

try {
    $stmt_status->execute();
} catch (PDOException $e) {
    echo "Error updating status: " . $e->getMessage();
    exit();
}

// Check and update overdue tickets
// Fetch the created_at from the report table
$stmt_created_at = $conn->prepare("SELECT created_at FROM report WHERE ticket_number = :ticket_number");
$stmt_created_at->bindParam(':ticket_number', $ticket_number);
$stmt_created_at->execute();
$report_data = $stmt_created_at->fetch(PDO::FETCH_ASSOC);
$created_at = $report_data['created_at'];

if ($created_at) {
    // Check if the ticket is overdue
    $overdue_date = date('Y-m-d', strtotime($created_at . ' +3 days'));
    $current_date = date('Y-m-d');

    if ($current_date >= $overdue_date) {
        $stmt_overdue = $conn->prepare("UPDATE discount SET STATUS = 'Overdue' WHERE STATUS = 'Pending' AND ticket_number = :ticket_number");
        $stmt_overdue->bindParam(':ticket_number', $ticket_number);

        try {
            $stmt_overdue->execute();
        } catch (PDOException $e) {
            echo "Error updating overdue tickets: " . $e->getMessage();
            exit();
        }
    }
}

echo "success";
?>
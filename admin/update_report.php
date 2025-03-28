<?php

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection file
include 'connection.php';

// Get the ticket number from the URL or POST data
$ticket_number = isset($_GET['ticket_number']) ? $_GET['ticket_number'] : (isset($_POST['ticket_number']) ? $_POST['ticket_number'] : '');

// Check if the ticket_number exists in the database
if (empty($ticket_number)) {
    echo "Ticket number is missing!";
    exit();
}

$stmt_check = $conn->prepare("SELECT COUNT(*) FROM report WHERE ticket_number = :ticket_number");
$stmt_check->bindParam(':ticket_number', $ticket_number);
$stmt_check->execute();
$result = $stmt_check->fetchColumn();

if ($result == 0) {
    echo "Ticket number does not exist!";
    exit();
}

// Collect form data
$first_name = isset($_POST['first_name']) ? $_POST['first_name'] : '';
$middle_name = isset($_POST['middle_name']) ? $_POST['middle_name'] : '';
$last_name = isset($_POST['last_name']) ? $_POST['last_name'] : '';
$dob = isset($_POST['dob']) ? $_POST['dob'] : '';
$address = isset($_POST['address']) ? $_POST['address'] : '';
$license = isset($_POST['license']) ? $_POST['license'] : '';
$violation_date = isset($_POST['violation_date']) ? $_POST['violation_date'] : '';
$violation_time = isset($_POST['violation_time']) ? $_POST['violation_time'] : '';
$confiscated = isset($_POST['confiscated']) ? $_POST['confiscated'] : '';
$vehicle_owner = isset($_POST['vehicle_owner']) ? $_POST['vehicle_owner'] : '';
$street = isset($_POST['street']) ? $_POST['street'] : '';
$city = isset($_POST['city']) ? $_POST['city'] : '';
$vehicle_type = isset($_POST['vehicle_type']) ? $_POST['vehicle_type'] : '';
$plate_number = isset($_POST['plate_number']) ? $_POST['plate_number'] : '';
$registration = isset($_POST['registration']) ? $_POST['registration'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';
$amount = isset($_POST['amount']) ? $_POST['amount'] : '';
$officer_name = isset($_POST['officer_name']) ? $_POST['officer_name'] : '';

// Collect other violation data.
$others_violation_text = isset($_POST['others_violation_text']) ? $_POST['others_violation_text'] : null;
$others_violation_amount = isset($_POST['others_violation_amount']) ? $_POST['others_violation_amount'] : null;

// Prepare the update query for the report table
$stmt = $conn->prepare("UPDATE report SET 
    first_name = :first_name,
    middle_name = :middle_name,
    last_name = :last_name,
    dob = :dob,
    address = :address,
    license = :license,
    violation_date = :violation_date,
    violation_time = :violation_time,
    confiscated = :confiscated,
    vehicle_owner = :vehicle_owner,
    street = :street,
    city = :city,
    vehicle_type = :vehicle_type,
    plate_number = :plate_number,
    registration = :registration
WHERE ticket_number = :ticket_number");

$stmt->bindParam(':first_name', $first_name);
$stmt->bindParam(':middle_name', $middle_name);
$stmt->bindParam(':last_name', $last_name);
$stmt->bindParam(':dob', $dob);
$stmt->bindParam(':address', $address);
$stmt->bindParam(':license', $license);
$stmt->bindParam(':violation_date', $violation_date);
$stmt->bindParam(':violation_time', $violation_time);
$stmt->bindParam(':confiscated', $confiscated);
$stmt->bindParam(':vehicle_owner', $vehicle_owner);
$stmt->bindParam(':street', $street);
$stmt->bindParam(':city', $city);
$stmt->bindParam(':vehicle_type', $vehicle_type);
$stmt->bindParam(':plate_number', $plate_number);
$stmt->bindParam(':registration', $registration);
$stmt->bindParam(':ticket_number', $ticket_number);

try {
    $stmt->execute();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

// Fetch existing violations
$stmt_violation = $conn->prepare("SELECT * FROM discount WHERE ticket_number = :ticket_number");
$stmt_violation->bindParam(':ticket_number', $ticket_number);
$stmt_violation->execute();
$existing_violations = $stmt_violation->fetch(PDO::FETCH_ASSOC);

// Get violations from POST request
$new_violations = isset($_POST['violations']) ? $_POST['violations'] : [];

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

// Redirect after update
header("Location: report.php");
exit();
?>
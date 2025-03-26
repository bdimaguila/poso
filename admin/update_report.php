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

// Prepare the update query
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
    echo "Update successful!";
    header("Location: report.php");
    exit();
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

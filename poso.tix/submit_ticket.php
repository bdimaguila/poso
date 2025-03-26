<?php
// Start the session
session_start();

// Include the database connection file
include 'connection.php'; // Ensure the path is correct

// Collect form data
$ticket_number = $_POST['ticket_number']; // Get the ticket number from the form
$first_name = $_POST['first_name'];
$middle_name = $_POST['middle_name'];
$last_name = $_POST['last_name'];
$dob = $_POST['dob'];
$address = $_POST['address'];
$license = $_POST['license'];
$confiscated = isset($_POST['confiscated']) ? $_POST['confiscated'] : 'no';
$violation_date = $_POST['date'];
$violation_time = $_POST['time'];
$street = $_POST['street'];
$plate_number = $_POST['plate_number'];
$city = $_POST['city'];
$registration = $_POST['registration'];
$vehicle_type = $_POST['vehicle_type'];
$vehicle_owner = $_POST['vehicle_owner'];

// Prepare SQL statement to insert data
$sql = "INSERT INTO report (ticket_number, first_name, middle_name, last_name, dob, address, license, confiscated, violation_date, violation_time, street, plate_number, city, registration, vehicle_type, vehicle_owner)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)" ;

// Prepare the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssssssssssssss",
    $ticket_number,
    $first_name,
    $middle_name,
    $last_name,
    $dob,
    $address,
    $license,
    $confiscated,
    $violation_date,
    $violation_time,
    $street,
    $plate_number,
    $city,
    $registration,
    $vehicle_type,
    $vehicle_owner
);

// Execute the query and check if it was successful
if ($stmt->execute()) {
    // Redirect to violation.php with the ticket number, first name, and last name in the URL
    header("Location: violation.php?ticket_number=" . urlencode($ticket_number) . "&first_name=" . urlencode($first_name) . "&last_name=" . urlencode($last_name));
    exit(); // Make sure to exit after redirect
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Close the connection
$stmt->close();
$conn->close();
?>

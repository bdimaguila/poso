<?php
// Start the session
session_start();

// Database connection settings
$servername = "localhost";
$username = "root"; // Change this to your database username
$password = "";     // Change this to your database password
$dbname = "poso";   // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
    echo "New record created successfully. Ticket Number: " . $ticket_number;
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

// Close the connection
$stmt->close();
$conn->close();
?>

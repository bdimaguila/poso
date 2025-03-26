<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "poso";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if a license number is passed
if (isset($_POST['license'])) {
    $license = $_POST['license'];

    // Query to check if the license exists and fetch the violator's details
    $sql = "SELECT first_name, middle_name, last_name, dob, address FROM report WHERE license = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $license);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the license exists
    if ($result->num_rows > 0) {
        // Fetch the violator's details
        $violator = $result->fetch_assoc();
        echo json_encode([
            'status' => 'exists',
            'data' => $violator
        ]);
    } else {
        echo json_encode(['status' => 'not_found']);
    }
} else {
    echo json_encode(['status' => 'error']);
}

$conn->close();
?>

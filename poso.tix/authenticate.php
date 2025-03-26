<?php
session_start();
include 'connection.php'; // Ensure this connects to the POSO database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT * FROM hh_login WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("Error in statement preparation: " . $conn->error);
    }

    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Store username in session and set success message
        $_SESSION['username'] = $username;
        $_SESSION['success'] = "You have logged in successfully!"; 
        header("Location: index.php"); // Redirect back to login page to show the success popup
        exit;
    } else {
        $_SESSION['error'] = "Invalid username or password."; // Set error message in session
        header("Location: index.php"); // Redirect back to login page
        exit;
    }

    $stmt->close();
}

$conn->close();
?>

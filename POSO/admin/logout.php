<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: index.php");
    exit();
}

// Check if the user has confirmed the logout
if (isset($_POST['confirm'])) {
    // Unset the session variable and destroy the session
    unset($_SESSION['user_id']);
    session_destroy();
    // Redirect to the login page
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout Confirmation</title>
    <script>
        function confirmLogout() {
            // Automatically submit the form without confirmation
            document.getElementById("logoutForm").submit();
        }
    </script>
</head>
<body>
    <h1>Logout Confirmation</h1>
    <p>Are you sure you want to logout?</p>
    <form id="logoutForm" method="POST" action="logout.php">
        <input type="hidden" name="confirm" value="yes"> <!-- Hidden input to confirm -->
        <button type="button" onclick="confirmLogout()">Yes</button>
        <button type="button" onclick="window.location.href='dashboard.php'">No</button>
    </form>
</body>
</html>
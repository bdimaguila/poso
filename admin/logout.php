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
 <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POSO Admin Logout</title>
    <link rel="stylesheet" href="/POSO/admin/css/index.css?v=1.0">
    <script>
        function confirmLogout() {
            // Automatically submit the form for confirmation
            document.getElementById("logoutForm").submit();
        }
    </script>
</head>
<body>
<nav class="navbar">
<img src="/POSO/images/left.png" alt="Left Logo" class="logo">
        <div>
            <p class="public" >PUBLIC ORDER & SAFETY OFFICE</p>
            <p class="city">CITY OF BIÑAN, LAGUNA</p>
        </div>
        <img src="/POSO/images/arman.png" alt="POSO Logo" class="logo">
</nav>
<img class="bg" src="/POSO/images/plaza1.jpg" alt="Background Image">
<br><br><br>
    <div class="container">
        <div class="login-form">
            <h3>Logout Confirmation</h3>
<p style="text-align: center; font-size: 18px;">Are you sure you want to logout?</p>
            <form id="logoutForm" method="POST" action="logout.php">
                <input type="hidden" name="confirm" value="yes">
                <button type="button" onclick="confirmLogout()">Yes</button>
<br>
                <button type="button" onclick="window.location.href='dashboard.php'">No</button>
            </form>
        </div>
    </div>
</body>
</html>

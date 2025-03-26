<?php 
// Start the session
session_start();

// Include the database connection file
include 'connection.php'; // Make sure this path is correct
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POSO Admin Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <br>
    <div class="header-container"> <!-- New div class for the header -->
        <img src="/POSO/images/left.png" alt="Left Logo" class="logo">
        <div>
            <h1>PUBLIC ORDER & SAFETY OFFICE</h1>
            <h2>CITY OF BIÑAN</h2>
        </div>
        <img src="/POSO/images/right.png" alt="Right Logo" class="logo">
    </div> 
    <br><br><br>
    <div class="container">
        <div class="login-form">
            <h3>LOGIN</h3>
            <form action="authenticate.php" method="POST">
                <label for="username">USERNAME</label>
                <input type="text" name="username" id="username" required>
                
                <label for="password">PASSWORD</label>
                <input type="password" name="password" id="password" required>
                <a href="#">Forgot Password?</a>
                <br>
                <button type="submit">LOGIN</button>
            </form>
        </div>
    </div>
</body>
</html>

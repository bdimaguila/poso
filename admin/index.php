<?php 
// Start the session
session_start();

// Include the database connection file
include 'connection.php'; // Make sure this path is correct
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POSO Admin Login</title>
    <link rel="stylesheet" href="/POSO/admin/css/index.css?v=1.0">

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

    <!-- Error Popup Overlay -->
    <?php if (isset($_SESSION['error'])): ?>
        <div id="errorOverlay" class="overlay fade-in">
            <div id="errorPopup" class="popup">
                <div class="icon error-icon">✖</div>
                <h2 class="error-text">Oh no!</h2>
                <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                <button onclick="closeError()" class="retry-btn">Try Again</button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Success Popup Overlay -->
    <?php if (isset($_SESSION['success'])): ?>
        <div id="successOverlay" class="overlay fade-in">
            <div id="successPopup" class="popup">
                <div class="icon success-icon">✔</div>
                <h2 class="success-text">Login Successfully!</h2>
                <p><?php echo $_SESSION['success']; ?></p>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p> <!-- Display username here -->
                <button onclick="continueToDashboard()" class="continue-btn">Continue</button>
            </div>
        </div>
        <?php unset($_SESSION['success']); // Remove success message after displaying ?>
    <?php endif; ?>

    <br>
    
    <br><br><br>
    <div class="container">
        <div class="login-form">
            <h3>LOGIN</h3>
            <form class="type" action="authenticate.php" method="POST">
                <label for="username"> Username</label>
                <input type="text" name="username" id="username" required>
                
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
                <a href="#">Forgot Password?</a>
                <br>
                <button type="submit">Login</button>
            </form>
        </div>
    </div>

    <script>
        function closeError() {
            const overlay = document.getElementById('errorOverlay');
            if (overlay) {
                overlay.classList.add('fade-out');
                setTimeout(() => overlay.remove(), 500);
            }
        }

        function continueToDashboard() {
            const overlay = document.getElementById('successOverlay');
            if (overlay) {
                overlay.classList.add('fade-out');
                setTimeout(() => {
                    overlay.remove();
                    window.location.href = 'dashboard.php'; // Redirect to the dashboard or desired page
                }, 500);
            }
        }

        // Automatically close the success popup after 4 seconds
        setTimeout(() => {
            if (document.getElementById('successOverlay')) {
                continueToDashboard();
            }
        }, 4000);
    </script>
</body>
</html>

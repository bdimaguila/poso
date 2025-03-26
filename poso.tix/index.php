<?php 
// Start the session
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POSO Ticketing System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/poso/poso.tix/css/index.css">
</head>
<body>



<div class="container d-flex justify-content-center align-items-center">
        <div class="ticket-container d-flex flex-column justify-content-center align-items-center">
            <div class="header-container d-flex justify-content-between align-items-center"> 
                <img src="/POSO/images/left.png" alt="Left Logo" class="logo">
                <div class="col text-center">
                    <p class="public">PUBLIC ORDER & SAFETY OFFICE</p>
                    <p class="city">-City of Binan, Laguna-</p>
                </div>
                <img src="/POSO/images/arman.png" alt="Right Logo" class="logo">
            </div>

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
                    <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
                    <button onclick="continueToDashboard()" class="continue-btn">Continue</button>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Login Form -->
        <div class="btn-container">
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
    </div>
</div>

</div>
</div>


    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.min.js"></script>
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
                    window.location.href = 'menu.php'; 
                }, 500);
            }
        }

        setTimeout(() => {
            if (document.getElementById('successOverlay')) {
                continueToDashboard();
            }
        }, 4000);
    </script>
</body>
</html>

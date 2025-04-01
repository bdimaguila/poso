<?php
session_start();
include 'connection.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$userId = isset($_GET['user_id']) ? $_GET['user_id'] : '';

if (empty($userId)) {
    echo "Invalid request.";
    exit();
}

$query = "SELECT firstname, lastname, username, email, password, signature FROM hh_login WHERE ID = ?";

$stmt = $conn->prepare($query);
$stmt->bindParam(1, $userId, PDO::PARAM_INT); // Correct usage

$stmt->execute();
$stmt->bindColumn(1, $firstname);
$stmt->bindColumn(2, $lastname);
$stmt->bindColumn(3, $username);
$stmt->bindColumn(4, $email);
$stmt->bindColumn(5, $password);
$stmt->bindColumn(6, $signature);
$stmt->fetch(PDO::FETCH_BOUND);
$stmt->closeCursor();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newFirstname = $_POST['firstname'];
    $newLastname = $_POST['lastname'];
    $newUsername = $_POST['username'];
    $newEmail = $_POST['email'];
    $newPassword = $_POST['password']; // Remember to hash this!
    $newSignature = $_FILES['signature']['tmp_name'];

    if (!empty($newSignature)) {
        $signatureData = file_get_contents($newSignature);
        $signatureData = $conn->real_escape_string($signatureData); // Important for BLOBs
    } else {
        $signatureData = $signature; // Keep existing signature if not updated
    }

    if (!empty($newPassword)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    } else {
        $hashedPassword = $password; // Keep existing password if not updated
    }

    $updateQuery = "UPDATE hh_login SET firstname = ?, lastname = ?, username = ?, email = ?, password = ?, signature = ? WHERE ID = ?";

    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(1, $newFirstname);
    $updateStmt->bindParam(2, $newLastname);
    $updateStmt->bindParam(3, $newUsername);
    $updateStmt->bindParam(4, $newEmail);
    $updateStmt->bindParam(5, $hashedPassword);
    $updateStmt->bindParam(6, $signatureData);
    $updateStmt->bindParam(7, $userId, PDO::PARAM_INT);

    if ($updateStmt->execute()) {
        $_SESSION['success'] = "Officer details updated successfully!";
        header("Location: settings.php"); // Redirect to user list page
        exit();
    } else {
        $_SESSION['error'] = "Error updating officer details: " . $updateStmt->error;
    }

    $updateStmt->closeCursor();
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Edit Officer Details</title>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <link rel="stylesheet" href="/poso/admin/css/editofficer.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  

</head>
<body>
<img class="bg" src="/POSO/images/plaza.jpg" alt="Background Image">

<div id="overlay"></div>

<header class="navbar">
            <img src="/POSO/images/left.png" alt="City Logo" class="logo">
            <div>
                <p class="public">PUBLIC ORDER & SAFETY OFFICE</p>
                <p class="city">CITY OF BIÑAN, LAGUNA</p>
            </div>
            <img src="/POSO/images/arman.png" alt="POSO Logo" class="logo">
            
            <div class="hamburger" id="hamburger-icon">
                <i class="fa fa-bars"></i>
            </div>
        </header>

        <div class="sidebar" id="sidebar">
            <div class="logo">
                <img src="/POSO/images/right.png" alt="POSO Logo">
            </div>
            <ul>
                <li><a href="dashboard.php" > <i class="fas fa-home"></i> Home</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="report.php" class="active"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

   <div class="form">     
   <div style="text-align: center;">
    <h2 class="edit">Edit Officer Details</h2> <br><br>
</div>
    <?php if (isset($_SESSION['error'])) : ?>
        <p style="color: red;"><?php echo $_SESSION['error']; ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data">
        First Name: <input type="text" name="firstname" value="<?php echo htmlspecialchars($firstname); ?>"><br>
        Last Name: <input type="text" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>"><br>
        Username: <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>"><br>
        Email: <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>"><br>
        Password: <input type="password" name="password" placeholder="Leave blank to keep current password"><br><br>
        Signature: <input type="file" name="signature"><br><br><br>
        <div class="container">
    <input type="submit" value="Submit">
        </div>
    </form>

    </div>
    <script>
        //hamburger and sidebar
        const hamburgerIcon = document.getElementById('hamburger-icon');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        hamburgerIcon.addEventListener('click', function(event) {
            sidebar.classList.toggle('show'); // Toggle sidebar
            overlay.classList.toggle('show'); // Show overlay
            event.stopPropagation(); // Prevent immediate close
        });

        // Close sidebar & overlay when clicking on the overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        // Close sidebar & overlay when clicking outside of the sidebar
        document.addEventListener('click', function(event) {
            if (!sidebar.contains(event.target) && !hamburgerIcon.contains(event.target)) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            }
        });
    </script>
</body>
</html>
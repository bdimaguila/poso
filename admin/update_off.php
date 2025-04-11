<?php
session_start();
include 'connection.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$currentUserId = $_SESSION['user_id']; // Get the ID of the logged-in user performing the action
$currentUsername = $_SESSION['username']; // Get the username of the logged-in user
$userIdToEdit = isset($_GET['user_id']) ? $_GET['user_id'] : '';

if (empty($userIdToEdit)) {
    echo "Invalid request.";
    exit();
}

$query = "SELECT firstname, lastname, username, email, password, signature FROM hh_login WHERE ID = ?";

$stmt = $conn->prepare($query);
$stmt->bindParam(1, $userIdToEdit, PDO::PARAM_INT); // Correct usage

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
    $newSignatureTmp = $_FILES['signature']['tmp_name'];
    $signatureData = $signature; // Default to existing signature

    if (!empty($newSignatureTmp)) {
        $signatureData = file_get_contents($newSignatureTmp);
        // No need for $conn->real_escape_string with PDO prepared statements for BLOBs
    }

    $hashedPassword = !empty($newPassword) ? password_hash($newPassword, PASSWORD_DEFAULT) : $password;

    $updateQuery = "UPDATE hh_login SET firstname = ?, lastname = ?, username = ?, email = ?, password = ?, signature = ? WHERE ID = ?";

    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(1, $newFirstname);
    $updateStmt->bindParam(2, $newLastname);
    $updateStmt->bindParam(3, $newUsername);
    $updateStmt->bindParam(4, $newEmail);
    $updateStmt->bindParam(5, $hashedPassword);
    $updateStmt->bindParam(6, $signatureData, PDO::PARAM_LOB); // Specify LOB parameter type
    $updateStmt->bindParam(7, $userIdToEdit, PDO::PARAM_INT);

    if ($updateStmt->execute()) {
        // Log the activity
        $activity = "$currentUsername updated officer $username (ID: $userIdToEdit).";
        $logQuery = "INSERT INTO profile_activity_log (user_id, username, activity, timestamp) VALUES (?, ?, ?, NOW())";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bindParam(1, $currentUserId, PDO::PARAM_INT);
        $logStmt->bindParam(2, $currentUsername);
        $logStmt->bindParam(3, $activity);
        $logStmt->execute();
        $logStmt->closeCursor();

        $_SESSION['success'] = "Officer details updated successfully!";
        header("Location: settings.php"); // Redirect to user list page
        exit();
    } else {
        $_SESSION['error'] = "Error updating officer details: " . print_r($updateStmt->errorInfo(), true);
    }

    $updateStmt->closeCursor();
}
?>

<!DOCTYPE html>
<html>
<head>

    <title>Edit Officer Details</title>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <link rel="stylesheet" href="/poso/admin/css/editofficer1.css">
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
            <li><a href="report.php" ><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
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
        <button id="previousButton" class="btn btn-secondary"
        onclick="goToPreviousPage()">Back</button>

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

        function goToPreviousPage() {
            window.history.back();
        }
    </script>
</body>
</html>
<?php
// Start the session
session_start();

// Check if user is logged in by verifying if 'user_id' is set in the session
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if user_id is not set
    header("Location: index.php");
    exit();
}

// Include the database connection file
include 'connection.php'; // Make sure this path is correct

// Fetch user data from login table
$user_id = $_SESSION['user_id'];  // Get the stored user_id from the session

// Prepare SQL query to prevent SQL injection
$stmt = $conn->prepare("SELECT username, image FROM login WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

// Initialize variables for username and image
$username = "ADMIN 123";  // Default value
$imageData = null;  // Default image data

if ($stmt->rowCount() > 0) {
    // Fetch data
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $row['username'];  // Get username from database
    $imageData = $row['image'];  // Get image data from database
}

// Function to output the image data
function displayImage($imageData) {
    if ($imageData) {
        // Set the correct content type for the image
        header("Content-Type: image/jpeg");
        echo $imageData; // Output the binary image data
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="d_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <img src="/POSO/images/right.png" alt="POSO Logo">
        </div>
        <ul>
            <li><a href="#" class="active"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="#"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="#"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    <div class="main-content">
        <header>
            <img src="/POSO/images/left.png" alt="City Logo">
            <h1>PUBLIC ORDER & SAFETY OFFICE<br>CITY OF BIÑAN</h1>
        </header>
        <br><br><br>
        <div class="welcome-box">
            <div class="left-side">
                <?php if ($imageData): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($imageData); ?>" alt="Admin Icon">
                <?php else: ?>
                    <img src="/POSO/images/default.png" alt="Default Admin Icon"> <!-- Fallback image -->
                <?php endif; ?>
            </div>
            <div class="right-side">
                <h3>Welcome!</h3>
                <h1><?php echo htmlspecialchars($username); ?></h1>
            </div>
        </div>
    </div>
</body>
</html>

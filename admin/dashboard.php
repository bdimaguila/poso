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
$stmt = $conn->prepare("SELECT username, image FROM login WHERE ID = :user_id");
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

// Query to count violations by ticket_number for each table
$stmt1 = $conn->prepare("SELECT ticket_number, COUNT(*) as violation_count FROM violation GROUP BY ticket_number");
$stmt1->execute();

$stmt2 = $conn->prepare("SELECT ticket_number, COUNT(*) as violation_count FROM 2_violation GROUP BY ticket_number");
$stmt2->execute();

$stmt3 = $conn->prepare("SELECT ticket_number, COUNT(*) as violation_count FROM 3_violation GROUP BY ticket_number");
$stmt3->execute();

$stmt4 = $conn->prepare("SELECT ticket_number, COUNT(*) as violation_count FROM report GROUP BY ticket_number");
$stmt4->execute();

// Query to count the total violations in the report table
$stmt5 = $conn->prepare("SELECT COUNT(*) as total_report_violations FROM report");
$stmt5->execute();
$row5 = $stmt5->fetch(PDO::FETCH_ASSOC);
$totalReportViolations = $row5['total_report_violations'];  // Get the count of violations from the report table

// Initialize counts for each violation type and the report table
$firstViolation = 0;
$secondViolation = 0;
$thirdViolation = 0;
$totalViolations = 0;

// Count the total occurrences of each violation type
while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
    $firstViolation++;
}
while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    $secondViolation++;
}
while ($row = $stmt3->fetch(PDO::FETCH_ASSOC)) {
    $thirdViolation++;
}
while ($row = $stmt4->fetch(PDO::FETCH_ASSOC)) {
    $totalViolations++;
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
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/poso/admin/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>
<div id="overlay"></div>

        <!-- <div class="sidebar">
            <div class="logo">
                <img src="/POSO/images/right.png" alt="POSO Logo">
            </div>
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="report.php"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div> -->

        <img class="bg" src="/POSO/images/db.jpg" alt="Background Image">


    <div class="main-content">
    <header class="navbar">
    <img src="/POSO/images/left.png" alt="City Logo" class="logo">
    <div>
        <p class="public">PUBLIC ORDER & SAFETY OFFICE</p>
        <p class="city">CITY OF BIÑAN, LAGUNA</p>
    </div>
    <img src="/POSO/images/arman.png" alt="POSO Logo" class="logo">
    
    <div class="hamburger" id="hamburger-icon">
    <i class="fa fa-bars"></i> <!-- Font Awesome hamburger icon -->
</div>

<div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="/POSO/images/right.png" alt="POSO Logo">
    </div>
    <ul>
        <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="report.php"><i class="fas fa-file-alt"></i> Reports</a></li>
        <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>


</header>




<div class="data-analytics-container">
    <h1 class="data" style="text-align: center; color:white;">DATA ANALYTICS</h1>
    <div class="analytics-container">
        <div class="container">
            <h2>1st Violation Count</h2>
            <canvas id="violationProgress" width="250" height="250"></canvas>
        </div>
        <div class="container">
            <h2>2nd Violation Count</h2>
            <canvas id="violation2Progress" width="250" height="250"></canvas>
        </div>
        <div class="container">
            <h2>3rd Violation Count</h2>
            <canvas id="violation3Progress" width="250" height="250"></canvas>
        </div>
    </div>
</div>

    <script>
        // Fetch the violation counts from PHP variables
        var firstViolation = <?php echo $firstViolation; ?>;
        var secondViolation = <?php echo $secondViolation; ?>;
        var thirdViolation = <?php echo $thirdViolation; ?>;
        var totalViolations = <?php echo $totalViolations; ?>;

        // Progress Circle Chart 1 (Violation table)
        var violationProgress = document.getElementById('violationProgress').getContext('2d');
        new Chart(violationProgress, {
            type: 'doughnut',
            data: {
                labels: ['1st Violations'],
                datasets: [{
                    data: [firstViolation, totalViolations - firstViolation],
                    backgroundColor: ['#008000', '#ccc'],
                    borderWidth: 2
                }]
            },
            options: {
                rotation: -90,
                circumference: 180,
                responsive: true,
                cutout: '70%'
            }
        });

        // Progress Circle Chart 2 (2_violation table)
        var violation2Progress = document.getElementById('violation2Progress').getContext('2d');
        new Chart(violation2Progress, {
            type: 'doughnut',
            data: {
                labels: ['2nd Violations'],
                datasets: [{
                    data: [secondViolation, totalViolations - secondViolation],
                    backgroundColor: ['#FFD700', '#ccc'],
                    borderWidth: 2
                }]
            },
            options: {
                rotation: -90,
                circumference: 180,
                responsive: true,
                cutout: '70%'
            }
        });

        // Progress Circle Chart 3 (3_violation table)
        var violation3Progress = document.getElementById('violation3Progress').getContext('2d');
        new Chart(violation3Progress, {
            type: 'doughnut',
            data: {
                labels: ['3rd Violations'],
                datasets: [{
                    data: [thirdViolation, totalViolations - thirdViolation],
                    backgroundColor: ['#8B4513', '#ccc'],
                    borderWidth: 2
                }]
            },
            options: {
                rotation: -90,
                circumference: 180,
                responsive: true,
                cutout: '70%'
            }
        });


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

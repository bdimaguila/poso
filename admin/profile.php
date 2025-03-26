<?php
session_start();
include('connection.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

try {
    // Fetch logged-in user data using PDO
    $query = "SELECT * FROM login WHERE ID = :user_id LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user data was found
    if (!$user) {
        throw new Exception("User data not found.");
    }

    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['tmp_name']) {
            $image = file_get_contents($_FILES['profile_image']['tmp_name']);
            $updateQuery = "UPDATE login SET firstname = :firstname, lastname = :lastname, username = :username, email = :email, password = :password, image = :image WHERE ID = :user_id";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(':image', $image, PDO::PARAM_LOB);
        } else {
            $updateQuery = "UPDATE login SET firstname = :firstname, lastname = :lastname, username = :username, email = :email, password = :password WHERE ID = :user_id";
            $updateStmt = $conn->prepare($updateQuery);
        }

        $updateStmt->bindParam(':firstname', $firstname);
        $updateStmt->bindParam(':lastname', $lastname);
        $updateStmt->bindParam(':username', $username);
        $updateStmt->bindParam(':email', $email);
        $updateStmt->bindParam(':password', $password);
        $updateStmt->bindParam(':user_id', $_SESSION['user_id']);
        $updateStmt->execute();

        // Refresh user data after update
        header("Location: profile.php");
        exit();
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <title>Admin Profile</title>
    <link rel="stylesheet" href="/poso/admin/css/profile.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>



</head>
<body>

<img class="bg" src="/POSO/images/paz.jpg" alt="Background Image">

<div id="overlay"></div>


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
    </header>


    <div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="/POSO/images/right.png" alt="POSO Logo">
    </div>
    <ul>
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="profile.php" class="active"> <i class="fas fa-user"></i> Profile</a></li>
        <li><a href="report.php"><i class="fas fa-file-alt"></i> Reports</a></li>
        <li><a href="settings.php"> <i class="fas fa-cog"></i> Settings</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>


        <?php
            $current_page = basename($_SERVER['PHP_SELF']); // Get the current file name
        ?>

            <div class="sidebar" id="sidebar">
                <div class="logo">
                    <img src="/POSO/images/right.png" alt="POSO Logo">
                </div>
                <ul>
                    <li><a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="profile.php" class="<?= $current_page == 'profile.php' ? 'active' : '' ?>"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="report.php" class="<?= $current_page == 'report.php' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Reports</a></li>
                    <li><a href="settings.php" class="<?= $current_page == 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
            </header>


    <div class="card">
        

<!-- Profile Picture Positioned on Top of Form -->
<div class="profile-container text-center mt-5">
    <label for="profileImage" class="profile-image-label position-relative">
        <?php if ($user['image']): ?>
            <img src="data:image/jpeg;base64,<?= base64_encode($user['image']) ?>" alt="Profile Picture" class="profile-image">
        <?php else: ?>
            <img src="https://via.placeholder.com/150" alt="Profile Picture" class="profile-image">
        <?php endif; ?>

        <!-- Hidden File Input -->
        <input type="file" name="profile_image" id="profileImage" class="d-none" accept="image/*">

        <!-- Overlay with Camera Icon (Initially Hidden) -->
        <div class="profile-overlay d-none" id="profileOverlay">
            <i class="fas fa-camera"></i>
        </div>
    </label>
</div>



<!-- Profile Form -->
<form method="POST" enctype="multipart/form-data" class="p-5 shadow rounded bg-white" id="profileForm">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            
<!-- Edit Profile Button (Now centered inside the shadow box) -->
<div class="d-flex flex-column align-items-center mb-3 mt-3">
    <button type="button" id="toggleUpdate" class="edit btn btn-success">Edit Profile</button>
    <div id="updateNotification" class="update-notification text-white mt-2" style="display: none;">
        You're updating your profile
    </div>
</div>


            <div class="form-group text-center">
                <input type="file" name="profile_image" class="form-control-file" id="profileImage" style="display: none;">
            </div>
            <div class="form-group mt-5">
                <label>First Name:</label>
                <input type="text" name="firstname" class="form-control mb-2" value="<?= htmlspecialchars($user['firstname']) ?>" required id="firstname" disabled>
                <label>Last Name:</label>
                <input type="text" name="lastname" class="form-control mb-2" value="<?= htmlspecialchars($user['lastname']) ?>" required id="lastname" disabled>
            </div>
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" class="form-control mb-2" value="<?= htmlspecialchars($user['username']) ?>" required id="username" disabled>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" class="form-control mb-2" value="<?= htmlspecialchars($user['email']) ?>" required id="email" disabled>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" class="form-control mb-2" value="<?= htmlspecialchars($user['password']) ?>" required id="password" disabled>
            </div>

            <!-- Update Profile Button (Initially Hidden) -->
            <div class="d-flex flex-column align-items-center mb-5 mt-5">
            <button type="submit" name="update_profile" class="update btn btn-success" id="updateButton" style="display: none;">Update Profile</button>
            </div>
        </div>
    </div>
</form>

    </div>



<script>
document.addEventListener("DOMContentLoaded", function () {
        let isUpdating = false;
        let originalValues = {};

        document.getElementById("toggleUpdate").addEventListener("click", function () {
            const toggleButton = document.getElementById("toggleUpdate");
            const profileOverlay = document.getElementById("profileOverlay");

            if (!isUpdating) {
                // Store original values when entering edit mode
                originalValues = {
                    firstname: document.getElementById("firstname").value,
                    lastname: document.getElementById("lastname").value,
                    username: document.getElementById("username").value,
                    email: document.getElementById("email").value,
                    password: document.getElementById("password").value
                };

                toggleButton.innerText = "Cancel Edit";
                isUpdating = true;

                // Show camera overlay
                profileOverlay.classList.remove("d-none");
            } else {
                // Restore original values when canceling
                document.getElementById("firstname").value = originalValues.firstname;
                document.getElementById("lastname").value = originalValues.lastname;
                document.getElementById("username").value = originalValues.username;
                document.getElementById("email").value = originalValues.email;
                document.getElementById("password").value = originalValues.password;

                toggleButton.innerText = "Edit Profile";
                isUpdating = false;

                // Hide camera overlay
                profileOverlay.classList.add("d-none");
            }

            // Enable or disable fields
            const elements = ["firstname", "lastname", "username", "email", "password", "profileImage"];
            elements.forEach(id => document.getElementById(id).disabled = !isUpdating);

            document.getElementById("updateButton").style.display = isUpdating ? "block" : "none";
            document.getElementById("profileImage").style.display = isUpdating ? "block" : "none";
            document.getElementById("updateNotification").style.display = isUpdating ? "block" : "none";
        });
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

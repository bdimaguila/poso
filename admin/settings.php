<?php
include('connection.php');
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

function fetchUsers($table) {
    global $conn;
    $query = "SELECT * FROM $table";
    $stmt = $conn->query($query);
    return $stmt;
}

function deleteUser($table, $id) {
    global $conn;
    $query = "DELETE FROM $table WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}

function addUser($table, $firstname, $lastname, $username, $email, $password, $signature) {
    global $conn;
    $query = "INSERT INTO $table (firstname, lastname, username, email, password, signature)
                VALUES (:firstname, :lastname, :username, :email, :password, :signature)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':firstname', $firstname);
    $stmt->bindParam(':lastname', $lastname);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);
    $stmt->bindParam(':signature', $signature);
    return $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $signature = file_get_contents($_FILES['signature']['tmp_name']);
        $table = ($role == 'admin') ? 'login' : 'hh_login';

        if (addUser($table, $firstname, $lastname, $username, $email, $password, $signature)) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }

    if (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        $role = $_POST['role'];
        $table = ($role == 'admin') ? 'login' : 'hh_login';

        if (deleteUser($table, $id)) {
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="/poso/admin/css/settings1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> <!-- Font Awesome -->

<!-- Bootstrap Grid (for layout) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css">

<!-- Bootstrap Forms (for form styling) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css">

</head>
<body>

<img class="bg" src="/POSO/images/plaza.jpg" alt="Background Image">

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


    
 <!-- Overlay for the form -->
 <div id="formOverlay" onclick="closeForm()"></div>

<!-- Centered Form -->
<div class="container d-flex justify-content-center align-items-center">
    <form class="card mt-5" id="userForm" method="POST" enctype="multipart/form-data">
        <label for="firstname">First Name:</label>
        <input type="text" id="firstname" name="firstname" required>

        <label for="lastname">Last Name:</label>
        <input type="text" id="lastname" name="lastname" required>

        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <label for="signature">Signature:</label>
        <input type="file" id="signature" name="signature" accept="image/*" required>

        <label for="role">Role:</label>
        <select id="role" name="role">
            <option value="admin">Admin</option>
            <option value="officer">Officer</option>
        </select>

<button type="submit" name="add_user" class="btn btn-success" style="margin-top: 35px; display: block; margin-left: auto; margin-right: auto;">Submit</button>
    </form>
</div>


<div class="sidebar" id="sidebar">
    <div class="logo">
        <img src="/POSO/images/right.png" alt="POSO Logo">
    </div>
    <ul>
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
        <li><a href="report.php"><i class="fas fa-file-alt"></i> Reports</a></li>
        <li><a href="settings.php"  class="active"><i class="fas fa-cog"></i> Settings</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

</header>

<div class="container mt-5 pt-5">
    <h1 class="text-white  heading text-center mt-5 mb-5 ">USER MANAGEMENT </h1>

            <form action="" method="post" class="mb-4">
                <div class="row g-5">
                <button type="button" class="btn btn-success " onclick="toggleForm()">Add New User</button>
                </div>
            </form>

    <div class="table-container">
         <table class="table table-bordered mt-1 mb-5">
            <thead>
                <tr>
                <th class="head">Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Password</th>
                    <th>Signature</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>

        <tbody>
        <?php
            $admins = fetchUsers('login');
            while ($row = $admins->fetch(PDO::FETCH_ASSOC)) {
                $fullname = $row['firstname'] . ' ' . $row['lastname'];
                echo "<tr>
                    <td>$fullname</td>
                    <td>{$row['username']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['password']}</td>
                    <td><img src='data:image/jpeg;base64," . base64_encode($row['signature']) . "' height='50'/></td>
                    <td>Admin</td>
                    <td>
                        <form method='POST' style='display:inline;'>
                            <input type='hidden' name='user_id' value='{$row['ID']}'>
                            <input type='hidden' name='role' value='admin'>
                            <button type='submit' name='delete_user' class='btn btn-danger'>Delete</button>
                        </form>
                    </td>
                    </tr>";
            }

            $officers = fetchUsers('hh_login');
            while ($row = $officers->fetch(PDO::FETCH_ASSOC)) {
                $fullname = $row['firstname'] . ' ' . $row['lastname'];
                echo "<tr>
                    <td>$fullname</td>
                    <td>{$row['username']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['password']}</td>
                    <td><img src='data:image/jpeg;base64," . base64_encode($row['signature']) . "' height='50'/></td>
                    <td>Officer</td>
                    <td>
                        <form method='POST' style='display:inline;'>
                            <input type='hidden' name='user_id' value='{$row['ID']}'>
                            <input type='hidden' name='role' value='officer'>
                            <button type='submit' name='delete_user' class='btn btn-danger'>Delete</button>
                        </form>
                    </td>
                </tr>";
            }
            ?>
    </tbody>
        </table>
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

        //add user button
        function toggleForm() {
        var form = document.getElementById("userForm");
        form.style.display = (form.style.display === "none") ? "block" : "none";
        }

        //form
        function toggleForm() {
            document.getElementById("userForm").style.display = "block";
            document.getElementById("formOverlay").style.display = "block";
        }

        function closeForm() {
            document.getElementById("userForm").style.display = "none";
            document.getElementById("formOverlay").style.display = "none";
        } 
    </script>
</body>
</html>
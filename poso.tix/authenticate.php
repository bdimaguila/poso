<?php
session_start();
include 'connection.php'; // Ensure this connects to the POSO database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT ID, username, password FROM hh_login WHERE username = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("Error in statement preparation: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            // Password is correct (hashed)
            $_SESSION['user_id'] = $row['ID'];
            $_SESSION['username'] = $username;
            $_SESSION['success'] = "You have logged in successfully!";
            header("Location: index.php");
            exit;
        } else {
            // Check if it's a non-hashed password
            $query_check_unhashed = "SELECT ID, username, password FROM hh_login WHERE username = ? AND password = ?";
            $stmt_check_unhashed = $conn->prepare($query_check_unhashed);
            $stmt_check_unhashed->bind_param("ss", $username, $password);
            $stmt_check_unhashed->execute();
            $result_unhashed = $stmt_check_unhashed->get_result();

            if ($row_unhashed = $result_unhashed->fetch_assoc()) {
                // Migrate the password to a hashed password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query_update_password = "UPDATE hh_login SET password = ? WHERE ID = ?";
                $stmt_update_password = $conn->prepare($query_update_password);
                $stmt_update_password->bind_param("si", $hashed_password, $row_unhashed['ID']);
                $stmt_update_password->execute();

                $_SESSION['user_id'] = $row_unhashed['ID'];
                $_SESSION['username'] = $username;
                $_SESSION['success'] = "You have logged in successfully! Your password has been updated for security.";
                header("Location: index.php");
                exit;
            } else {
                $_SESSION['error'] = "Invalid username or password.";
                header("Location: index.php");
                exit;
            }
        }
    } else {
        $_SESSION['error'] = "Invalid username or password.";
        header("Location: index.php");
        exit;
    }

    $stmt->close();
}

$conn->close();
?>
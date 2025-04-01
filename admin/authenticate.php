<?php
session_start();
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = "SELECT ID, username, password FROM login WHERE username = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (password_verify($password, $row['password'])) {
            // Password is correct (hashed)
            $_SESSION['user_id'] = $row['ID'];
            $_SESSION['username'] = $username;
            $_SESSION['success'] = 'You have successfully logged in!';
            header("Location: index.php");
            exit();
        } else {
            // Check if it's a non-hashed password
            $query_check_unhashed = "SELECT ID, username, password FROM login WHERE username = :username AND password = :password";
            $stmt_check_unhashed = $conn->prepare($query_check_unhashed);
            $stmt_check_unhashed ->bindParam(':username', $username);
            $stmt_check_unhashed ->bindParam(':password', $password);
            $stmt_check_unhashed ->execute();

            if($row_unhashed = $stmt_check_unhashed ->fetch(PDO::FETCH_ASSOC)){
                //migrate the password to a hashed password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query_update_password = "UPDATE login SET password = :password WHERE ID = :id";
                $stmt_update_password = $conn->prepare($query_update_password);
                $stmt_update_password ->bindParam(':password', $hashed_password);
                $stmt_update_password ->bindParam(':id', $row_unhashed['ID']);
                $stmt_update_password ->execute();

                $_SESSION['user_id'] = $row_unhashed['ID'];
                $_SESSION['username'] = $username;
                $_SESSION['success'] = 'You have successfully logged in! Your password has been updated for security.';
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['error'] = 'Invalid username or password.';
                header("Location: index.php");
                exit();
            }

        }
    } else {
        $_SESSION['error'] = 'Invalid username or password.';
        header("Location: index.php");
        exit();
    }
}
?>
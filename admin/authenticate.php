<?php 
// Start the session
session_start();

// Include the database connection file
include 'connection.php'; // Ensure the path is correct

// Handle authentication logic here
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Example query (modify according to your database structure)
    $query = "SELECT * FROM login WHERE username = :username AND password = :password"; // Ensure password handling is secure
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password);
    $stmt->execute();

    // Check if user exists
    if ($stmt->rowCount() > 0) {
        // User authenticated successfully
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the user data
        $_SESSION['user_id'] = $row['ID']; // Store user ID in the session
        $_SESSION['username'] = $username; // Store username in the session (optional)
        
        // Set success message and redirect
        $_SESSION['success'] = 'You have successfully logged in!';
        header("Location: index.php"); // Redirect back to the login page
        exit(); // Always use exit after a redirect
    } else {
        // Authentication failed
        $_SESSION['error'] = 'Invalid username or password.';
        header("Location: index.php"); // Redirect back to the login page
        exit(); // Always use exit after a redirect
    }
}
?>

<?php
// Start the session
session_start();

// Prevent the page from being cached after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: index.php");
    exit();
}

// Unset the session variables and destroy the session
session_unset();
session_destroy();

// Redirect to the login page
header("Location: index.php");
exit();
?>

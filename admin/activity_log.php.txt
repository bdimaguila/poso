<?php
session_start();
include 'connection.php';

if (isset($_POST['user_id']) && isset($_POST['username']) && isset($_POST['activity'])) {
    $user_id = $_POST['user_id'];
    $username = $_POST['username'];
    $activity = $_POST['activity'];

    $stmt_log = $conn->prepare("INSERT INTO profile_activity_log (user_id, username, activity, timestamp) VALUES (:user_id, :username, :activity, NOW())");
    $stmt_log->bindParam(':user_id', $user_id);
    $stmt_log->bindParam(':username', $username);
    $stmt_log->bindParam(':activity', $activity);

    if ($stmt_log->execute()) {
        echo 'Activity logged successfully.';
    } else {
        echo 'Error logging activity: ' . $stmt_log->errorInfo()[2];
    }
} else {
    echo 'Invalid parameters for logging activity.';
}
?>
<?php
$conn = new mysqli("localhost", "root", "", "poso");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_number = $_POST['ticket_number'];
    $signature = $_POST['signature'];

    $signature = str_replace('data:image/png;base64,', '', $signature);
    $signature = base64_decode($signature);

    $stmt = $conn->prepare("UPDATE report SET signature = ? WHERE ticket_number = ?");
    $stmt->bind_param("ss", $signature, $ticket_number);

    if ($stmt->execute()) {
        echo "Signature saved successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

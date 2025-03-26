<?php
// Start the session
session_start();


// Database connection (adjust with your DB settings)
$conn = new mysqli('localhost', 'root', '', 'poso'); // Update with your DB details

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch tickets from the 'report' table
$sql = "SELECT ticket_number, created_at FROM report ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket History</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=1.0">
    <style>
        .ticket-history-container {
            width: 80%;
            margin: auto;
            margin-top: 30px;
        }

        table {
            width: 100%;
            margin-top: 20px;
        }

        th, td {
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        .btn-container {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }

        .btn-container a {
            text-decoration: none;
            color: #fff;
            background-color: #007bff;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .btn-container a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="ticket-history-container">
            <h3 class="text-center">Ticket History</h3>

            <!-- Ticket History Table -->
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Ticket Number</th>
                        <th>Date and Time Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Check if there are any tickets
                    if ($result->num_rows > 0) {
                        // Output data of each row
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$row['ticket_number']}</td>
                                    <td>" . date('Y-m-d H:i:s', strtotime($row['created_at'])) . "</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No tickets found.</td></tr>";
                    }

                    // Close the database connection
                    $conn->close();
                    ?>
                </tbody>
            </table>

            <!-- Back to Main Menu Button -->
            <div class="btn-container">
                <a href="menu.php">Back to Main Menu</a>
            </div>
        </div>
    </div>
</body>
</html>

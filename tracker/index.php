<?php
// Database connection
$host = 'localhost';
$db = 'poso';
$user = 'root';
$pass = '';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
    exit;
}

// Get the ticket number and license number from the URL
$ticket_number = isset($_GET['ticket_number']) ? $_GET['ticket_number'] : '';
$license_number = isset($_GET['license_number']) ? $_GET['license_number'] : '';

// Initialize variables
$violations_data = [];

// Fetch all violations for the given ticket number and/or license number
if ($ticket_number && $license_number) {
    $query = "
        SELECT v.ticket_number, v.first_name, v.last_name, v.first_violation AS violation, v.first_total AS amount, v.status, r.confiscated, r.violation_date, r.violation_time, r.license 
        FROM violation v
        JOIN report r ON v.ticket_number = r.ticket_number 
        WHERE v.ticket_number = ? AND r.license = ?
        
        UNION
        
        SELECT v.ticket_number, v.first_name, v.last_name, v.second_violation AS violation, v.second_total AS amount, v.status, r.confiscated, r.violation_date, r.violation_time, r.license 
        FROM 2_violation v
        JOIN report r ON v.ticket_number = r.ticket_number 
        WHERE v.ticket_number = ? AND r.license = ?
        
        UNION
        
        SELECT v.ticket_number, v.first_name, v.last_name, v.third_violation AS violation, v.third_total AS amount, v.status, r.confiscated, r.violation_date, r.violation_time, r.license 
        FROM 3_violation v
        JOIN report r ON v.ticket_number = r.ticket_number 
        WHERE v.ticket_number = ? AND r.license = ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$ticket_number, $license_number, $ticket_number, $license_number, $ticket_number, $license_number]);
    $violations_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($ticket_number) {
    // Fetch data only based on ticket number
    $query = "
        SELECT v.ticket_number, v.first_name, v.last_name, v.first_violation AS violation, v.first_total AS amount, v.status, r.confiscated, r.violation_date, r.violation_time, r.license 
        FROM violation v
        JOIN report r ON v.ticket_number = r.ticket_number 
        WHERE v.ticket_number = ?
        
        UNION
        
        SELECT v.ticket_number, v.first_name, v.last_name, v.second_violation AS violation, v.second_total AS amount, v.status, r.confiscated, r.violation_date, r.violation_time, r.license 
        FROM 2_violation v
        JOIN report r ON v.ticket_number = r.ticket_number 
        WHERE v.ticket_number = ?
        
        UNION
        
        SELECT v.ticket_number, v.first_name, v.last_name, v.third_violation AS violation, v.third_total AS amount, v.status, r.confiscated, r.violation_date, r.violation_time, r.license 
        FROM 3_violation v
        JOIN report r ON v.ticket_number = r.ticket_number 
        WHERE v.ticket_number = ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$ticket_number, $ticket_number, $ticket_number]);
    $violations_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($license_number) {
    // Fetch data only based on license number
    $query = "
        SELECT v.ticket_number, v.first_name, v.last_name, v.first_violation AS violation, v.first_total AS amount, v.status, r.confiscated, r.violation_date, r.violation_time, r.license 
        FROM violation v
        JOIN report r ON v.ticket_number = r.ticket_number 
        WHERE r.license = ?
        
        UNION
        
        SELECT v.ticket_number, v.first_name, v.last_name, v.second_violation AS violation, v.second_total AS amount, v.status, r.confiscated, r.violation_date, r.violation_time, r.license 
        FROM 2_violation v
        JOIN report r ON v.ticket_number = r.ticket_number 
        WHERE r.license = ?
        
        UNION
        
        SELECT v.ticket_number, v.first_name, v.last_name, v.third_violation AS violation, v.third_total AS amount, v.status, r.confiscated, r.violation_date, r.violation_time, r.license 
        FROM 3_violation v
        JOIN report r ON v.ticket_number = r.ticket_number 
        WHERE r.license = ?
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$license_number, $license_number, $license_number]);
    $violations_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Clear the search
if (isset($_GET['clear'])) {
    $ticket_number = '';
    $license_number = '';
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/POSO/tracker/css/style.css">
    <title>POSO Violation Tracker</title>
    <style>
        .no-data {
            text-align: center;
            font-size: 18px;
            margin-top: 50px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <header>
            <img src="/POSO/images/left.png" alt="City Logo">
            <h1>PUBLIC ORDER & SAFETY OFFICE<br>CITY OF BIÑAN</h1>
            <img src="/POSO/images/arman.png" alt="POSO Logo">
        </header>
        <br><br> 

        <div class="report-container">
            <h1>Please enter a valid ticket number or license number</h1>
            <form method="GET" action="" class="search-filter">
                <div class="search-bar">
                    <input type="text" id="ticket_number" name="ticket_number" placeholder="Ticket Number" value="<?php echo $ticket_number; ?>">
                    <input type="text" id="license_number" name="license_number" placeholder="License Number" value="<?php echo $license_number; ?>">
                    <?php if ($ticket_number || $license_number): ?>
                        <a href="?clear=true" class="clear-search">CLEAR</a>
                    <?php endif; ?>
                    &nbsp;&nbsp;<button type="submit">Search</button>
                </div>
                <p>*License Number can be empty if Ticket Number is present.</p>
            </form>
        </div>
    </div>

    <?php if (!empty($violations_data)): ?>
        <table>
            <thead>
                <tr>
                    <th>Ticket Number</th>
                    <?php if (!empty($license_number)): ?>
                        <th>License Number</th>
                    <?php endif; ?>
                    <th>Violator's Name</th>
                    <th>Violation</th>
                    <th>Amount</th>
                    <th>Payment Status</th>
                    <th>License Confiscated</th>
                    <th>Violation Date</th>
                    <th>Violation Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($violations_data as $violation): ?>
                    <tr>
                        <td><?php echo $violation['ticket_number']; ?></td>
                        <?php if (!empty($license_number)): ?>
                            <td><?php echo $violation['license']; ?></td>
                        <?php endif; ?>
                        <td><?php echo $violation['first_name'] . ' ' . $violation['last_name']; ?></td>
                        <td><?php echo $violation['violation']; ?></td>
                        <td><?php echo $violation['amount']; ?></td>
                        <td class="<?php echo ($violation['status'] == 'Paid' ? 'paid' : 'unpaid'); ?>">
                            <?php echo $violation['status']; ?>
                        </td>
                        <td><?php echo $violation['confiscated']; ?></td>
                        <td><?php echo $violation['violation_date']; ?></td>
                        <td><?php echo $violation['violation_time']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif (($ticket_number || $license_number) && empty($violations_data)): ?>
        <p class="no-data">No data found for the provided ticket number or license number.</p>
    <?php endif; ?>
</body>
</html>

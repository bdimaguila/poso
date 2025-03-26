<?php
// Start session
session_start();

// Include the database connection file
include 'connection.php'; // Ensure the path is correct

// Get the ticket number from the session
$ticket_number = $_GET['ticket_number']; // Get from URL

// Get the first name, last name, and total from the URL
$first_name = $_GET['first_name'];
$last_name = $_GET['last_name'];
$total_amount = $_GET['total'];

// Fetch the violation record for the given ticket number
$sql = "SELECT 'First Violation' AS violation_type, first_violation, first_total, notes FROM violation WHERE ticket_number = ?
        UNION ALL
        SELECT 'Second Violation' AS violation_type, second_violation, second_total, notes FROM 2_violation WHERE ticket_number = ?
        UNION ALL
        SELECT 'Third Violation' AS violation_type, third_violation, third_total, notes FROM 3_violation WHERE ticket_number = ?
        ORDER BY violation_type ASC"; // Order the results by violation type

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $ticket_number, $ticket_number, $ticket_number);
$stmt->execute();
$result = $stmt->get_result();

// Fetch officer information (combine lastname and firstname from hh_login table)
$sql_officer = "SELECT CONCAT(lastname, ', ', firstname) AS officer_name FROM hh_login LIMIT 1";
$stmt_officer = $conn->prepare($sql_officer);
$stmt_officer->execute();
$officer_result = $stmt_officer->get_result();
$officer = $officer_result->fetch_assoc();
$officer_name = $officer['officer_name'];

// Fetch violator's information (name, license number, plate number, street, city/municipality from report table)
$sql_violator = "SELECT CONCAT(last_name, ', ', first_name) AS violator_name, license, plate_number, street, city FROM report WHERE ticket_number = ?";
$stmt_violator = $conn->prepare($sql_violator);
$stmt_violator->bind_param("i", $ticket_number);
$stmt_violator->execute();
$violator_result = $stmt_violator->get_result();
$violator = $violator_result->fetch_assoc();
$violator_name = $violator['violator_name'];
$license_number = $violator['license'];
$plate_number = $violator['plate_number'];
$street = $violator['street'];
$city = $violator['city'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POSO Violation Receipt</title>
    <link rel="stylesheet" href="style1.css?v=1.0">
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <style>
        .impound-warning {
            color: red;
            text-align: center;
            font-weight: bold;
            font-size: 1.2em;
            margin-top: 10px;
        }
        .compliance-message {
            text-align: center;
            font-weight: regular;
            margin-top: 20px;
            font-size: 1.2em;
        }
        .button-container {
            text-align: center;
            margin-top: 20px;
        }
        button {
            width: 120px;    /* Set a specific width */
            height: 35px;    /* Set a specific height */
            padding: 0;        /* Remove padding */
            font-size: 14px;    /* Smaller font size */
            margin: 5px;        /* Reduced margin */
            display: inline-block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
 @media print {
            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .button-container {
                display: none; /* Hide buttons during printing */
            }

            .container {
                width: 100%; /* Expand to full width for print */
            }

            .ticket-container {
                margin: 0;
                border: none; /* Adjust for clean edges in print */
            }
        }    </style>
</head>
<body>
    <div class="container">
        <?php if ($result->num_rows > 0) : ?>
            <div class="ticket-container">
                <div class="header-container d-flex justify-content-between align-items-center">
                    <img src="/POSO/images/left.png" alt="Left Logo" class="logo">
                    <div class="col text-center">
                        <p class="title">Traffic Violations</p>
                        <p class="city">-City of Binan, Laguna-</p>
                    </div>
                    <img src="/POSO/images/arman.png" alt="Right Logo" class="logo">
                </div>

                <div class="ticket-info">
                    <p class="ticket-label">Ordinance Infraction Ticket</p>
                    <p class="ticket-number">No. <?php echo htmlspecialchars($ticket_number); ?></p>
                </div>
<div class="gray">
    <h3>Officer Information</h3>
</div>
<p>Name: <?php echo htmlspecialchars($officer_name); ?></p>
<p>Street: <?php echo htmlspecialchars($street); ?></p>
<p>City/Municipality: <?php echo htmlspecialchars($city); ?></p>

                <div class="gray">
                    <h3>Violator Information</h3>
                </div>
                <p>Name: <?php echo htmlspecialchars($violator_name); ?></p>
                <p>License Number: <?php echo htmlspecialchars($license_number); ?></p>
                <p>Plate Number: <?php echo htmlspecialchars($plate_number); ?></p>
                
                <div class="gray">
                    <h3>BREAKDOWN OF VIOLATION CHARGES</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>VIOLATION NUMBER</th>
                            <th>VIOLATION</th>
                            <th>AMOUNT</th>
                        </tr>
                    </thead>
                  <tbody>
    <?php
    $impoundFound = false;
    $violation_columns = [
        'FTWH' => 'FAILURE TO WEAR HELMET',
        'OMN' => 'OPEN MUFFLER/NUISANCE',
        'ARG' => 'ARROGANT',
        'ONEWAY' => 'ONEWAY',
        'ILP' => 'ILLEGAL PARKING',
        'DWL' => 'DRIVING WITHOUT LICENSE/INVALID LICENSE',
        'NORCR' => 'NO OR/CR WHILE DRIVING',
        'DUV' => 'DRIVING UNREGISTERED VEHICLE',
        'UMV' => 'UNREGISTERED MOTOR VEHICLE',
        'OBS' => 'OBSTRUCTION',
        'DTS' => 'DISREGARDING TRAFFIC SIGNS',
        'DTO' => 'DISREGARDING TRAFFIC OFFICER',
        'TRB' => 'TRUCK BAN',
        'STV' => 'STALLED VEHICLE',
        'RCD' => 'RECKLESS DRIVING',
        'DUL' => 'DRIVING UNDER THE INFLUENCE OF LIQUOR',
        'INF' => 'INVALID OR NO FRANCHISE/COLORUM',
        'OOL' => 'OPERATING OUT OF LINE',
        'TCT' => 'TRIP - CUTTING',
        'OVL' => 'OVERLOADING',
        'LUZ' => 'LOADING/UNLOADING IN PROHIBITED ZONE',
        'IVA' => 'INVOLVE IN ACCIDENT',
        'SMB' => 'SMOKE BELCHING',
        'NSM' => 'NO SIDE MIRROR',
        'JWK' => 'JAY WALKING',
        'WSS' => 'WEARING SLIPPERS/SHORTS/SANDO',
        'ILV' => 'ILLEGAL VENDING',
        'IMP' => 'IMPOUNDED'
    ];

    while ($violation = $result->fetch_assoc()) {
        $violation_details = htmlspecialchars($violation['first_violation'] ?? $violation['second_violation'] ?? $violation['third_violation']);
        $violation_amount = htmlspecialchars($violation['first_total'] ?? $violation['second_total'] ?? $violation['third_total']);
        $formatted_violation_details = str_replace(", ", "<br>", $violation_details);

        if (stripos($violation_details, 'IMPOUNDED') !== false) {
            $impoundFound = true;
        }

        if ($violation_amount >= 1) {
            $sql_discount = "SELECT * FROM discount WHERE ticket_number = ? AND license = ?";
            $stmt_discount = $conn->prepare($sql_discount);
            $stmt_discount->bind_param("is", $ticket_number, $license_number);
            $stmt_discount->execute();
            $discount_result = $stmt_discount->get_result();
            $discount = $discount_result->fetch_assoc();
            $stmt_discount->close();

            if ($discount) {
                $discount_applied = false;
                foreach ($violation_columns as $col => $violation_name) {
                    if ($discount[$col] !== null) {
                        echo "<tr>
                                <td>" . htmlspecialchars($violation['violation_type']) . "</td>
                                <td>" . htmlspecialchars($violation_name) . "</td>
                                <td>" . htmlspecialchars($discount[$col]) . "</td>
                            </tr>";
                        $discount_applied = true;
                    }
                }
                if (!$discount_applied) {
                    echo "<tr>
                            <td>" . htmlspecialchars($violation['violation_type']) . "</td>
                            <td>" . $formatted_violation_details . "</td>
                            <td>" . $violation_amount . "</td>
                        </tr>";
                }
                //check if OTHERS and OTHERS_P has value.
                if ($discount['OTHERS'] !== null && $discount['OTHERS_P'] !== null) {
                    echo "<tr>
                            <td>OTHERS</td>
                            <td>" . htmlspecialchars($discount['OTHERS']) . "</td>
                            <td>" . htmlspecialchars($discount['OTHERS_P']) . "</td>
                        </tr>";
                }
            } else {
                echo "<tr>
                        <td>" . htmlspecialchars($violation['violation_type']) . "</td>
                        <td>" . $formatted_violation_details . "</td>
                        <td>" . $violation_amount . "</td>
                    </tr>";
            }
        }
    }

    if ($impoundFound) {
        echo "<tr><td colspan='3' class='impound-warning'>THIS VIOLATOR IS SUBJECT FOR VEHICLE IMPOUND.</td></tr>";
    }

    if ($result->num_rows == 0) {
        echo "<tr><td colspan='3'>No violations found.</td></tr>";
    }
    ?>
</tbody>
            </table>
<br>
<h4>NOTES:</h4>
<ul>
<?php
// Reset the result pointer to fetch notes
$result->data_seek(0); // Move to the first record
while ($violation = $result->fetch_assoc()) {
echo "<li>" . htmlspecialchars($violation['notes']) . "</li>";
}
?>
</ul>
<p class="compliance-message">PLEASE PROCEED TO OFFICE OF THE CITY TREASURER. THANK YOU FOR YOUR COMPLIANCE.</p>

            <div class="button-container">
                <button id="printButton" onclick="printReceipt()">Print</button>
                <button id="nextButton" class="btn btn-secondary" onclick="goToNextPage()">Next</button>

            </div>
        </div>
    <?php else : ?>
        <p>No violation records found for this individual.</p>
    <?php endif; ?>
</div>

<script>
function printReceipt() {
try {
document.querySelector('.button-container').style.display = 'none';

    if (typeof InnerPrinter !== "undefined" && InnerPrinter.print) {
        const receiptContent = document.querySelector('.container').innerHTML;

        const formattedContent = `
            <html>
                <head>
                    <title>Receipt</title>
                </head>
                <body>
                    ${receiptContent}
                </body>
            </html>
        `;

        InnerPrinter.print(formattedContent, function (success) {
            if (success) {
                alert("Printed successfully!");
            } else {
                alert("Failed to print. Please try again.");
            }
        });
    } else {
        window.print();
    }
} catch (error) {
    console.error("Printing error: ", error);
    alert("Printing failed. Check your printer connection.");
} finally {
    document.querySelector('.button-container').style.display = 'block';
}
}

function goToNextPage() {
const ticketNumber = <?php echo json_encode($ticket_number); ?>;
window.location.href = "BLK.php?ticket_number=" + ticketNumber;
}
</script>
</body>
</html>
<?php
// Close connection
$stmt->close();
$stmt_officer->close();
$stmt_violator->close();
$conn->close();
?>
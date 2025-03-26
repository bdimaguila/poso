<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection file
include 'connection.php';

// Get the ticket number from the query parameter
$ticket_number = isset($_GET['ticket_number']) ? $_GET['ticket_number'] : '';

// Fetch report details based on the ticket number
$stmt = $conn->prepare("
    SELECT
        r.ticket_number,
        r.violation_date,
        r.violation_time,
        r.first_name,
        r.middle_name,
        r.last_name,
        r.dob,
        r.address,
        r.license,
        r.registration,
        r.vehicle_owner,
        r.confiscated,
        r.street,                -- Street of violation
        r.city,                 -- City/Municipality
        r.vehicle_type,             -- Vehicle Type
        r.plate_number,             -- Plate Number
        r.signature AS violator_signature  -- Signature of violator (BLOB)
    FROM
        report AS r
    WHERE
        r.ticket_number = :ticket_number
");
$stmt->bindParam(':ticket_number', $ticket_number);
$stmt->execute();

$report = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if no report is found
if (!$report) {
    header("Location: report.php");
    exit();
}

// Initialize penalty-related variables
$status = $amount = $officer_name = $officer_signature = '';

// Check in violation table for penalty information
$stmt = $conn->prepare("
    SELECT
        v.status,
        v.first_total,
        v.others_total,
        v.o_firstname,
        v.o_lastname,
        v.o_signature
    FROM
        violation AS v
    WHERE
        v.ticket_number = :ticket_number
");
$stmt->bindParam(':ticket_number', $ticket_number);
$stmt->execute();
$violation = $stmt->fetch(PDO::FETCH_ASSOC);

if ($violation) {
    $status = $violation['status'];
    $amount = $violation['first_total'] + $violation['others_total'];
    $officer_name = $violation['o_firstname'] . ' ' . $violation['o_lastname'];
    $officer_signature = $violation['o_signature']; // BLOB file
}

// Check in 2_violation table for penalty information
$stmt = $conn->prepare("
    SELECT
        v2.status,
        v2.second_total,
        v2.others_total,
        v2.2o_firstname,
        v2.2o_lastname,
        v2.2o_signature
    FROM
        2_violation AS v2
    WHERE
        v2.ticket_number = :ticket_number
");
$stmt->bindParam(':ticket_number', $ticket_number);
$stmt->execute();
$violation2 = $stmt->fetch(PDO::FETCH_ASSOC);

if ($violation2) {
    $status = $violation2['status'];
    $amount = $violation2['second_total'] + $violation2['others_total'];
    $officer_name = $violation2['2o_firstname'] . ' ' . $violation2['2o_lastname'];
    $officer_signature = $violation2['2o_signature']; // BLOB file
}

// Check in 3_violation table for penalty information
$stmt = $conn->prepare("
    SELECT
        v3.status,
        v3.third_total,
        v3.others_total,
        v3.3o_firstname,
        v3.3o_lastname,
        v3.3o_signature
    FROM
        3_violation AS v3
    WHERE
        v3.ticket_number = :ticket_number
");
$stmt->bindParam(':ticket_number', $ticket_number);
$stmt->execute();
$violation3 = $stmt->fetch(PDO::FETCH_ASSOC);

if ($violation3) {
    $status = $violation3['status'];
    $amount = $violation3['third_total'] + $violation3['others_total'];
    $officer_name = $violation3['3o_firstname'] . ' ' . $violation3['3o_lastname'];
    $officer_signature = $violation3['3o_signature']; // BLOB file
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"> </script>

    <link rel="stylesheet" href="/poso/admin/css/sm1.css">


</head>
<body>
<div id="overlay"></div>

    <nav class="navbar">
        <img src="/POSO/images/left.png" alt="Left Logo" class="logo">
        <div>
            <p class="public" >PUBLIC ORDER & SAFETY OFFICE</p>
            <p class="city">CITY OF BIÑAN, LAGUNA</p>
        </div>
        <img src="/POSO/images/arman.png" alt="POSO Logo" class="logo">
        <div class="hamburger" id="hamburger-icon">
            <i class="fa fa-bars"></i>
        </div>
    </nav>
    <img class="bg" src="/POSO/images/plaza1.jpg" alt="Background Image">
    <?php
        $current_page = basename($_SERVER['PHP_SELF']); // Get the current file name
    ?>
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="/POSO/images/right.png" alt="POSO Logo">
        </div>
        <ul>
            <li><a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="profile.php" class="<?= $current_page == 'profile.php' ? 'active' : '' ?>"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="report.php" class="<?= $current_page == 'report.php' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="settings.php" class="<?= $current_page == 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    </header>
    
    <img class="bg" src="/POSO/images/plaza1.jpg" alt="Background Image">
    <form class="sm mb-5" method="POST" action="update_report.php?ticket_number=<?= $_GET['ticket_number'] ?>">
        <div class="inside">
            <h2 class="gray" style="display: flex; justify-content: space-between; align-items: center;">
                ORDINANCE INFRACTION TICKET
                <span style="color: red;">NO. <?= htmlspecialchars($report['ticket_number']) ?></span>
            </h2>
            <div class="violator-info">
                <br>
                <h3 class="title">VIOLATOR INFORMATION</h3>
                <br>
                <div class="info-container">
                    <div><strong>First Name:</strong></div>
                    <div><input type="text" name="first_name" value="<?= htmlspecialchars($report['first_name']) ?>"></div>
                </div>
                <div class="info-container">
                    <div><strong>Middle Name:</strong></div>
                    <div><input type="text" name="middle_name" value="<?= htmlspecialchars($report['middle_name']) ?>"></div>
                </div>
                <div class="info-container">
                    <div><strong>Last Name:</strong></div>
                    <div><input type="text" name="last_name" value="<?= htmlspecialchars($report['last_name']) ?>"></div>
                </div>
                <div class="info-container">
                    <div><strong>Birthday:</strong></div>
<div><input type="date" name="dob" value="<?= htmlspecialchars($report['dob']) ?>"></div>
                </div>
                <div class="info-container">
                    <div><strong>Address:</strong></div>
                    <div><input type="text" name="address" value="<?= htmlspecialchars($report['address']) ?>"></div>
                </div>
                <div class="info-container">
                    <div><strong>License Number:</strong></div>
                    <div><input type="text" name="license" value="<?= htmlspecialchars($report['license']) ?>" readonly></div>
                </div>
                <div class="info-container">
                    <div><strong>Violation Date:</strong></div>
                    <div><input type="date" name="violation_date" value="<?= htmlspecialchars($report['violation_date']) ?>" readonly></div>
                </div>
                <div class="info-container">
                    <div><strong>Violation Time:</strong></div>
                    <div><input type="time" name="violation_time" value="<?= htmlspecialchars($report['violation_time']) ?>" readonly></div>
                </div>
                <div class="info-container">
                    <div><strong>License Confiscated:</strong></div>
                    <div>
                        <select name="confiscated">
                            <option value="1" <?= $report['confiscated'] ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= !$report['confiscated'] ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="place-info">
                <br>
                <h3 class="title">PLACE OF VIOLATION</h3>
                <br>
                <div class="info-container">
                    <div><strong>Street:</strong></div>
                    <div><input type="text" name="street" value="<?= htmlspecialchars($report['street']) ?>"readonly></div>
                </div>
                <div class="info-container">
                    <div><strong>City/Municipality:</strong></div>
                    <div><input type="text" name="city" value="<?= htmlspecialchars($report['city']) ?>"readonly></div>
                </div>
                <div class="info-container">
                    <div><strong>Vehicle Type:</strong></div>
                    <div>
                        <select id="vehicle_type" name="vehicle_type" class="form-control">
                            <option value="">Select Vehicle Type</option>
                            <option value="Passenger Car" <?= $report['vehicle_type'] == 'Passenger Car' ? 'selected' : '' ?>>Passenger Car</option>
                            <option value="Motorcycle or Scooter" <?= $report['vehicle_type'] == 'Motorcycle or Scooter' ? 'selected' : '' ?>>Motorcycle or Scooter</option>
                            <option value="Public Utility Vehicle" <?= $report['vehicle_type'] == 'Public Utility Vehicle' ? 'selected' : '' ?>>Public Utility Vehicle (PUV)</option>
                            <option value="Truck or Delivery Vehicle" <?= $report['vehicle_type'] == 'Truck or Delivery Vehicle' ? 'selected' : '' ?>>Truck or Delivery Vehicle</option>
                            <option value="Commercial Vehicle" <?= $report['vehicle_type'] == 'Commercial Vehicle' ? 'selected' : '' ?>>Commercial Vehicle</option>
                            <option value="Emergency Vehicle" <?= $report['vehicle_type'] == 'Emergency Vehicle' ? 'selected' : '' ?>>Emergency Vehicle</option>
                            <option value="Heavy Equipment" <?= $report['vehicle_type'] == 'Heavy Equipment' ? 'selected' : '' ?>>Heavy Equipment Vehicle</option>
                        </select>
                    </div>
                </div>
                <div class="info-container">
                    <div><strong>Plate Number:</strong></div>
                    <div><input type="text" name="plate_number" value="<?= htmlspecialchars($report['plate_number']) ?>"readonly></div>
                </div>
                <div class="info-container">
                    <div><strong>Registration Number:</strong></div>
                    <div><input type="text" name="registration" value="<?= htmlspecialchars($report['registration']) ?>"readonly></div>
                </div>
                <div class="info-container">
                    <div><strong>Vehicle Owner:</strong></div>
                    <div><input type="text" name="vehicle_owner" value="<?= htmlspecialchars($report['vehicle_owner']) ?>"></div>
                </div>
            </div>
            <div class="penalty-info">
                <br>
                <h3 class="title">PENALTY</h3>
                <br>
                <div class="info-container">
                    <div><strong>Status:</strong></div>
                    <div><input type="text" name="status" value="<?= htmlspecialchars($status) ?>"></div>
                </div>
                <div class="info-container">
                    <div><strong>Total Amount:</strong></div>
                    <div><input type="number" name="amount" value="<?= htmlspecialchars($amount) ?>" step="0.01"></div>
                </div>
                <div class="info-container">
                    <div><strong>Officer In Charge:</strong></div>
                    <div><input type="text" name="officer_name" value="<?= htmlspecialchars($officer_name) ?>"readonly></div>
                </div>
                <h3 class="title">VIOLATIONS</h3> <br>


<script>
function sortViolationsAlphabetically() {
  const violationsContainer = document.querySelector('.violations-container');
  const labels = Array.from(violationsContainer.querySelectorAll('label'));

  labels.sort((a, b) => {
    const textA = a.textContent.trim().toLowerCase();
    const textB = b.textContent.trim().toLowerCase();
    return textA.localeCompare(textB);
  });

  // Clear the existing container
  violationsContainer.innerHTML = '';

  // Append the sorted labels back to the container
  labels.forEach(label => {
    violationsContainer.appendChild(label);
    violationsContainer.appendChild(document.createElement('br')); // Add line breaks
  });
}

// Call the function when the page loads or after the violations are added to the DOM
window.addEventListener('load', sortViolationsAlphabetically);

// or call it after the form is generated.
// sortViolationsAlphabetically()

</script>

                    <div class="section">
                    <select class="violations" name="violations[]" multiple="multiple" style="width: 100%;">
        <option value="ARROGANT - 1000" data-price="1000">ARROGANT</option>
        <option value="DISREGARDING TRAFFIC OFFICER - 200" data-price="200">DISREGARDING TRAFFIC OFFICER</option>
        <option value="DISREGARDING TRAFFIC SIGNS - 200" data-price="200">DISREGARDING TRAFFIC SIGNS</option>
        <option value="DRIVING UNDER THE INFLUENCE OF LIQUOR - 200" data-price="200">DRIVING UNDER THE INFLUENCE OF LIQUOR</option>
        <option value="DRIVING UNREGISTERED VEHICLE - 500" data-price="500">DRIVING UNREGISTERED VEHICLE</option>
        <option value="DRIVING WITHOUT LICENSE/INVALID LICENSE - 1000" data-price="1000">DRIVING WITHOUT LICENSE/INVALID LICENSE</option>
        <option value="FAILURE TO WEAR HELMET - 200" data-price="200">FAILURE TO WEAR HELMET</option>
        <option value="ILLEGAL PARKING - 200" data-price="200">ILLEGAL PARKING</option>
        <option value="ILLEGAL VENDING - 200" data-price="200">ILLEGAL VENDING</option>
        <option value="IMPOUNDED - 800" data-price="800">IMPOUNDED</option>
        <option value="INVOLVE IN ACCIDENT - 200" data-price="200">INVOLVE IN ACCIDENT</option>
        <option value="JAY WALKING - 200" data-price="200">JAY WALKING</option>
        <option value="LOADING/UNLOADING IN PROHIBITED ZONE - 200" data-price="200">LOADING/UNLOADING IN PROHIBITED ZONE</option>
        <option value="NO OR/CR WHILE DRIVING - 500" data-price="500">NO OR/CR WHILE DRIVING</option>
        <option value="NO SIDE MIRROR - 200" data-price="200">NO SIDE MIRROR</option>
        <option value="OPEN MUFFLER/NUISANCE - 1000" data-price="1000">OPEN MUFFLER/NUISANCE</option>
        <option value="ONEWAY - 200" data-price="200">ONEWAY</option>
        <option value="OPERATING OUT OF LINE - 2000" data-price="2000">OPERATING OUT OF LINE</option>
        <option value="OVERLOADING - 200" data-price="200">OVERLOADING</option>
        <option value="RECKLESS DRIVING - 100" data-price="100">RECKLESS DRIVING</option>
        <option value="SMOKE BELCHING - 500" data-price="500">SMOKE BELCHING</option>
        <option value="STALLED VEHICLE - 200" data-price="200">STALLED VEHICLE</option>
        <option value="TRIP - CUTTING - 200" data-price="200">TRIP - CUTTING</option>
        <option value="TRUCK BAN - 200" data-price="200">TRUCK BAN</option>
        <option value="UNREGISTERED MOTOR VEHICLE - 500" data-price="500">UNREGISTERED MOTOR VEHICLE</option>
        <option value="INVALID OR NO FRANCHISE/COLORUM - 2000" data-price="2000">INVALID OR NO FRANCHISE/COLORUM</option>
        <option value="WEARING SLIPPERS/SHORTS/SANDO - 300" data-price="300">WEARING SLIPPERS/SHORTS/SANDO</option>
        <option value="OTHERS" id="others-checkbox" onclick="toggleOthersField()" data-price="0">OTHERS</option>
    </select>
    <div class="section" id="othersViolation" style="display:none;">
        <label for="others_violation">Describe OTHERS Violation:</label>
        <input type="text" id="others_violation" name="others_violation" placeholder="Specify others violation">
    </div>
             
                </div> <br>
                <h3 class="title">SIGNATURES</h3> <br>
                <div class="info-container">
                    <div><strong>Officer Signature:</strong></div>
                    <div>
                        <?php if ($officer_signature): ?>
                            <img src="data:image/png;base64,<?= base64_encode($officer_signature) ?>" alt="Officer Signature" class="signature-img">
                        <?php else: ?>
                            <?= htmlspecialchars($officer_signature) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="info-container">
                    <div><strong>Violator's Signature:</strong></div>  <br><br><br><br><br><br>
                    <div>
                        <?php if ($report['violator_signature']): ?>
                            <img src="data:image/png;base64,<?= base64_encode($report['violator_signature']) ?>" alt="Violator Signature" class="signature-img">
                        <?php else: ?>
                            <?= htmlspecialchars($report['violator_signature']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div style="text-align: center;"> 
            <button type="submit">Update</button> <br><br><br>
        </div>
    </form>
    </div>

    <div class="spacer"></div>


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



    //DROPDOWN of violations
                $(document).ready(function() {
            // Enable Select2 on the dropdown
            $('.violations').select2({
                placeholder: "Select Violations ▼",
                allowClear: true,
                closeOnSelect: false, // Keep dropdown open to select multiple options
            });
        })
    </script>
</body>
</html>

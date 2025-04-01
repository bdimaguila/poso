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
        r.street,
        r.city,
        r.vehicle_type,
        r.plate_number,
        r.signature AS violator_signature
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
$receipt_num = ''; // Initialize receipt_num

// Fetch violations from discount table
$stmt = $conn->prepare("
    SELECT * FROM discount WHERE ticket_number = :ticket_number
");
$stmt->bindParam(':ticket_number', $ticket_number);
$stmt->execute();
$discount = $stmt->fetch(PDO::FETCH_ASSOC);

$selectedViolations = [];
$othersViolationText = '';
$othersViolationAmount = '';
$discountSubtotal = 0;

//Get Status from discount table
if($discount){
    $status = $discount['STATUS'];
    $receipt_num = $discount['receipt_num']; // Get receipt number
}

if ($discount) {
    if ($discount['ARG'] != null) { $selectedViolations[] = 'ARROGANT'; $discountSubtotal += $discount['ARG']; }
    if ($discount['DTO'] != null) { $selectedViolations[] = 'DISREGARDING TRAFFIC OFFICER'; $discountSubtotal += $discount['DTO']; }
    if ($discount['DTS'] != null) { $selectedViolations[] = 'DISREGARDING TRAFFIC SIGNS'; $discountSubtotal += $discount['DTS']; }
    if ($discount['DUL'] != null) { $selectedViolations[] = 'DRIVING UNDER THE INFLUENCE OF LIQUOR'; $discountSubtotal += $discount['DUL']; }
    if ($discount['DUV'] != null) { $selectedViolations[] = 'DRIVING UNREGISTERED VEHICLE'; $discountSubtotal += $discount['DUV']; }
    if ($discount['DWL'] != null) { $selectedViolations[] = 'DRIVING WITHOUT LICENSE/INVALID LICENSE'; $discountSubtotal += $discount['DWL']; }
    if ($discount['FTWH'] != null) { $selectedViolations[] = 'FAILURE TO WEAR HELMET'; $discountSubtotal += $discount['FTWH']; }
    if ($discount['INF'] != null) { $selectedViolations[] = 'INVALID OR NO FRANCHISE/COLORUM'; $discountSubtotal += $discount['INF']; }
    if ($discount['ILP'] != null) { $selectedViolations[] = 'ILLEGAL PARKING'; $discountSubtotal += $discount['ILP']; }
    if ($discount['ILV'] != null) { $selectedViolations[] = 'ILLEGAL VENDING'; $discountSubtotal += $discount['ILV']; }
    if ($discount['IMP'] != null) { $selectedViolations[] = 'IMPOUNDED'; $discountSubtotal += $discount['IMP']; }
    if ($discount['IVA'] != null) { $selectedViolations[] = 'INVOLVE IN ACCIDENT'; $discountSubtotal += $discount['IVA']; }
    if ($discount['JWK'] != null) { $selectedViolations[] = 'JAY WALKING'; $discountSubtotal += $discount['JWK']; }
    if ($discount['LUZ'] != null) { $selectedViolations[] = 'LOADING/UNLOADING IN PROHIBITED ZONE'; $discountSubtotal += $discount['LUZ']; }
    if ($discount['NORCR'] != null) { $selectedViolations[] = 'NO OR/CR WHILE DRIVING'; $discountSubtotal += $discount['NORCR']; }
    if ($discount['NSM'] != null) { $selectedViolations[] = 'NO SIDE MIRROR'; $discountSubtotal += $discount['NSM']; }
    if ($discount['OBS'] != null) { $selectedViolations[] = 'OBSTRUCTION'; $discountSubtotal += $discount['OBS']; }
    if ($discount['OMN'] != null) { $selectedViolations[] = 'OPEN MUFFLER/NUISANCE'; $discountSubtotal += $discount['OMN']; }
    if ($discount['ONEWAY'] != null) { $selectedViolations[] = 'ONEWAY'; $discountSubtotal += $discount['ONEWAY']; }
    if ($discount['OOL'] != null) { $selectedViolations[] = 'OPERATING OUT OF LINE'; $discountSubtotal += $discount['OOL']; }
    if ($discount['OVL'] != null) { $selectedViolations[] = 'OVERLOADING'; $discountSubtotal += $discount['OVL']; }
    if ($discount['RCD'] != null) { $selectedViolations[] = 'RECKLESS DRIVING'; $discountSubtotal += $discount['RCD']; }
    if ($discount['SMB'] != null) { $selectedViolations[] = 'SMOKE BELCHING'; $discountSubtotal += $discount['SMB']; }
    if ($discount['STV'] != null) { $selectedViolations[] = 'STALLED VEHICLE'; $discountSubtotal += $discount['STV']; }
    if ($discount['TCT'] != null) { $selectedViolations[] = 'TRIP - CUTTING'; $discountSubtotal += $discount['TCT']; }
    if ($discount['TRB'] != null) { $selectedViolations[] = 'TRUCK BAN'; $discountSubtotal += $discount['TRB']; }
    if ($discount['UMV'] != null) { $selectedViolations[] = 'UNREGISTERED MOTOR VEHICLE'; $discountSubtotal += $discount['UMV']; }
    if ($discount['WSS'] != null) { $selectedViolations[] = 'WEARING SLIPPERS/SHORTS/SANDO'; $discountSubtotal += $discount['WSS']; }
    

    if ($discount['OTHERS'] != null) {
        $othersViolationText = $discount['OTHERS'];
    }
    if ($discount['OTHERS_P'] != null) {
        $othersViolationAmount = (float)$discount['OTHERS_P'];
    } else {
        $othersViolationAmount = 0;
    }
}

// Calculate the total amount
$amount = $discountSubtotal + $othersViolationAmount;

// Fetch officer details
$stmt = $conn->prepare("
    SELECT o_firstname, o_lastname, o_signature FROM violation WHERE ticket_number = :ticket_number
    UNION
    SELECT 2o_firstname, 2o_lastname, 2o_signature FROM 2_violation WHERE ticket_number = :ticket_number
    UNION
    SELECT 3o_firstname, 3o_lastname, 3o_signature FROM 3_violation WHERE ticket_number = :ticket_number
");
$stmt->bindParam(':ticket_number', $ticket_number);
$stmt->execute();
$officer = $stmt->fetch(PDO::FETCH_ASSOC);

if ($officer) {
    $officer_name = $officer['o_firstname'] . ' ' . $officer['o_lastname'];
    $officer_signature = $officer['o_signature'];
}

$isPaid = ($status === 'Released');

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
            <li><a href="dashboard.php" > <i class="fas fa-home"></i> Home</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            <li><a href="report.php" class="active"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    </header>
    <img class="bg" src="/POSO/images/plaza1.jpg" alt="Background Image">
    <form class="sm mb-5 pb-5" method="POST" action="update_report.php?ticket_number=<?= $_GET['ticket_number'] ?>">
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
                    <div><input type="text" name="first_name" value="<?= htmlspecialchars($report['first_name']) ?>" <?= $isPaid ? 'readonly' : '' ?>></div>
                </div>
                <div class="info-container">
                    <div><strong>Middle Name:</strong></div>
                    <div><input type="text" name="middle_name" value="<?= htmlspecialchars($report['middle_name']) ?>" <?= $isPaid ? 'readonly' : '' ?>></div>
                </div>
                <div class="info-container">
                    <div><strong>Last Name:</strong></div>
                    <div><input type="text" name="last_name" value="<?= htmlspecialchars($report['last_name']) ?>" <?= $isPaid ? 'readonly' : '' ?>></div>
                </div>
                <div class="info-container">
                    <div><strong>Birthday:</strong></div>
                    <div><input type="date" name="dob" value="<?= htmlspecialchars($report['dob']) ?>" <?= $isPaid ? 'readonly' : '' ?>></div>
                </div>
                <div class="info-container">
                    <div><strong>Address:</strong></div>
                    <div><input type="text" name="address" value="<?= htmlspecialchars($report['address']) ?>" <?= $isPaid ? 'readonly' : '' ?>></div>
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
                        <select name="confiscated" <?= $isPaid ? 'disabled' : '' ?>>
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
                        <select id="vehicle_type" name="vehicle_type" class="form-control" <?= $isPaid ? 'disabled' : '' ?>>
                            <option value="">Select Vehicle Type</option>
                            <option value="Passenger Car" <?= $report['vehicle_type'] == 'Passenger Car' ? 'selected' : '' ?>>Passenger Car</option>
                            <option value="Motorcycle or Scooter" <?= $report['vehicle_type'] == 'Motorcycle or Scooter' ? 'selected' : '' ?>>Motorcycle or Scooter</option>
                            <option value="Public Utility Vehicle" <?= $report['vehicle_type'] == 'Public Utility Vehicle' ? 'selected' : '' ?>>Public Utility Vehicle (PUV)</option>
                            <option value="Truck or Delivery Vehicle" <?= $report['vehicle_type'] == 'Truck or Delivery Vehicle' ? 'selected' : '' ?>>Truck or Delivery Vehicle</option>
                            <option value="Commercial Vehicle" <?= $report['vehicle_type'] == 'Commercial Vehicle' ? 'selected' : '' ?>>Commercial Vehicle</option><option value="Emergency Vehicle" <?= $report['vehicle_type'] == 'Emergency Vehicle' ? 'selected' : '' ?>>Emergency Vehicle</option>
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
                </div><div class="info-container">
                    <div><strong>Vehicle Owner:</strong></div>
                    <div><input type="text" name="vehicle_owner" value="<?= htmlspecialchars($report['vehicle_owner']) ?>" <?= $isPaid ? 'readonly' : '' ?>></div>
                </div>
            </div>

            <div class="penalty-info">
                <br>
                <h3 class="title">VIOLATIONS and PENALTY</h3> <br>

                <div class="section">
                    <select class="violations" name="violations[]" multiple="multiple" style="width: 100%;" <?= $isPaid ? 'disabled' : '' ?>>
                        <option value="ARROGANT" data-price="1000" <?= in_array('ARROGANT', $selectedViolations) ? 'selected' : '' ?>>ARROGANT</option>
                        <option value="DISREGARDING TRAFFIC OFFICER" data-price="200" <?= in_array('DISREGARDING TRAFFIC OFFICER', $selectedViolations) ? 'selected' : '' ?>>DISREGARDING TRAFFIC OFFICER</option>
                        <option value="DISREGARDING TRAFFIC SIGNS" data-price="200" <?= in_array('DISREGARDING TRAFFIC SIGNS', $selectedViolations) ? 'selected' : '' ?>>DISREGARDING TRAFFIC SIGNS</option>
<option value="DRIVING UNDER THE INFLUENCE OF LIQUOR" data-price="200" <?= in_array('DRIVING UNDER THE INFLUENCE OF LIQUOR', $selectedViolations) ? 'selected' : '' ?>>DRIVING UNDER THE INFLUENCE OF LIQUOR</option>
                        <option value="DRIVING UNREGISTERED VEHICLE" data-price="500" <?= in_array('DRIVING UNREGISTERED VEHICLE', $selectedViolations) ? 'selected' : '' ?>>DRIVING UNREGISTERED VEHICLE</option>
                        <option value="DRIVING WITHOUT LICENSE/INVALID LICENSE" data-price="1000" <?= in_array('DRIVING WITHOUT LICENSE/INVALID LICENSE', $selectedViolations) ? 'selected' : '' ?>>DRIVING WITHOUT LICENSE/INVALID LICENSE</option>
                        <option value="FAILURE TO WEAR HELMET" data-price="200" <?= in_array('FAILURE TO WEAR HELMET', $selectedViolations) ? 'selected' : '' ?>>FAILURE TO WEAR HELMET</option>
                        <option value="ILLEGAL PARKING" data-price="200" <?= in_array('ILLEGAL PARKING', $selectedViolations) ? 'selected' : '' ?>>ILLEGAL PARKING</option>
                        <option value="ILLEGAL VENDING" data-price="200" <?= in_array('ILLEGAL VENDING', $selectedViolations) ? 'selected' : '' ?>>ILLEGAL VENDING</option>
                        <option value="IMPOUNDED" data-price="800" <?= in_array('IMPOUNDED', $selectedViolations) ? 'selected' : '' ?>>IMPOUNDED</option>
                        <option value="INVOLVE IN ACCIDENT" data-price="200" <?= in_array('INVOLVE IN ACCIDENT', $selectedViolations) ? 'selected' : '' ?>>INVOLVE IN ACCIDENT</option>
                        <option value="JAY WALKING" data-price="200" <?= in_array('JAY WALKING', $selectedViolations) ? 'selected' : '' ?>>JAY WALKING</option>
                        <option value="LOADING/UNLOADING IN PROHIBITED ZONE" data-price="200" <?= in_array('LOADING/UNLOADING IN PROHIBITED ZONE', $selectedViolations) ? 'selected' : '' ?>>LOADING/UNLOADING IN PROHIBITED ZONE</option>
                        <option value="NO OR/CR WHILE DRIVING" data-price="500" <?= in_array('NO OR/CR WHILE DRIVING', $selectedViolations) ? 'selected' : '' ?>>NO OR/CR WHILE DRIVING</option>
                        <option value="NO SIDE MIRROR" data-price="200" <?= in_array('NO SIDE MIRROR', $selectedViolations) ? 'selected' : '' ?>>NO SIDE MIRROR</option>
                        <option value="OPEN MUFFLER/NUISANCE" data-price="1000" <?= in_array('OPEN MUFFLER/NUISANCE', $selectedViolations) ? 'selected' : '' ?>>OPEN MUFFLER/NUISANCE</option>
                        <option value="ONEWAY" data-price="200" <?= in_array('ONEWAY', $selectedViolations) ? 'selected' : '' ?>>ONEWAY</option>
                        <option value="OPERATING OUT OF LINE" data-price="2000" <?= in_array('OPERATING OUT OF LINE', $selectedViolations) ? 'selected' : '' ?>>OPERATING OUT OF LINE</option>
                        <option value="OVERLOADING" data-price="200" <?= in_array('OVERLOADING', $selectedViolations) ? 'selected' : '' ?>>OVERLOADING</option>
                        <option value="RECKLESS DRIVING" data-price="100" <?= in_array('RECKLESS DRIVING', $selectedViolations) ? 'selected' : '' ?>>RECKLESS DRIVING</option>
                        <option value="SMOKE BELCHING" data-price="500" <?= in_array('SMOKE BELCHING', $selectedViolations) ? 'selected' : '' ?>>SMOKE BELCHING</option>
                        <option value="STALLED VEHICLE" data-price="200" <?= in_array('STALLED VEHICLE', $selectedViolations) ? 'selected' : '' ?>>STALLED VEHICLE</option>
                        <option value="TRIP - CUTTING" data-price="200" <?= in_array('TRIP - CUTTING', $selectedViolations) ? 'selected' : '' ?>>TRIP - CUTTING</option>
                        <option value="TRUCK BAN" data-price="200" <?= in_array('TRUCK BAN', $selectedViolations) ? 'selected' : '' ?>>TRUCK BAN</option>
                        <option value="UNREGISTERED MOTOR VEHICLE" data-price="500" <?= in_array('UNREGISTERED MOTOR VEHICLE', $selectedViolations) ? 'selected' : '' ?>>UNREGISTERED MOTOR VEHICLE</option>
                        <option value="INVALID OR NO FRANCHISE/COLORUM" data-price="2000" <?= in_array('INVALID OR NO FRANCHISE/COLORUM', $selectedViolations) ? 'selected' : '' ?>>INVALID OR NO FRANCHISE/COLORUM</option>
                        <option value="WEARING SLIPPERS/SHORTS/SANDO" data-price="300" <?= in_array('WEARING SLIPPERS/SHORTS/SANDO', $selectedViolations) ? 'selected' : '' ?>>WEARING SLIPPERS/SHORTS/SANDO</option>
                        <option value="OBSTRUCTION" data-price="200" <?= in_array('OBSTRUCTION', $selectedViolations) ? 'selected' : '' ?>>OBSTRUCTION</option>
                    </select>
                    <br><br>

                    <div class="info-container ">
                        <div><strong>Subtotal:</strong></div>
                        <div><input type="number" name="subtotal" id="subtotal" value="<?= $discountSubtotal ?>" step="0.01" readonly></div>
                    </div>

                    <?php if (!empty($othersViolationText)): ?>
                        <div class="info-container"><div>
                            <strong>Others Violation:</strong>
                        </div>
                        <div>
                            <input type="text" name="others_violation_text" value="<?= htmlspecialchars($othersViolationText) ?>" <?= $isPaid ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="info-container">
                        <div>
                            <strong>Others Violation Amount:</strong>
                        </div>
                        <div>
                            <input type="number" name="others_violation_amount" id="others_violation_amount" value="<?= htmlspecialchars($othersViolationAmount) ?>" step="0.01" <?= $isPaid ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="info-container">
                        <div><strong>Status:</strong></div>
                        <div>
                            <select name="status" id="status-select" <?= $isPaid ? 'disabled' : '' ?>>
                                <option value="Impounded" <?= $status == 'Impounded'? 'selected' : '' ?>>Impounded</option>
                                <option value="Towed" <?= $status == 'Towed' ? 'selected' : '' ?>>Towed</option>
                                <option value="Unattended" <?= $status == 'Unattended' ? 'selected' : '' ?>>Unattended</option>
                                <option value="Released" <?= $status == 'Released' ? 'selected' : '' ?>>Released</option>
                                <option value="Unreleased" <?= $status == 'Unreleased' ? 'selected' : '' ?>>Unreleased</option>
                                <option value="License Confiscated" <?= $status == 'License Confiscated' ? 'selected' : '' ?>>License Confiscated</option>
                            </select>
                        </div>
                    </div>
                    <div class="info-container" id="receipt-num-container" style="display: <?= $status == 'Released' ? 'flex' : 'none' ?>;">
                        <div><strong>Receipt Number:</strong></div>
                        <div>
                            <input type="text" name="receipt_num" value="<?= htmlspecialchars($receipt_num) ?>" <?= $isPaid ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="info-container">
                        <div><strong>Total Amount:</strong></div>
                        <div><input type="number" name="amount" id="total_amount" value="<?= htmlspecialchars($amount) ?>" step="0.01" readonly></div>
                    </div>
                    <div class="info-container">
                        <div><strong>Officer In Charge:</strong></div>
                        <div><input type="text" name="officer_name" value="<?= htmlspecialchars($officer_name) ?>"readonly></div>
                    </div>

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
                        <div><strong>Violator's Signature:</strong></div><br><br><br><br><br><br>
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
                <button type="submit" class="update-btn" <?= $isPaid ? 'style="display:none;"' : '' ?>>Update</button>
                <a href="vb.php?ticket_number=<?= htmlspecialchars($report['ticket_number']) ?>" class="see" <?= $isPaid ? 'style="display:none;"' : '' ?>>See Breakdown of Violations</a>
            </div>
        </form>
    </div>

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

            // Calculate subtotal on violation selection change
            $('.violations').on('change', function() {
                updateTotalAmount();
            });

            $('#others_violation_amount').on('input', function() {
                updateTotalAmount();
            });

            function updateTotalAmount() {
                let subtotal = parseFloat($('#subtotal').val()) || 0;
                let othersAmount = parseFloat($('#others_violation_amount').val()) || 0;
                $('#total_amount').val((subtotal + othersAmount).toFixed(2));
            }
            updateTotalAmount();

            // Show/hide receipt number input
            $('#status-select').on('change', function() {
                if ($(this).val() === 'Released') {
                    $('#receipt-num-container').show();
                } else {
                    $('#receipt-num-container').hide();
                }
            });
        })
    </script>
</body>
</html>
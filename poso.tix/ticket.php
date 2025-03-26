<?php
// Start session
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "poso";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the last issued ticket number from the database
$sql = "SELECT ticket_number FROM report ORDER BY ID DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_ticket_number = (int)$row['ticket_number']; // Convert to integer
    $new_ticket_number = $last_ticket_number + 1; // Increment
} else {
    $new_ticket_number = 1; // Start from 000001 if no tickets exist
}

// Format ticket number with leading zeros
$ticket_number = str_pad($new_ticket_number, 6, '0', STR_PAD_LEFT);

// Store in session
$_SESSION['ticket_number'] = $ticket_number;

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordinance Infraction Ticket</title> 
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/poso/poso.tix/style1.css">
    <style>
        /* FAB styling */
        .fab {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background-color: #dc3545; /* Bootstrap Danger Color */
            color: #fff;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1050;
            cursor: pointer;
            text-decoration: none;
        }

        .fab:hover {
            background-color: #c82333; /* Darker shade for hover effect */
        }

        .fab i {
            font-size: 24px;
        }
.btn-secondary {
    background-color: #6c757d; /* Bootstrap grey */
    border-color: #6c757d;
    color: white;
    padding: 10px 20px;
    font-size: 16px;
    border-radius: 5px;
    text-transform: uppercase; /* Makes text all capital letters */
    text-decoration: none; /* Removes underline */
    display: block; /* Makes it behave like a block element */
    width: 100%; /* Adjust width as needed */
    text-align: center; /* Centers the text */
    margin: 10px auto; /* Centers the button */
    transition: background-color 0.3s ease;
}

.btn-secondary:hover {
    background-color: #5a6268; /* Darker shade of grey */
    border-color: #545b62;
    text-decoration: none; /* Ensure underline doesn't appear */
}

    </style>
</head>
<body>
<script>
    // Automatically set today's date, time, and default city
    document.addEventListener("DOMContentLoaded", function () {
        const today = new Date();
        const formattedDate = today.toISOString().slice(0, 10);
        const formattedTime = today.toTimeString().slice(0, 5);
        document.getElementById("date").value = formattedDate;
        document.getElementById("time").value = formattedTime;
        document.getElementById("city").value = "Biñan City";
    });
</script>
<div class="container">
    <div class="ticket-container">
        <div class="header-container d-flex justify-content-between align-items-center"> 
            <img src="/POSO/images/left.png" alt="Left Logo" class="logo">
            <div class="col text-center">
                <p class="title">POSO Traffic Violations</p>
                <p class="city">-City of Binan, Laguna-</p>
            </div>
            <img src="/POSO/images/arman.png" alt="Right Logo" class="logo">
        </div>
        <br>
        <div class="ticket-info">
            <p class="ticket-label">ORDINANCE INFRACTION TICKET</p>
            <p class="ticket-number">No. <?php echo $ticket_number; ?></p>
        </div>
        <form action="submit_ticket.php" method="POST">
            <input type="hidden" name="ticket_number" value="<?php echo $ticket_number; ?>">

          <!-- Violator's Information Section -->
<div class="gray">
    <p>Violator's Information:</p> 
</div>
<div class="section">
   <div class="section">
    <label for="license">License No.:</label>
    <div class="input-group">
        <input type="text" id="license" name="license" required class="form-control">
        <button type="button" class="btn btn-info" id="checkLicense">Check License</button>
    </div>
    <small id="licenseCheckResult" class="text-danger"></small>
</div>
    
    <label for="first_name">First Name:</label>
    <input type="text" id="first_name" name="first_name" required class="form-control">
    
    <label for="middle_name">Middle Name:</label>
    <input type="text" id="middle_name" name="middle_name" class="form-control">
    
    <label for="last_name">Last Name:</label>
    <input type="text" id="last_name" name="last_name" required class="form-control">
    
    <label for="dob">Date of Birth:</label>
    <input type="date" id="dob" name="dob" required class="form-control">
    
    <label for="address">Address:</label>
    <input type="text" id="address" name="address" required class="form-control">
</div>


            <!-- License Confiscation Section -->
            <div class="gray">
                <p>License Confiscated:</p> 
            </div>
            <div class="radio-container">
                <label for="confiscated_yes">Yes</label> 
                <input type="radio" id="confiscated_yes" name="confiscated" value="yes" class="me-2">
                <label for="confiscated_no">No</label>
                <input type="radio" id="confiscated_no" name="confiscated" value="no" class="me-2">
            </div>
            <br>

            <!-- Date & Time Section -->
            <div class="gray">
                <p>Date & Time:</p> 
            </div>
            <div class="section">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" class="form-control">
                <br>
                <label for="time">Time:</label>
                <input type="time" id="time" name="time" class="form-control">
            </div>

            <!-- Place of Violation Section -->
            <div class="gray">
                <p>Place of Violation</p> 
            </div>
            <div class="section">
                <label for="street">Street:</label>
                <input type="text" id="street" name="street" class="form-control">
                <label for="plate_number">Plate Number:</label>
                <input type="text" id="plate_number" name="plate_number" class="form-control">
                <label for="city">City/Municipality:</label>
                <input type="text" id="city" name="city" class="form-control">
                <label for="registration">Registration Number:</label>
                <input type="text" id="registration" name="registration" class="form-control">
                <label for="vehicle_type">Vehicle Type:</label>
                <select id="vehicle_type" name="vehicle_type" class="form-control">
                    <option value="">Select Vehicle Type</option>
                    <option value="Passenger Car">Passenger Car</option>
                    <option value="Motorcycle or Scooter">Motorcycle or Scooter</option>
                    <option value="Public Utility Vehicle">Public Utility Vehicle (PUV)</option>
                    <option value="Truck or Delivery Vehicle">Truck or Delivery Vehicle</option>
                    <option value="Commercial Vehicle">Commercial Vehicle</option>
                    <option value="Emergency Vehicle">Emergency Vehicle</option>
                    <option value="Heavy Equipment">Heavy Equipment Vehicle</option>
                </select>
                <label for="vehicle_owner">Vehicle Owner:</label>
                <input type="text" id="vehicle_owner" name="vehicle_owner" class="form-control">
            </div>

            <!-- Back and Next Buttons -->
            <div class="d-flex justify-content-between mt-3">
                
                <button type="submit" class="btn btn-primary">Next</button>
                <a href="menu.php" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/js/bootstrap.min.js"></script>

<script>
$(document).ready(function () {
    var originalLicense = ""; // Store the original license number

    $("#checkLicense").click(function () {
        var licenseNo = $("#license").val().trim();
        if (licenseNo === "") {
            $("#licenseCheckResult").text("Please enter a License No.");
            return;
        }
        
        $.ajax({
            url: "check_license.php",
            type: "POST",
            data: { license: licenseNo },
            success: function (response) {
                var result = JSON.parse(response);
                if (result.status === "exists") {
                    $("#licenseCheckResult").text("License number exists.").css("color", "green");
                    originalLicense = licenseNo; // Store the valid license number

                    // Auto-fill and disable fields
                    $("#first_name").val(result.data.first_name).prop("readonly", true);
                    $("#middle_name").val(result.data.middle_name).prop("readonly", true);
                    $("#last_name").val(result.data.last_name).prop("readonly", true);
                    $("#dob").val(result.data.dob).prop("readonly", true);
                    $("#address").val(result.data.address).prop("readonly", true);
                } else {
                    $("#licenseCheckResult").text("License number not found.").css("color", "red");
                }
            },
            error: function () {
                $("#licenseCheckResult").text("Error checking license.").css("color", "red");
            }
        });
    });

    // Detect changes in the license input field
    $("#license").on("input", function () {
        var currentLicense = $(this).val().trim();
        if (originalLicense !== "" && currentLicense !== originalLicense) {
            // Clear fields and make them editable again
            $("#first_name, #middle_name, #last_name, #dob, #address").val("").prop("readonly", false);
            $("#licenseCheckResult").text(""); // Clear the validation message
        }
    });
});
</script>
</body>
</html>

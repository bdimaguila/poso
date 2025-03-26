<?php
// Start the session
session_start();

// Generate random 8-digit ticket number
$ticket_number = rand(10000000, 99999999);

// Store the ticket number in the session
$_SESSION['ticket_number'] = $ticket_number;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordinance Infraction Ticket</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="ticket-container">
    <div class="header-container"> <!-- New div class for the header -->
        <img src="/POSO/images/left.png" alt="Left Logo" class="logo">
        <h1>City of Biñan</h1>
        <img src="/POSO/images/right.png" alt="Right Logo" class="logo">
    </div>

    <div class="ticket-info">
        <p class="ticket-label">Ordinance Infraction Ticket</p>
        <p class="ticket-number">No. <?php echo $ticket_number; ?></p>
    </div>

    <form action="submit_ticket.php" method="POST">
        <!-- Hidden input field to pass the ticket number -->
        <input type="hidden" name="ticket_number" value="<?php echo $ticket_number; ?>">

        <div class="section">
            <h3>Violator's Information:</h3>
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" required>

            <label for="middle_name">Middle Name:</label>
            <input type="text" id="middle_name" name="middle_name">

            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" required>

            <label for="dob">Date of Birth:</label>
            <input type="date" id="dob" name="dob" required>

            <label for="address">Address:</label>
            <input type="text" id="address" name="address" required>

            <label for="license">License No.:</label>
            <input type="text" id="license" name="license" required>
        </div>

        <div class="section">
            <h3>License Confiscated:</h3>
            <label for="confiscated_yes">Yes</label> 
            <input type="radio" id="confiscated_yes" name="confiscated" value="yes">
            <label for="confiscated_no">No</label>
            <input type="radio" id="confiscated_no" name="confiscated" value="no">
        </div>

        <div class="section">
            <h3>Date & Time:</h3>
            <label for="date">Date:</label>
            <input type="date" id="date" name="date">

            <label for="time">Time:</label>
            <input type="time" id="time" name="time">
        </div>

        <div class="section">
            <h3>Place of Violation</h3>
            <label for="street">Street:</label>
            <input type="text" id="street" name="street">

            <label for="plate_number">Plate Number:</label>
            <input type="text" id="plate_number" name="plate_number">

            <label for="city">City/Municipality:</label>
            <input type="text" id="city" name="city">

            <label for="registration">Registration Number:</label>
            <input type="text" id="registration" name="registration">

            <label for="vehicle_type">Vehicle Type:</label>
            <input type="text" id="vehicle_type" name="vehicle_type">

            <label for="vehicle_owner">Vehicle Owner:</label>
            <input type="text" id="vehicle_owner" name="vehicle_owner">
        </div>

        <button type="submit">Submit</button>
    </form>
</div>

</body>
</html>

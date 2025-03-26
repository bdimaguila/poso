<?php
// Start the session
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POSO Main Menu</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/poso/poso.tix/css/menu.css">

</head>
<body>
    <div class="container d-flex justify-content-center align-items-center">
        <div class="ticket-container d-flex flex-column justify-content-center align-items-center">
            <div class="header-container d-flex justify-content-between align-items-center"> 
                <img src="/POSO/images/left.png" alt="Left Logo" class="logo">
                <div class="col text-center">
                    <p class="title">POSO Traffic Violations</p>
                    <p class="city">-City of Binan, Laguna-</p>
                </div>
                <img src="/POSO/images/arman.png" alt="Right Logo" class="logo">
            </div>
            
            <!-- Button Container -->
            <div class="btn-container">
                <button class="btn btn-primary square-button" onclick="location.href='ticket.php'">CREATE INFRACTION TICKET</button>
                <button class="btn btn-primary square-button" onclick="location.href='history.php'">TICKET HISTORY</button>
                <button class="btn btn-secondary square-button" onclick="location.href='logout.php'">LOGOUT</button>

          
            </div>
        </div>
    </div>
</body>
</html>

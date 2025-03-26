<?php
// Start session
session_start();
// Include the database connection file
include 'connection.php'; // Ensure the path is correct

// Get the ticket number from the session
$ticket_number = $_GET['ticket_number']; // Get from URL


// Fetch the first name and last name from the report table using the ticket number
$sql = "SELECT first_name, last_name FROM report WHERE ticket_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $ticket_number);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

$first_name = $report['first_name'];
$last_name = $report['last_name'];

// Insert the violation into the violation table
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $violations = $_POST['violations'];
    $total_amount = $_POST['total'];
    $others_total = isset($_POST['others_total']) ? $_POST['others_total'] : 0;
    $total_amount += $others_total;

    $others_violation = isset($_POST['others_violation']) ? $_POST['others_violation'] : null;

    $sql_insert = "INSERT INTO violation (ticket_number, first_name, last_name, violations, total_amount, others_violation) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("isssss", $ticket_number, $first_name, $last_name, implode(", ", $violations), $total_amount, $others_violation);

    if ($stmt_insert->execute()) {
        echo "Violation recorded successfully!";
    } else {
        echo "Error: " . $stmt_insert->error;
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordinance Infraction Ticket</title>
    <!-- Bootstrap CSS -->
      <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"> </script>

    <link rel="stylesheet" href="style1.css">
    <script>
        function calculateTotal() {
            let checkboxes = document.querySelectorAll('input[name="violations[]"]:checked');
            let total = 0;
            let othersSelected = false;

            checkboxes.forEach((checkbox) => {
                if (checkbox.value.startsWith("OTHERS")) {
                    othersSelected = true;
                } else {
                    total += parseFloat(checkbox.getAttribute('data-price'));
                }
            });

            let totalField = document.getElementById('total');
            let othersTotalField = document.getElementById('othersTotal');

            if (othersSelected && checkboxes.length === 1) {
                totalField.removeAttribute("readonly");
                totalField.value = '';
                othersTotalField.style.display = "none";
            } else if (othersSelected && checkboxes.length > 1) {
                totalField.setAttribute("readonly", true);
                totalField.value = total.toFixed(2);
                othersTotalField.style.display = "block";
            } else {
                totalField.setAttribute("readonly", true);
                totalField.value = total.toFixed(2);
                othersTotalField.style.display = "none";
            }
        }

        function toggleOthersField() {
            let othersViolationField = document.getElementById('othersViolation');
            let othersCheckbox = document.getElementById('others-checkbox');
            othersViolationField.style.display = othersCheckbox.checked ? "block" : "none";
        }

        document.addEventListener('DOMContentLoaded', (event) => {
            let checkboxes = document.querySelectorAll('input[name="violations[]"]');
            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', calculateTotal);
            });
        });

        //DROPDOWN
                $(document).ready(function() {
            // Enable Select2 on the dropdown
            $('.violations').select2({
                placeholder: "Select Violations ▼",
                allowClear: true,
                closeOnSelect: false, // Keep dropdown open to select multiple options
            });

    // Function to calculate total
    function calculateTotal() {
        let total = 0;
        $('.violations option:selected').each(function() {
            total += parseFloat($(this).data('price')) || 0;
        });
        $('#total').val(total.toFixed(2));
        
        // Handle 'OTHERS' field visibility
        if ($('#others-checkbox').is(':selected')) {
            $('#othersViolation').show();
            $('#othersTotal').show();
            $('#others_total').val(total.toFixed(2));  // Set the same total for OTHERS section
        } else {
            $('#othersViolation').hide();
            $('#othersTotal').hide();
        }
    }

    // Recalculate total on change in the select
    $('.violations').on('change', function() {
        calculateTotal();
    });

    // Initial total calculation on page load
    calculateTotal();
});
    </script>
</head>
<body>
    <style>
    .select2-container--default .select2-selection--multiple{
    height: 40px;
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border: 2px solid #a0d4fa;
    background-color: #eef6fa;
    border-radius: 4px;
    font-size: 17px;
    display: flex;
    align-items: center;
}

        </style>

        <div class="ticket-container">
        <div class="header-container">
            <img src="/POSO/images/left.png" alt="Left Logo" class="logo">
            <div class="col text-center">
                <p class="title">POSO Traffic Violations</p>
                <p class="city">-City of Biñan, Laguna-</p>
            </div>
            <img src="/POSO/images/arman.png" alt="Right Logo" class="logo">
        </div>

        <div class="ticket-info">
            <p class="ticket-label">Ordinance Infraction Ticket</p>
            <p class="ticket-number">No. <?php echo $ticket_number; ?></p>
        </div>

        <!-- Violation Form -->
       <form action="process_violation.php?ticket_number=<?php echo urlencode($ticket_number); ?>&first_name=<?php echo urlencode($first_name); ?>&last_name=<?php echo urlencode($last_name); ?>" method="POST">

            <input type="hidden" name="ticket_number" value="<?php echo $ticket_number; ?>">

            <div class="gray1">
                <p>You are hereby cited for committing the traffic violations:</p>
            </div>
            
            <!-- <div class="section" remove>
                <input type="checkbox" name="violations[]" value="FAILURE TO WEAR HELMET - 200" data-price="200"> FAILURE TO WEAR HELMET <br>
                <input type="checkbox" name="violations[]" value="OPEN MUFFLER/NUISANCE - 1000" data-price="1000"> OPEN MUFFLER/NUISANCE <br>
                <input type="checkbox" name="violations[]" value="ARROGANT - 1000" data-price="1000"> ARROGANT<br>
                <input type="checkbox" name="violations[]" value="ONEWAY - 200" data-price="200"> ONEWAY<br>
                <input type="checkbox" name="violations[]" value="ILLEGAL PARKING - 200" data-price="200"> ILLEGAL PARKING<br>
                <input type="checkbox" name="violations[]" value="DRIVING WITHOUT LICENSE/INVALID LICENSE - 1000" data-price="1000"> DRIVING WITHOUT LICENSE/INVALID LICENSE<br>
                <input type="checkbox" name="violations[]" value="NO OR/CR WHILE DRIVING - 500" data-price="500"> NO OR/CR WHILE DRIVING<br>
                <input type="checkbox" name="violations[]" value="DRIVING UNREGISTERED VEHICLE - 500" data-price="DRIVING UNREGISTERED VEHICLE"> ONEWAY<br>
                <input type="checkbox" name="violations[]" value="UNREGISTERED MOTOR VEHICLE - 500" data-price="500"> UNREGISTERED MOTOR VEHICLE<br>
                <input type="checkbox" name="violations[]" value="OBSTRUCTION - 200" data-price="200"> OBSTRUCTION<br>
                <input type="checkbox" name="violations[]" value="DISREGARDING TRAFFIC SIGNS - 200" data-price="200"> DISREGARDING TRAFFIC SIGNS<br>
                <input type="checkbox" name="violations[]" value="DISREGARDING TRAFFIC OFFICER - 200" data-price="200"> DISREGARDING TRAFFIC OFFICER<br>
                <input type="checkbox" name="violations[]" value="TRUCK BAN - 200" data-price="200"> TRUCK BAN<br>
                <input type="checkbox" name="violations[]" value="STALLED VEHICLE - 200" data-price="200"> STALLED VEHICLE<br>
                <input type="checkbox" name="violations[]" value="RECKLESS DRIVING - 100" data-price="100"> RECKLESS DRIVING<br>
                <input type="checkbox" name="violations[]" value="DRIVING UNDER THE INFLUENCE OF LIQUOR - 200" data-price="200"> DRIVING UNDER THE INFLUENCE OF LIQUOR<br>
                <input type="checkbox" name="violations[]" value="INVALID OR NO FRANCHISE/COLORUM - 2000" data-price="2000"> INVALID OR NO FRANCHISE/COLORUM<br>
                <input type="checkbox" name="violations[]" value="OPERATING OUT OF LINE - 2000" data-price="2000"> OPERATING OUT OF LINE<br>
                <input type="checkbox" name="violations[]" value="TRIP - CUTTING - 200" data-price="200"> TRIP - CUTTING<br>
                <input type="checkbox" name="violations[]" value="OVERLOADING - 200" data-price="200"> OVERLOADING<br>
                <input type="checkbox" name="violations[]" value="LOADING/UNLOADING IN PROHIBITED ZONE - 200" data-price="200"> LOADING/UNLOADING IN PROHIBITED ZONE<br>
                <input type="checkbox" name="violations[]" value="INVOLVE IN ACCIDENT - 200" data-price="200"> INVOLVE IN ACCIDENT<br>
                <input type="checkbox" name="violations[]" value="SMOKE BELCHING - 500" data-price="500"> SMOKE BELCHING<br>
                <input type="checkbox" name="violations[]" value="NO SIDE MIRROR - 200" data-price="200"> NO SIDE MIRROR<br>
                <input type="checkbox" name="violations[]" value="JAY WALKING - 200" data-price="200"> JAY WALKING<br>
                <input type="checkbox" name="violations[]" value="WEARING SLIPPERS/SHORTS/SANDO - 300" data-price="300"> WEARING SLIPPERS/SHORTS/SANDO<br>
                <input type="checkbox" name="violations[]" value="ILLEGAL VENDING - 200" data-price="200"> ILLEGAL VENDING<br>
                <input type="checkbox" name="violations[]" value="IMPOUNDED - 800" data-price="800"> IMPOUNDED<br>
                <input type="checkbox" name="violations[]" value="OTHERS" id="others-checkbox" onclick="toggleOthersField()"> OTHERS<br>
                <div class="section" id="othersViolation" style="display:none;">
                    <label for="others_violation">Describe OTHERS Violation:</label>
                    <input type="text" id="others_violation" name="others_violation" placeholder="Specify others violation">
                </div>
            </div> -->


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
</div>

<div class="gray">
    <p>Total Amount:</p>
</div>

<div class="section">
    <label for="total">Total:</label>
    <input type="text" id="total" name="total" value="0.00" readonly>
</div>

<div class="section" id="othersTotal" style="display:none;">
    <label for="others_total">Total for OTHERS:</label>
    <input type="text" id="others_total" name="others_total" value="0.00">
</div>

            <div class="gray">
                <p>Notes:</p>
            </div>
            <div class="section">
                <label for="notes">Notes:</label>
                <input type="text" id="notes" name="notes" value="">
            </div>

            <div class="section">
                <button type="submit">See Breakdown of Violation</button>
            </div>
        </form>
    </div>
</body>
</html>

<?php 
 // Start session 
 session_start(); 

 // Include the database connection file 
 include 'connection.php'; 

 // Get the ticket number from the URL 
 $ticket_number = $_GET['ticket_number']; 

 // Fetch the ticket details from the report table using the ticket number 
 $sql = "SELECT ticket_number, license, first_name, last_name FROM report WHERE ticket_number = ?"; 
 $stmt = $conn->prepare($sql); 
 $stmt->bind_param("s", $ticket_number); 
 $stmt->execute(); 
 $result = $stmt->get_result(); 
 $report = $result->fetch_assoc(); 

 // Check if the report exists 
 if ($report) { 
  // Extract data from the fetched report 
  $ticket_number = $report['ticket_number']; 
  $license = $report['license']; 
  $first_name = $report['first_name']; 
  $last_name = $report['last_name']; 

  // Check for previous violations based on license 
  $sql_violation = "SELECT * FROM violation WHERE license = ?"; 
  $stmt_violation = $conn->prepare($sql_violation); 
  $stmt_violation->bind_param("s", $license); 
  $stmt_violation->execute(); 
  $violation_count = $stmt_violation->get_result()->num_rows; 

  $sql_violation2 = "SELECT * FROM 2_violation WHERE license = ?"; 
  $stmt_violation2 = $conn->prepare($sql_violation2); 
  $stmt_violation2->bind_param("s", $license); 
  $stmt_violation2->execute(); 
  $violation_count2 = $stmt_violation2->get_result()->num_rows; 

  $sql_violation3 = "SELECT * FROM 3_violation WHERE license = ?"; 
  $stmt_violation3 = $conn->prepare($sql_violation3); 
  $stmt_violation3->bind_param("s", $license); 
  $stmt_violation3->execute(); 
  $violation_count3 = $stmt_violation3->get_result()->num_rows; 

  // Determine the violation number
  $violation_number = "";
  if ($violation_count == 0) {
      $violation_number = "First Violation";
  } elseif ($violation_count == 1 && $violation_count2 == 0) {
      $violation_number = "Second Violation";
  } elseif ($violation_count == 1 && $violation_count2 == 1 && $violation_count3 == 0) {
      $violation_number = "Third Violation";
  } else {
      $violation_number = "Multiple Violations"; // Or handle as needed
  }
  
  // Prepare for new insertion based on the count of existing violations 
  if ($_SERVER["REQUEST_METHOD"] == "POST") { 
   $violations = $_POST['violations']; // Array of selected violations 
   $total_amount = $_POST['total']; 
   $others_total = isset($_POST['others_total']) ? $_POST['others_total'] : 0; 
   $others_violation = isset($_POST['others_violation']) ? $_POST['others_violation'] : null; 
   $notes = isset($_POST['notes']) ? $_POST['notes'] : null; 
   $violations_str = implode(", ", $violations); 

   // Check for which violation to insert 
   if ($violation_count == 0) { 
    // First violation 
    $sql_insert = "INSERT INTO violation (ticket_number, license, first_name, last_name, first_violation, first_total, others_violation, others_total, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"; 
    $stmt_insert = $conn->prepare($sql_insert); 
    $stmt_insert->bind_param("sssssssss", $ticket_number, $license, $first_name, $last_name, $violations_str, $total_amount, $others_violation, $others_total, $notes); 
   } elseif ($violation_count == 1 && $violation_count2 == 0) { 
    // Second violation 
    $sql_insert = "INSERT INTO 2_violation (ticket_number, license, first_name, last_name, second_violation, second_total, others_violation, others_total, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"; 
    $stmt_insert = $conn->prepare($sql_insert); 
    $stmt_insert->bind_param("sssssssss", $ticket_number, $license, $first_name, $last_name, $violations_str, $total_amount, $others_violation, $others_total, $notes); 
   } elseif ($violation_count == 1 && $violation_count2 == 1 && $violation_count3 == 0) { 
    // Third violation 
    $sql_insert = "INSERT INTO 3_violation (ticket_number, license, first_name, last_name, third_violation, third_total, others_violation, others_total, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"; 
    $stmt_insert = $conn->prepare($sql_insert); 
    $stmt_insert->bind_param("sssssssss", $ticket_number, $license, $first_name, $last_name, $violations_str, $total_amount, $others_violation, $others_total, $notes); 
   } else { 
    echo "Cannot add more violations."; 
    exit(); 
   } 

   // Execute the violation insertion 
   if ($stmt_insert->execute()) { 
    // Insert into discount table 
    $sql_discount = "INSERT INTO discount (ticket_number, license, first_name, last_name, OTHERS, OTHERS_P) VALUES (?, ?, ?, ?, ?, ?)"; 
    $stmt_discount = $conn->prepare($sql_discount); 
    $stmt_discount->bind_param("ssssss", $ticket_number, $license, $first_name, $last_name, $others_violation, $others_total); 
    $stmt_discount->execute(); 

    // Check each violation and update discount table 
    foreach ($violations as $violation) { 
     $violation_name = explode(' - ', $violation)[0]; // Extract violation name 
     $sql_update_discount = "UPDATE discount SET "; 
     $setClauseAdded = false; 

     switch ($violation_name) { 
      case 'FAILURE TO WEAR HELMET': 
       $sql_update_discount .= "FTWH = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'OPEN MUFFLER/NUISANCE': 
       $sql_update_discount .= "OMN = 1000"; 
       $setClauseAdded = true; 
       break; 
      case 'ARROGANT': 
       $sql_update_discount .= "ARG = 1000"; 
       $setClauseAdded = true; 
       break; 
      case 'ONEWAY': 
       $sql_update_discount .= "ONEWAY = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'ILLEGAL PARKING': 
       $sql_update_discount .= "ILP = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'DRIVING WITHOUT LICENSE/INVALID LICENSE': 
       $sql_update_discount .= "DWL = 1000"; 
       $setClauseAdded = true; 
       break; 
      case 'NO OR/CR WHILE DRIVING': 
       $sql_update_discount .= "NORCR = 500"; 
       $setClauseAdded = true; 
       break; 
      case 'DRIVING UNREGISTERED VEHICLE': 
       $sql_update_discount .= "DUV = 500"; 
       $setClauseAdded = true; 
       break; 
      case 'UNREGISTERED MOTOR VEHICLE': 
       $sql_update_discount .= "UMV = 500"; 
       $setClauseAdded = true; 
       break; 
      case 'OBSTRUCTION': 
       $sql_update_discount .= "OBS = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'DISREGARDING TRAFFIC SIGNS': 
       $sql_update_discount .= "DTS = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'DISREGARDING TRAFFIC OFFICER': 
       $sql_update_discount .= "DTO = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'TRUCK BAN': 
       $sql_update_discount .= "TRB = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'STALLED VEHICLE': 
       $sql_update_discount .= "STV = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'RECKLESS DRIVING': 
       $sql_update_discount .= "RCD = 100"; 
       $setClauseAdded = true; 
       break; 
      case 'DRIVING UNDER THE INFLUENCE OF LIQUOR': 
       $sql_update_discount .= "DUL = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'INVALID OR NO FRANCHISE/COLORUM': 
       $sql_update_discount .= "INF = 2000"; 
       $setClauseAdded = true; 
       break; 
      case 'OPERATING OUT OF LINE': 
       $sql_update_discount .= "OOL = 2000"; 
       $setClauseAdded = true; 
       break; 
      case 'TRIP - CUTTING': 
       $sql_update_discount .= "TCT = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'OVERLOADING': 
       $sql_update_discount .= "OVL = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'LOADING/UNLOADING IN PROHIBITED ZONE': 
       $sql_update_discount .= "LUZ = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'INVOLVE IN ACCIDENT': 
       $sql_update_discount .= "IVA = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'SMOKE BELCHING': 
       $sql_update_discount .= "SMB = 500"; 
       $setClauseAdded = true; 
       break; 
      case 'NO SIDE MIRROR': 
       $sql_update_discount .= "NSM = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'JAY WALKING': 
       $sql_update_discount .= "JWK = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'WEARING SLIPPERS/SHORTS/SANDO': 
       $sql_update_discount .= "WSS = 300"; 
       $setClauseAdded = true; 
       break; 
      case 'ILLEGAL VENDING': 
       $sql_update_discount .= "ILV = 200"; 
       $setClauseAdded = true; 
       break; 
      case 'IMPOUNDED': 
       $sql_update_discount .= "IMP = 800"; 
       $setClauseAdded = true; 
       break; 
      default: 
       // No SET clause needed if no match 
       break; 
     } 

     if ($setClauseAdded) { 
      $sql_update_discount .= " WHERE ticket_number = ?"; 
      $stmt_update_discount = $conn->prepare($sql_update_discount); 
      $stmt_update_discount->bind_param("s", $ticket_number); 
      $stmt_update_discount->execute(); 
     } 

    } 

    header("Location: receipt.php?ticket_number=" . urlencode($ticket_number) . "&first_name=" . urlencode($first_name) . "&last_name=" . urlencode($last_name) . "&total=" . urlencode($total_amount)); 
    exit(); 
   } else { 
    echo "Error: " . $stmt_insert->error; 
   } 
  }
 } else { 
  echo "No report found for this ticket number."; 
 } 

 // Close connection 
 $stmt->close(); 
 $conn->close(); 
 ?> 

 For Violation Number: <?php echo $violation_number; ?>
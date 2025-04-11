<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include database connection
include 'connection.php';

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, image FROM login WHERE ID = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$username = $row['username'] ?? "ADMIN 123";
$imageData = $row['image'] ?? null;

try {
    // Fetch activity log for the current user
    $logQuery = "SELECT activity, timestamp FROM profile_activity_log WHERE user_id = :user_id ORDER BY timestamp DESC LIMIT 5"; // Fetch last 5 activities for dashboard
    $logStmt = $conn->prepare($logQuery);
    $logStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $logStmt->execute();
    $activityLog = $logStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Log the error or handle it as needed
    error_log("Error fetching activity log for dashboard: " . $e->getMessage());
    $activityLog = []; // Initialize as empty array to avoid errors in display
}

// Function to fetch ticket counts per month
function getMonthlyTicketCounts($conn) {
    $monthlyCounts = array_fill(1, 12, 0); // Initialize counts for all months to 0
    $currentYear = date('Y');

    $stmt = $conn->prepare("
        SELECT
            MONTH(created_at) AS month,
            COUNT(*) AS ticket_count
        FROM report
        WHERE YEAR(created_at) = :year
        GROUP BY MONTH(created_at)
        ORDER BY MONTH(created_at)
    ");
    $stmt->bindParam(':year', $currentYear, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $result) {
        $monthlyCounts[(int)$result['month']] = (int)$result['ticket_count'];
    }

    return $monthlyCounts;
}

// Get the monthly ticket data
$monthlyTicketData = getMonthlyTicketCounts($conn);
$months = json_encode(array_keys($monthlyTicketData));
$ticketCounts = json_encode(array_values($monthlyTicketData));

// Month names for chart labels
$monthNames = json_encode([
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
]);

// Query other dashboard statistics (as in your original code)
$stmt1 = $conn->prepare("SELECT ticket_number, COUNT(*) as violation_count FROM violation GROUP BY ticket_number");
$stmt1->execute();
$stmt2 = $conn->prepare("SELECT ticket_number, COUNT(*) as violation_count FROM 2_violation GROUP BY ticket_number");
$stmt2->execute();
$stmt3 = $conn->prepare("SELECT ticket_number, COUNT(*) as violation_count FROM 3_violation GROUP BY ticket_number");
$stmt3->execute();
$stmt4 = $conn->prepare("SELECT ticket_number, COUNT(*) as violation_count FROM report GROUP BY ticket_number");
$stmt4->execute();
$stmt5 = $conn->prepare("SELECT COUNT(*) as total_report_violations FROM report");
$stmt5->execute();
$row5 = $stmt5->fetch(PDO::FETCH_ASSOC);
$totalReportViolations = $row5['total_report_violations'] ?? 0;
$firstViolation = 0;
$secondViolation = 0;
$thirdViolation = 0;
$totalViolations = 0;
while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
    $firstViolation++;
}
while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    $secondViolation++;
}
while ($row = $stmt3->fetch(PDO::FETCH_ASSOC)) {
    $thirdViolation++;
}
while ($row = $stmt4->fetch(PDO::FETCH_ASSOC)) {
    $totalViolations++;
}
$stmtNewTickets = $conn->prepare("SELECT COUNT(*) as new_ticket_count FROM report WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
$stmtNewTickets->execute();
$rowNewTickets = $stmtNewTickets->fetch(PDO::FETCH_ASSOC);
$newTicketsCount = $rowNewTickets['new_ticket_count'] ?? 0;
$stmtOverdueTickets = $conn->prepare("SELECT COUNT(*) as overdue_ticket_count FROM discount WHERE STATUS = 'Overdue'");
$stmtOverdueTickets->execute();
$rowOverdueTickets = $stmtOverdueTickets->fetch(PDO::FETCH_ASSOC);
$overdueTicketsCount = $rowOverdueTickets['overdue_ticket_count'] ?? 0;
$stmtTicketCount = $conn->prepare("SELECT COUNT(DISTINCT ticket_number) as ticket_count FROM discount");
$stmtTicketCount->execute();
$rowTicketCount = $stmtTicketCount->fetch(PDO::FETCH_ASSOC);
$ticketCount = $rowTicketCount['ticket_count'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link rel="stylesheet" href="/poso/admin/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .chart-container {
            background-color: white;
            padding: 30px; /* Increased padding */
            border-radius: 8px;
            margin-top: 30px;
            max-width: 900px; /* Increased maximum width */
            margin-left: auto;
            margin-right: auto;
        }
        .activity-log-dashboard-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            margin-left: 20px;
            margin-right: 20px;
        }

        .activity-log-dashboard-container h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.9em;
            color: #555;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .timestamp {
            color: #777;
            font-size: 0.8em;
            float: right;
        }
    </style>

</head>

<body>
    <div id="overlay"></div>

    <div class="main-content">
        <header class="navbar">
            <img src="/POSO/images/left.png" alt="City Logo" class="logo">
            <div>
                <p class="public">PUBLIC ORDER & SAFETY OFFICE</p>
                <p class="city">CITY OF BIÑAN, LAGUNA</p>
            </div>
            <img src="/POSO/images/arman.png" alt="POSO Logo" class="logo">

            <div class="hamburger" id="hamburger-icon">
                <i class="fa fa-bars"></i>
            </div>
        </header>

        <div class="sidebar" id="sidebar">
            <div class="logo">
                <img src="/POSO/images/right.png" alt="POSO Logo">
            </div>
            <ul>
                <li><a href="dashboard.php" class="active"> <i class="fas fa-home"></i> Home</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="report.php"> <i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>


    <br><br>
<div class="slider">
    <div class="slide-track">
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg1.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg8.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg3.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg4.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg5.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg6.jpg">
        </div>

        <div class="slide">
            <img class="carousel" src="/POSO/images/bg7.webp">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg2.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg9.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg10.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg11.jpg">
        </div>
        <div class="slide">
            <img class="carousel" src="/POSO/images/bg12.jpg">
        </div>
    </div>
</div>
<div class="data-analytics-container">
    <h1 class="data" style="text-align: center; color:white;">DATA ANALYTICS</h1> <br><br>
    <div class="analytics-container">
        <div class="container">
            <div class="c1">
                <a href="report.php?filter=New" class="DA">
                    <h2>New Tickets</h2> <br><br>
                    <div class="number-display">
                        <?php echo $newTicketsCount; ?>
                    </div>
                </a>
            </div>
        </div>
        <div class="container">
            <div class="c2">
                <a href="report.php?filter=Overdue">
                    <h2>Overdue Tickets</h2>  <br><br>
                    <div class="number-display">
                        <?php echo $overdueTicketsCount; ?>
                    </div>
                </a>
            </div>
        </div>
        <div class="container container-with-c3">
            <div class="c3">
                <h2>Ticket Count</h2>  <br><br>
                <div class="number-display">
                    <?php echo $ticketCount; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="chart-container">
        <h2 style="text-align: center; margin-bottom: 20px; color: #333;">Monthly Ticket Statistics (<?php echo date('Y'); ?>)</h2>
        <canvas id="monthlyTicketBarChart"></canvas>
    </div>

    

        <script>
            //hamburger and sidebar
            const hamburgerIcon = document.getElementById('hamburger-icon');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');

            hamburgerIcon.addEventListener('click', function(event) {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
                event.stopPropagation();
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });

            document.addEventListener('click', function(event) {
                if (!sidebar.contains(event.target) && !hamburgerIcon.contains(event.target)) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }
            });

            // Monthly Ticket Bar Chart
            const monthNames = <?php echo $monthNames; ?>;
            const ticketCounts = <?php echo $ticketCounts; ?>;
            const barChartCtx = document.getElementById('monthlyTicketBarChart').getContext('2d');
            const monthlyTicketBarChart = new Chart(barChartCtx, {
                type: 'bar',
                data: {
                    labels: monthNames,
                    datasets: [{
                        label: 'Number of Tickets',
                        data: ticketCounts,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Tickets'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false // Hide the legend
                        }
                    }
                }
            });
        </script>

</body>
</html>
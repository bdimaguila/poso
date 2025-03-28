<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Include the database connection file
include 'connection.php';

// Fetch user data from login table
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username, image FROM login WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

$username = "ADMIN 123";
$imageData = null;

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $row['username'];
    $imageData = $row['image'];
}

// Get filter value (default to all if not set)
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Search functionality
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination logic
$limit = 8; // Number of records per page
$page = isset($_GET['page']) ? $_GET['page'] : 1; // Current page
$start = ($page - 1) * $limit; // Calculate the starting row

// Prepare the base query with a WHERE clause for search term
$sql = "
    SELECT
        r.ticket_number,
        r.violation_date,
        r.first_name,
        r.last_name,
        d.STATUS as payment_status,
        CASE
            WHEN v.ticket_number IS NOT NULL THEN 'First Violation'
            WHEN v2.ticket_number IS NOT NULL THEN 'Second Violation'
            WHEN v3.ticket_number IS NOT NULL THEN 'Third Violation'
            ELSE 'Unknown'
        END AS violation_level,
        CONCAT(
            IFNULL(v.first_violation, ''),
            IFNULL(v.others_violation, ''),
            IFNULL(v2.second_violation, ''),
            IFNULL(v2.others_violation, ''),
            IFNULL(v3.third_violation, ''),
            IFNULL(v3.others_violation, '')
        ) AS violations,
        r.created_at
    FROM
        report AS r
    LEFT JOIN
        violation AS v ON r.ticket_number = v.ticket_number
    LEFT JOIN
        2_violation AS v2 ON r.ticket_number = v2.ticket_number
    LEFT JOIN
        3_violation AS v3 ON r.ticket_number = v3.ticket_number
    LEFT JOIN discount as d ON r.ticket_number = d.ticket_number
    WHERE
        (r.ticket_number LIKE :searchTerm
        OR r.first_name LIKE :searchTerm
        OR r.last_name LIKE :searchTerm)
";

// Add a filter condition if a specific violation level or status is selected
if ($filter) {
    if (in_array($filter, ['First Violation', 'Second Violation', 'Third Violation'])) {
        $sql .= " AND CASE
                    WHEN v.ticket_number IS NOT NULL THEN 'First Violation'
                    WHEN v2.ticket_number IS NOT NULL THEN 'Second Violation'
                    WHEN v3.ticket_number IS NOT NULL THEN 'Third Violation'
                END = :filter";
    } elseif ($filter === 'New') {
        $sql .= " AND r.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
    } else {
        // Filter by status (Paid, Unpaid, Pending, Overdue)
        $sql .= " AND d.STATUS = :filter";
    }
}

// Add order by clause for ticket number
$sql .= " ORDER BY r.ticket_number ASC";

// Add pagination limit
$sql .= " LIMIT :start, :limit";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':searchTerm', '%' . $searchTerm . '%'); // Wildcards for partial match
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

// Bind filter parameter if a filter is applied
if ($filter && $filter !== 'New') {
    $stmt->bindValue(':filter', $filter);
}

$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total number of records to calculate total pages
$totalStmt = $conn->prepare("
    SELECT COUNT(*)
    FROM report AS r
    LEFT JOIN
        violation AS v ON r.ticket_number = v.ticket_number
    LEFT JOIN
        2_violation AS v2 ON r.ticket_number = v2.ticket_number
    LEFT JOIN
        3_violation AS v3 ON r.ticket_number = v3.ticket_number
    LEFT JOIN discount as d ON r.ticket_number = d.ticket_number
    WHERE
        (r.ticket_number LIKE :searchTerm
        OR r.first_name LIKE :searchTerm
        OR r.last_name LIKE :searchTerm)
");

// Add the filter to the total count query
if ($filter && $filter !== 'New') {
    $totalStmt->bindValue(':filter', $filter);
}
$totalStmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
$totalStmt->execute();
$totalRecords = $totalStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Function to sort reports
function sortReports($reports, $sortBy, $sortOrder) {
    usort($reports, function ($a, $b) use ($sortBy, $sortOrder) {
        $comparison = strcmp($a[$sortBy], $b[$sortBy]);
        return ($sortOrder == 'asc') ? $comparison : -$comparison;
    });
    return $reports;
}

// Handle sorting
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'ticket_number';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'asc';

$reports = sortReports($reports, $sortBy, $sortOrder);

// Function to get violations from discount table
function getDiscountViolations($conn, $ticketNumber) {
    $stmt = $conn->prepare("SELECT * FROM discount WHERE ticket_number = :ticket_number");
    $stmt->bindParam(':ticket_number', $ticketNumber);
    $stmt->execute();
    $discount = $stmt->fetch(PDO::FETCH_ASSOC);

    $violations = [];
    if ($discount) {
        if ($discount['FTWH'] != null) $violations[] = 'FAILURE TO WEAR HELMET';
        if ($discount['OMN'] != null) $violations[] = 'OPEN MUFFLER/NUISANCE';
        if ($discount['ARG'] != null) $violations[] = 'ARROGANT';
        if ($discount['ONEWAY'] != null) $violations[] = 'ONEWAY';
        if ($discount['ILP'] != null) $violations[] = 'ILLEGAL PARKING';
        if ($discount['DWL'] != null) $violations[] = 'DRIVING WITHOUT LICENSE/INVALID LICENSE';
        if ($discount['NORCR'] != null) $violations[] = 'NO OR/CR WHILE DRIVING';
        if ($discount['DUV'] != null) $violations[] = 'DRIVING UNREGISTERED VEHICLE';
        if ($discount['UMV'] != null) $violations[] = 'UNREGISTERED MOTOR VEHICLE';
        if ($discount['OBS'] != null) $violations[] = 'OBSTRUCTION';
        if ($discount['DTS'] != null) $violations[] = 'DISREGARDING TRAFFIC SIGNS';
        if ($discount['DTO'] != null) $violations[] = 'DISREGARDING TRAFFIC OFFICER';
        if ($discount['TRB'] != null) $violations[] = 'TRUCK BAN';
        if ($discount['STV'] != null) $violations[] = 'STALLED VEHICLE';
        if ($discount['RCD'] != null) $violations[] = 'RECKLESS DRIVING';
        if ($discount['DUL'] != null) $violations[] = 'DRIVING UNDER THE INFLUENCE OF LIQUOR';
        if ($discount['INF'] != null) $violations[] = 'INVALID OR NO FRANCHISE/COLORUM';
        if ($discount['OOL'] != null) $violations[] = 'OPERATING OUT OF LINE';
        if ($discount['TCT'] != null) $violations[] = 'TRIP - CUTTING';
        if ($discount['OVL'] != null) $violations[] = 'OVERLOADING';
        if ($discount['LUZ'] != null) $violations[] = 'LOADING/UNLOADING IN PROHIBITED ZONE';
        if ($discount['IVA'] != null) $violations[] = 'INVOLVE IN ACCIDENT';
        if ($discount['SMB'] != null) $violations[] = 'SMOKE BELCHING';
        if ($discount['NSM'] != null) $violations[] = 'NO SIDE MIRROR';
        if ($discount['JWK'] != null) $violations[] = 'JAY WALKING';
        if ($discount['WSS'] != null) $violations[] = 'WEARING SLIPPERS/SHORTS/SANDO';
        if ($discount['ILV'] != null) $violations[] = 'ILLEGAL VENDING';
        if ($discount['IMP'] != null) $violations[] = 'IMPOUNDED';
        if ($discount['OTHERS'] != null) $violations[] = $discount['OTHERS']; // Include OTHERS violation
    }
    return implode(', ', $violations);
}

// Function to check if a ticket is new (created within the last 24 hours)
function isNewTicket($createdAt) {
    $createdAtTimestamp = strtotime($createdAt);
    $twentyFourHoursAgo = strtotime('-24 hours');
    return $createdAtTimestamp > $twentyFourHoursAgo;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/POSO/images/poso.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="/poso/admin/css/report.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .status-paid {
            color: green;
        }
        .status-unpaid {
            color: red;
        }
        .status-overdue {
            color: orange;
        }
        .status-pending {
            color: yellow;
        }
        .new-ticket {
            color: green;
            font-size: 0.8em;
            margin-left: 5px;
        }
    </style>
</head>

<body>
    <img class="bg" src="/POSO/images/reports1.jpg" alt="Background Image">

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
                <li><a href="dashboard.php" > <i class="fas fa-home"></i> Home</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="report.php" class="active"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <div class="search-filter">
            <form action="report.php" method="get">
                <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                <select name="filter">
                    <option value="">All</option>
                    <option value="First Violation" <?php echo ($filter == 'First Violation') ? 'selected' : ''; ?>>First Violation</option>
                    <option value="Second Violation" <?php echo ($filter == 'Second Violation') ? 'selected' : ''; ?>>Second Violation</option>
                    <option value="Third Violation" <?php echo ($filter == 'Third Violation') ? 'selected' : ''; ?>>Third Violation</option>
                    <option value="Paid" <?php echo ($filter == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                    <option value="Unpaid" <?php echo ($filter == 'Unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                    <option value="Pending" <?php echo ($filter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="Overdue" <?php echo ($filter == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                    <option value="New" style="display:none;">New</option>
                </select>
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>
                        <a class="link" href="?sort=ticket_number&order=<?php echo ($sortBy == 'ticket_number' && $sortOrder == 'asc') ? 'desc' : 'asc'; ?>&search=<?php echo urlencode($searchTerm); ?>&filter=<?php echo urlencode($filter); ?>">
                            Ticket No. <i class="fa <?php echo ($sortBy == 'ticket_number' ? ($sortOrder == 'asc' ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short') : 'fa-arrows-up-down'); ?>"></i>
                        </a>
                    </th>
                    <th>Name</th>
                    <th>
                        <a class="link" href="?sort=violation_level&order=<?php echo ($sortBy == 'violation_level' && $sortOrder == 'asc') ? 'desc' : 'asc'; ?>&search=<?php echo urlencode($searchTerm); ?>&filter=<?php echo urlencode($filter); ?>">
                            Violation Level <i class="fa <?php echo ($sortBy == 'violation_level' ? ($sortOrder == 'asc' ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short') : 'fa-arrows-up-down'); ?>"></i>
                        </a>
                    </th>
                    <th>Violation/s</th>
                    <th>
                        <a class="link" href="?sort=violation_date&order=<?php echo ($sortBy == 'violation_date' && $sortOrder == 'asc') ? 'desc' : 'asc'; ?>&search=<?php echo urlencode($searchTerm); ?>&filter=<?php echo urlencode($filter); ?>">
                            Violation Date <i class="fa <?php echo ($sortBy == 'violation_date' ? ($sortOrder == 'asc' ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short') : 'fa-arrows-up-down'); ?>"></i>
                        </a>
                    </th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report) : ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($report['ticket_number']); ?>
                            <?php if (isNewTicket($report['created_at'])): ?>
                                <span class="new-ticket">NEW</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($report['first_name']) . ' ' . htmlspecialchars($report['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($report['violation_level']); ?></td>
                        <td><?php echo htmlspecialchars(getDiscountViolations($conn, $report['ticket_number'])); ?></td>
                        <td><?php echo htmlspecialchars($report['violation_date']); ?></td>
                        <td class="<?php
                            switch (htmlspecialchars($report['payment_status'])) {
                                case 'Paid':
                                    echo 'status-paid';
                                    break;
                                case 'Unpaid':
                                    echo 'status-unpaid';
                                    break;
                                case 'Overdue':
                                    echo 'status-overdue';
                                    break;
                                case 'Pending':
                                    echo 'status-pending';
                                    break;
                                default:
                                    break;
                            }
                        ?>">
                            <?php echo htmlspecialchars($report['payment_status']); ?>
                        </td>
                        <td>
                            <a href="sm.php?ticket_number=<?php echo htmlspecialchars($report['ticket_number']); ?>" class="pagination-btn">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php
                // Show previous button only if not on the first page
                if ($page > 1):
            ?>
                <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($searchTerm); ?>&filter=<?php echo urlencode($filter); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" class="pagination-btn previous">Prev</a>
            <?php endif; ?>

            <?php
                // Display numbered pagination links
                for ($i = 1; $i <= $totalPages; $i++):
                    $activeClass = ($i == $page) ? 'active' : ''; // Highlight the current page
            ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>&filter=<?php echo urlencode($filter); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" class="pagination-btn <?php echo $activeClass; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php
                // Show next button only if not on the last page
                if ($page < $totalPages):
            ?>
                <a href="?page=<?php echo min($totalPages, $page + 1); ?>&search=<?php echo urlencode($searchTerm); ?>&filter=<?php echo urlencode($filter); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>" class="pagination-btn next">Next</a>
            <?php endif; ?>
        </div>
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
    </script>
</body>
</html>
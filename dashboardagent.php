<?php
require_once __DIR__ . '/module-auth/google_sheets_integration.php';
require_once __DIR__ . '/module-auth/dbconnection.php';

session_start();
if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

// Fetch agent name and ID from the database
$user = $_SESSION['auser'];
$stmt = $conn->prepare("SELECT AgentID, AgentName FROM agent WHERE AgentEmail = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$stmt->bind_result($agentID, $agentName);
$stmt->fetch();
$stmt->close();

// Get the current month and year
$currentMonth = date('F'); // Example: "October"
$currentYear = date('Y');

// Check if a specific month is selected via GET parameter
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : $currentMonth;

// Fetch outstanding payment data for tenants under the current agent for the selected month
$outstandingPayments = [];
$totalOutstanding = 0;
$query = "
    SELECT 
        Tenant.TenantName, 
        Unit.UnitNo, 
        Property.PropertyName,
        Payment.Amount, 
        Payment.Month, 
        Payment.Year 
    FROM Payment 
    JOIN Tenant ON Payment.TenantID = Tenant.TenantID
    JOIN Room ON Payment.RoomID = Room.RoomID 
    JOIN Unit ON Room.UnitID = Unit.UnitID 
    JOIN Property ON Unit.PropertyID = Property.PropertyID
    WHERE Payment.Claimed = 0 
    AND Payment.AgentID = ?
    AND Payment.Month = ?
    AND Payment.Year = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $agentID, $selectedMonth, $currentYear); // Bind the agent ID, selected month, and current year to the query
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $outstandingPayments[] = $row;
    $totalOutstanding += $row['Amount']; // Calculate total outstanding amount
}
$stmt->close();

// Fetch monthly statistics for total outstanding payments
$monthlyStats = [
    'January' => 0, 'February' => 0, 'March' => 0, 'April' => 0,
    'May' => 0, 'June' => 0, 'July' => 0, 'August' => 0,
    'September' => 0, 'October' => 0, 'November' => 0, 'December' => 0
];

$query = "
    SELECT 
        Month, 
        SUM(Amount) as TotalAmount
    FROM Payment 
    WHERE AgentID = ? AND Claimed = 0
    GROUP BY Month
    ORDER BY FIELD(Month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')
";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $agentID);
$stmt->execute();
$result = $stmt->get_result();

// Update monthly stats with actual values from the query result
while ($row = $result->fetch_assoc()) {
    $monthlyStats[$row['Month']] = $row['TotalAmount'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Rentronics</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Feathericon CSS -->
    <link rel="stylesheet" href="assets/css/feathericon.min.css">

    <!-- Chart.js for statistics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/dashboardagent.css" rel="stylesheet">
    <link href="css/navbar.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
    body {
        background-color: #e3ecf5;
        font-family: 'Heebo', sans-serif;
    }

    .page-header {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .outstanding {
        background-color: #f5f5f5;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    .payment-box {
        background-color: #ffffff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
    }

    .payment-header {
        display: flex;
        justify-content: space-between;
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .payment-row {
        display: flex;
        justify-content: space-between;
        font-size: 18px;
    }

    .month-value {
        color: #000;
    }

    .total-value {
        color: red;
        font-weight: bold;
    }

    hr {
        border: none;
        border-top: 1px solid #ddd;
        margin: 10px 0;
    }

    .tenant-info {
        font-size: 14px;
        margin-top: 10px;
    }

    .tenant-info div {
        margin-bottom: 5px;
    }

    .outstanding-amount {
        font-size: 16px;
        font-weight: bold;
        margin-top: 10px;
    }

    .property-name, .unit-no {
        font-weight: bold;
        margin-top: 5px;
    }

    /* Styles for the collapse button */
    .collapse-btn {
        position: absolute;
        right: 10px;
        top: 10px;
        font-size: 24px;
        cursor: pointer;
        transform: rotate(0deg);
        transition: transform 0.3s ease;
    }

    .collapse-btn.collapsed {
        transform: rotate(180deg);
    }

    /* Hide the tenant details by default */
    .tenant-details {
        display: none;
        margin-top: 10px;
    }

    .tenant-details.expanded {
        display: block;
    }

    .stats {
        background-color: #f5f5f5;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    /* Chart Container */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
        margin-top: 20px;
    }
    </style>
</head>

<body>
    <div class="container-fluid p-0">
        <!-- Navbar and Sidebar Start -->
        <?php include('nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <!-- Page Wrapper -->
        <div class="page-wrapper">

            <div class="content container-fluid">

                <!-- Page Header -->
                <div class="page-header">
                    <div class="row">
                        <div class="col-sm-9">
                            <h3 class="page-title">Welcome, <?php echo $agentName; ?></h3>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <!-- Outstanding Payments -->
                <div class="outstanding">
                    <h3 class="page-title">Outstanding Payment</h3>
                    <form method="GET" action="">
                        <div class="payment-header">
                            <div class="month">Month</div>
                            <div class="total">Total</div>
                        </div>
                        <div class="payment-row">
                            <div class="month-value">
                                <select name="month" id="month" onchange="this.form.submit()">
                                    <option value="January" <?php if($selectedMonth == 'January') echo 'selected'; ?>>January</option>
                                    <option value="February" <?php if($selectedMonth == 'February') echo 'selected'; ?>>February</option>
                                    <option value="March" <?php if($selectedMonth == 'March') echo 'selected'; ?>>March</option>
                                    <option value="April" <?php if($selectedMonth == 'April') echo 'selected'; ?>>April</option>
                                    <option value="May" <?php if($selectedMonth == 'May') echo 'selected'; ?>>May</option>
                                    <option value="June" <?php if($selectedMonth == 'June') echo 'selected'; ?>>June</option>
                                    <option value="July" <?php if($selectedMonth == 'July') echo 'selected'; ?>>July</option>
                                    <option value="August" <?php if($selectedMonth == 'August') echo 'selected'; ?>>August</option>
                                    <option value="September" <?php if($selectedMonth == 'September') echo 'selected'; ?>>September</option>
                                    <option value="October" <?php if($selectedMonth == 'October') echo 'selected'; ?>>October</option>
                                    <option value="November" <?php if($selectedMonth == 'November') echo 'selected'; ?>>November</option>
                                    <option value="December" <?php if($selectedMonth == 'December') echo 'selected'; ?>>December</option>
                                </select>
                            </div>
                            <div class="total-value">RM <?php echo number_format($totalOutstanding, 2); ?></div>
                        </div>
                    </form>
                    <i class="bi bi-chevron-down collapse-btn" onclick="toggleDetails(this)"></i>

                    <div class="tenant-details">
                        <?php if (!empty($outstandingPayments)): ?>
                            <?php foreach ($outstandingPayments as $payment): ?>
                                <hr>
                                <div class="tenant-info">
                                    <div>Tenant Info</div>
                                    <div class="tenant-name"><?php echo $payment['TenantName']; ?></div>
                                    <div class="property-name"><?php echo $payment['PropertyName']; ?></div>
                                    <div class="unit-no"><?php echo $payment['UnitNo']; ?></div>
                                </div>
                                <div class="outstanding-amount">
                                    Outstanding Payment: RM <?php echo number_format($payment['Amount'], 2); ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No outstanding payments for <?php echo $selectedMonth; ?>.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Stats Section -->
                <div class="stats">
                    <h3 class="page-title">Closing Statistics</h3>
                    <div class="chart-container">
                        <canvas id="closingStatsChart"></canvas>
                    </div>
                </div>

            </div>
        </div>
        <!-- /Page Wrapper -->
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js" crossorigin="anonymous">
    </script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

    <!-- Chart.js Script to Render the Chart -->
    <script>
    // Chart.js Script to Render the Chart
    const ctx = document.getElementById('closingStatsChart').getContext('2d');
    const closingStatsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ],
            datasets: [{
                label: 'Total Outstanding Payments',
                data: [
                    <?php 
                    foreach ($monthlyStats as $amount) {
                        echo $amount . ',';
                    }
                    ?>
                ],
                borderColor: 'rgba(75, 192, 192, 1)',
                fill: false,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,  // Ensure the chart adapts to the container size
            scales: {
                x: {
                    beginAtZero: false
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: 100
                }
            }
        }
    });

    // Toggle Details Script
    function toggleDetails(element) {
        const details = document.querySelector('.tenant-details');
        details.classList.toggle('expanded');
        element.classList.toggle('collapsed');
    }
    </script>
    <script src="js/main.js"></script>

</body>

</html>

<?php
require_once __DIR__ . '/../../../module-auth/dbconnection.php';

session_start();

// Debug session
error_log("Session contents: " . print_r($_SESSION, true));

// More permissive session check
if (!isset($_SESSION['auser']) || empty($_SESSION['auser'])) {
    error_log("No session found - redirecting to login");
    header("Location: index.php");
    exit();
}

// Debug the session variable structure
error_log("Session auser contents: " . print_r($_SESSION['auser'], true));

// Simplified user extraction
$user = null;
if (is_array($_SESSION['auser'])) {
    // Try multiple common array keys
    $possibleKeys = ['email', 'AgentEmail', 'user_email', 0];
    foreach ($possibleKeys as $key) {
        if (isset($_SESSION['auser'][$key])) {
            $user = $_SESSION['auser'][$key];
            break;
        }
    }
} else {
    $user = $_SESSION['auser'];
}

// Debug the extracted user
error_log("Extracted user: " . print_r($user, true));

if (!$user) {
    error_log("No valid user found in session - redirecting to login");
    header("Location: index.php");
    exit();
}

// Fetch agent name and ID from the database
$stmt = $conn->prepare("SELECT AgentID, AgentName FROM agent WHERE AgentEmail = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Add null checks and set default values
if ($row) {
    $agentID = $row['AgentID'];
    $agentName = $row['AgentName'];
} else {
    error_log("No agent found for email: " . $user);
    // Redirect to login if no agent found
    header("Location: index.php");
    exit();
}
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
        t.TenantName, 
        u.UnitNo, 
        prop.PropertyName,
        pay.Amount, 
        pay.Month, 
        pay.Year 
    FROM payment pay
    JOIN tenant t ON pay.TenantID = t.TenantID
    JOIN bed b ON pay.BedID = b.BedID 
    JOIN unit u ON b.UnitID = u.UnitID 
    JOIN property prop ON u.PropertyID = prop.PropertyID
    WHERE pay.Claimed = 0 
    AND pay.AgentID = ?
    AND pay.Month = ?
    AND pay.Year = ?
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
    'January' => 0,
    'February' => 0,
    'March' => 0,
    'April' => 0,
    'May' => 0,
    'June' => 0,
    'July' => 0,
    'August' => 0,
    'September' => 0,
    'October' => 0,
    'November' => 0,
    'December' => 0
];

$query = "
    SELECT 
        Month, 
        Year,
        SUM(Amount) as TotalAmount
    FROM payment 
    WHERE AgentID = ? 
    AND Claimed = 0 
    AND Year = ?
    AND PaymentStatus = 'Successful'
    GROUP BY Month, Year
    ORDER BY FIELD(Month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $agentID, $currentYear);
$stmt->execute();
$result = $stmt->get_result();

// Reset monthly stats for the selected year
$monthlyStats = array_fill_keys([
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December'
], 0);

// Update monthly stats with actual values from the query result
while ($row = $result->fetch_assoc()) {
    $monthlyStats[$row['Month']] = $row['TotalAmount'];
}
$stmt->close();

// Get Bed Vacancy - Count all available beds
$vacancyQuery = "
    SELECT COUNT(*) as vacancy_count 
    FROM bed b
    WHERE b.BedStatus = 'Available'
";
$stmt = $conn->prepare($vacancyQuery);
$stmt->execute();
$vacancyResult = $stmt->get_result();
$bedVacancy = $vacancyResult->fetch_assoc()['vacancy_count'];
$stmt->close();

// Add debug logging for bed vacancy
error_log("Bed Vacancy Count: " . $bedVacancy);

// Get Total Earning for the current agent
$earningQuery = "
    SELECT COALESCE(SUM(Amount), 0) as total_earning 
    FROM payment 
    WHERE AgentID = ? 
    AND PaymentStatus = 'Successful'
    AND Month = ? 
    AND Year = ?
";
$stmt = $conn->prepare($earningQuery);
$currentMonth = date('M'); // Gets current month in 'Jan' format
$currentYear = date('Y');
$stmt->bind_param("sss", $agentID, $currentMonth, $currentYear);
$stmt->execute();
$earningResult = $stmt->get_result();
$totalEarning = $earningResult->fetch_assoc()['total_earning'];
$stmt->close();

// Add debug logging
error_log("Agent ID: " . $agentID);
error_log("Current Month: " . $currentMonth);
error_log("Current Year: " . $currentYear);
error_log("Total Earning for " . $currentMonth . " " . $currentYear . ": " . $totalEarning);   

// Get Current Tenants - Updated query
$tenantQuery = "
    SELECT COUNT(DISTINCT t.TenantID) as tenant_count 
    FROM tenant t 
    JOIN bed b ON t.BedID = b.BedID 
    WHERE b.AgentID = ? 
    AND b.BedStatus = 'Rented'
";
$stmt = $conn->prepare($tenantQuery);
$stmt->bind_param("s", $agentID);
$stmt->execute();
$tenantResult = $stmt->get_result();
$currentTenants = $tenantResult->fetch_assoc()['tenant_count'];
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
    <link href="/img/favicon.ico" rel="icon">

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
    <link href="../../../css/dashboardagent.css" rel="stylesheet">
    <link href="../../../css/navbar.css" rel="stylesheet">
    <link href="css/agent.css" rel="stylesheet">
    <!-- Customized Bootstrap Stylesheet -->
    <link href="../../../css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <!-- Include the loading overlay at the start of body -->

    <div class="container-fluid p-0">
        <!-- Navbar and Sidebar Start -->
        <?php include('../../../nav_sidebar.php'); ?>
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

                <!-- Add this HTML after the page header and before the Outstanding Payments section -->
                <div class="row">
                    <!-- Bed Vacancy Card -->
                    <div class="col-md-4">
                        <a href="../propertylist.php" style="text-decoration: none;">
                            <div class="stat-card" style="cursor: pointer;">
                                <h4>Bed Vacancy</h4>
                                <div class="stat-number purple"><?php echo $bedVacancy; ?></div>
                            </div>
                        </a>
                    </div>
                    <!-- Total Earning Card -->
                    <div class="col-md-4">
                        <div class="stat-card">
                            <h4>Total Earning (<?php echo date('F Y'); ?>)</h4>
                            <div class="stat-number">RM <?php echo number_format($totalEarning, 2); ?></div>
                        </div>
                    </div>
                    <!-- Current Tenants Card -->
                    <div class="col-md-4">
                        <div class="stat-card">
                            <h4>Current Tenants</h4>
                            <div class="stat-number purple"><?php echo $currentTenants; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Outstanding Payments -->
                <div class="outstanding">
                    <h3 class="page-title">Outstanding Payment</h3>
                    <div class="payment-header">
                        <div class="month">Month</div>
                        <div class="total">Total</div>
                    </div>
                    <div class="payment-row">
                        <div class="month-value">
                            <select name="month" id="month" onchange="updateOutstandingPayments()" style="margin-right: 10px;">
                                <option value="January" <?php if ($selectedMonth == 'January') echo 'selected'; ?>>January</option>
                                <option value="February" <?php if ($selectedMonth == 'February') echo 'selected'; ?>>February</option>
                                <option value="March" <?php if ($selectedMonth == 'March') echo 'selected'; ?>>March</option>
                                <option value="April" <?php if ($selectedMonth == 'April') echo 'selected'; ?>>April</option>
                                <option value="May" <?php if ($selectedMonth == 'May') echo 'selected'; ?>>May</option>
                                <option value="June" <?php if ($selectedMonth == 'June') echo 'selected'; ?>>June</option>
                                <option value="July" <?php if ($selectedMonth == 'July') echo 'selected'; ?>>July</option>
                                <option value="August" <?php if ($selectedMonth == 'August') echo 'selected'; ?>>August</option>
                                <option value="September" <?php if ($selectedMonth == 'September') echo 'selected'; ?>>September</option>
                                <option value="October" <?php if ($selectedMonth == 'October') echo 'selected'; ?>>October</option>
                                <option value="November" <?php if ($selectedMonth == 'November') echo 'selected'; ?>>November</option>
                                <option value="December" <?php if ($selectedMonth == 'December') echo 'selected'; ?>>December</option>
                            </select>
                            <select name="year" id="year" onchange="updateOutstandingPayments()">
                                <?php
                                $startYear = 2024;
                                $endYear = date('Y') + 1;
                                for ($year = $startYear; $year <= $endYear; $year++) {
                                    $selected = ($year == $currentYear) ? 'selected' : '';
                                    echo "<option value='$year' $selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="total-value">RM <?php echo number_format($totalOutstanding, 2); ?></div>
                    </div>
                    
                    <!-- Add this container for tenant details -->
                    <div class="tenant-details">
                        <!-- Details will be populated here by JavaScript -->
                    </div>
                </div>

                <!-- Stats Section -->
                <div class="stats">
                    <h3 class="page-title">Closing Statistics for <?php echo $currentYear; ?></h3>
                    <div class="stats-controls">
                        <select id="statsYear" onchange="updateStats(this.value)">
                            <?php
                            $startYear = 2024; // You can adjust this starting year
                            $endYear = date('Y') + 1; // Current year plus one
                            for ($year = $startYear; $year <= $endYear; $year++) {
                                $selected = ($year == $currentYear) ? 'selected' : '';
                                echo "<option value='$year' $selected>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
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
        // Loading overlay functions
        function showLoading() {
            // Create loading overlay if it doesn't exist
            if (!document.getElementById('loadingOverlay')) {
                const overlay = document.createElement('div');
                overlay.id = 'loadingOverlay';
                overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(255, 255, 255, 0.8);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                `;
                
                const spinner = document.createElement('div');
                spinner.style.cssText = `
                    width: 50px;
                    height: 50px;
                    border: 5px solid #f3f3f3;
                    border-top: 5px solid #3498db;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                `;
                
                // Add the spinner animation
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `;
                document.head.appendChild(style);
                
                overlay.appendChild(spinner);
                document.body.appendChild(overlay);
            }
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.style.display = 'none';
            }
        }

        // Chart.js Script to Render the Chart
        let closingStatsChart;

        function initializeChart(data) {
            const ctx = document.getElementById('closingStatsChart').getContext('2d');

            if (closingStatsChart) {
                closingStatsChart.destroy();
            }

            closingStatsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [
                        'January', 'February', 'March', 'April', 'May', 'June',
                        'July', 'August', 'September', 'October', 'November', 'December'
                    ],
                    datasets: [{
                        label: 'Total Outstanding Payments',
                        data: data,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'RM ' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'RM ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Initialize the chart with current data
        initializeChart([<?php echo implode(',', $monthlyStats); ?>]);

        // Function to update stats when year changes
        function updateStats(year) {
            showLoading();
            fetch(`get_stats.php?year=${year}&agent_id=<?php echo $agentID; ?>`)
                .then(response => response.json())
                .then(data => {
                    initializeChart(Object.values(data));
                    document.querySelector('.stats h3').textContent = `Closing Statistics for ${year}`;
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error:', error);
                    hideLoading();
                });
        }

        // Toggle Details Script
        function toggleDetails(element) {
            const details = document.querySelector('.tenant-details');
            details.classList.toggle('expanded');
            element.classList.toggle('collapsed');
        }

        function updateOutstandingPayments() {
            const month = document.getElementById('month').value;
            const year = document.getElementById('year').value;
            showLoading();
            
            fetch(`get_outstanding.php?month=${month}&year=${year}&agent_id=<?php echo $agentID; ?>`)
                .then(response => response.json())
                .then(data => {
                    // Update the total amount
                    document.querySelector('.total-value').textContent = `RM ${parseFloat(data.total).toFixed(2)}`;
                    
                    // Update the tenant details section
                    const detailsContainer = document.querySelector('.tenant-details');
                    if (data.payments && data.payments.length > 0) {
                        let html = '';
                        data.payments.forEach(payment => {
                            html += `
                                <hr>
                                <div class="tenant-info">
                                    <div>Tenant Info</div>
                                    <div class="tenant-name">${payment.TenantName}</div>
                                    <div class="property-name">${payment.PropertyName}</div>
                                    <div class="unit-no">${payment.UnitNo}</div>
                                </div>
                                <div class="outstanding-amount">
                                    Outstanding Payment: RM ${parseFloat(payment.Amount).toFixed(2)}
                                </div>
                            `;
                        });
                        detailsContainer.innerHTML = html;
                    } else {
                        detailsContainer.innerHTML = `<p>No outstanding payments for ${month}.</p>`;
                    }
                    hideLoading();
                })
                .catch(error => {
                    console.error('Error:', error);
                    hideLoading();
                });
        }
    </script>
    <script src="../../../js/main.js"></script>

</body>

</html>
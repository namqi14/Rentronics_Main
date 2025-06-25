<?php
session_start();
require_once '../../vendor/autoload.php';
require_once __DIR__ . '/../../module-auth/dbconnection.php';

// Function to generate unique payment ID
function generateUniquePaymentID($conn) {
    do {
        $timestamp = date('ymdHis'); // Format: YYMMDDHHMMSS
        $random = mt_rand(1000, 9999);
        $paymentID = 'RENT' . $timestamp . $random;
        
        // Check if this ID already exists
        $stmt = $conn->prepare("SELECT PaymentID FROM payment WHERE PaymentID = ?");
        $stmt->bind_param("s", $paymentID);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
    } while ($exists);
    
    return $paymentID;
}

// Check if agent is logged in
if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Generate next 6 months for dropdown
$months = [];
$currentDate = new DateTime();
for ($i = 0; $i < 6; $i++) {
    $date = clone $currentDate;
    $date->modify("+$i month");
    $months[$date->format('Y-m')] = $date->format('F Y');
}

$user = $_SESSION['auser']['AgentEmail'];
// Debug line - you can remove this after confirming the value
error_log("Agent Email from Session: " . $user);

$stmt = $conn->prepare("SELECT AgentID FROM agent WHERE AgentEmail = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$agentRow = $result->fetch_assoc();
$agentID = $agentRow['AgentID'];

// Get all tenants under the agent excluding those who have already paid
$tenantQuery = "SELECT DISTINCT t.TenantID, t.TenantName, t.TenantPhoneNo, t.TenantEmail, 
                       t.RentStartDate, t.RentExpiryDate, t.RentalType,
                       t.UnitID, t.RoomID, t.BedID,
                       p.PropertyName, u.UnitNo,
                       CASE 
                           WHEN t.BedID IS NOT NULL THEN b.BedRentAmount
                           WHEN t.RoomID IS NOT NULL THEN r.BaseRentAmount
                           ELSE 0
                       END as MonthlyRental,
                       r.RoomID as RoomNo, b.BedNo,
                       CONCAT(u.UnitNo, 
                             COALESCE(CONCAT('-R', r.RoomID), ''),
                             COALESCE(CONCAT('-B', b.BedNo), '')
                       ) as SortKey,
                       GROUP_CONCAT(CONCAT(pay.Year, '-', LPAD(CASE 
                           WHEN pay.Month = 'Jan' THEN '01'
                           WHEN pay.Month = 'Feb' THEN '02'
                           WHEN pay.Month = 'Mar' THEN '03'
                           WHEN pay.Month = 'Apr' THEN '04'
                           WHEN pay.Month = 'May' THEN '05'
                           WHEN pay.Month = 'Jun' THEN '06'
                           WHEN pay.Month = 'Jul' THEN '07'
                           WHEN pay.Month = 'Aug' THEN '08'
                           WHEN pay.Month = 'Sep' THEN '09'
                           WHEN pay.Month = 'Oct' THEN '10'
                           WHEN pay.Month = 'Nov' THEN '11'
                           WHEN pay.Month = 'Dec' THEN '12'
                       END, 2, '0'))) as paid_months
                FROM tenant t 
                INNER JOIN unit u ON t.UnitID = u.UnitID
                INNER JOIN property p ON u.PropertyID = p.PropertyID 
                LEFT JOIN room r ON t.RoomID = r.RoomID
                LEFT JOIN bed b ON t.BedID = b.BedID
                LEFT JOIN payment pay ON t.TenantID = pay.TenantID 
                    AND pay.PaymentStatus = 'Successful'
                    AND (pay.PaymentType = 'Rent Payment' OR pay.PaymentType = 'Lumpsum Rent')
                WHERE (t.AgentID = ? OR ? IN ('A001', 'A002'))
                AND t.TenantStatus IN ('Active', 'Booked', 'Rented')
                GROUP BY t.TenantID, t.TenantName, t.TenantPhoneNo, t.TenantEmail,
                         t.RentStartDate, t.RentExpiryDate, t.RentalType,
                         t.UnitID, t.RoomID, t.BedID, p.PropertyName, u.UnitNo,
                         MonthlyRental, r.RoomID, b.BedNo, SortKey
                ORDER BY p.PropertyName, SortKey";

$stmt = $conn->prepare($tenantQuery);
$stmt->bind_param("ss", $agentID, $agentID);
$stmt->execute();
$result = $stmt->get_result();
$tenants = $result->fetch_all(MYSQLI_ASSOC);

// Get unique units for dropdown
$unitQuery = "SELECT DISTINCT u.UnitID, u.UnitNo, p.PropertyName
              FROM unit u 
              INNER JOIN property p ON u.PropertyID = p.PropertyID
              INNER JOIN tenant t ON u.UnitID = t.UnitID
              WHERE (t.AgentID = ? OR ? IN ('A001', 'A002'))
              AND t.TenantStatus IN ('Active', 'Booked', 'Rented')
              ORDER BY p.PropertyName, u.UnitNo";

$stmt = $conn->prepare($unitQuery);
$stmt->bind_param("ss", $agentID, $agentID);
$stmt->execute();
$unitResult = $stmt->get_result();
$units = $unitResult->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('POST request received in lumpsumpayment.php');
    error_log('POST data: ' . print_r($_POST, true));
    
    if (isset($_POST['submit_payment'])) {
        error_log('Submit payment button clicked');
        $selectedTenants = isset($_POST['selected_tenants']) ? $_POST['selected_tenants'] : [];
        $startMonth = isset($_POST['payment_month']) ? $_POST['payment_month'] : '';

        error_log('Selected tenants: ' . print_r($selectedTenants, true));
        error_log('Start month: ' . $startMonth);

        if (empty($selectedTenants)) {
            $error = "Please select at least one tenant.";
            error_log('Error: No tenants selected');
        } elseif (empty($startMonth)) {
            $error = "Please select a payment month.";
            error_log('Error: No payment month selected');
        } else {
            $totalAmount = 0;
            $paymentDetails = [];
            $uniquePaymentID = generateUniquePaymentID($conn);

            // Calculate payment period
            $startDate = new DateTime($startMonth . '-01');
            $endDate = clone $startDate;
            $endDate->modify('last day of this month');

            // Calculate total amount and prepare payment details
            foreach ($selectedTenants as $tenantID) {
                foreach ($tenants as $tenant) {
                    if ($tenant['TenantID'] == $tenantID) {
                        $amount = $tenant['MonthlyRental'];
                        $totalAmount += $amount;
                        $paymentDetails[] = [
                            'tenant_id' => $tenantID,
                            'amount' => $amount,
                            'months' => 1
                        ];
                    }
                }
            }

            error_log('Payment details prepared:');
            error_log('Total amount: ' . $totalAmount);
            error_log('Payment details: ' . print_r($paymentDetails, true));

            // Store payment details in session for processing
            $_SESSION['lumpsum_payment'] = [
                'payment_id' => $uniquePaymentID,
                'amount' => $totalAmount,
                'details' => $paymentDetails,
                'payment_type' => 'Lumpsum Rent',
                'description' => 'Rent Payment for ' . count($selectedTenants) . ' tenant(s) for ' . 
                                $startDate->format('F Y'),
                'months' => 1,
                'start_month' => $startMonth
            ];

            error_log('Session data set:');
            error_log('Lumpsum payment data: ' . print_r($_SESSION['lumpsum_payment'], true));

            // Redirect to Billplz payment processing
            error_log('Redirecting to billplzpost.php');
            header("Location: ../../module-payment/billplz/billplzpost.php");
            exit();
        }
    } else {
        error_log('Submit payment button not found in POST data');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Rentronics - Lumpsum Payment</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="/rentronics/img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Feathericon CSS -->
    <link rel="stylesheet" href="assets/css/feathericon.min.css">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../../css/dashboardagent.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <!-- Customized Bootstrap Stylesheet -->
    <link href="../../css/bootstrap.min.css" rel="stylesheet">

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

        .payment-section {
            margin-bottom: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #333;
        }

        select.form-control {
            height: calc(1.5em + 0.75rem + 2px);
            padding: 0.375rem 0.75rem;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 5px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 12px;
            padding-right: 2rem;
        }

        select.form-control:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        select.form-control option {
            padding: 8px;
        }

        .payment-inputs {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .payment-inputs .form-group {
            flex: 1;
            min-width: 200px;
        }

        .table {
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: transform 0.2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .form-control {
            border-radius: 5px;
            border: 1px solid #ced4da;
            padding: 8px 12px;
        }

        .tenant-checkbox {
            width: 18px;
            height: 18px;
        }
    </style>
</head>

<body>
    <div class="container-fluid p-0">
        <!-- Navbar and Sidebar Start -->
        <?php include('../../nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="content container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row">
                        <div class="col-sm-12">
                            <h3 class="page-title">Lumpsum Rent Payment</h3>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <!-- Payment Section -->
                <div class="payment-section">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="payment-inputs">
                            <div class="form-group">
                                <label for="payment_month" class="form-label">Payment Month:</label>
                                <select class="form-control" id="payment_month" name="payment_month" required>
                                    <option value="">Select Month</option>
                                    <?php foreach ($months as $value => $label): ?>
                                        <option value="<?php echo htmlspecialchars($value); ?>">
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="unit_filter" class="form-label">Filter by Unit:</label>
                                <select class="form-control" id="unit_filter" name="unit_filter">
                                    <option value="">All Units</option>
                                    <?php foreach ($units as $unit): ?>
                                        <option value="<?php echo htmlspecialchars($unit['UnitID']); ?>">
                                            <?php echo htmlspecialchars($unit['PropertyName'] . ' - Unit ' . $unit['UnitNo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Add CSRF token -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select-all" class="tenant-checkbox"></th>
                                        <th>Tenant Name</th>
                                        <th>Contact</th>
                                        <th>Property</th>
                                        <th>Location Details</th>
                                        <th>Monthly Rental</th>
                                        <th>Rental Period</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tenants as $tenant): 
                                        $paidMonths = isset($tenant['paid_months']) ? $tenant['paid_months'] : '';
                                    ?>
                                        <tr data-unit-id="<?php echo htmlspecialchars($tenant['UnitID']); ?>">
                                            <td>
                                                <input type="checkbox" name="selected_tenants[]" 
                                                    value="<?php echo $tenant['TenantID']; ?>" 
                                                    class="tenant-checkbox"
                                                    data-paid-months="<?php echo htmlspecialchars($paidMonths); ?>">
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($tenant['TenantName']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($tenant['TenantID']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($tenant['TenantPhoneNo']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($tenant['TenantEmail']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($tenant['PropertyName']); ?></td>
                                            <td>
                                                Unit <?php echo htmlspecialchars($tenant['UnitNo']); ?>
                                                <?php if ($tenant['RoomNo']): ?>
                                                    <br><small>Room <?php echo htmlspecialchars($tenant['RoomNo']); ?></small>
                                                <?php endif; ?>
                                                <?php if ($tenant['BedNo']): ?>
                                                    <br><small>Bed <?php echo htmlspecialchars($tenant['BedNo']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>RM <?php echo number_format($tenant['MonthlyRental'], 2); ?></td>
                                            <td>
                                                <?php 
                                                echo date('d/m/Y', strtotime($tenant['RentStartDate'])) . ' - ' . 
                                                    date('d/m/Y', strtotime($tenant['RentExpiryDate']));
                                                ?>
                                            </td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($tenant['RentalType']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" name="submit_payment" class="btn btn-primary">
                                <i class="bi bi-credit-card me-2"></i>Proceed to Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /Page Wrapper -->
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js" crossorigin="anonymous"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

    <!-- Template Javascript -->
    <script src="../../js/main.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMonthSelect = document.getElementById('payment_month');
        const unitFilterSelect = document.getElementById('unit_filter');
        const tenantRows = document.querySelectorAll('tbody tr');
        
        function updateTenantVisibility() {
            const selectedMonth = paymentMonthSelect.value;
            const selectedUnit = unitFilterSelect.value;
            
            tenantRows.forEach(row => {
                const checkbox = row.querySelector('.tenant-checkbox');
                const paidMonths = checkbox.dataset.paidMonths ? checkbox.dataset.paidMonths.split(',') : [];
                const unitId = row.dataset.unitId;
                
                let shouldShow = true;
                
                // Check month payment
                if (selectedMonth && paidMonths.includes(selectedMonth)) {
                    shouldShow = false;
                }
                
                // Check unit filter
                if (shouldShow && selectedUnit && unitId !== selectedUnit) {
                    shouldShow = false;
                }
                
                row.style.display = shouldShow ? '' : 'none';
                if (!shouldShow) {
                    checkbox.checked = false;
                }
            });
            
            // Update "select all" checkbox state
            updateSelectAllState();
        }
        
        function updateSelectAllState() {
            const selectAllCheckbox = document.getElementById('select-all');
            const visibleCheckboxes = Array.from(document.querySelectorAll('tbody tr:not([style*="display: none"]) .tenant-checkbox'));
            const allChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(cb => cb.checked);
            selectAllCheckbox.checked = allChecked;
        }
        
        // Add event listeners
        paymentMonthSelect.addEventListener('change', updateTenantVisibility);
        unitFilterSelect.addEventListener('change', updateTenantVisibility);
        
        // Modify the existing select-all functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const visibleCheckboxes = document.querySelectorAll('tbody tr:not([style*="display: none"]) .tenant-checkbox');
            visibleCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Update visibility on page load
        updateTenantVisibility();
    });
    </script>
</body>
</html>
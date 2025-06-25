<?php
session_start();
require_once '../../module-auth/dbconnection.php';

$error = "";
$tenantData = null;

// Add more detailed error logging
error_log('Starting flashpay.php processing');
error_log('POST data: ' . print_r($_POST, true));
error_log('GET data: ' . print_r($_GET, true));
error_log('SESSION data: ' . print_r($_SESSION, true));

// Check for both POST and GET parameters
if (isset($_POST['id']) || isset($_GET['id'])) {
    $id = $_POST['id'] ?? $_GET['id'];  
    error_log('Processing tenant ID: ' . $id);
    
    // Add database connection check
    if (!$conn) {
        error_log('Database connection failed: ' . mysqli_connect_error());
        $error = "Database connection failed";
    } else {
        error_log('Database connection successful');
        
        // Add input validation function
        function validateInput($data) {
            $errors = [];
            if (empty($data['id'])) {
                $errors[] = "Tenant ID is required";
            }
            if (!is_numeric($data['id'])) {
                $errors[] = "Invalid Tenant ID format";
            }
            return $errors;
        }

        // Optimize database query by selecting only needed fields
        $sql = "SELECT 
            t.TenantID,
            t.TenantName,
            t.TenantEmail,
            t.TenantPhoneNo,
            t.TenantStatus,
            t.BedID,
            t.RoomID,
            t.RentalType,
            b.BedNo,
            b.BedRentAmount,
            r.RoomNo,
            r.RoomRentAmount,
            u.UnitNo,
            p.PropertyName,
            a.AgentID,
            a.AgentName,
            d.DepositAmount as total_amount,
            d.RemainingAmount as remaining_amount
        FROM tenant t
        LEFT JOIN bed b ON b.BedID = t.BedID
        LEFT JOIN room r ON (r.RoomID = b.RoomID OR r.RoomID = t.RoomID)
        LEFT JOIN unit u ON u.UnitID = r.UnitID
        LEFT JOIN property p ON p.PropertyID = u.PropertyID
        LEFT JOIN agent a ON a.AgentID = t.AgentID
        LEFT JOIN deposit d ON d.TenantID = t.TenantID
        WHERE t.TenantID = ?";
        error_log('SQL Query: ' . $sql);
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log('Prepare statement failed: ' . $conn->error);
            $error = "Database query preparation failed";
        } else {
            $stmt->bind_param("s", $id);
            
            if (!$stmt->execute()) {
                error_log('Execute failed: ' . $stmt->error);
                $error = "Database query execution failed";
            } else {
                $result = $stmt->get_result();
                $tenantData = $result->fetch_assoc();
                
                if (!$tenantData) {
                    error_log('No tenant found for ID: ' . $id);
                    $error = "No tenant found with ID: " . htmlspecialchars($id);
                } else {
                    error_log('Tenant data found: ' . print_r($tenantData, true));
                    error_log('SQL Query executed: ' . $sql);
                    error_log('Room ID from query: ' . ($tenantData['room_id'] ?? 'null'));
                    error_log('Bed ID from query: ' . ($tenantData['BedID'] ?? 'null'));
                }
            }
            $stmt->close();
        }
    }
} else {
    error_log('No tenant ID provided in request parameters');
    $error = "No tenant ID provided";
}

// If we have tenant data, set up the session
if ($tenantData) {
    error_log('Setting up session with tenant data');
    
    // Determine rental type and set appropriate property info
    $rentalType = $tenantData['RentalType'] ?? '';
    $propertyInfo = [
        'unitNo' => $tenantData['UnitNo'] ?? ''
    ];
    
    // Add specific rental type properties
    if ($rentalType === 'Bed') {
        $propertyInfo['bedID'] = $tenantData['BedID'] ?? '';
        $propertyInfo['bedNo'] = $tenantData['BedNo'] ?? '';
        $propertyInfo['rentAmount'] = $tenantData['BedRentAmount'] ?? 0;
    } else if ($rentalType === 'Room') {
        $propertyInfo['roomID'] = $tenantData['RoomID'] ?? '';
        $propertyInfo['roomNo'] = $tenantData['RoomNo'] ?? '';
        $propertyInfo['rentAmount'] = $tenantData['RoomRentAmount'] ?? 0;
    }
    
    $_SESSION['flashpay_data'] = [
        'tenant_info' => [
            'name' => $tenantData['TenantName'] ?? '',
            'email' => $tenantData['TenantEmail'] ?? '',
            'phone' => $tenantData['TenantPhoneNo'] ?? '',
            'id' => $tenantData['TenantID'] ?? ''
        ],
        'property_info' => $propertyInfo,
        'agent_info' => [
            'agentID' => $tenantData['AgentID'] ?? '',
            'agentName' => $tenantData['AgentName'] ?? ''
        ],
        'payment_info' => [
            'total_amount' => $tenantData['total_amount'] ?? 0,
            'remaining_amount' => $tenantData['remaining_amount'] ?? 0,
            'rental_type' => $rentalType,
            'selected_month' => $_POST['selected_month'] ?? date('M'),
            'selected_year' => $_POST['selected_year'] ?? date('Y')
        ]
    ];
    
    error_log('Session data set successfully');
    error_log('Property Info in Session: ' . print_r($_SESSION['flashpay_data']['property_info'], true));
    error_log('Rental Type: ' . $rentalType);
}

// Move this check inside an if statement
if ($tenantData) {
    $depositFullyPaid = $tenantData['remaining_amount'] <= 0;
    $firstMonthDeducted = isset($tenantData['first_month_deducted']) ? $tenantData['first_month_deducted'] : false;
} else {
    $depositFullyPaid = false;
    $firstMonthDeducted = false;
}

// Add CSRF protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flash Pay - Rentronics</title>
    <link href="/img/favicon.ico" rel="icon">
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f0f4f8;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Arial', sans-serif;
        }
        .payment-card {
            background: white;
            border-radius: 10px;
            width: 100%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 30px 20px 15px 20px;
            background-color: #1c2f59;
            color: white;
            border-radius: 5px;
        }
        .header h2 {
            color: white;
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .card-icon {
            width: 80px;
            height: 50px;
            margin: 2rem auto;
        }
        .card-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .tenant-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.2rem;
            color: #333;
        }
        .tenant-id {
            color: #666;
            font-size: 1rem;
        }
        .property-name {
            color: #666;
            font-size: 1rem;
            margin-bottom: 2rem;
        }
        .payment-section {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .payment-label {
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .payment-amount {
            color: #dc3545;
            font-weight: bold;
            font-size: 1.8rem;
        }
        hr {
            margin: 10px;
            height:5px !important;
            color: black
        }
        .form-control {
            margin-bottom: 1rem;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            border-color: #1c2f59;
            box-shadow: none;
            outline: none;
        }
        .form-control::placeholder {
            color: #999;
        }
        .terms-check {
            font-size: 0.9rem;
            margin: 1rem 0;
            text-align: left;
        }
        .btn-pay {
            background-color: #1c2f59;
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn-pay:hover {
            background-color: #2a4374;
            transform: translateY(-2px);
        }
        .error {
            color: #dc3545;
            margin-bottom: 1rem;
        }
        .month-selector select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-top: 5px;
            background-color: white;
        }

        .payment-label.mt-3 {
            margin-top: 1rem;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        .property-details {
            color: #666;
            font-size: 1rem;
            margin-bottom: 2rem;
            text-align: center;
            background: none;
            padding: 0;
            box-shadow: none;
        }
        
        .property-info {
            color: #666;
            font-size: 1rem;
            word-wrap: break-word;
            line-height: 1.5;
        }
        
        .property-info strong {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="payment-card">
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
            <a href="/module-auth/login.php" class="btn btn-secondary">Back to Login</a>
        <?php elseif ($tenantData): ?>
            <div class="header">
                <h2>Rentronic</h2>
                <h2>Flash Pay</h2>
            </div>

            <div class="card-icon">
                <img src="/img/credit-card.png" alt="Credit Card Icon">
            </div>

            <h3>Hello!</h3>
            <div class="tenant-name"><?php echo htmlspecialchars($tenantData['TenantName']); ?></div>
            <div class="tenant-id">ID: <?php echo htmlspecialchars($tenantData['TenantID']); ?></div>
            <div class="property-details">
                <?php 
                $rental_type = $tenantData['RentalType'];
                $unit_details = '';
                
                if ($rental_type === 'Bed') {
                    $unit_details = sprintf(
                        '%s - %s',
                        htmlspecialchars($tenantData['PropertyName']),
                        htmlspecialchars($tenantData['BedNo'])
                    );
                } else {
                    $unit_details = sprintf(
                        '%s - %s',
                        htmlspecialchars($tenantData['PropertyName']),
                        htmlspecialchars($tenantData['RoomNo'])
                    );
                }
                ?>
                <div class="property-info">
                    <strong>Rental Type:</strong> <?php echo $rental_type; ?> | 
                    <strong>Unit:</strong> <?php echo $unit_details; ?>
                </div>
            </div>

            <div class="payment-section">
                <?php if ($tenantData['TenantStatus'] == 'Booked'): ?>
                    <!-- Deposit Payment Section -->
                    <div class="payment-label">Remaining Deposit Payment:</div>
                    <div class="payment-amount">RM <?php echo number_format($tenantData['remaining_amount'], 2); ?></div>
                    <div class="payment-note">
                        <small class="text-muted">
                            * First month's rent (RM <?php echo number_format($tenantData['BedRentAmount'], 2); ?>) 
                            will be deducted from your deposit once fully paid.
                        </small>
                    </div>
                    <input type="hidden" name="payment_type" value="deposit">
                <?php else: ?>
                    <!-- Rent Payment Section -->
                    <div class="payment-label">Monthly Rent Amount:</div>
                    <div class="payment-amount">
                        RM <?php 
                        $rentAmount = $tenantData['RentalType'] === 'Bed'
                            ? number_format($tenantData['BedRentAmount'], 2)
                            : number_format($tenantData['RoomRentAmount'], 2);
                        echo $rentAmount;
                        ?>
                    </div>
                    <div class="payment-label mt-3">Select Month to Pay:</div>
                    <div class="month-selector mb-3">
                        <select class="form-control" name="payment_month" id="payment_month" required>
                            <option value="">Please select Month</option>
                            <?php
                            // Get current date
                            $currentDate = new DateTime();
                            $currentDay = (int)$currentDate->format('d');
                            
                            // Check if there's deposit data
                            $hasDepositData = isset($tenantData['total_amount']) && $tenantData['total_amount'] > 0;
                            
                            if (!$hasDepositData) {
                                // Old tenant (manually added) logic
                                $startDate = clone $currentDate;
                                if ($currentDay <= 15) {
                                    // If current date is 15th or earlier, include current month
                                    $startDate->modify('first day of this month');
                                } else {
                                    // If after 15th, start from next month
                                    $startDate->modify('first day of next month');
                                }
                            } else {
                                // New tenant logic with deposit
                                if ($depositFullyPaid && !$firstMonthDeducted) {
                                    $startDate = clone $currentDate;
                                    $startDate->modify('+1 month');
                                } else {
                                    $startDate = DateTime::createFromFormat('M Y', $tenantData['LastPaidMonth'] . ' ' . $tenantData['LastPaidYear']);
                                    if (!$startDate) {
                                        // Fallback if LastPaidMonth/Year is not set
                                        $startDate = clone $currentDate;
                                    }
                                }
                            }
                            
                            // Generate next 3 months options
                            for ($i = 0; $i < 3; $i++) {
                                $nextMonth = clone $startDate;
                                $nextMonth->modify('+' . $i . ' month');
                                
                                $monthYear = $nextMonth->format('M Y');
                                $monthValue = $nextMonth->format('M');
                                $yearValue = $nextMonth->format('Y');
                                
                                echo "<option value='{$monthValue}' data-year='{$yearValue}'>{$monthYear}</option>";
                            }
                            ?>
                        </select>
                    </div>
                <?php endif; ?>

                <form action="/rentronics/module-payment/billplz/billplzpost.php" method="POST" id="paymentForm">
                    <?php if ($tenantData['TenantStatus'] == 'Booked'): ?>
                        <!-- Deposit Payment Form -->
                        <input type="hidden" name="payment_type" value="deposit">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label for="payment_amount" class="payment-label">Payment Amount (RM):</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="payment_amount" 
                                   name="amount" 
                                   required 
                                   placeholder="Payment Amount (RM)"
                                   style="border: none; border-bottom: 1px solid #ccc; border-radius: 0; box-shadow: none; padding: 0.5rem 0;"
                                   oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                            >
                        </div>
                    <?php else: ?>
                        <!-- Rent Payment Form -->
                        <input type="hidden" name="payment_type" value="rent">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="amount" value="<?php echo $propertyInfo['rentAmount']; ?>">
                        <input type="hidden" name="selected_month" id="selected_month">
                        <input type="hidden" name="selected_year" id="selected_year">
                    <?php endif; ?>
                    
                    <div class="terms-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">
                            I understand and accept the <a href="#" class="text-primary">Terms & Conditions</a>
                        </label>
                    </div>

                    <input type="hidden" name="tenant_id" value="<?php echo htmlspecialchars($tenantData['TenantID']); ?>">
                    <button type="submit" class="btn-pay" id="payButton">
                        <?php echo $tenantData['TenantStatus'] == 'Booked' ? 'PAY DEPOSIT' : 'PAY RENT'; ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('paymentForm');
        const amountInput = document.getElementById('payment_amount');
        const monthSelect = document.getElementById('payment_month');
        const selectedMonthInput = document.getElementById('selected_month');
        const selectedYearInput = document.getElementById('selected_year');
        
        // Update hidden fields when month is selected
        if (monthSelect) {
            monthSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const year = selectedOption.getAttribute('data-year');
                selectedMonthInput.value = this.value;
                selectedYearInput.value = year;

                // Ensure payment_info is up to date
                const paymentInfo = {
                    selected_month: this.value,
                    selected_year: year,
                    rental_type: '<?php echo htmlspecialchars($tenantData['RentalType']); ?>'
                };

                // Update or create payment_info hidden field
                let paymentInfoInput = form.querySelector('input[name="payment_info"]');
                if (!paymentInfoInput) {
                    paymentInfoInput = document.createElement('input');
                    paymentInfoInput.type = 'hidden';
                    paymentInfoInput.name = 'payment_info';
                    form.appendChild(paymentInfoInput);
                }
                paymentInfoInput.value = JSON.stringify(paymentInfo);
            });
        }
        
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Check CSRF token
                const csrfToken = form.querySelector('input[name="csrf_token"]');
                if (!csrfToken || !csrfToken.value) {
                    alert('Security token is missing. Please refresh the page and try again.');
                    return;
                }
                
                // Validate amount
                if (amountInput && amountInput.value) {
                    const amount = parseFloat(amountInput.value);
                    if (isNaN(amount) || amount <= 0) {
                        alert('Please enter a valid amount');
                        return;
                    }
                }
                
                // Validate month selection for rent payment
                if (monthSelect && !monthSelect.value) {
                    alert('Please select a month');
                    return;
                }

                // Update hidden fields one last time before submission
                if (monthSelect) {
                    const selectedOption = monthSelect.options[monthSelect.selectedIndex];
                    const year = selectedOption.getAttribute('data-year');
                    selectedMonthInput.value = monthSelect.value;
                    selectedYearInput.value = year;

                    // Ensure payment_info is up to date
                    const paymentInfo = {
                        selected_month: monthSelect.value,
                        selected_year: year,
                        rental_type: '<?php echo htmlspecialchars($tenantData['RentalType']); ?>'
                    };

                    // Update or create payment_info hidden field
                    let paymentInfoInput = form.querySelector('input[name="payment_info"]');
                    if (!paymentInfoInput) {
                        paymentInfoInput = document.createElement('input');
                        paymentInfoInput.type = 'hidden';
                        paymentInfoInput.name = 'payment_info';
                        form.appendChild(paymentInfoInput);
                    }
                    paymentInfoInput.value = JSON.stringify(paymentInfo);
                }
                
                // If all validations pass, submit the form
                this.submit();
            });
        }
    });
    </script>
</body>
</html>
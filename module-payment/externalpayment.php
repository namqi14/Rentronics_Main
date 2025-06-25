<?php
session_start();
require_once('../module-auth/dbconnection.php');

// Check if user is admin (AccessLevel = 1)
if (!isset($_SESSION['auser']) || $_SESSION['access_level'] != 1) {
    header('Location: ../module-auth/login.php');
    exit();
}

// Check if PDO connection is established
if (!isset($pdo) || $pdo === null) {
    die("Database connection failed. Please check your connection settings.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // Get tenant's rental type
        $stmt = $pdo->prepare("SELECT RentalType FROM tenant WHERE tenantid = ?");
        $stmt->execute([$_POST['tenant_id']]);
        $tenant = $stmt->fetch();

        // Format the filename
        $amount = (int)$_POST['amount'];  // Convert to integer (removes decimal)
        $date = date('dmy');  // Format: 070125

        // Get the appropriate ID and number based on rental type
        if ($tenant['RentalType'] == 'Room') {
            $stmt = $pdo->prepare("SELECT roomno FROM room WHERE roomid = ?");
            $stmt->execute([$_POST['bed_id']]); // bed_id contains roomid for room rentals
            $unit = $stmt->fetch();
            $unit_no = $unit['roomno'];
            $roomid = $_POST['bed_id']; // For Room rentals, use bed_id field which actually contains the roomid
            $bedid = null; // For Room rentals, BedID should be NULL
        } else {
            $stmt = $pdo->prepare("SELECT bedno FROM bed WHERE bedid = ?");
            $stmt->execute([$_POST['bed_id']]);
            $unit = $stmt->fetch();
            $unit_no = $unit['bedno'];
            $bedid = $_POST['bed_id'];
            $roomid = null; // For Bed rentals, RoomID should be NULL
        }

        $receipt_filename = $amount . '-' . $unit_no . '-' . $date;

        // Get file extension from uploaded file
        $file_extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
        $final_filename = $receipt_filename . '.' . $file_extension;

        // Create upload directory if it doesn't exist
        $upload_dir = '../uploads/receipts/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Handle file upload
        $target_file = $upload_dir . $final_filename;
        if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $target_file)) {
            throw new Exception("Failed to upload receipt file.");
        }

        if ($_POST['payment_type'] == 'Deposit Payment') {
            // For deposit payments, just record in payment and paymenthistory tables
            // Get rent amount for reference based on rental type
            $rent_query = ($tenant['RentalType'] == 'Room') 
                ? "SELECT roomrentamount as rentamount FROM room WHERE roomid = ?"
                : "SELECT bedrentamount as rentamount FROM bed WHERE bedid = ?";

            $stmt = $pdo->prepare($rent_query);
            $stmt->execute([$_POST['bed_id']]);
            $rent_data = $stmt->fetch();
            $rent_amount = $rent_data ? $rent_data['rentamount'] : 0;

            $payment_amount = $_POST['amount'];

            // Insert deposit payment record
            insertPayment($pdo, [
                'tenant_id' => $_POST['tenant_id'],
                'bed_id' => $bedid,
                'room_id' => $roomid,
                'agent_id' => $_POST['agent_id'],
                'month' => $_POST['month'],
                'year' => $_POST['year'],
                'amount' => $payment_amount,
                'payment_type' => 'Deposit Payment',
                'remarks' => $_POST['remarks']
            ]);

            // Also insert a rent payment record if rent amount is available
            if ($rent_amount > 0) {
                insertPayment($pdo, [
                    'tenant_id' => $_POST['tenant_id'],
                    'bed_id' => $bedid,
                    'room_id' => $roomid,
                    'agent_id' => $_POST['agent_id'],
                    'month' => $_POST['month'],
                    'year' => $_POST['year'],
                    'amount' => $rent_amount,
                    'payment_type' => 'Rent Payment',
                    'remarks' => 'Payment ' . $_POST['month']
                ]);
            }

        } else {
            // Regular payment
            insertPayment($pdo, [
                'tenant_id' => $_POST['tenant_id'],
                'bed_id' => $bedid,
                'room_id' => $roomid,
                'agent_id' => $_POST['agent_id'],
                'month' => $_POST['month'],
                'year' => $_POST['year'],
                'amount' => $_POST['amount'],
                'payment_type' => $_POST['payment_type'],
                'remarks' => $_POST['remarks']
            ]);
        }

        $pdo->commit();
        $_SESSION['success'] = "Payment record added successfully!";
        header('Location: externalpayment.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: externalpayment.php');
        exit();
    }
}

// Helper function to insert payment
function insertPayment($pdo, $data)
{
    // Generate payment ID with random number
    $random_number = mt_rand(1000000, 9999999);
    $payment_id = 'RENT' . $random_number;

    // Verify uniqueness
    while ($pdo->query("SELECT 1 FROM payment WHERE paymentid = '$payment_id'")->fetch()) {
        $random_number = mt_rand(1000000, 9999999);
        $payment_id = 'RENT' . $random_number;
    }

    // Insert payment based on rental type
    $sql_payment = "INSERT INTO payment (
        paymentid, tenantid, bedid, roomid, agentid, datecreated, 
        month, year, amount, overpayment, paymenttype, 
        chargefee, paymentstatus, remarks, claimed, claimer
    ) VALUES (
        ?, ?, ?, ?, ?, NOW(),
        ?, ?, ?, ?, ?,
        ?, 'Successful', ?, 0, NULL
    )";

    $stmt = $pdo->prepare($sql_payment);
    $stmt->execute([
        $payment_id,
        $data['tenant_id'],
        $data['bed_id'],  // Will be NULL for Room rentals
        $data['room_id'], // Will be NULL for Bed rentals
        $data['agent_id'],
        $data['month'],
        $data['year'],
        $data['amount'],
        0.00, // Overpayment
        $data['payment_type'],
        0.00, // ChargeFee
        $data['remarks']
    ]);

    // Insert into PaymentHistory for ALL payment types
    $sql_history = "INSERT INTO paymenthistory (
        paymenthistoryid, tenantid, agentid, amount, 
        paymentdate, paymenttype, remarks
    ) VALUES (
        ?, ?, ?, ?,
        NOW(), ?, ?
    )";

    $stmt = $pdo->prepare($sql_history);
    $stmt->execute([
        $payment_id,
        $data['tenant_id'],
        $data['agent_id'],
        $data['amount'],
        $data['payment_type'],
        $data['remarks']
    ]);

    return $payment_id;
}

// Modify the agents query to include agents from both bed and room tables
$agents = $pdo->query("SELECT DISTINCT a.agentid, a.agentname 
                       FROM agent a 
                       WHERE a.agentid IN (
                           SELECT agentid FROM bed 
                           UNION 
                           SELECT agentid FROM room
                       )
                       ORDER BY a.agentid")->fetchAll();

// Modify the tenants query to get agent ID directly from tenant table
$tenants = $pdo->query("SELECT 
    t.tenantid, 
    t.tenantname, 
    t.RentalType,
    t.agentid,
    b.bedid, 
    b.bedno, 
    b.bedrentamount,
    r.roomid,
    r.roomno,
    r.roomrentamount, 
    a.agentname 
FROM tenant t 
LEFT JOIN bed b ON t.bedid = b.bedid 
LEFT JOIN room r ON t.roomid = r.roomid
LEFT JOIN agent a ON a.agentid = t.agentid
ORDER BY t.tenantname")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>External Payment Receipt</title>
    <link href="/rentronics/img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
    <link href="../module-property/css/bed.css" rel="stylesheet">
    <style>
        /* Form styling */
        .form-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-section {
            padding: 20px 0;
        }

        .form-section h5 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        /* Signature pad styling */
        .signature-pad-container {
            border: 2px solid #e4e4e4;
            border-radius: 4px;
            margin-bottom: 20px;
            background: #fff;
        }

        .signature-pad-wrapper {
            position: relative;
            width: 100%;
            padding: 10px;
        }

        .signature-pad {
            width: 100%;
            border: none;
            background-color: #fff;
            touch-action: none;
        }

        /* Button styling */
        #clearSignature {
            position: absolute;
            bottom: 10px;
            right: 10px;
            padding: 5px 15px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #ccc;
        }

        /* Form controls */
        .form-control {
            border: 1px solid #dce4ec;
            border-radius: 4px;
            padding: 8px 12px;
            transition: border-color 0.15s ease-in-out;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .col-form-label {
                margin-bottom: 5px;
            }

            .form-group {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid bg-white p-0">
        <!-- Navbar and Sidebar Start-->
        <?php include('../nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="content container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="head row">
                        <div class="col">
                            <h3 class="page-title">External Payment Receipt</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="/rentronics/dashboardagent.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Payment</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card form-container">
                            <div class="card-header">
                                <h4 class="card-title">Add External Payment Receipt</h4>
                            </div>
                            <div class="card-body">
                                <?php if (isset($_SESSION['success'])): ?>
                                    <div class="alert alert-success">
                                        <?php
                                        echo $_SESSION['success'];
                                        unset($_SESSION['success']);
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($_SESSION['error'])): ?>
                                    <div class="alert alert-danger">
                                        <?php
                                        echo $_SESSION['error'];
                                        unset($_SESSION['error']);
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="form-section">
                                        <h5>Payment Details</h5>
                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Tenant</label>
                                            <div class="col-lg-9">
                                                <select name="tenant_id" class="form-control" required>
                                                    <option value="">Select Tenant</option>
                                                    <?php foreach ($tenants as $tenant): ?>
                                                        <option value="<?php echo htmlspecialchars($tenant['tenantid']); ?>">
                                                            <?php
                                                            if ($tenant['RentalType'] == 'Bed') {
                                                                echo htmlspecialchars($tenant['tenantname'] . ' - Bed: ' . $tenant['bedno']);
                                                            } else {
                                                                echo htmlspecialchars($tenant['tenantname'] . ' - Room: ' . $tenant['roomno']);
                                                            }
                                                            ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Bed ID</label>
                                            <div class="col-lg-9">
                                                <input type="text" id="bed_id" class="form-control" readonly>
                                                <input type="hidden" name="bed_id" required>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Agent ID</label>
                                            <div class="col-lg-9">
                                                <select name="agent_id" class="form-control" required>
                                                    <option value="">Select Agent</option>
                                                    <?php foreach ($agents as $agent): ?>
                                                        <option value="<?php echo htmlspecialchars($agent['agentid']); ?>">
                                                            <?php echo htmlspecialchars($agent['agentid'] . ' - ' . $agent['agentname']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Amount</label>
                                            <div class="col-lg-9">
                                                <input type="number" name="amount" placeholder="Enter Amount" class="form-control" step="0.01" required>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Month</label>
                                            <div class="col-lg-9">
                                                <select name="month" class="form-control" required>
                                                    <?php
                                                    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                                    foreach ($months as $month):
                                                    ?>
                                                        <option value="<?php echo $month; ?>"><?php echo $month; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Year</label>
                                            <div class="col-lg-9">
                                                <input type="number" name="year" class="form-control" value="<?php echo date('Y'); ?>" required>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Remarks</label>
                                            <div class="col-lg-9">
                                                <textarea name="remarks" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Receipt Upload</label>
                                            <div class="col-lg-9">
                                                <input type="file" name="receipt" class="form-control" accept="image/*,.pdf" required>
                                                <small class="text-muted">Upload receipt image or PDF (Max size: 5MB)</small>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label class="col-lg-3 col-form-label">Payment Type</label>
                                            <div class="col-lg-9">
                                                <select name="payment_type" class="form-control" required>
                                                    <option value="">Select Payment Type</option>
                                                    <option value="Rent Payment">Rent Payment</option>
                                                    <option value="Deposit Payment">Deposit Payment</option>
                                                    <option value="Utility Payment">Utility Payment</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <button type="submit" class="btn btn-primary">Submit Payment</button>
                                        <button type="reset" class="btn btn-secondary">Reset</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Cache DOM elements
        const elements = {
            tenantSelect: document.querySelector('select[name="tenant_id"]'),
            bedInput: document.getElementById('bed_id'),
            bedIdInput: document.querySelector('input[name="bed_id"]'),
            agentSelect: document.querySelector('select[name="agent_id"]'),
            amountInput: document.querySelector('input[name="amount"]')
        };

        // Create tenant data map
        const tenantMap = {
            <?php 
            foreach ($tenants as $tenant) {
                $tenantData = [
                    'rentalType' => $tenant['RentalType'],
                    'bedId' => $tenant['bedid'],
                    'bedNo' => $tenant['bedno'],
                    'roomId' => $tenant['roomid'],
                    'roomNo' => $tenant['roomno'],
                    'agentId' => $tenant['agentid'],
                    'rentAmount' => $tenant['RentalType'] == 'Bed' ? 
                        $tenant['bedrentamount'] : $tenant['roomrentamount']
                ];
                echo "'" . $tenant['tenantid'] . "': " . json_encode($tenantData) . ",";
            }
            ?>
        };

        // Handle tenant selection change
        elements.tenantSelect.addEventListener('change', function() {
            const selectedTenant = tenantMap[this.value] || null;
            
            if (!selectedTenant) {
                resetFields(elements);
                return;
            }

            updateFields(elements, selectedTenant);
        });
    });

    // Helper functions
    function resetFields(elements) {
        elements.bedInput.value = '';
        elements.bedIdInput.value = '';
        elements.agentSelect.value = '';
        elements.amountInput.placeholder = 'Enter Amount';
    }

    function updateFields(elements, tenantInfo) {
        // Update bed/room information
        const isRoomRental = tenantInfo.rentalType !== 'Bed';
        const displayId = isRoomRental ? tenantInfo.roomId : tenantInfo.bedId;
        const displayNo = isRoomRental ? tenantInfo.roomNo : tenantInfo.bedNo;
        
        elements.bedInput.value = `${displayId} - ${displayNo}`;
        elements.bedIdInput.value = isRoomRental ? tenantInfo.roomId : tenantInfo.bedId;

        // Update agent and amount
        if (tenantInfo.agentId) {
            elements.agentSelect.value = tenantInfo.agentId;
        }
        elements.amountInput.placeholder = `Enter Amount : ${tenantInfo.rentAmount || ''}`;
    }
    </script>
</body>

</html>
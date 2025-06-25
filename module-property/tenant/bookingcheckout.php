<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Initialize variables
$error = '';
$msg = '';

// Check if booking data exists in session
if (!isset($_SESSION['booking_data'])) {
    header("Location: index.php");
    exit;
}

$bookingData = $_SESSION['booking_data'];
//var_dump($bookingData);

// Validate required parameters - support both bed and room bookings
if ((!isset($_GET['bedID']) && !isset($_GET['roomID'])) || !isset($_GET['tenantID'])) {
    $error = "Missing required parameters";
} else {
    // Determine if this is a bed or room booking
    $is_room_booking = isset($_GET['roomID']);
    $property_id = $is_room_booking ? $_GET['roomID'] : $_GET['bedID'];
    
    // Prepare the appropriate query based on booking type
    if ($is_room_booking) {
        // Fetch room and agent data
        $stmt = $conn->prepare("
            SELECT r.*, r.BaseRentAmount as RoomRentAmount, u.UnitNo, u.UnitID, a.AgentID,
                   a.AgentName, a.AgentWhatsapp, a.AgentEmail 
            FROM room r
            JOIN unit u ON r.UnitID = u.UnitID 
            LEFT JOIN agent a ON r.AgentID = a.AgentID
            WHERE r.RoomID = ?
        ");
    } else {
        // Fetch bed and agent data
        $stmt = $conn->prepare("
            SELECT b.*, b.BaseRentAmount, b.BedRentAmount, r.RoomNo, r.RoomID, u.UnitNo, u.UnitID, a.AgentID,
                   a.AgentName, a.AgentWhatsapp, a.AgentEmail 
            FROM bed b 
            JOIN room r ON b.RoomID = r.RoomID 
            JOIN unit u ON r.UnitID = u.UnitID 
            LEFT JOIN agent a ON b.AgentID = a.AgentID
            WHERE b.BedID = ?
        ");
    }
    
    $stmt->bind_param("s", $property_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $property_data = $result->fetch_assoc();
    $stmt->close();

    // Check if property data exists and is an array
    if (!isset($property_data) || !is_array($property_data)) {
        $property_data = array();
    }

    // Get the current agent's ID from the session
    $agent_id = $_SESSION['auser']['AgentID'] ?? null;

    // Calculate rent amount based on booking type and available data
    $rent_amount = 0;
    
    // First check if there's an amount from the booking form in the session
    if (isset($_SESSION['booking_data']['payment_info']['amount']) && 
        $_SESSION['booking_data']['payment_info']['amount'] > 0) {
        // Use the amount from booking form
        $rent_amount = floatval($_SESSION['booking_data']['payment_info']['amount']);
    } elseif ($is_room_booking) {
        // For room bookings
        if ($property_data && isset($property_data['RoomRentAmount']) && $property_data['RoomRentAmount'] > 0) {
            $rent_amount = floatval($property_data['RoomRentAmount']);
        } elseif ($property_data && isset($property_data['BaseRentAmount'])) {
            $rent_amount = floatval($property_data['BaseRentAmount']);
        } else {
            $error = "Could not retrieve rent amount for this room";
        }
    } else {
        // For bed bookings
        if ($property_data && isset($property_data['BedRentAmount']) && $property_data['BedRentAmount'] > 0) {
            $rent_amount = floatval($property_data['BedRentAmount']);
        } elseif ($property_data && isset($property_data['BaseRentAmount'])) {
            $rent_amount = floatval($property_data['BaseRentAmount']);
        } else {
            $error = "Could not retrieve rent amount for this bed";
        }
    }

    $deposit = $rent_amount; // 1 month deposit
    $advance_rental = $rent_amount; // 1 month advance rental
    $processing_fee = 50.00;
    $total_amount = $deposit + $advance_rental + $processing_fee;
    
    //var_dump($bookingData);

    // Update session with complete booking data based on booking type
    $property_info = [];
    
    if ($is_room_booking) {
        // Room booking property info
        $property_info = [
            'roomID' => $property_data['RoomID'] ?? '',
            'unitID' => $property_data['UnitID'] ?? '',
            'roomNo' => $property_data['RoomNo'] ?? '',
            'unitNo' => $property_data['UnitNo'] ?? '',
            'rental_type' => 'Room'
        ];
    } else {
        // Bed booking property info
        $property_info = [
            'bedID' => $property_data['BedID'] ?? '',
            'roomID' => $property_data['RoomID'] ?? '',
            'unitID' => $property_data['UnitID'] ?? '',
            'bedNo' => $property_data['BedNo'] ?? '',
            'roomNo' => $property_data['RoomNo'] ?? '',
            'unitNo' => $property_data['UnitNo'] ?? '',
            'rental_type' => 'Bed'
        ];
    }
    
    $_SESSION['booking_data'] = [
        'tenant_info' => [
            'tenantID' => $_GET['tenantID'],
            'tenant_id' => $_GET['tenantID'],
            'tenantName' => $bookingData['tenant_info']['tenantName'] ?? '',
            'tenant_name' => $bookingData['tenant_info']['tenantName'] ?? '',
            'tenantEmail' => $bookingData['tenant_info']['tenantEmail'] ?? '',
            'tenantPhoneNo' => $bookingData['tenant_info']['tenantPhoneNo'] ?? '',
            'passport' => $bookingData['tenant_info']['passport'] ?? '',
            'rentStartDate' => $bookingData['tenant_info']['rentStartDate'] ?? '',
            'safeTenantName' => $bookingData['tenant_info']['safeTenantName'] ?? preg_replace('/[^a-zA-Z0-9_-]/', '_', $bookingData['tenant_info']['tenantName'] ?? '')
        ],
        'property_info' => $property_info,
        'agent_info' => [
            'agentID' => $agent_id,
            'agentName' => $_SESSION['auser']['AgentName'] ?? ''
        ],
        'payment_info' => [
            'deposit' => $deposit,
            'advance_rental' => $advance_rental,
            'processing_fee' => $processing_fee,
            'total_amount' => $total_amount,
            'amount' => $rent_amount,
            'rental_type' => $is_room_booking ? 'Room' : 'Bed',
            'duration' => $bookingData['payment_info']['duration'] ?? '12'
        ]
    ];

    // Store ALL payment info in the booking_data session
    //$_SESSION['booking_data']['payment_info'] = [
    //    'deposit' => $deposit,
    //    'advance_rental' => $advance_rental,
    //    'processing_fee' => $processing_fee,
    //    'total_amount' => $total_amount,
    //    'amount' => $rent_amount
    //];

    //var_dump( $_SESSION['booking_data']);
}

// Extract data for display
$tenant_name = $bookingData['tenant_info']['tenantName'] ?? '';
$tenant_email = $bookingData['tenant_info']['tenantEmail'] ?? '';
$mobile_number = $bookingData['tenant_info']['tenantPhoneNo'] ?? '';
$ic_passport = $bookingData['tenant_info']['passport'] ?? '';
$rental_start_date = $bookingData['tenant_info']['rentStartDate'] ?? '';

// Update the payment_details session with all required information
$_SESSION['payment_details'] = [
    'id' => 'PAY' . time() . rand(1000, 9999),
    'amount' => $rent_amount * 100,
    'payment_date' => date('Y-m-d H:i:s')
];

// Set payment success flag
$_SESSION['payment_success'] = true;

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// For debugging purposes, you might want to add:
error_log('Payment Details Session: ' . print_r($_SESSION['payment_details'], true));

// If there's an error from billplz redirect
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// If there's a success message from billplz redirect
if (isset($_GET['success'])) {
    $msg = $_GET['success'];
}

// Make booking data available to the view
$viewData = [
    'tenant' => $bookingData['tenant_info'],
    'property' => $bookingData['property_info'],
    'payment' => $bookingData['payment_info'],
    'error' => $error,
    'msg' => $msg
];

//var_dump($_SESSION['booking_data']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentronics</title>
    <link href="/img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <link href="../css/booking-form.css" rel="stylesheet">
    <link href="../css/bed.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .nav-bar {
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        .detail-item {
            margin-bottom: 1rem;
        }
        
        .form-control-lg {
            padding: 0.8rem 1rem;
            font-size: 1rem;
        }
        
        .payment-summary {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-primary:hover {
            background-color: #2980b9 !important;
            transform: translateY(-1px);
            transition: all 0.3s ease;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container-fluid bg-white p-0">
        <!-- Navbar and Sidebar Start-->
        <?php include('../../nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="content container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="page-title">Checkout</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Checkout</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="row">
                    <!-- Checkout Form -->
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Checkout</h4>
                            </div>
                            <form method="POST" action="../../module-payment/billplz/billplzpost.php">
                                <!-- CSRF Token -->
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <!-- Hidden fields with tenant data -->
                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($tenant_name); ?>">
                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($tenant_email); ?>">
                                <input type="hidden" name="mobile" value="<?php echo htmlspecialchars($mobile_number); ?>">
                                <input type="hidden" name="description" value="Rental Payment for Unit <?php echo htmlspecialchars($bed_data['UnitNo']); ?> Room <?php echo htmlspecialchars($bed_data['RoomNo']); ?> Bed <?php echo htmlspecialchars($bed_data['BedNo']); ?>">
                                <input type="hidden" name="reference_1_label" value="Bed ID">
                                <input type="hidden" name="reference_1" value="<?php echo htmlspecialchars($bed_id); ?>">
                                <input type="hidden" name="reference_2_label" value="Payment Type">
                                <input type="hidden" name="reference_2" value="Booking Fee">
                                <input type="hidden" name="collection_id" value="m_wpys01">
                                <input type="hidden" name="amount" value="<?php echo $total_amount; ?>">
                                <input type="hidden" name="insert_amount" value="<?php echo $total_amount; ?>">

                                <div class="card-body">
                                    <h5 class="card-title text-center mb-4" style="font-size: 1.5rem; color: #2c3e50;">CHECKOUT</h5>
                                    
                                    <!-- Booking Details Section -->
                                    <div class="p-4 mb-4 rounded shadow-sm" style="background-color: #f8f9fa;">
                                        <h5 class="mb-4" style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
                                            <i class="fas fa-info-circle me-2"></i>Booking Details
                                        </h5>
                                        
                                        <!-- Personal Info Grid -->
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <div class="detail-item">
                                                    <label class="text-muted mb-1">Tenant Name</label>
                                                    <div class="form-control-lg bg-white rounded">
                                                        <?php echo htmlspecialchars($tenant_name ?? ''); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="detail-item">
                                                    <label class="text-muted mb-1">IC/Passport</label>
                                                    <div class="form-control-lg bg-white rounded">
                                                        <?php echo htmlspecialchars($ic_passport ?? ''); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <div class="detail-item">
                                                    <span class="detail-label">Property Type:</span>
                                                    <span class="detail-value"><?php echo $_SESSION['booking_data']['payment_info']['rental_type'] ?? 'Property'; ?></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="detail-item">
                                                    <span class="detail-label">Room:</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($_SESSION['booking_data']['property_info']['roomNo'] ?? 'N/A'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <?php if (isset($_SESSION['booking_data']['property_info']['bedNo'])): ?>
                                            <div class="col-md-6">
                                                <div class="detail-item">
                                                    <span class="detail-label">Bed No:</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($_SESSION['booking_data']['property_info']['bedNo'] ?? 'N/A'); ?></span>
                                                </div>
                                            </div>
                                            <?php else: ?>
                                            <div class="col-md-6">
                                                <div class="detail-item">
                                                    <span class="detail-label">Unit No:</span>
                                                    <span class="detail-value"><?php echo htmlspecialchars($_SESSION['booking_data']['property_info']['unitNo'] ?? 'N/A'); ?></span>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            <div class="col-md-6">
                                                <div class="detail-item">
                                                    <span class="detail-label">Rental Amount:</span>
                                                    <span class="detail-value">RM <?php echo number_format($_SESSION['booking_data']['payment_info']['amount'] ?? 0, 2); ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Payment Details Section -->
                                        <div class="payment-summary p-3 rounded" style="background-color: #ffffff; border: 1px solid #dee2e6;">
                                            <h6 class="mb-3" style="color: #2c3e50;">Payment Summary</h6>
                                            <div class="row mb-2">
                                                <div class="col-8">Deposit (1 Month)</div>
                                                <div class="col-4 text-end">RM <?php echo number_format($_SESSION['booking_data']['payment_info']['deposit'] ?? 0, 2); ?></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-8">Advance Rental</div>
                                                <div class="col-4 text-end">RM <?php echo number_format($_SESSION['booking_data']['payment_info']['advance_rental'] ?? 0, 2); ?></div>
                                            </div>
                                            <div class="row mb-2">
                                                <div class="col-8">Processing Fee</div>
                                                <div class="col-4 text-end">RM <?php echo number_format($_SESSION['booking_data']['payment_info']['processing_fee'] ?? 0, 2); ?></div>
                                            </div>
                                            <div class="row pt-2 border-top">
                                                <div class="col-8 fw-bold">Total Amount</div>
                                                <div class="col-4 text-end fw-bold">RM <?php echo number_format($_SESSION['booking_data']['payment_info']['total_amount'] ?? 0, 2); ?></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Payment Input Section -->
                                    <div class="text-center mb-4">
                                        <label for="insertAmount" class="form-label fw-bold mb-3">Insert Amount To Pay</label>
                                        <div class="input-group w-75 mx-auto">
                                            <span class="input-group-text">RM</span>
                                            <input type="number" 
                                                   id="insertAmount" 
                                                   class="form-control form-control-lg text-center" 
                                                   name="insert_amount" 
                                                   min="100" 
                                                   step="0.01" 
                                                   required 
                                                   placeholder="Minimum: RM 100">
                                        </div>
                                        <div class="text-muted small mt-2">Minimum payment amount is RM 100</div>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="text-center">
                                        <button type="submit" 
                                                class="btn btn-primary btn-lg px-5" 
                                                name="submit" 
                                                onclick="return validatePayment()"
                                                style="background-color: #3498db; border: none; min-width: 200px;">
                                            <i class="fas fa-lock me-2"></i>Pay Now
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- /Checkout Form -->
                </div>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Message</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($msg)) : ?>
                        <div class='alert alert-success'><?php echo $msg; ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error)) : ?>
                        <div class='alert alert-danger'><?php echo $error; ?></div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Ensure jQuery is loaded first -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js" crossorigin="anonymous"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

    <!-- PHP condition to include JavaScript code to show the modal -->
    <?php if (!empty($msg) || !empty($error)) : ?>
        <script>
            $(document).ready(function() {
                $('#messageModal').modal('show');
            });
        </script>
    <?php endif; ?>
    <!-- Template Javascript -->
    <script src="../../js/main.js"></script>
    <script>
    function validatePayment() {
        const amount = document.getElementById('insertAmount').value;
        if (amount < 1) {
            alert('Minimum payment amount is RM 1');
            return false;
        }
        return true;
    }
    </script>

    <?php
    // Generate agreement in the background after page load
    if (isset($_SESSION['booking_data']['files']['agreementData'])) {
        $agreementData = $_SESSION['booking_data']['files']['agreementData'];
        try {
            // Create AgreementProcessor instance
            $agreementProcessor = new AgreementProcessor($conn);
            
            // Generate the agreement asynchronously
            ignore_user_abort(true);
            set_time_limit(0);
            
            // Generate the agreement
            $agreementPaths = $agreementProcessor->generateAgreement($agreementData);
            
            // Store the generated paths in session
            $_SESSION['booking_data']['files']['agreementPaths'] = $agreementPaths;
            
        } catch (Exception $e) {
            error_log("Error generating agreement in background: " . $e->getMessage());
        }
    }
    ?>
</body>

</html>
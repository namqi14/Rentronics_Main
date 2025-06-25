<?php
session_start();
require_once __DIR__ . '/../module-auth/dbconnection.php';

// Set timezone to Kuala Lumpur
date_default_timezone_set('Asia/Kuala_Lumpur');

// Debug logging
error_log("Session data: " . print_r($_SESSION, true));
error_log("GET data: " . print_r($_GET, true));

// Add near the top after session_start()
error_log("Payment success data: " . print_r($_SESSION['payment_success'] ?? [], true));
error_log("Flashpay data: " . print_r($_SESSION['flashpay_data'] ?? [], true));
error_log("Payment details: " . print_r($_SESSION['payment_details'] ?? [], true));

// Check for error parameter first
if (isset($_GET['error'])) {
    // Display error page directly without further redirects
    $_SESSION['payment_error'] = [
        'message' => $_GET['error']
    ];
    include 'error.php';
    exit();
}

// Get and validate Billplz response data
$billplz_data = $_GET['billplz'] ?? null;
$payment_status = $_GET['payment'] ?? '';
$error_message = $_GET['error'] ?? null;

// Debug logging
error_log("Billplz Data: " . print_r($billplz_data, true));
error_log("Payment Status: " . $payment_status);
error_log("Error Message: " . $error_message);

// Check if payment was successful from Billplz response
if ($billplz_data && isset($billplz_data['paid']) && $billplz_data['paid'] === 'true') {
    try {
        // Get payment type and details
        $payment_type = $_SESSION['payment_success']['type'] ?? null;
        $payment_details = $_SESSION['payment_details'] ?? null;
        
        if (!$payment_type || !$payment_details) {
            throw new Exception("Missing payment data in session");
        }

        error_log("Processing payment of type: " . $payment_type);
        error_log("Payment details: " . print_r($payment_details, true));

        // Process payment based on type
        switch ($payment_type) {
            case 'lumpsum':
                $lumpsum_data = $_SESSION['payment_success']['metadata']['lumpsum_data'] ?? null;
                if (!$lumpsum_data) {
                    error_log("Payment success metadata: " . print_r($_SESSION['payment_success']['metadata'], true));
                    throw new Exception("Payment details not found in metadata");
                }
                $result = handleLumpsumPayment(
                    $conn, 
                    $billplz_data['id'],
                    $billplz_data['paid_at'],
                    $lumpsum_data
                );

                if ($result) {
                    // Get the payment data from session for the receipt
                    $payment_data = $_SESSION['payment_data'];
                    
                    // Clear other payment sessions but keep payment_data
                    unset($_SESSION['payment_success']);
                    unset($_SESSION['payment_details']);
                    unset($_SESSION['billplz_bill_id']);
                    unset($_SESSION['lumpsum_payment']);
                    
                    // Show receipt
                    if (file_exists('receipt.html.php')) {
                        include 'receipt.html.php';
                        exit();
                    } else {
                        throw new Exception("Receipt template file not found");
                    }
                }
                break;

            case 'booking':
                $booking_data = $_SESSION['payment_success']['metadata']['booking_data'] ?? null;
                if (!$booking_data) {
                    throw new Exception("Missing booking payment data");
                }
                $result = handleBookingPayment(
                    $conn, 
                    $billplz_data['id'],
                    $billplz_data['paid_at'],
                    $booking_data
                );
                break;

            case 'flashpay':
                $flashpay_data = $_SESSION['payment_success']['metadata']['flashpay_data'] ?? null;
                if (!$flashpay_data) {
                    throw new Exception("Missing flashpay payment data");
                }
                $result = handleFlashPayment(
                    $conn, 
                    $billplz_data['id'],
                    $billplz_data['paid_at'],
                    $flashpay_data
                );
                break;

            default:
                throw new Exception("Unknown payment type: " . $payment_type);
        }

    } catch (Exception $e) {
        error_log("Error processing payment: " . $e->getMessage());
        $_SESSION['payment_error'] = [
            'message' => $e->getMessage(),
            'billplz_id' => $billplz_data['id']
        ];
        include 'error.php';
        exit();
    }
} elseif ($billplz_data && isset($billplz_data['paid']) && $billplz_data['paid'] === 'false') {
    // Payment explicitly failed
    $_SESSION['payment_error'] = [
        'message' => 'Payment was not successful',
        'billplz_id' => $billplz_data['id'] ?? null
    ];
} elseif ($payment_status === 'failed' || isset($_SESSION['payment_error'])) {
    // Handle other failure cases
    $error_message = $_SESSION['payment_error']['message'] ?? 'Payment was not successful';
}

// If we reach here, show error page
if (isset($_SESSION['payment_error'])) {
    include 'error.php';
    exit();
}

// Check if payment success data exists
if (!isset($_SESSION['payment_success'])) {
    error_log("No payment success data in session");
    header("Location: ../module-payment/successpayment?error=" . urlencode("Invalid payment session"));
    exit();
}

$payment_data = $_SESSION['payment_success'];
$payment_type = $payment_data['type'];

// Before processing payment
if (!isset($_SESSION['payment_success']) || !isset($_SESSION['payment_success']['type'])) {
    error_log("Missing payment success data or type");
    header("Location: ../module-payment/successpayment.php?error=" . urlencode("Invalid payment session"));
    exit();
}

$_SESSION['debug'] = true; // Add this in your development environment

try {
    // Begin transaction
    $conn->begin_transaction();

    // Process payment using the common handler
    $receipt_data = handlePayment(
        $conn,
        $payment_data['type'],
        $payment_data,
        $payment_data['billplz_id'],
        $payment_data['payment_date']
    );

    // Commit transaction
    $conn->commit();

    // Store receipt data in session
    $_SESSION['receipt_data'] = $receipt_data;
    unset($_SESSION['payment_success']);

    // Display receipt
    if (file_exists('receipt.html.php')) {
        include 'receipt.html.php';
    } else {
        throw new Exception("Receipt template file not found");
    }

} catch (Exception $e) {
    $conn->rollback();
    error_log("Payment processing error: " . $e->getMessage());
    $_SESSION['payment_error'] = [
        'message' => $e->getMessage()
    ];
    include 'error.php'; // Include error page directly instead of redirecting
    exit();
}

/**
 * Insert Payment Record
 * Common function to handle payment record insertion
 */
function insertPaymentRecord($conn, $payment_id, $tenant_id, $bed_id, $room_id, $agent_id, $amount, $payment_type, $rental_type, $remarks, $payment_month = null, $payment_year = null) {
    error_log("Starting insertPaymentRecord");
    
    // Validate rental type
    if (!in_array(strtolower($rental_type), ['bed', 'room'])) {
        throw new Exception("Invalid rental type: " . $rental_type);
    }
    
    // Set IDs based on rental type
    $bed_id = strtolower($rental_type) == 'bed' ? $bed_id : null;
    $room_id = strtolower($rental_type) == 'room' ? $room_id : null;
    
    // Use current date if month/year not provided
    $current_date = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
    $payment_month = $payment_month ?? $current_date->format('M');
    $payment_year = $payment_year ?? $current_date->format('Y');
    $charge_fee = 1.10;
    
    // Insert payment record
    $stmt = $conn->prepare("
        INSERT INTO payment (
            PaymentID, TenantID, BedID, RoomID, AgentID,
            Amount, PaymentType, PaymentStatus, DateCreated,
            Month, Year, ChargeFee, Remarks
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Successful', NOW(), ?, ?, 1.10, ?)
    ");

    $stmt->bind_param(
        "sssssdsssds",
        $payment_id,
        $tenant_id,
        $bed_id,
        $room_id,
        $agent_id,
        $amount,
        $payment_type,
        $payment_month,
        $payment_year,
        $charge_fee,
        $remarks
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert payment: " . $stmt->error);
    }

    // Insert payment history
    insertPaymentHistory($conn, $payment_id, $tenant_id, $agent_id, $amount, $payment_type, $remarks);
    
    return true;
}

/**
 * Update Property and Tenant Status
 * Common function to handle all status updates
 */
function updatePropertyAndTenantStatus($conn, $tenant_id, $property_id, $property_type, $status) {
    // Update tenant status
    $stmt = $conn->prepare("
        UPDATE tenant 
        SET TenantStatus = ?, UpdatedAt = NOW() 
        WHERE TenantID = ?
    ");
    
    $stmt->bind_param("ss", $status, $tenant_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update tenant status");
    }

    // Update property status
    $property_table = strtolower($property_type);
    $property_id_column = $property_type . "ID";
    $status_column = $property_type . "Status";
    
    $stmt = $conn->prepare("
        UPDATE {$property_table}
        SET {$status_column} = ?, UpdatedAt = NOW()
        WHERE {$property_id_column} = ?
    ");
    
    $stmt->bind_param("ss", $status, $property_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update {$property_type} status");
    }
}

/**
 * Handle Payment Process
 * Common function to handle all types of payments
 */
function handlePayment($conn, $payment_type, $payment_data, $billplz_id, $payment_date) {
    try {
        switch ($payment_type) {
            case 'lumpsum':
                return handleLumpsumPayment($conn, $billplz_id, $payment_date, $payment_data);
                
            case 'booking':
                return handleBookingPayment($conn, $billplz_id, $payment_date, $payment_data);
                
            case 'flashpay':
                return handleFlashPayment($conn, $billplz_id, $payment_date, $payment_data);
                
            default:
                throw new Exception("Unknown payment type: " . $payment_type);
        }
    } catch (Exception $e) {
        error_log("Error in handlePayment: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Prepare Receipt Data
 * Improved version with consistent prepared statements
 */
function prepareReceiptData($conn, $payment_id) {
    $max_retries = 3;
    $retry_count = 0;
    
    while ($retry_count < $max_retries) {
        try {
            $stmt = $conn->prepare("
                SELECT 
                    p.*,
                    t.TenantName,
                    t.TenantEmail,
                    t.TenantPhoneNo,
                    t.RentalType,
                    COALESCE(b.BedID, '') as BedID,
                    COALESCE(b.BedNo, '') as BedNo,
                    COALESCE(r.RoomID, '') as RoomID,
                    COALESCE(r.RoomNo, '') as RoomNo,
                    COALESCE(u.UnitNo, '') as UnitNo
                FROM payment p
                INNER JOIN tenant t ON t.TenantID = p.TenantID
                LEFT JOIN bed b ON b.BedID = p.BedID
                LEFT JOIN room r ON r.RoomID = COALESCE(p.RoomID, b.RoomID)
                LEFT JOIN unit u ON u.UnitID = r.UnitID
                WHERE p.PaymentID = ?
            ");
            
            $stmt->bind_param("s", $payment_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute payment query");
            }
            
            $result = $stmt->get_result();
            $payment_data = $result->fetch_assoc();
            
            if ($payment_data) {
                return $payment_data;
            }
            
            $retry_count++;
            usleep(500000); // 0.5 second delay
            
        } catch (Exception $e) {
            $retry_count++;
            if ($retry_count >= $max_retries) {
                throw $e;
            }
            usleep(500000);
        }
    }
    
    throw new Exception("Failed to retrieve payment data after {$max_retries} attempts");
}

/**
 * Generate a unique payment ID
 * Creates a unique payment ID with retry mechanism
 */
function generatePaymentID($conn) {
    $max_attempts = 5;
    $attempt = 0;
    
    while ($attempt < $max_attempts) {
        $prefix = 'RENT';
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        $payment_id = $prefix . $timestamp . $random;
        
        // Check if this ID already exists
        $stmt = $conn->prepare("SELECT PaymentID FROM payment WHERE PaymentID = ?");
        $stmt->bind_param("s", $payment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return $payment_id; // ID is unique, return it
        }
        
        $attempt++;
        usleep(100000); // 0.1 second delay before retrying
    }
    
    throw new Exception("Could not generate unique payment ID after {$max_attempts} attempts");
}

/**
 * Insert Tenant
 * Creates a new tenant record with booking information
 */
function insertTenant($conn, $bookingData) {
    // Determine rental type and set appropriate IDs
    $rental_type = isset($bookingData['property_info']['bedID']) && !empty($bookingData['property_info']['bedID']) 
        ? 'Bed' 
        : 'Room';

    // Set IDs based on rental type
    $bed_id = ($rental_type === 'Bed') ? $bookingData['property_info']['bedID'] : NULL;
    $room_id = ($rental_type === 'Room') ? $bookingData['property_info']['roomID'] : NULL;

    $stmt = $conn->prepare("
        INSERT INTO tenant (
            TenantID, 
            UnitID, 
            RoomID,    -- Will be NULL for bed rentals
            BedID,     -- Will be NULL for room rentals
            AgentID,
            TenantName, 
            TenantPhoneNo, 
            TenantEmail,
            RentStartDate, 
            RentExpiryDate,
            TenantStatus, 
            CreatedAt, 
            UpdatedAt, 
            RentalType
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Booked', NOW(), NOW(), ?)
    ");

    // Fix DateTime reference error by creating intermediate variables
    $rent_start_date = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
    $start_day = (int)$rent_start_date->format('d');
    if ($start_day > 15) {
        $rent_start_date->modify('first day of next month');
    }
    $rent_start_formatted = $rent_start_date->format('Y-m-d');
    
    $rent_expiry_date = clone $rent_start_date;
    $rent_expiry_date->modify('+1 year');
    $rent_expiry_formatted = $rent_expiry_date->format('Y-m-d');

    $stmt->bind_param(
        "sssssssssss",
        $bookingData['tenant_info']['tenantID'],
        $bookingData['property_info']['unitID'],
        $room_id,    // Will be NULL for bed rentals
        $bed_id,     // Will be NULL for room rentals
        $bookingData['agent_info']['agentID'],
        $bookingData['tenant_info']['tenantName'],
        $bookingData['tenant_info']['tenantPhoneNo'],
        $bookingData['tenant_info']['tenantEmail'],
        $rent_start_formatted,
        $rent_expiry_formatted,
        $rental_type
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert tenant: " . $stmt->error);
    }
}

/**
 * Insert Deposit
 * Records the initial deposit payment for either bed or room
 */
function insertDeposit($conn, $tenant_id, $property_id, $agent_id, $total_amount, $paid_amount, $remaining_amount, $property_type) {
    $stmt = $conn->prepare("
        INSERT INTO deposit (
            DepositID, TenantID, " . ($property_type === 'Bed' ? "BedID" : "RoomID") . ", AgentID,
            DepositAmount, PaidAmount, RemainingAmount,
            " . ($property_type === 'Bed' ? "BedRentAmount" : "RoomRentAmount") . ", PaymentMadeDate
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $deposit_id = 'DEP' . time() . rand(1000, 9999);
    
    // Get rent amount based on property type
    $rent_stmt = $conn->prepare("SELECT " . ($property_type === 'Bed' ? "BedRentAmount" : "RoomRentAmount") . " FROM " . 
                                ($property_type === 'Bed' ? "bed" : "room") . " WHERE " . 
                                ($property_type === 'Bed' ? "BedID" : "RoomID") . " = ?");
    $rent_stmt->bind_param("s", $property_id);
    $rent_stmt->execute();
    $result = $rent_stmt->get_result();
    $rent_amount = $result->fetch_assoc()[$property_type === 'Bed' ? "BedRentAmount" : "RoomRentAmount"];

    $stmt->bind_param(
        "ssssdddd",
        $deposit_id,
        $tenant_id,
        $property_id,
        $agent_id,
        $total_amount,
        $paid_amount,
        $remaining_amount,
        $rent_amount
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert " . strtolower($property_type) . " deposit: " . $stmt->error);
    }
}

/**
 * Insert Payment
 * Records a payment transaction
 */
function insertPayment($conn, $payment_id, $tenant_id, $bed_id, $room_id, $agent_id, $amount, $payment_type, $rental_type, $remarks) {
    error_log("Starting insertPayment with amount: " . $amount);
    
    // Validate amount
    if (!is_numeric($amount) || $amount <= 0) {
        error_log("Invalid payment amount: " . $amount);
        throw new Exception("Invalid payment amount");
    }

    error_log("Payment ID: " . $payment_id);
    error_log("Tenant ID: " . $tenant_id);
    error_log("Amount: " . $amount);
    error_log("Rental Type: " . $rental_type);
    error_log("Bed ID: " . $bed_id);
    error_log("Room ID: " . $room_id);
    
    // Validate rental type
    if (!in_array(strtolower($rental_type), ['bed', 'room'])) {
        error_log("Invalid rental type: " . $rental_type);
        throw new Exception("Invalid rental type");
    }
    
    // Set IDs based on rental type
    $bed_id = strtolower($rental_type) == 'bed' ? $bed_id : null;
    $room_id = strtolower($rental_type) == 'room' ? $room_id : null;
    
    error_log("Final Bed ID: " . $bed_id);
    error_log("Final Room ID: " . $room_id);
    
    $stmt = $conn->prepare("
        INSERT INTO payment (
            PaymentID, TenantID, BedID, RoomID, AgentID,
            Amount, PaymentType, PaymentStatus, DateCreated,
            Month, Year, ChargeFee, Remarks
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Successful', NOW(), ?, ?, ?, ?)
    ");

    $current_date = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
    $payment_month = $current_date->format('M');
    $payment_year = $current_date->format('Y');
    $charge_fee = 1.10;

    $stmt->bind_param(
        "sssssdsssds",
        $payment_id,
        $tenant_id,
        $bed_id,
        $room_id,
        $agent_id,
        $amount,
        $payment_type,
        $payment_month,
        $payment_year,
        $charge_fee,
        $remarks
    );

    if (!$stmt->execute()) {
        error_log("Failed to insert payment: " . $stmt->error);
        throw new Exception("Failed to insert payment: " . $stmt->error);
    }

    error_log("Payment inserted successfully");

    // Insert single payment history record
    insertPaymentHistory($conn, $payment_id, $tenant_id, $agent_id, $amount, $payment_type, $remarks);
}

/**
 * Insert Payment History
 * Maintains a record of all payment transactions
 */
function insertPaymentHistory($conn, $payment_id, $tenant_id, $agent_id, $amount, $payment_type, $remarks) {
    $stmt = $conn->prepare("
        INSERT INTO paymenthistory (
            PaymentHistoryID, TenantID, AgentID,
            Amount, PaymentDate, PaymentType, Remarks
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssdss",
        $payment_id,
        $tenant_id,
        $agent_id,
        $amount,
        $payment_type,
        $remarks
    );

    if (!$stmt->execute()) {
        throw new Exception("Failed to insert payment history: " . $stmt->error);
    }
}

/**
 * Handle First Rent Payment
 * Creates the initial rent payment record when deposit is fully paid
 */
function handleFirstRentPayment($conn, $bookingData) {
    $rent_payment_id = 'RENT' . time() . rand(1000, 9999);
    $advance_rental = $bookingData['payment_info']['advance_rental'];
    
    // Check if rent payment doesn't already exist
    $check_stmt = $conn->prepare("
        SELECT PaymentID 
        FROM payment 
        WHERE TenantID = ? 
        AND PaymentType = 'Rent Payment'
        AND Month = ? 
        AND Year = ?
    ");

    $current_month = date('M');
    $current_year = date('Y');
    
    $check_stmt->bind_param("sss", 
        $bookingData['tenant_info']['tenantID'],
        $current_month,
        $current_year
    );
    
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows === 0) {
        insertPayment(
            $conn,
            $rent_payment_id,
            $bookingData['tenant_info']['tenantID'],
            $bookingData['property_info']['bedID'],
            $bookingData['property_info']['roomID'],
            $bookingData['agent_info']['agentID'],
            $advance_rental,
            'Rent Payment',
            $bookingData['payment_info']['rental_type'],
            "First month rent payment"
        );
    }
}

/**
 * Get Deposit
 * Retrieves deposit record for a tenant
 */
function getDeposit($conn, $tenant_id) {
    $stmt = $conn->prepare("SELECT * FROM deposit WHERE TenantID = ?");
    $stmt->bind_param("s", $tenant_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Handle Rent Payment
 * Processes a rent payment for an existing tenant
 */
function handleRentPayment($conn, $paymentDetails, $flashpayData, $payment_amount) {
    error_log("Starting handleRentPayment");
    error_log("Payment Details: " . print_r($paymentDetails, true));
    error_log("Flashpay Data: " . print_r($flashpayData, true));
    
    try {
        // Get tenant ID from the correct location
        $tenant_id = $flashpayData['tenant_info']['id'];
        
        // Get property IDs based on rental type
        $rental_type = $flashpayData['payment_info']['rental_type'];
        $bed_id = ($rental_type === 'Bed') ? $flashpayData['property_info']['bedID'] : null;
        $room_id = ($rental_type === 'Room') ? $flashpayData['property_info']['roomID'] : null;
        
        // Get agent ID
        $agent_id = $flashpayData['agent_info']['agentID'];
        
        // Get payment month and year from flashpay data or use current date
        $payment_month = $flashpayData['payment_info']['selected_month'] ?: date('M');
        $payment_year = $flashpayData['payment_info']['selected_year'] ?: date('Y');

        // Use the Billplz payment ID directly
        $payment_id = $paymentDetails['id'];
        error_log("Using Billplz Payment ID: " . $payment_id);

        // Start a new transaction
        $conn->begin_transaction();

        // Insert payment record with month and year
        $stmt = $conn->prepare("
            INSERT INTO payment (
                PaymentID, TenantID, BedID, RoomID, AgentID,
                Amount, PaymentType, PaymentStatus, DateCreated,
                Month, Year, ChargeFee, Remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Rent Payment', 'Successful', NOW(), ?, ?, 1.10, ?)
        ");
        
        $remarks = sprintf("Rent payment for %s %s", $payment_month, $payment_year);
        
        $stmt->bind_param(
            "sssssdsss",
            $payment_id,
            $tenant_id,
            $bed_id,
            $room_id,
            $agent_id,
            $payment_amount,
            $payment_month,
            $payment_year,
            $remarks
        );
        
        if (!$stmt->execute()) {
            error_log("Failed to insert payment record: " . $stmt->error);
            throw new Exception("Failed to insert payment record: " . $stmt->error);
        }
        
        error_log("Payment record inserted successfully with ID: " . $payment_id);
        
        // Update property status if needed
        if ($rental_type === 'Room' && $room_id) {
            updatePropertyStatus($conn, $tenant_id, 'Active', $room_id, 'Room');
        } else if ($rental_type === 'Bed' && $bed_id) {
            updatePropertyStatus($conn, $tenant_id, 'Active', $bed_id, 'Bed');
        }
        
        // Insert payment history record
        insertPaymentHistory($conn, $payment_id, $tenant_id, $agent_id, $payment_amount, 'Rent Payment', $remarks);
        
        // Commit the transaction immediately after inserting all records
        $conn->commit();
        error_log("Transaction committed successfully");
        
        // Add a small delay to ensure the commit is complete
        usleep(500000); // 0.5 second delay
        
        // Return the payment ID for receipt generation
        return $payment_id;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in handleRentPayment: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle Deposit Payment
 * Processes a deposit payment for an existing tenant
 */
function handleDepositPayment($conn, $paymentDetails, $flashpayData, $payment_amount, $depositData) {
    try {
        $tenant_id = $flashpayData['tenant_info']['id'];
        $rental_type = $flashpayData['payment_info']['rental_type'];
        $bed_id = ($rental_type === 'Bed') ? $flashpayData['property_info']['bedID'] : null;
        $room_id = ($rental_type === 'Room') ? $flashpayData['property_info']['roomID'] : null;
        $agent_id = $flashpayData['agent_info']['agentID'];

        // Get the original deposit amount (should be 2x rent)
        $rent_amount = $depositData['BedRentAmount'] ?? $depositData['RoomRentAmount'];
        $original_deposit_amount = $rent_amount * 2; // Should be RM500 (2x RM250)

        if ($depositData) {
            // Calculate how much of the payment goes to deposit
            $deposit_remaining = $depositData['RemainingAmount'];
            $deposit_payment = min($payment_amount, $deposit_remaining);
            $rent_payment = $payment_amount - $deposit_payment;

            // Update deposit record
            $new_remaining = $deposit_remaining - $deposit_payment;
            $new_paid = $depositData['PaidAmount'] + $deposit_payment;
            
            $stmt = $conn->prepare("
                UPDATE deposit 
                SET PaidAmount = ?, 
                    RemainingAmount = ? 
                WHERE TenantID = ?
            ");
            $stmt->bind_param("dds", $new_paid, $new_remaining, $tenant_id);
            $stmt->execute();

            // If there's a rent payment portion, create a rent payment record
            if ($rent_payment > 0) {
                insertPayment(
                    $conn,
                    $paymentDetails['id'],
                    $tenant_id,
                    $bed_id,
                    $room_id,
                    $agent_id,
                    $rent_payment,
                    'Rent Payment',
                    $rental_type,
                    "First month rent payment"
                );
            }

            // Update statuses if deposit is fully paid
            if ($new_remaining <= 0) {
                if ($rental_type === 'Room') {
                    updatePropertyStatus($conn, $tenant_id, 'Rented', $room_id, 'Room');
                } else {
                    updatePropertyStatus($conn, $tenant_id, 'Rented', $bed_id, 'Bed');
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error in handleDepositPayment: " . $e->getMessage());
        throw $e;
    }
}

// Add helper function for data validation and extraction
function validateAndExtract($data, $path, $optional = false) {
    $parts = explode('.', $path);
    $current = $data;
    
    foreach ($parts as $part) {
        if (!isset($current[$part])) {
            if ($optional) {
                return null;
            }
            throw new Exception("Missing required field: $path");
        }
        $current = $current[$part];
    }
    
    return $current;
}

function handleLumpsumPayment($conn, $billplz_id, $payment_date, $lumpsum_data) {
    error_log("Starting handleLumpsumPayment with data: " . print_r($lumpsum_data, true));
    
    try {
        // Get the details directly from lumpsum_data
        $details = $lumpsum_data['details'] ?? null;
        if (empty($details) || !is_array($details)) {
            throw new Exception("Invalid payment details structure");
        }

        // Begin transaction
        $conn->begin_transaction();

        $payment_records = [];
        $total_amount = 0;

        // Process each tenant payment
        foreach ($details as $payment) {
            if (empty($payment['tenant_id']) || empty($payment['amount'])) {
                throw new Exception("Missing required payment data");
            }

            // Get tenant details
            $stmt = $conn->prepare("SELECT t.*, a.AgentID, a.AgentName,
                                  p.PropertyName, u.UnitNo,
                                  COALESCE(b.BedNo, 'N/A') as BedNo,
                                  COALESCE(b.BedID, 'N/A') as BedID,
                                  COALESCE(r.RoomNo, 'N/A') as RoomNo,
                                  COALESCE(r.RoomID, 'N/A') as RoomID,
                                  CASE 
                                      WHEN t.BedID IS NOT NULL THEN CONCAT('Bed ', b.BedNo)
                                      WHEN t.RoomID IS NOT NULL THEN CONCAT('Room ', r.RoomNo)
                                  END as Location
                                  FROM tenant t 
                                  JOIN agent a ON t.AgentID = a.AgentID
                                  LEFT JOIN bed b ON t.BedID = b.BedID
                                  LEFT JOIN room r ON t.RoomID = r.RoomID OR b.RoomID = r.RoomID
                                  LEFT JOIN unit u ON r.UnitID = u.UnitID
                                  LEFT JOIN property p ON u.PropertyID = p.PropertyID 
                                  WHERE t.TenantID = ?");
            $stmt->bind_param("s", $payment['tenant_id']);
            $stmt->execute();
            $tenant_data = $stmt->get_result()->fetch_assoc();

            if (!$tenant_data) {
                throw new Exception("Tenant data not found for ID: " . $payment['tenant_id']);
            }

            $start_date = new DateTime($lumpsum_data['start_month'] . '-01');
            $tenant_total = 0;
            $months_paid = [];

            // Create payment records for each month
            $months = (int)$lumpsum_data['months'];
            for ($i = 0; $i < $months; $i++) {
                $payment_id = $billplz_id . '_' . $payment['tenant_id'] . '_' . $i;
                $month = $start_date->format('M');
                $year = $start_date->format('Y');

                // Insert payment record
                $stmt = $conn->prepare("INSERT INTO payment (
                    PaymentID, TenantID, RoomID, BedID, AgentID,
                    DateCreated, Month, Year, Amount,
                    PaymentType, PaymentStatus, Remarks, ChargeFee
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    NOW(), ?, ?, ?,
                    'Rent Payment', 'Successful', ?, 1.10
                )");

                $remarks = "Rent payment for {$month} {$year} (Bill ID: {$billplz_id})";

                $stmt->bind_param(
                    "sssssssds",
                    $payment_id,
                    $tenant_data['TenantID'],
                    $tenant_data['RoomID'],
                    $tenant_data['BedID'],
                    $tenant_data['AgentID'],
                    $month,
                    $year,
                    $payment['amount'],
                    $remarks
                );

                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert payment record: " . $stmt->error);
                }

                $months_paid[] = $month . ' ' . $year;
                $tenant_total += $payment['amount'];

                // Move to next month
                $start_date->modify('+1 month');
            }

            // Insert payment history record
            $history_payment_id = 'HIST' . time() . rand(1000, 9999);
            $history_stmt = $conn->prepare("
                INSERT INTO paymenthistory (
                    PaymentHistoryID, TenantID, AgentID,
                    Amount, PaymentDate, PaymentType, Remarks
                ) VALUES (?, ?, ?, ?, NOW(), 'Rent Payment', ?)
            ");

            $history_remarks = "Rent payment for {$months} months";

            $history_stmt->bind_param(
                "sssds",
                $history_payment_id,
                $tenant_data['TenantID'],
                $tenant_data['AgentID'],
                $tenant_total,
                $history_remarks
            );

            if (!$history_stmt->execute()) {
                throw new Exception("Failed to insert payment history record: " . $history_stmt->error);
            }

            // Store payment record for receipt
            $payment_records[] = [
                'tenant_name' => $tenant_data['TenantName'],
                'tenant_email' => $tenant_data['TenantEmail'],
                'tenant_phone' => $tenant_data['TenantPhoneNo'],
                'property_name' => $tenant_data['PropertyName'],
                'unit_no' => $tenant_data['UnitNo'],
                'location' => $tenant_data['Location'],
                'amount' => $tenant_total,
                'months_paid' => $months_paid,
                'payment_date' => date('Y-m-d H:i:s'),
                'payment_id' => $history_payment_id
            ];

            $total_amount += $tenant_total;
        }

        // Commit transaction
        $conn->commit();

        // After successful transaction, store receipt data in session
        $payment_data = [
            'PaymentID' => $billplz_id,
            'Amount' => $total_amount,
            'DateCreated' => date('Y-m-d H:i:s'),
            'AgentName' => $tenant_data['AgentName'],
            'TenantName' => $tenant_data['TenantName'],
            'BedID' => $tenant_data['BedID'],
            'BedNo' => $tenant_data['BedNo'],
            'RoomID' => $tenant_data['RoomID'],
            'RoomNo' => $tenant_data['RoomNo'],
            'UnitNo' => $tenant_data['UnitNo'],
            'PropertyName' => $tenant_data['PropertyName']
        ];

        $_SESSION['payment_data'] = $payment_data;
        return true;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in handleLumpsumPayment: " . $e->getMessage());
        throw $e;
    }
}

function handleBookingPayment($conn, $billplz_id, $billplz_paid_at, $payment_details) {
    try {
        // Get booking data from session
        $booking_data = $_SESSION['payment_success']['metadata']['booking_data'] ?? null;
        if (!$booking_data) {
            error_log("Session payment_success data: " . print_r($_SESSION['payment_success'], true));
            throw new Exception("Booking data not found in payment details");
        }

        // Extract required data
        $tenant_id = $booking_data['tenant_info']['tenantID'];
        $bed_id = $booking_data['property_info']['bedID'] ?? null;
        $room_id = $booking_data['property_info']['roomID'] ?? null;
        $agent_id = $booking_data['agent_info']['agentID'];
        $rental_type = !empty($bed_id) ? 'Bed' : 'Room';
        
        // Get rent amount and calculate deposit
        $rent_amount = $booking_data['payment_info']['amount']; // Base rent amount
        $processing_fee = $booking_data['payment_info']['processing_fee']; // Usually 50
        $total_deposit = ($rent_amount * 2) + $processing_fee; // Correct deposit calculation
        
        // Total payment made
        $payment_amount = $booking_data['payment_info']['total_amount'];
        
        // Begin transaction
        $conn->begin_transaction();

        // 1. Insert tenant record
        insertTenant($conn, $booking_data);

        // 2. Insert deposit record with correct calculation
        if ($rental_type === 'Bed') {
            insertDeposit($conn, $tenant_id, $bed_id, $agent_id, $total_deposit, $payment_amount, 0, 'Bed');
        } else {
            insertDeposit($conn, $tenant_id, $room_id, $agent_id, $total_deposit, $payment_amount, 0, 'Room');
        }

        // 3. Insert first payment record for deposit
        $deposit_payment_id = $billplz_id;
        $deposit_remarks = "Initial Deposit Payment (Bill ID: {$billplz_id})";
        
        insertPayment(
            $conn,
            $deposit_payment_id,
            $tenant_id,
            $bed_id,
            $room_id,
            $agent_id,
            $payment_amount,
            'Deposit Payment',
            $rental_type,
            $deposit_remarks
        );

        // Insert payment history for deposit
        insertPaymentHistory(
            $conn,
            $deposit_payment_id,
            $tenant_id,
            $agent_id,
            $payment_amount,
            'Deposit Payment',
            $deposit_remarks,
            date('Y-m-d H:i:s')
        );

        // 4. Insert second payment record for first month's rent
        $rent_payment_id = $billplz_id . '_RENT';
        $rent_remarks = "First Month Rent Payment (Bill ID: {$billplz_id})";
        
        insertPayment(
            $conn,
            $rent_payment_id,
            $tenant_id,
            $bed_id,
            $room_id,
            $agent_id,
            $rent_amount,
            'Rent Payment',
            $rental_type,
            $rent_remarks
        );

        // Insert payment history for rent
        insertPaymentHistory(
            $conn,
            $rent_payment_id,
            $tenant_id,
            $agent_id,
            $rent_amount,
            'Rent Payment',
            $rent_remarks,
            date('Y-m-d H:i:s')
        );

        // 5. Update tenant and property status to Rented
        $stmt = $conn->prepare("
            UPDATE tenant 
            SET TenantStatus = 'Rented', 
                UpdatedAt = NOW() 
            WHERE TenantID = ?
        ");
        $stmt->bind_param("s", $tenant_id);
        $stmt->execute();

        // Update property status
        if ($rental_type === 'Bed') {
            $stmt = $conn->prepare("
                UPDATE bed 
                SET BedStatus = 'Rented', 
                    UpdatedAt = NOW() 
                WHERE BedID = ?
            ");
            $stmt->bind_param("s", $bed_id);
        } else {
            $stmt = $conn->prepare("
                UPDATE room 
                SET RoomStatus = 'Rented', 
                    UpdatedAt = NOW() 
                WHERE RoomID = ?
            ");
            $stmt->bind_param("s", $room_id);
        }
        $stmt->execute();

        // Commit transaction
        $conn->commit();

        // Format payment data for receipt
        $_SESSION['payment_data'] = [
            'PaymentID' => $billplz_id,
            'Amount' => $payment_amount,
            'DateCreated' => date('Y-m-d H:i:s'),
            'AgentName' => $booking_data['agent_info']['agentName'],
            'TenantName' => $booking_data['tenant_info']['tenantName'],
            'BedID' => $bed_id,
            'BedNo' => $booking_data['property_info']['bedNo'],
            'RoomID' => $room_id,
            'RoomNo' => $booking_data['property_info']['roomNo'],
            'UnitNo' => $booking_data['property_info']['unitNo'],
            'PaymentType' => 'Booking Fee'
        ];

        return true;

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in handleBookingPayment: " . $e->getMessage());
        throw $e;
    }
}

function handleFlashPayment($conn, $billplz_id, $billplz_paid_at, $payment_details) {
    try {
        // Get the amount and convert from cents to ringgit if needed
        $payment_amount = $payment_details['amount'] ?? 0;
        if ($payment_amount > 1000) { // If amount is in cents
            $payment_amount = $payment_amount / 100;
        }

        // Get metadata
        $metadata = $payment_details['metadata'] ?? null;
        if (!$metadata) {
            throw new Exception("Missing payment metadata");
        }

        // Get reference data
        $tenant_id = $payment_details['reference_1'] ?? null;
        if (!$tenant_id) {
            throw new Exception("Missing tenant ID");
        }

        // Extract property and payment info
        $property_info = $metadata['property_info'] ?? null;
        $payment_info = $metadata['payment_info'] ?? null;
        
        if (!$property_info || !$payment_info) {
            throw new Exception("Missing required payment information");
        }

        $bed_id = $property_info['bedID'] ?? null;
        $payment_month = $payment_info['selected_month'] ?? date('M');
        $payment_year = $payment_info['selected_year'] ?? date('Y');
        $rental_type = $payment_info['rental_type'] ?? 'Bed';

        // If month/year are empty, use current date
        if (empty($payment_month) || empty($payment_year)) {
            $payment_month = date('M');
            $payment_year = date('Y');
        }

        // Insert payment record
        $remarks = "Rent payment for {$payment_month} {$payment_year} (Bill ID: {$billplz_id})";
        
        $result = insertPayment(
            $conn,
            $billplz_id,
            $tenant_id,
            $bed_id,
            null, // room_id
            null, // agent_id will be fetched from tenant record
            $payment_amount,
            'Rent Payment',
            $rental_type,
            $remarks
        );

        if (!$result) {
            throw new Exception("Failed to insert payment record");
        }

        return true;

    } catch (Exception $e) {
        error_log("Error in handleFlashPayment: " . $e->getMessage());
        throw $e;
    }
}

function handleIncompleteDeposit($conn, $billplz_id, $billplz_paid_at, $flashpay_data, $payment_amount, $deposit_status) {
    try {
        // Calculate payment split
        $deposit_payment = min($payment_amount, $deposit_status['remaining_amount']);
        $rent_payment = $payment_amount - $deposit_payment;
        
        $tenant_id = $flashpay_data['tenant_info']['id'];
        $rental_type = $flashpay_data['payment_info']['rental_type'];
        $bed_id = ($rental_type === 'Bed') ? $flashpay_data['property_info']['bedID'] : null;
        $room_id = ($rental_type === 'Room') ? $flashpay_data['property_info']['roomID'] : null;
        $agent_id = $flashpay_data['agent_info']['agentID'];

        // Begin transaction
        $conn->begin_transaction();

        // Update deposit record
        if ($deposit_payment > 0) {
            $stmt = $conn->prepare("
                UPDATE deposit 
                SET PaidAmount = PaidAmount + ?,
                    RemainingAmount = RemainingAmount - ?,
                    UpdatedAt = NOW()
                WHERE TenantID = ?
            ");
            $stmt->bind_param("dds", $deposit_payment, $deposit_payment, $tenant_id);
            $stmt->execute();

            // Insert deposit payment record
            $deposit_payment_id = $billplz_id . '_DEP';
            insertPayment(
                $conn,
                $deposit_payment_id,
                $tenant_id,
                $bed_id,
                $room_id,
                $agent_id,
                $deposit_payment,
                'Deposit Payment',
                $rental_type,
                "Remaining deposit payment (Bill ID: {$billplz_id})"
            );

            insertPaymentHistory(
                $conn,
                $deposit_payment_id,
                $tenant_id,
                $agent_id,
                $deposit_payment,
                'Deposit Payment',
                "Remaining deposit payment"
            );
        }

        // Handle rent payment portion if any
        $rent_payment_details = null;
        if ($rent_payment > 0) {
            $rent_payment_id = $billplz_id . '_RENT';
            $payment_month = $flashpay_data['payment_info']['selected_month'] ?? date('M');
            $payment_year = $flashpay_data['payment_info']['selected_year'] ?? date('Y');
            $rent_remarks = "Rent payment for {$payment_month} {$payment_year} (Bill ID: {$billplz_id})";

            insertPayment(
                $conn,
                $rent_payment_id,
                $tenant_id,
                $bed_id,
                $room_id,
                $agent_id,
                $rent_payment,
                'Rent Payment',
                $rent_remarks
            );

            insertPaymentHistory(
                $conn,
                $rent_payment_id,
                $tenant_id,
                $agent_id,
                $rent_payment,
                'Rent Payment',
                $rent_remarks
            );

            $rent_payment_details = [
                'payment_id' => $rent_payment_id,
                'amount' => $rent_payment,
                'type' => 'Rent Payment',
                'month' => $payment_month,
                'year' => $payment_year,
                'remarks' => $rent_remarks
            ];
        }

        // Commit transaction
        $conn->commit();

        // Return complete receipt data
        return [
            'payment_type' => 'Mixed Payment',
            'payment_id' => $billplz_id,
            'payment_date' => $billplz_paid_at,
            'billplz_id' => $billplz_id,
            
            // Payment breakdown
            'deposit_amount' => $deposit_payment,
            'rent_amount' => $rent_payment,
            'total_amount' => $payment_amount,
            'remaining_deposit' => $deposit_status['remaining_amount'] - $deposit_payment,
            
            // Tenant details
            'tenant_name' => $flashpay_data['tenant_info']['name'],
            'tenant_email' => $flashpay_data['tenant_info']['email'],
            'tenant_phone' => $flashpay_data['tenant_info']['phone'],
            
            // Property details
            'property_details' => [
                'rental_type' => $rental_type,
                'bed_id' => $bed_id,
                'bed_no' => $flashpay_data['property_info']['bedNo'] ?? '',
                'room_id' => $room_id,
                'room_no' => $flashpay_data['property_info']['roomNo'] ?? '',
                'unit_no' => $flashpay_data['property_info']['unitNo'] ?? ''
            ],
            
            // Payment details
            'payment_details' => [
                'deposit' => $deposit_payment > 0 ? [
                    'payment_id' => $billplz_id . '_DEP',
                    'amount' => $deposit_payment,
                    'type' => 'Deposit Payment',
                    'remarks' => "Remaining deposit payment (Bill ID: {$billplz_id})"
                ] : null,
                'rent' => $rent_payment_details
            ]
        ];

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        error_log("Error in handleIncompleteDeposit: " . $e->getMessage());
        throw $e;
    }
}

function checkDepositStatus($conn, $tenant_id) {
    $stmt = $conn->prepare("
        SELECT 
            DepositID,
            DepositAmount,
            PaidAmount,
            RemainingAmount,
            PaymentMadeDate,
            BedRentAmount,
            RoomID,
            BedID
        FROM deposit 
        WHERE TenantID = ?
        ORDER BY PaymentMadeDate DESC
        LIMIT 1
    ");
    
    $stmt->bind_param("s", $tenant_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to check deposit status: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $deposit = $result->fetch_assoc();

    if (!$deposit) {
        throw new Exception("No deposit record found for tenant: " . $tenant_id);
    }

    return [
        'is_fully_paid' => $deposit['RemainingAmount'] <= 0,
        'remaining_amount' => $deposit['RemainingAmount'],
        'total_amount' => $deposit['DepositAmount'],
        'paid_amount' => $deposit['PaidAmount'],
        'deposit_id' => $deposit['DepositID'],
        'payment_date' => $deposit['PaymentMadeDate'],
        'bed_rent_amount' => $deposit['BedRentAmount'],
        'room_id' => $deposit['RoomID'],
        'bed_id' => $deposit['BedID']
    ];
}

function clearPaymentSession() {
    // Don't clear receipt_data as it's needed for the receipt
    unset($_SESSION['payment_details']);
    unset($_SESSION['billplz_bill_id']);
    unset($_SESSION['lumpsum_payment']);
    unset($_SESSION['booking_data']);
    unset($_SESSION['flashpay_data']);
}

function getErrorRedirect() {
    if (isset($_SESSION['lumpsum_payment'])) {
        return "../module-payment/successpayment.php";
    } elseif (isset($_SESSION['booking_data'])) {
        return "../module-payment/successpayment.php";
    } else {
        return "../module-payment/successpayment.php";
    }
}

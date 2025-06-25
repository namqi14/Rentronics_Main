<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';
require_once __DIR__ . '/lib/API.php';
require_once __DIR__ . '/lib/Connect.php';
require_once __DIR__ . '/configuration.php';

// Add debug logging
error_log('Session data in billplzpost.php: ' . print_r($_SESSION, true));
error_log('POST data in billplzpost.php: ' . print_r($_POST, true));

// At the start of the file, define error redirect URLs
$error_redirects = [
    'lumpsum' => '../../module-property/agent/lumpsumpayment.php',
    'booking' => '../../module-property/tenant/bookingcheckout.php',
    'flashpay' => '../../module-property/tenant/flashpay.php'
];

// Function to handle error redirection
function handleErrorRedirect($error_message, $payment_type) {
    // Log the error
    error_log("Payment Error ({$payment_type}): " . $error_message);
    
    // Store error in session
    $_SESSION['payment_error'] = [
        'message' => $error_message,
        'type' => $payment_type
    ];
    
    // Always redirect to successpayment.php
    header('Location: ../../module-payment/successpayment.php?payment=failed&error=' . urlencode($error_message));
    exit;
}

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        handleErrorRedirect('Security token is missing. Please try again.', $payment_type);
    }
    
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        handleErrorRedirect('Security validation failed. Please try again.', $payment_type);
    }
}

// Generate new CSRF token for next request
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Validate booking data
if (isset($_SESSION['booking_data'])) {
    $required_fields = [
        'tenant_info' => ['tenantName', 'tenantEmail', 'tenantPhoneNo'],
        'property_info' => ['bedID', 'bedNo'],
    ];

    $missing_fields = [];
    foreach ($required_fields as $section => $fields) {
        foreach ($fields as $field) {
            if (empty($_SESSION['booking_data'][$section][$field])) {
                $missing_fields[] = "$section.$field";
            }
        }
    }

    if (!empty($missing_fields)) {
        handleErrorRedirect(
            'Missing required booking information: ' . implode(', ', $missing_fields),
            'booking'
        );
    }
}

// Update session with POST data if available
if (isset($_POST['selected_month']) && isset($_POST['selected_year']) && isset($_SESSION['flashpay_data'])) {
    $_SESSION['flashpay_data']['payment_info']['selected_month'] = $_POST['selected_month'];
    $_SESSION['flashpay_data']['payment_info']['selected_year'] = $_POST['selected_year'];
}

use Billplz\Minisite\API;
use Billplz\Minisite\Connect;

// Add input validation function
function validatePaymentData($data) {
    $errors = [];
    if (empty($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
        $errors[] = "Invalid payment amount";
    }
    if (empty($data['payer_email']) || !filter_var($data['payer_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address";
    }
    if (empty($data['payer_phone'])) {
        $errors[] = "Phone number is required";
    }
    return $errors;
}

// Optimize createBillplzPayment function
function createBillplzPayment($paymentData) {
    global $api_key, $is_sandbox, $collection_id, $redirect_url, $callback_url;

    // Validate payment data
    $errors = validatePaymentData($paymentData);
    if (!empty($errors)) {
        throw new Exception('Invalid payment data: ' . implode(', ', $errors));
    }

    // Extract and sanitize payment details
    $payer_name = htmlspecialchars($paymentData['payer_name'] ?? '');
    $payer_email = filter_var($paymentData['payer_email'], FILTER_SANITIZE_EMAIL);
    $payer_phone = preg_replace('/[^0-9+]/', '', $paymentData['payer_phone']);
    $amount = floatval($paymentData['amount']);
    $description = htmlspecialchars($paymentData['description'] ?? '');
    $reference_id = htmlspecialchars($paymentData['reference_id'] ?? '');
    $payment_type = htmlspecialchars($paymentData['payment_type'] ?? '');

    // Convert amount to cents for Billplz
    $amount_in_cents = (int)($amount * 100);

    // Initialize Billplz connection with error handling
    try {
        $connect = new Connect($api_key);
        $connect->setStaging($is_sandbox);
        $billplz = new API($connect);

        // Create Bill with optimized parameters
        $billParams = [
            'collection_id' => $collection_id,
            'email' => $payer_email,
            'mobile' => $payer_phone,
            'name' => $payer_name,
            'amount' => $amount_in_cents,
            'description' => $description,
            'reference_1_label' => 'Reference ID',
            'reference_1' => $reference_id,
            'reference_2_label' => 'Payment Type',
            'reference_2' => $payment_type,
            'redirect_url' => $redirect_url,
            'callback_url' => $callback_url
        ];

        // Add additional metadata if available
        if (isset($paymentData['metadata'])) {
            $billParams['metadata'] = $paymentData['metadata'];
        }

        list($header, $body) = $billplz->createBill($billParams);

        // Validate response
        if ($header !== 200) {
            throw new Exception('Billplz API Error: ' . ($body['error']['message'] ?? 'Unknown error'));
        }

        return $billplz->toArray([$header, $body]);
    } catch (Exception $e) {
        error_log('Billplz Error: ' . $e->getMessage());
        throw new Exception('Payment processing failed: ' . $e->getMessage());
    }
}

// Optimize payment data preparation
function preparePaymentData($sessionData, $postData) {
    if (isset($sessionData['booking_data'])) {
        return prepareBookingPaymentData($sessionData['booking_data'], $postData);
    } elseif (isset($sessionData['flashpay_data'])) {
        return prepareFlashpayPaymentData($sessionData['flashpay_data'], $postData);
    } elseif (isset($sessionData['lumpsum_payment'])) {
        return prepareLumpsumPaymentData($sessionData['lumpsum_payment']);
    }
    throw new Exception('Invalid payment type or missing payment data');
}

function prepareBookingPaymentData($bookingData, $postData) {
    // Validate required fields
    $required_fields = [
        'tenant_info' => ['tenantName', 'tenantEmail', 'tenantPhoneNo'],
        'property_info' => ['bedID', 'bedNo', 'unitNo', 'roomNo'],
        'agent_info' => ['agentID', 'agentName'],
        'payment_info' => ['deposit', 'advance_rental', 'processing_fee', 'total_amount', 'rental_type']
    ];

    validateRequiredFields($bookingData, $required_fields);

    // Validate amount
    $amount = floatval($postData['insert_amount'] ?? 0);
    if ($amount <= 0) {
        throw new Exception('Invalid payment amount');
    }

    // Get rental type
    $rental_type = strtolower($bookingData['payment_info']['rental_type'] ?? 'bed');
    error_log("Booking Payment Data - Rental Type: " . $rental_type);

    return [
        'payer_name' => $bookingData['tenant_info']['tenantName'],
        'payer_email' => $bookingData['tenant_info']['tenantEmail'],
        'payer_phone' => $bookingData['tenant_info']['tenantPhoneNo'],
        'amount' => $amount,
        'description' => sprintf(
            "Rental Payment for Unit %s Room %s Bed %s",
            $bookingData['property_info']['unitNo'],
            $bookingData['property_info']['roomNo'],
            $bookingData['property_info']['bedNo']
        ),
        'reference_id' => $bookingData['property_info']['bedID'],
        'payment_type' => 'Booking Fee',
        'metadata' => [
            'agent_info' => $bookingData['agent_info'],
            'payment_details' => $bookingData['payment_info'],
            'rental_type' => $rental_type
        ]
    ];
}

function prepareFlashpayPaymentData($flashpayData, $postData) {
    // Get rental type
    $rentalType = $flashpayData['payment_info']['rental_type'] ?? '';
    
    // Define base required fields
    $required_fields = [
        'tenant_info' => ['name', 'email', 'phone'],
        'payment_info' => ['rental_type']
    ];

    // Add property fields based on rental type
    if ($rentalType === 'Bed') {
        $required_fields['property_info'] = ['bedID', 'bedNo', 'unitNo'];
    } else if ($rentalType === 'Room') {
        $required_fields['property_info'] = ['roomID', 'roomNo', 'unitNo'];
    } else {
        throw new Exception('Invalid rental type');
    }

    validateRequiredFields($flashpayData, $required_fields);

    // Prepare payment data
    $paymentData = [
        'payer_name' => $flashpayData['tenant_info']['name'],
        'payer_email' => $flashpayData['tenant_info']['email'],
        'payer_phone' => $flashpayData['tenant_info']['phone'],
        'amount' => floatval($postData['amount'] ?? 0),
        'description' => sprintf(
            "Payment for Unit %s %s %s",
            $flashpayData['property_info']['unitNo'],
            $rentalType === 'Room' ? 'Room' : 'Bed',
            $rentalType === 'Room' ? 
                $flashpayData['property_info']['roomNo'] : 
                $flashpayData['property_info']['bedNo']
        ),
        'reference_id' => $flashpayData['tenant_info']['id'],
        'payment_type' => 'flashpay',
        'metadata' => [
            'property_info' => $flashpayData['property_info'],
            'payment_info' => $flashpayData['payment_info'],
            'payment_type' => 'flashpay'
        ]
    ];

    error_log('Prepared FlashPay payment data: ' . print_r($paymentData, true));
    return $paymentData;
}

function validateRequiredFields($data, $required_fields) {
    $missing_fields = [];
    foreach ($required_fields as $section => $fields) {
        if (!isset($data[$section])) {
            $missing_fields[] = $section;
            continue;
        }
        foreach ($fields as $field) {
            if (empty($data[$section][$field]) && $data[$section][$field] !== '0') {
                $missing_fields[] = "$section.$field";
            }
        }
    }
    if (!empty($missing_fields)) {
        error_log('Missing fields: ' . implode(', ', $missing_fields));
        error_log('Data received: ' . print_r($data, true));
        
        // Determine payment type based on data structure
        $payment_type = 'flashpay';
        if (isset($data['tenant_info']['tenantName'])) {
            $payment_type = 'booking';
        } elseif (isset($data['payment_id'])) {
            $payment_type = 'lumpsum';
        }
        
        handleErrorRedirect(
            'Missing required fields: ' . implode(', ', $missing_fields),
            $payment_type
        );
    }
}

function prepareLumpsumPaymentData($lumpsumData) {
    // Validate required fields
    if (empty($lumpsumData['payment_id']) || empty($lumpsumData['amount']) || 
        empty($lumpsumData['details']) || empty($lumpsumData['description'])) {
        throw new Exception('Missing required lumpsum payment data');
    }

    // Get the first tenant's details from the payment details
    $firstTenant = null;
    if (!empty($lumpsumData['details'][0]['tenant_id'])) {
        global $conn;
        $stmt = $conn->prepare("SELECT TenantName, TenantEmail, TenantPhoneNo FROM tenant WHERE TenantID = ?");
        $stmt->bind_param("s", $lumpsumData['details'][0]['tenant_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $firstTenant = $result->fetch_assoc();
        $stmt->close();
    }

    if (!$firstTenant) {
        throw new Exception('Could not find tenant information');
    }

    return [
        'payer_name' => $firstTenant['TenantName'],
        'payer_email' => $firstTenant['TenantEmail'],
        'payer_phone' => $firstTenant['TenantPhoneNo'],
        'amount' => $lumpsumData['amount'],
        'description' => $lumpsumData['description'],
        'reference_id' => $lumpsumData['payment_id'],
        'payment_type' => 'Lumpsum Rent',
        'metadata' => [
            'payment_details' => $lumpsumData['details'],
            'start_month' => $lumpsumData['start_month'],
            'months' => $lumpsumData['months']
        ]
    ];
}

// At the start of the file, determine payment type
$payment_type = '';
if (isset($_SESSION['lumpsum_payment'])) {
    $payment_type = 'lumpsum';
} elseif (isset($_SESSION['booking_data'])) {
    $payment_type = 'booking';
} elseif (isset($_SESSION['flashpay_data'])) {
    $payment_type = 'flashpay';
} else {
    handleErrorRedirect('Invalid payment session data', 'unknown');
}

error_log('Payment type detected: ' . $payment_type);

// Use the redirect_url from configuration.php
$redirect_url = $websiteurl . '/module-payment/billplz/redirect.php';
error_log('Using redirect URL: ' . $redirect_url);

// Main payment processing
try {
    // Prepare payment data based on payment type
    if (isset($_SESSION['lumpsum_payment'])) {
        $paymentData = prepareLumpsumPaymentData($_SESSION['lumpsum_payment']);
    } else {
        $paymentData = preparePaymentData($_SESSION, $_POST);
    }
    
    // Create payment
    $response = createBillplzPayment($paymentData);
    
    if ($response[0] === 200) {
        // Store payment details in session
        $_SESSION['billplz_bill_id'] = $response[1]['id'];
        $_SESSION['payment_details'] = [
            'id' => $response[1]['id'],
            'amount' => $paymentData['amount'] * 100,
            'description' => $paymentData['description'],
            'reference_1' => $paymentData['reference_id'],
            'reference_2' => $paymentData['payment_type'],
            'metadata' => $paymentData['metadata']
        ];

        // For lumpsum payments, store the payment details in session for later processing
        if (isset($_SESSION['lumpsum_payment'])) {
            $_SESSION['payment_details']['metadata'] = [
                'payment_details' => $_SESSION['lumpsum_payment']['details'],
                'start_month' => $_SESSION['lumpsum_payment']['start_month'],
                'months' => $_SESSION['lumpsum_payment']['months']
            ];
        }
        
        // When storing payment success data, use the correct type and metadata
        $_SESSION['payment_success'] = [
            'type' => $payment_type,
            'billplz_id' => $response[1]['id'],
            'payment_date' => date('Y-m-d H:i:s'),
            'metadata' => [
                'payment_details' => $paymentData
            ]
        ];
        
        // Add specific data based on payment type
        switch ($payment_type) {
            case 'lumpsum':
                $_SESSION['payment_success']['metadata']['lumpsum_data'] = $_SESSION['lumpsum_payment'];
                break;
            case 'booking':
                $_SESSION['payment_success']['metadata']['booking_data'] = $_SESSION['booking_data'];
                break;
            case 'flashpay':
                $_SESSION['payment_success']['metadata']['flashpay_data'] = $_SESSION['flashpay_data'];
                break;
        }
        
        // Then redirect to Billplz
        header('Location: ' . $response[1]['url']);
        exit;
    } else {
        throw new Exception('Failed to create bill: ' . ($response[1]['error']['message'] ?? 'Unknown error'));
    }

} catch (Exception $e) {
    // Determine payment type from session
    $payment_type = '';
    if (isset($_SESSION['lumpsum_payment'])) {
        $payment_type = 'lumpsum';
    } elseif (isset($_SESSION['booking_data'])) {
        $payment_type = 'booking';
    } elseif (isset($_SESSION['flashpay_data'])) {
        $payment_type = 'flashpay';
    }

    handleErrorRedirect($e->getMessage(), $payment_type);
}
?>


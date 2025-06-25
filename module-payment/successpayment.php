<?php
session_start();
require_once __DIR__ . '/../module-auth/dbconnection.php';
require_once 'PaymentHandler.php';

// Set timezone to Kuala Lumpur
date_default_timezone_set('Asia/Kuala_Lumpur');

// Debug logging
error_log("Session data: " . print_r($_SESSION, true));
error_log("GET data: " . print_r($_GET, true));
error_log("Payment success data: " . print_r($_SESSION['payment_success'] ?? [], true));
error_log("Flashpay data: " . print_r($_SESSION['flashpay_data'] ?? [], true));
error_log("Payment details: " . print_r($_SESSION['payment_details'] ?? [], true));

// Check for error parameter first
if (isset($_GET['error'])) {
    $_SESSION['payment_error'] = ['message' => $_GET['error']];
    include 'error.php';
    exit();
}

try {
    $paymentHandler = new PaymentHandler($conn);

    // Get and validate Billplz response data
    $billplz_data = $_GET['billplz'] ?? null;
    
    if ($billplz_data && isset($billplz_data['paid']) && $billplz_data['paid'] === 'true') {
        $payment_type = $_SESSION['payment_success']['type'] ?? null;
        $payment_details = $_SESSION['payment_details'] ?? null;
        
        if (!$payment_type || !$payment_details) {
            throw new Exception("Missing payment data in session");
        }

        // Process payment
        $result = $paymentHandler->handlePayment(
            $payment_type,
            $payment_details,
            $billplz_data['id'],
            $billplz_data['paid_at']
        );

        if ($result) {
            // Get payment data for receipt
            try {
                $payment_data = $paymentHandler->prepareReceiptData($billplz_data['id']);
                if ($payment_data) {
                    $_SESSION['payment_data'] = $payment_data;
                    error_log("Receipt payment data prepared: " . print_r($payment_data, true));
                } else {
                    error_log("Failed to prepare receipt data for payment ID: " . $billplz_data['id']);
                    throw new Exception("Failed to prepare receipt data");
                }
            } catch (Exception $e) {
                error_log("Error preparing receipt data: " . $e->getMessage());
                throw $e;
            }

            if (file_exists('receipt.html.php')) {
                include 'receipt.html.php';
                exit();
            }
        }
    } else {
        throw new Exception("Payment was not successful");
    }

} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    $_SESSION['payment_error'] = [
        'message' => $e->getMessage(),
        'billplz_id' => $billplz_data['id'] ?? null
    ];
    include 'error.php';
    exit();
}

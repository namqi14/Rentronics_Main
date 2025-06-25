<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';
error_log("Session ID: " . session_id());
error_log("Full Session Data: " . print_r($_SESSION, true));

// Debug information
error_log("Payment Session Data: " . print_r($_SESSION, true));

// Validate payment session data
if (!isset($_SESSION['payment_success']) || !isset($_SESSION['payment_details']) || !isset($_SESSION['otherpayment_data'])) {
    error_log("Missing payment session data");
    $error = "Payment data not found. Required session data is missing.";
} elseif (empty($_SESSION['payment_details'])) {
    error_log("Empty payment details");
    $error = "Payment details are empty.";
} else {
    $paymentDetails = $_SESSION['payment_details'];
    $otherpaymentData = $_SESSION['otherpayment_data'];
    $payment_amount = $paymentDetails['amount'] / 100;

    // Set payment data for receipt
    $payment_data = [
        'PaymentID' => $paymentDetails['id'],
        'Amount' => $payment_amount,
        'DateCreated' => $paymentDetails['payment_date'] ?? date('Y-m-d H:i:s'),
        'name' => $otherpaymentData['payer_name'] ?? 'N/A',
        'email' => $otherpaymentData['payer_email'] ?? 'N/A',
        'mobile' => $otherpaymentData['payer_phone'] ?? 'N/A',
        'amount' => $payment_amount,
        'description' => $otherpaymentData['description'] ?? 'N/A',
        'reference_1' => $paymentDetails['id'] ?? 'N/A',
        'reference_2' => $otherpaymentData['payment_type'] ?? 'N/A'
    ];
    error_log("Other payment_data set: " . print_r($payment_data, true));

    try {
        // Create temporary variables for nullable fields
        $tenant_id = $otherpaymentData['tenant_id'] ?? NULL;
        $agent_id = $otherpaymentData['agent_id'] ?? NULL;

        // Insert into paymenthistory table
        $sql_history = "INSERT INTO paymenthistory (PaymentHistoryID, TenantID, AgentID, Amount, PaymentDate, PaymentType, Remarks) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_history = $conn->prepare($sql_history);
        $stmt_history->bind_param("sssdsss",
            $payment_data['PaymentID'],
            $tenant_id,
            $agent_id,
            $payment_data['Amount'],
            $payment_data['DateCreated'],
            $payment_data['reference_2'],
            $payment_data['description']
        );
        $stmt_history->execute();
        
        // Insert into payment table
        $sql_payment = "INSERT INTO payment (PaymentID, TenantID, AgentID, DateCreated, Amount, PaymentType, PaymentStatus, Remarks) 
                       VALUES (?, ?, ?, ?, ?, ?, 'Successful', ?)";
        $stmt_payment = $conn->prepare($sql_payment);
        $stmt_payment->bind_param("ssssdss",
            $payment_data['PaymentID'],
            $tenant_id,
            $agent_id,
            $payment_data['DateCreated'],
            $payment_data['Amount'],
            $payment_data['reference_2'],
            $payment_data['description']
        );
        $stmt_payment->execute();
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        $error = "Failed to save payment data";
    }

    // Clear payment session data
    unset($_SESSION['payment_success']);
    unset($_SESSION['payment_details']);
    unset($_SESSION['otherpayment_data']);
}

// Add debug logging
error_log("Setting payment data for other payment flow");

// Add additional validation before including receipt template
if (!isset($payment_data) || empty($payment_data)) {
    error_log("Payment data is not set or is empty");
    $error = "Payment data not found or is incomplete.";
} else {
    error_log("Payment data is valid, proceeding to receipt template");
}

// Include your HTML template here
include 'receipt-other.html.php';

<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';
require_once __DIR__ . '/lib/API.php';
require_once __DIR__ . '/lib/Connect.php';
require_once __DIR__ . '/configuration.php';

use Billplz\Minisite\API;
use Billplz\Minisite\Connect;

error_log('Session data at the start of billplzother.php: ' . print_r($_SESSION['otherpayment_data'], true));

// Create Billplz payment function
function createBillplzPayment($paymentData) {
    global $api_key, $is_sandbox, $collection_id, $redirect_url, $callback_url;

    // Extract payment details
    $payer_name = $paymentData['payer_name'] ?? '';
    $payer_email = $paymentData['payer_email'] ?? '';
    $payer_phone = $paymentData['payer_phone'] ?? '';
    $amount = $paymentData['amount'] ?? 0;
    $description = $paymentData['description'] ?? '';
    $reference_id = $paymentData['reference_id'] ?? '';
    $payment_type = $paymentData['payment_type'] ?? '';

    // Convert amount to cents for Billplz
    $amount_in_cents = $amount * 100;

    // Initialize Billplz connection
    $connect = new Connect($api_key);
    $connect->setStaging($is_sandbox);
    $billplz = new API($connect);

    try {
        // Create Bill
        list($header, $body) = $billplz->createBill([
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
        ]);

        return $billplz->toArray([$header, $body]);
    } catch (Exception $e) {
        throw new Exception('Billplz Error: ' . $e->getMessage());
    }
}

try {
    // Process POST data and store in session
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Format phone number
        $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
        if (substr($phone, 0, 2) === '60') {
            // Phone already starts with 60, do nothing
        } elseif (substr($phone, 0, 1) === '0') {
            // Convert 01x to 601x
            $phone = '6' . $phone;
        } else {
            // Add 60 prefix
            $phone = '60' . $phone;
        }

        $_SESSION['otherpayment_data'] = [
            'payer_name' => $_POST['name'],
            'payer_email' => $_POST['email'],
            'payer_phone' => $phone,
            'amount' => floatval($_POST['amount']), // Amount is already in MYR from other-payment.php
            'description' => $_POST['description'] ?: 'Payment for services',
            'reference_id' => uniqid('PAY_'),
            'payment_type' => 'OTHER_PAYMENT'
        ];

        // Add debug logging
        error_log('Payment Data: ' . print_r($_SESSION['otherpayment_data'], true));
    }

    if (!isset($_SESSION['otherpayment_data'])) {
        throw new Exception('Missing payment data');
    }

    $response = createBillplzPayment($_SESSION['otherpayment_data']);
    
    if ($response[0] === 200) {
        // Store the response data in session for later use
        $_SESSION['billplz_bill_id'] = $response[1]['id'];
        $_SESSION['payment_details'] = [
            'id' => $response[1]['id'],
            'amount' => $_SESSION['otherpayment_data']['amount'] * 100,
            'description' => $_SESSION['otherpayment_data']['description'],
            'reference_1' => $_SESSION['otherpayment_data']['reference_id'],
            'reference_2' => $_SESSION['otherpayment_data']['payment_type']
        ];
        
        header('Location: ' . $response[1]['url']);
        exit;
    } else {
        throw new Exception('Failed to create bill: ' . ($response[1]['error']['message'] ?? 'Unknown error'));
    }

} catch (Exception $e) {
    error_log('Payment Error: ' . $e->getMessage());
    header('Location: ../other-payment/other-payment.php?error=' . urlencode('Payment initialization failed: ' . $e->getMessage()));
    exit;
}
?>

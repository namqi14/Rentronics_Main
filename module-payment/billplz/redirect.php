<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';
require_once 'configuration.php';
require_once 'lib/API.php';
require_once 'lib/Connect.php';

use Billplz\Minisite\API;
use Billplz\Minisite\Connect;

// Debug logging
if ($debug) {
    error_log('Session data: ' . print_r($_SESSION, true));
    error_log('GET data: ' . print_r($_GET, true));
}

try {
    // Validate X-Signature
    $data = Connect::getXSignature($x_signature, 'bill_redirect');
    if (!$data) {
        throw new Exception('Invalid X-Signature');
    }

    // Determine payment type from session
    $payment_type = null;
    if (isset($_SESSION['lumpsum_payment'])) {
        $payment_type = 'lumpsum';
    } elseif (isset($_SESSION['booking_data'])) {
        $payment_type = 'booking';
    } elseif (isset($_SESSION['flashpay_data'])) {
        $payment_type = 'flashpay';
    }

    if (!$payment_type) {
        throw new Exception('Invalid payment session data');
    }

    if ($debug) {
        error_log('Payment type determined: ' . $payment_type);
        error_log('Billplz response: ' . print_r($data, true));
    }

    // Check if payment was successful
    if ($data['paid'] === 'true' || $data['paid'] === true) {
        // Build payment success data
        $success_data = [
            'type' => $payment_type,
            'billplz_id' => $data['id'],
            'payment_date' => $data['paid_at'],
            'metadata' => [
                'payment_details' => $_SESSION['payment_details'] ?? []
            ]
        ];

        // Add type-specific data
        switch ($payment_type) {
            case 'lumpsum':
                $success_data['metadata']['lumpsum_data'] = $_SESSION['lumpsum_payment'];
                break;
            case 'booking':
                $success_data['metadata']['booking_data'] = $_SESSION['booking_data'];
                break;
            case 'flashpay':
                $success_data['metadata']['flashpay_data'] = $_SESSION['flashpay_data'];
                break;
        }

        $_SESSION['payment_success'] = $success_data;

        // Clean up session
        unset($_SESSION['lumpsum_payment']);
        unset($_SESSION['booking_data']);
        unset($_SESSION['flashpay_data']);

        // Redirect with success parameters
        header("Location: $successpath?billplz[id]=" . urlencode($data['id']) . 
               "&billplz[paid]=true&billplz[paid_at]=" . urlencode($data['paid_at']) .
               "&billplz[x_signature]=" . urlencode($data['x_signature']));
        exit();
    } else {
        $_SESSION['payment_error'] = [
            'message' => 'Payment was not successful',
            'billplz_id' => $data['id'],
            'type' => $payment_type
        ];

        // Clean up session
        unset($_SESSION['lumpsum_payment']);
        unset($_SESSION['booking_data']);
        unset($_SESSION['flashpay_data']);

        header("Location: $successpath?payment=failed&error=payment_unsuccessful");
    }

} catch (Exception $e) {
    if ($debug) {
        error_log('Redirect Error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
    }
    
    $_SESSION['payment_error'] = [
        'message' => $e->getMessage(),
        'type' => $payment_type ?? 'unknown'
    ];
    
    header("Location: $successpath?payment=failed&error=" . urlencode($e->getMessage()));
}
exit();

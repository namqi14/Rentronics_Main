<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';
require_once __DIR__ . '/../PaymentHandler.php';
require 'lib/API.php';
require 'lib/Connect.php';
require 'configuration.php';

use Billplz\Minisite\API;
use Billplz\Minisite\Connect;

try {
    // Validate X-Signature
    $data = Connect::getXSignature($x_signature, 'bill_callback');
    if (!$data) {
        throw new Exception('Invalid X-Signature');
    }

    // Get bill details
    $connect = new Connect($api_key);
    $connect->setStaging($is_sandbox);
    $billplz = new API($connect);
    list($rheader, $rbody) = $billplz->toArray($billplz->getBill($data['id']));

    if ($rbody['paid']) {
        // Process successful payment
        $billplz_id = $data['id'];
        $payment_type = $rbody['reference_2'] ?? 'unknown';
        $paid_at = date('Y-m-d H:i:s');

        // Store payment details in session for processing
        $_SESSION['payment_success'] = [
            'type' => $payment_type,
            'metadata' => $rbody
        ];
        $_SESSION['payment_details'] = $rbody;

        // Initialize payment handler
        $paymentHandler = new PaymentHandler($conn);

        // Process the payment using the handler
        try {
            $result = $paymentHandler->handlePayment(
                $payment_type,
                $rbody,
                $billplz_id,
                $paid_at
            );

            if ($debug) {
                error_log("Payment processed successfully for bill ID: $billplz_id");
                error_log("Payment details: " . print_r($rbody, true));
            }
        } catch (Exception $e) {
            if ($debug) {
                error_log("Payment processing error: " . $e->getMessage());
                error_log("Payment data: " . print_r($rbody, true));
            }
        }
    } else {
        if ($debug) {
            error_log("Payment failed for bill ID: " . $data['id']);
        }
    }

    // Always return 200 OK to Billplz
    http_response_code(200);
    echo 'OK';

} catch (Exception $e) {
    if ($debug) {
        error_log('Callback Error: ' . $e->getMessage());
    }
    http_response_code(200); // Still return 200 to Billplz
    echo 'Error';
}

/*
 * In variable (array) $moreData you may get this information:
 * 1. reference_1
 * 2. reference_1_label
 * 3. reference_2
 * 4. reference_2_label
 * 5. amount
 * 6. description
 * 7. id // bill_id
 * 8. name
 * 9. email
 * 10. paid
 * 11. collection_id
 * 12. due_at
 * 13. mobile
 * 14. url
 * 15. callback_url
 * 16. redirect_url
 */

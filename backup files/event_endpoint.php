<?php
require 'vendor/autoload.php';

use Stripe\Stripe;

\Stripe\Stripe::setApiKey('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');

$endpoint_secret = 'whsec_b9gk8dYNryg8ntsaVkmR2o7Py7dixgSD'; // Replace with your actual webhook secret

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

$event = null;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

// Handle the event
switch ($event->type) {
    case 'charge.succeeded':
        $charge = $event->data->object; // contains a StripeCharge
        handleChargeSucceeded($charge);
        break;
    case 'checkout.session.completed':
        $checkoutSession = $event->data->object; // contains a StripeCheckoutSession
        handleCheckoutSessionCompleted($checkoutSession);
        break;
    default:
        // Unexpected event type
        http_response_code(400);
        exit();
}

http_response_code(200);

function handleChargeSucceeded($charge) {
    // Handle successful charge
    $data = [
        'event_type' => 'handleChargeSucceeded',
        'status' => $charge->status,
        'amount_paid' => $charge->amount,
        'agent_id' => null,
        'agent_name' => null,
        'property_id' => null,
        'room_id' => null,
        'reference' => null,
        'receipt_number' => $charge->receipt_number,
        'receipt_url' => $charge->receipt_url,
        'date' => date('Y-m-d'),
        'time' => date('H:i:s')
    ];
    sendToGoogleAppsScript($data);
}

function handleCheckoutSessionCompleted($checkoutSession) {
    // Handle completed checkout session
    $data = [
        'event_type' => 'handleCheckoutSessionCompleted',
        'status' => $checkoutSession->payment_status,
        'amount_paid' => $checkoutSession->amount_total,
        'agent_id' => $checkoutSession->metadata->{'Agent ID'},
        'agent_name' => $checkoutSession->metadata->{'Agent Name'},
        'property_id' => $checkoutSession->metadata->{'Property ID'},
        'room_id' => $checkoutSession->metadata->{'Room ID'},
        'reference' => $checkoutSession->metadata->{'Reference'},
        'receipt_number' => null,
        'receipt_url' => null,
        'date' => date('Y-m-d'),
        'time' => date('H:i:s')
    ];
    sendToGoogleAppsScript($data);
}

function sendToGoogleAppsScript($data) {
    $url = 'https://script.google.com/macros/s/AKfycbxIXXozu0tMmZNWX-0ZSV48TSRgOJaMEO_ZeUXLLWiC_6CUls9JacArkEhPfN3QyzbzcQ/exec'; // Replace with your Google Apps Script URL
    $options = [
        'http' => [
            'header'  => "Content-type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode($data)
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE) { /* Handle error */ }
}
?>

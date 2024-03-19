<?php
require_once 'vendor/autoload.php';
require_once('google_sheets_integration.php');

// Set your Stripe API key
\Stripe\Stripe::setApiKey('sk_test_51KQ9mIAzYk65hFefMIQR5Q53nJnuC6YGJOngj1wABkJcU3Htmg97XeonE4N59CIEWd9SBdwj23mnjJBTdFFsXzbq00TB5RBhMg');

// Retrieve the Checkout Session ID from the query string
$session_id = $_GET['session_id'];

try {
  // Retrieve the Checkout Session to get payment status
  $session = \Stripe\Checkout\Session::retrieve($session_id);

  // Display relevant payment information
  $payment_status = $session->payment_status;
  $amount_paid = number_format($session->amount_total / 100, 2, '.', '');
  $currency = strtoupper($session->currency);
  $agentName = $session->metadata->agent_name;
  $propertyId = $session->metadata->{"Property ID"};
  $roomId = $session->metadata->{"Room ID"};

  echo "<h1>Payment Successful</h1>";
  echo "<p>Amount Paid: $currency $amount_paid</p>";
  echo "<p>Payment Status: $payment_status</p>";
  echo "<p>Agent Name: $agentName</p>";
  echo "<p>Property ID: $propertyId</p>";
  echo "<p>Room ID: $roomId</p>";

  // Prepare data for Google Sheet
  $data = [
    'payment_status' => $payment_status,
    'amount_paid' => $amount_paid,
    'currency' => $currency,
    'agentName' => $agentName,
    'propertyId' => $propertyId,
    'roomId' => $roomId,
  ];

  $spreadsheetId = '1saIMUxbothIXVgimL9EMgnGIZ7lNWN1d_YnjvK1Znyw';
  $rangeSheet1 = 'Sheet5!A2:F';

  // Save data to Google Sheet
  writeToSheet($data);

} catch (\Stripe\Exception\InvalidRequestException $e) {
  // Handle any exceptions or errors here
  echo "<h1>Error</h1>";
  echo "<p>An error occurred: " . $e->getMessage() . "</p>";
}

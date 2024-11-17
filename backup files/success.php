<?php
require_once 'vendor/autoload.php';
require_once('google_sheets_integration.php');

// Set your Stripe API key
\Stripe\Stripe::setApiKey('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');

// Retrieve the Checkout Session ID from the query string
$session_id = $_GET['session_id'];

try {
  // Retrieve the Checkout Session to get payment status
  $session = \Stripe\Checkout\Session::retrieve($session_id);

  // Display relevant payment information
  $payment_status = $session->payment_status;
  $amount_paid = number_format($session->amount_total / 100, 2, '.', '');
  $currency = strtoupper($session->currency);
  $agentID = $session->metadata->{"Agent ID"};
  $agentName = $session->metadata->{"Agent Name"};
  $propertyId = $session->metadata->{"Property ID"};
  $roomId = $session->metadata->{"Room ID"};
  $reference = $session->metadata->{"Reference"};

  // Prepare data to be written to Google Sheet
  $data = [
    [$payment_status, $amount_paid, $agentID, $agentName, $propertyId, $roomId, $reference]
  ];

  // Google Sheets ID and range
  $spreadsheetId = '1saIMUxbothIXVgimL9EMgnGIZ7lNWN1d_YnjvK1Znyw';
  $rangeSheet1 = 'Sheet5!A2:G';

  // Save data to Google Sheet
  writeData($spreadsheetId, $rangeSheet1, $data);

} catch (\Stripe\Exception\InvalidRequestException $e) {
  // Handle any exceptions or errors here
  echo "<h1>Error</h1>";
  echo "<p>An error occurred: " . $e->getMessage() . "</p>";
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .receipt-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 600px;
            padding: 20px;
            text-align: center;
        }
        .header {
            background-color: #3b5998;
            padding: 10px;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .header img {
            width: 50px;
            height: 50px;
        }
        .header h2 {
            color: #fff;
            margin: 10px 0;
        }
        .content {
            padding: 20px;
        }
        .content h1 {
            margin-top: 0;
        }
        .content p {
            margin: 5px 0;
        }
        .summary {
            margin: 20px 0;
            text-align: left;
        }
        .summary p {
            display: flex;
            justify-content: space-between;
        }
        .footer {
            margin-top: 20px;
            font-size: 0.9em;
        }
        .footer a {
            color: #3b5998;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <img src="img/rentronics.jpg" alt="Company Logo">
            <h2>Receipt from Pavilone PLT</h2>
        </div>
        <div class="content">
            <h1>Payment Successful</h1>
            <p>Receipt #<?php echo htmlspecialchars($session->id); ?></p>
            <p>Amount Paid: <?php echo htmlspecialchars($currency . ' ' . $amount_paid); ?></p>
            <p>Date Paid: <?php echo htmlspecialchars(date('F j, Y, g:i:s A', $session->created)); ?></p>
            <div class="summary">
                <p><strong>SUMMARY</strong></p>
                <p>Property Deposit Payment <?php echo htmlspecialchars($propertyId); ?> × 1 <span><?php echo htmlspecialchars($currency . ' ' . $amount_paid); ?></span></p>
                <p>Amount charged <span><?php echo htmlspecialchars($currency . ' ' . $amount_paid); ?></span></p>
            </div>
            <p>Agent ID: <?php echo htmlspecialchars($agentID); ?></p>
            <p>Agent Name: <?php echo htmlspecialchars($agentName); ?></p>
            <p>Room ID: <?php echo htmlspecialchars($roomId); ?></p>
            <p>If you have any questions, contact us at <a href="mailto:accs.sparta@gmail.com">accs.sparta@gmail.com</a> or call us at <a href="tel:+60136600635">+60 13-660 0635</a>.</p>
        </div>
        <div class="footer">
            <p>You're receiving this email because you made a purchase at Pavilone PLT, which partners with Stripe to provide invoicing and payment processing.</p>
            <p>Something wrong with the email? <a href="#">View it in your browser</a>.</p>
        </div>
    </div>
</body>
</html>

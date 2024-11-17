<?php
require_once 'vendor/autoload.php';

use Stripe\Stripe;

\Stripe\Stripe::setApiKey('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');
//\Stripe\Stripe::setApiKey('sk_test_51KQ9mIAzYk65hFefMIQR5Q53nJnuC6YGJOngj1wABkJcU3Htmg97XeonE4N59CIEWd9SBdwj23mnjJBTdFFsXzbq00TB5RBhMg');

if (!isset($_GET['session_id'])) {
    echo "<h1>Error</h1>";
    echo "<p>Session ID is required.</p>";
    exit;
}

$session_id = $_GET['session_id'];
$processed = isset($_GET['processed']) && $_GET['processed'] == 'true';

if (!$processed) {
    try {
        $session = \Stripe\Checkout\Session::retrieve($session_id);

        // Retrieve payment intent to get charge info
        $payment_intent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
        $charges = $payment_intent->charges->data;
        $charge = $charges[0];

        $payment_status = $session->payment_status;
        $amount_paid = number_format($session->amount_total / 100, 2, '.', '');
        $currency = strtoupper($session->currency);
        $agentID = $session->metadata->{"Agent ID"};
        $agentName = $session->metadata->{"Agent Name"};
        $tenantName = $session->metadata->{"Tenant Name"};
        $propertyId = $session->metadata->{"Property ID"};
        $roomId = $session->metadata->{"Room ID"};
        $roomName = $session->metadata->{"Room Name"};
        $reference = $session->metadata->{"Reference"};
        $months = $session->metadata->{"Month"};
        $roomIDCellRef = $session->metadata->{"RoomIDCellRef"};
        $monthIDCellRef = $session->metadata->{"MonthIDCellRef"};
        $receipt_number = $charge->receipt_number;
        $receipt_url = $charge->receipt_url;

        // Prepare data to be sent to Google Sheets
        $postData = [
            'depositAmount' => $amount_paid,
            'AgentID' => $agentID,
            'AgentName' => $agentName,
            'TenantName' => $tenantName,
            'propertyId' => $propertyId,
            'roomId' => $roomId,
            'reference' => $reference,
            'months' => $months,
            'RoomIDCellRef' => $roomIDCellRef,
            'MonthIDCellRef' => $monthIDCellRef,
            'receipt_number' => $receipt_number,
            'receipt_url' => $receipt_url
        ];

        $scriptUrl = 'https://script.google.com/macros/s/AKfycbxO3wAO8OJGmo44zH9jCNUy71wnS6iAYTPTuoRO2tlWoZj_OPDhjYvkORBIPTThRkmD/exec';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $scriptUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        // Execute the cURL session and check the response
        $response = curl_exec($curl);
        curl_close($curl);

        // Redirect to the same page with a processed flag
        header("Location: {$_SERVER['PHP_SELF']}?session_id={$session_id}&processed=true");
        exit;

    } catch (\Stripe\Exception\InvalidRequestException $e) {
        echo "<h1>Error</h1>";
        echo "<p>An error occurred: " . $e->getMessage() . "</p>";
        exit;
    }
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
            <p>Receipt #<?php echo htmlspecialchars($receipt_number); ?></p>
            <p>Amount Paid: <?php echo htmlspecialchars($currency . ' ' . $amount_paid); ?></p>
            <p>Date Paid: <?php echo htmlspecialchars(date('F j, Y, g:i:s A', $session->created)); ?></p>
            <div class="summary">
                <p><strong>SUMMARY</strong></p>
                <p>Payment <?php echo htmlspecialchars($roomName); ?> × 1 <span><?php echo htmlspecialchars($currency . ' ' . $amount_paid); ?></span></p>
                <p>Amount charged <span><?php echo htmlspecialchars($currency . ' ' . $amount_paid); ?></span></p>
            </div>
            <p>Agent ID: <?php echo htmlspecialchars($agentID); ?></p>
            <p>Agent Name: <?php echo htmlspecialchars($agentName); ?></p>
            <p>Tenant Name: <?php echo htmlspecialchars($tenantName); ?></p>
            <p>Room ID: <?php echo htmlspecialchars($roomId); ?></p>
            <p>Room Name: <?php echo htmlspecialchars($roomName); ?></p>
            <p>If you have any questions, contact us at <a href="mailto:accs.sparta@gmail.com">accs.sparta@gmail.com</a> or call us at <a href="tel:+60136600635">+60 13-660 0635</a>.</p>
        </div>
        <div class="footer">
            <p>You're receiving this email because you made a purchase at Pavilone PLT, which partners with Stripe to provide invoicing and payment processing.</p>
            <p>Something wrong with the email? <a href="#">View it in your browser</a>.</p>
        </div>
    </div>
</body>
</html>

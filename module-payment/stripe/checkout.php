<?php

require_once('vendor/autoload.php');

// Set your Stripe publishable key
\Stripe\Stripe::setApiKey('sk_test_51KQ9mIAzYk65hFefMIQR5Q53nJnuC6YGJOngj1wABkJcU3Htmg97XeonE4N59CIEWd9SBdwj23mnjJBTdFFsXzbq00TB5RBhMg');

// Extract the session ID from the query parameters
$sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : null;

try {
    if (!$sessionId) {
        throw new Exception('Session ID is missing in the URL.');
    }

    // Retrieve the Checkout Session from the session ID
    $session = \Stripe\Checkout\Session::retrieve($sessionId);

    // Display the payment form with the Stripe.js script
    echo "<!DOCTYPE html>
        <html lang=\"en\">
        <head>
            <meta charset=\"UTF-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <title>Stripe Payment Checkout</title>
            <script src=\"https://js.stripe.com/v3/\"></script>
        </head>
        <body>
            <h1>Stripe Payment Checkout</h1>
            <p>Complete your payment below:</p>
            <form id=\"payment-form\">
                <!-- Display the payment amount and other details -->
                <p><strong>Amount:</strong> " . number_format($session->amount_total / 100, 2) . " " . strtoupper($session->currency) . "</p>
                <p><strong>Property ID:</strong> " . $session->metadata['property_id'] . "</p>
                <p><strong>Room ID:</strong> " . $session->metadata['room_id'] . "</p>
                
                <!-- Stripe Element to collect card details -->
                <div id=\"card-element\"></div>
                
                <!-- Used to display form errors -->
                <div id=\"card-errors\" role=\"alert\"></div>
                
                <!-- Hidden input to pass the session ID to the server -->
                <input type=\"hidden\" name=\"session_id\" value=\"$sessionId\">
                
                <button type=\"submit\">Pay Now</button>
            </form>
            <script>
                var stripe = Stripe('" . 'your_publishable_key' . "');
                var elements = stripe.elements();
                var cardElement = elements.create('card');
                cardElement.mount('#card-element');
                var card = elements.getElement('card');
                var form = document.getElementById('payment-form');
                form.addEventListener('submit', function(event) {
                    event.preventDefault();
                    stripe.confirmCardPayment('$sessionId', {
                        payment_method: {
                            card: card,
                        },
                    }).then(function(result) {
                        if (result.error) {
                            var errorElement = document.getElementById('card-errors');
                            errorElement.textContent = result.error.message;
                        } else {
                            window.location.href = 'success.php'; // Redirect to success page
                        }
                    });
                });
            </script>
        </body>
        </html>";
} catch (\Stripe\Exception\ApiErrorException $e) {
    // Handle API errors
    echo 'Error retrieving Checkout Session: ' . $e->getError()->message;
} catch (Exception $e) {
    // Handle other errors
    echo 'Error: ' . $e->getMessage();
}
?>

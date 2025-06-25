<?php
session_start();
require_once(__DIR__ . '/../../module-auth/dbconnection.php');
require_once(__DIR__ . '/../billplz/configuration.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['auser'])) {
    header("Location: /index.php");
    exit();
}

// Function to get exchange rate
function getExchangeRate($from = 'USD', $to = 'MYR')
{
    $apiKey = '478addc78ae50bb630c29c99'; // Replace with your API key
    $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$from}/{$to}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['conversion_rate'] ?? 4.51; // Fallback rate
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Validate form data
    $name = trim($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $currency = $_POST['currency'] ?? 'MYR';
    $description = trim($_POST['description'] ?? '');

    // Comprehensive validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    if (!$email) {
        $errors[] = 'Invalid email address';
    }
    if (strlen($phone) < 10) {
        $errors[] = 'Invalid phone number';
    }
    if ($amount <= 0) {
        $errors[] = 'Invalid amount';
    }

    if (!empty($errors)) {
        error_log('Validation errors: ' . implode(', ', $errors));
        header('Location: ' . $_SERVER['PHP_SELF'] . '?error=' . urlencode(implode(', ', $errors)));
        exit;
    }

    // Format phone number
    if (substr($phone, 0, 2) === '60') {
        // Phone already starts with 60, do nothing
    } elseif (substr($phone, 0, 1) === '0') {
        // Convert 01x to 601x
        $phone = '6' . $phone;
    } else {
        // Add 60 prefix
        $phone = '60' . $phone;
    }

    // Convert USD to MYR if needed
    $myr_amount = $amount;
    if ($currency === 'USD') {
        $exchange_rate = getExchangeRate('USD', 'MYR');
        $myr_amount = $amount * $exchange_rate;
    }

    // Debug logging
    error_log('Setting session data for: ' . $email);

    // Store payment data in session with a status flag
    $_SESSION['otherpayment_data'] = [
        'payer_name' => $name,
        'payer_email' => $email,
        'payer_phone' => $phone,
        'amount' => round($myr_amount, 2),
        'description' => $description ?: 'Payment for services',
        'reference_id' => uniqid('PAY_'),
        'payment_type' => 'OTHER_PAYMENT',
        'status' => 'pending' // Add status flag
    ];

    // Debug log
    error_log('Payment session data before redirect: ' . print_r($_SESSION['otherpayment_data'], true));

    // Ensure session is written before redirect
    session_write_close();

    // Redirect with absolute path
    header('Location: ' . '../../module-payment/billplz/billplzother.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/rentronics/img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f4f8;
            min-height: 100vh;
            margin: 0;
            font-family: 'Arial', sans-serif;
        }

        .page-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 70px);
            /* Adjust based on your navbar height */
            padding: 20px;
        }

        .payment-card {
            background: white;
            border-radius: 15px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #1c2f59 0%, #2a4374 100%);
            color: white;
            padding: 40px;
            position: relative;
            border-radius: 10px 10px 0 0;
        }

        /* .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: white;
            clip-path: ellipse(60% 100% at 50% 100%);
        } */

        .header-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            position: relative;
            z-index: 1;
        }



        .header-logo {
            height: 90px;
            width: auto;
            object-fit: contain;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            background: white;
        }

        .header-text {
            text-align: center;
        }

        .header-text .title {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
        }

        .card-icon {
            text-align: center;
            margin: -20px auto 5px;
            position: relative;
            z-index: 2;
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(28, 47, 89, 0.15);
            transition: transform 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-icon img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .card-icon:hover {
            transform: scale(1.05);
        }

        .error {
            color: #dc3545;
            padding: 10px 30px;
            margin-bottom: 20px;
            text-align: center;
        }

        .payment-section {
            padding: 10px 30px;
            background-color: #f8f9fa;
        }

        .form-group {
            margin-bottom: 25px;
            width: 100%;
        }

        .form-group label {
            color: #1c2f59;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            width: 100%;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: #1c2f59;
            box-shadow: 0 0 0 3px rgba(28, 47, 89, 0.1);
        }

        .amount-container {
            display: flex;
            gap: 10px;
            align-items: center;
            width: 100%;
        }

        .amount-container input {
            flex: 1;
        }

        .amount-container select {
            width: 100px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .btn-pay {
            background: linear-gradient(135deg, #1c2f59 0%, #2a4374 100%);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            display: block;
            margin: 0 auto;
            width: fit-content;
            min-width: 200px;
        }

        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(28, 47, 89, 0.2);
            background: linear-gradient(135deg, #2a4374 0%, #1c2f59 100%);
        }

        .terms-check {
            margin: 25px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        img {
            width: 100px;
            height: 100px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        /* Add subtle animation to form inputs */
        .form-control:focus {
            transform: translateY(-1px);
        }

        /* Style for the converted amount text */
        #convertedAmount {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>

<body>
    <div class="container-fluid bg-white p-0">
        <!-- Navbar and Sidebar Start -->
        <?php include('../../nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="payment-card">
                <div class="header">
                    <div class="header-content">
                        <img src="../../img/rentronics.jpg" alt="Rentronic Logo" class="header-logo">
                        <div class="header-text">
                            <div class="title">Other Payment</div>
                        </div>
                    </div>
                </div>

                <div class="card-icon">
                    <img src="../../img/credit-card.png" alt="Credit Card Icon">
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="error"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>

                <div class="payment-section">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="paymentForm">
                        <div class="form-group">
                            <label for="name">Full Name:</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number:</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>

                        <div class="form-group">
                            <label for="amount">Amount:</label>
                            <div class="amount-container">
                                <input type="number" class="form-control" id="amount" name="amount" min="1" step="0.01" required>
                                <select name="currency" id="currency" class="form-control">
                                    <option value="MYR">MYR</option>
                                    <option value="USD">USD</option>
                                </select>
                            </div>
                            <small id="convertedAmount" class="conversion-text"></small>
                        </div>

                        <div class="form-group">
                            <label for="description">Payment Description:</label>
                            <textarea class="form-control" id="description" name="description" required></textarea>
                        </div>

                        <div class="terms-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I understand and accept the <a href="#" class="text-primary">Terms & Conditions</a>
                            </label>
                        </div>

                        <button type="submit" class="btn-pay" id="payButton">PROCEED TO PAYMENT</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js" integrity="sha512-4lykFR6C2W55I60sYddEGjieC2fU79R7GUtaqr3DzmNbo0vSaO1MfUjMoTFYYuedjfEix6uV9jVTtRCSBU/Xiw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const amountInput = document.getElementById('amount');
            const currencySelect = document.getElementById('currency');
            const convertedText = document.getElementById('convertedAmount');
            let currentRate = 4.51; // Default rate

            // Function to fetch current exchange rate
            async function fetchExchangeRate() {
                try {
                    const response = await fetch('get_rate.php');
                    const data = await response.json();
                    currentRate = data.rate;
                    updateConversion(); // Update the display with new rate
                } catch (error) {
                    console.error('Error fetching exchange rate:', error);
                }
            }

            function updateConversion() {
                const amount = parseFloat(amountInput.value) || 0;
                const currency = currencySelect.value;

                if (currency === 'USD') {
                    const myrAmount = (amount * currentRate).toFixed(2);
                    convertedText.textContent = `Estimated amount in MYR: ${myrAmount} MYR (Rate: ${currentRate})`;
                } else {
                    convertedText.textContent = '';
                }
            }

            // Fetch rate when currency changes to USD
            currencySelect.addEventListener('change', function() {
                if (this.value === 'USD') {
                    fetchExchangeRate();
                }
                updateConversion();
            });

            amountInput.addEventListener('input', updateConversion);

            // Initial fetch if USD is selected
            if (currencySelect.value === 'USD') {
                fetchExchangeRate();
            }
        });
    </script>
    <script src="../../js/main.js"></script>
</body>

</html>
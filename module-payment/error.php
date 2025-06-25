<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get error message and details
$error_message = htmlspecialchars($_GET['error'] ?? $_SESSION['payment_error']['message'] ?? 'An unexpected error occurred');
$billplz_id = htmlspecialchars($_SESSION['billplz_bill_id'] ?? '');
$payment_type = htmlspecialchars($_SESSION['payment_details']['reference_2'] ?? 'unknown');

// Clear error-related session data
unset($_SESSION['payment_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Error - Rentronics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-card {
            background: white;
            max-width: 500px;
            width: 100%;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .error-icon {
            color: #dc3545;
            font-size: 4.5rem;
            margin-bottom: 1.5rem;
        }
        .error-title {
            color: #343a40;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #6c757d;
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            font-size: 1.1rem;
        }
        .transaction-details {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }
        .btn-return {
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container error-container">
        <div class="error-card text-center">
            <div class="error-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h2 class="error-title">Payment Failed</h2>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
            <?php if ($billplz_id): ?>
            <div class="transaction-details">
                <small>
                    Transaction ID: <?php echo $billplz_id; ?><br>
                    Payment Type: <?php echo ucfirst($payment_type); ?>
                </small>
            </div>
            <?php endif; ?>
            <div class="btn-return">
                <?php if ($payment_type === 'flashpay'): ?>
                    <a href="../module-property/tenant/flashpay.php" class="btn btn-primary">Try Again</a>
                <?php elseif ($payment_type === 'booking'): ?>
                    <a href="../module-property/booking.php" class="btn btn-primary">Return to Booking</a>
                <?php else: ?>
                    <a href="../dashboard/dashboard.php" class="btn btn-primary">Return to Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

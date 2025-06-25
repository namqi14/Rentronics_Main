<?php
// Debug logging
error_log("Starting receipt.html.php");
error_log("Session data in receipt: " . print_r($_SESSION, true));

// Get payment data from session
$payment_data = $_SESSION['payment_data'] ?? null;

// Debug logging
error_log("Payment data from session: " . print_r($payment_data, true));

if (!isset($payment_data) || !is_array($payment_data)) {
    error_log("No payment data available in session: " . print_r($_SESSION['payment_data'] ?? 'not set', true));
    die("Error: Payment data is not available. Please contact support if this issue persists.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - Rentronics</title>
    <link href="/rentronics/img/favicon.ico" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #e0dcdc;
            margin: 0;
            padding: 16px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .receipt {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 380px;
            overflow: hidden;
        }
        .header {
            background-color: #0A1229;
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }
        .logo {
            width: 70px;
            height: 70px;
            margin-bottom: 8px;
        }
        .payment-type {
            font-size: 14px;
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
        }
        .content {
            padding: 20px;
            background: #f8f9fa;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .label {
            color: #666;
            font-size: 14px;
        }
        .value {
            color: #000;
            font-size: 14px;
            text-align: right;
        }
        .summary {
            margin: 24px 0;
        }
        .summary-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 12px;
        }
        .contact {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-top: 24px;
        }
        .contact a {
            color: #6c5ce7;
            text-decoration: none;
        }
        .divider {
            height: 1px;
            background: #eee;
            margin: 12px 0;
        }
    </style>
</head>
<body>
    <?php if (!isset($payment_data)): ?>
    <div class="error-container" style="background: #fff; padding: 20px; border-radius: 10px; text-align: center; margin: 20px;">
        <h4 style="color: #dc3545;">Error</h4>
        <p>Payment data not found.</p>
        <button class="btn btn-primary" onclick="window.location.href='../dashboardagent.php'">Back to Home</button>
    </div>
    <?php elseif (!empty($error)): ?>
    <div class="error-container" style="background: #fff; padding: 20px; border-radius: 10px; text-align: center; margin: 20px;">
        <h4 style="color: #dc3545;">Payment Error</h4>
        <p><?php echo htmlspecialchars($error); ?></p>
        <button class="btn-home" onclick="window.location.href='../dashboardagent.php'">Back to Home</button>
    </div>
    <?php else: ?>

    <div class="receipt" id="receipt">
        <div class="header">
            <img src="/rentronics/img/android-chrome-512x512.png" alt="Rentronics" class="logo">
            <div class="payment-type">
                <span><?php echo htmlspecialchars($payment_data['PaymentType'] ?? 'Payment'); ?></span>
                <span class="status"><?php echo htmlspecialchars($payment_data['PaymentStatus'] ?? 'Successful'); ?></span>
            </div>
        </div>

        <div class="content">
            <div class="receipt-details">
                <div class="detail-row">
                    <div class="detail-label">References Number</div>
                    <div class="detail-value"><?php echo htmlspecialchars($payment_data['PaymentID'] ?? 'N/A'); ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Date & Time</div>
                    <div class="detail-value"><?php echo isset($payment_data['DateCreated']) ? date('d M Y h:i A', strtotime($payment_data['DateCreated'])) : 'N/A'; ?></div>
                </div>
            </div>

            <div class="divider"></div>

            <?php if (isset($payment_data['Payments'])): ?>
                <?php foreach ($payment_data['Payments'] as $payment): ?>
                    <div class="detail-row">
                        <span class="label">Tenant Name</span>
                        <span class="value"><?php echo htmlspecialchars($payment['TenantName'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Amount</span>
                        <span class="value">RM <?php echo number_format($payment['Amount'] ?? 0, 2); ?></span>
                    </div>
                    <?php if (isset($payment['RentalType']) && $payment['RentalType'] === 'Bed'): ?>
                        <div class="detail-row">
                            <span class="label">Bed No</span>
                            <span class="value"><?php echo htmlspecialchars($payment['BedNo'] ?? 'N/A'); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="detail-row">
                            <span class="label">Room No</span>
                            <span class="value"><?php echo htmlspecialchars($payment['RoomNo'] ?? 'N/A'); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="divider"></div>
                <?php endforeach; ?>

                <div class="summary">
                    <div class="summary-title">Summary</div>
                    <div class="detail-row">
                        <span class="label">Total Amount</span>
                        <span class="value">RM <?php echo number_format($payment_data['Amount'] ?? 0, 2); ?></span>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="detail-row">
                    <span class="label">Agent Name</span>
                    <span class="value"><?php echo htmlspecialchars($payment_data['AgentName'] ?? 'N/A'); ?></span>
                </div>

            <?php else: ?>
                <div class="summary">
                    <div class="summary-title">Summary</div>
                    <div class="detail-row">
                        <span class="label">Payment Amount</span>
                        <span class="value">RM <?php echo number_format($payment_data['Amount'] ?? 0, 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Amount Charged</span>
                        <span class="value">RM <?php echo number_format($payment_data['Amount'] ?? 0, 2); ?></span>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="detail-row">
                    <span class="label">Agent Name</span>
                    <span class="value"><?php echo htmlspecialchars($payment_data['AgentName'] ?? 'N/A'); ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Tenant Name</span>
                    <span class="value"><?php echo htmlspecialchars($payment_data['TenantName'] ?? 'N/A'); ?></span>
                </div>

                <div class="detail-row">
                    <span class="label">Room / Bed Details</span>
                    <span class="value">
                        <?php
                        if (isset($payment_data['BedID']) && $payment_data['BedID'] !== '') {
                            echo 'Bed: ' . htmlspecialchars($payment_data['BedNo']);
                        } elseif (isset($payment_data['RoomID']) && $payment_data['RoomID'] !== '') {
                            echo 'Room: ' . htmlspecialchars($payment_data['RoomNo']);
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>

            <?php if (isset($payment_data['BedID']) && $payment_data['BedID'] !== 'N/A'): ?>
            <div class="detail-row">
                <span class="label">Bed ID</span>
                <span class="value"><?php echo htmlspecialchars($payment_data['BedID']); ?></span>
            </div>
            <?php endif; ?>

            <?php if (isset($payment_data['RoomID']) && $payment_data['RoomID'] !== 'N/A'): ?>
            <div class="detail-row">
                <span class="label">Room ID</span>
                <span class="value"><?php echo htmlspecialchars($payment_data['RoomID']); ?></span>
            </div>
            <?php endif; ?>

            <div class="contact">
                If you have any question, do contact us at<br>
                <a href="mailto:accs.sparta@gmail.com">accs.sparta@gmail.com</a><br>
                or<br>
                call us at <a href="tel:+6013-6600635">+6013-6600635</a>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <button class="btn btn-secondary" onclick="window.location.href='../module-property/agent/lumpsumpayment.php'" style="margin-right: 10px;">Home</button>
                <button class="btn btn-primary" id="download">Download Receipt</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
    document.getElementById('download').addEventListener('click', function() {
        // Get the property identifier (bed or room number)
        const propertyId = '<?php 
            if (isset($payment_data['BedID']) && $payment_data['BedID'] !== 'N/A') {
                echo htmlspecialchars($payment_data['BedNo']);
            } else if (isset($payment_data['RoomID']) && $payment_data['RoomID'] !== 'N/A') {
                echo htmlspecialchars($payment_data['RoomNo']);
            } else {
                echo 'N/A';
            }
        ?>';

        const receipt = document.getElementById('receipt');

        html2canvas(receipt, {
            scale: 2,
            width: receipt.offsetWidth,
            height: receipt.offsetHeight,
            backgroundColor: null
        }).then((canvas) => {
            const imgData = canvas.toDataURL('image/png');

            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');

            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = pdf.internal.pageSize.getHeight();
            const imgRatio = canvas.height / canvas.width;
            const imgWidth = pdfWidth * 0.75;
            const imgHeight = imgWidth * imgRatio;
            const x = (pdfWidth - imgWidth) / 2;
            const y = 20;

            pdf.addImage(imgData, 'PNG', x, y, imgWidth, imgHeight);
            pdf.save(`Receipt_${propertyId}.pdf`);
        });
    });
    </script>
</body>
</html>

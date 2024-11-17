<?php
    require_once('vendor/autoload.php');
    require_once('google_sheets_integration.php');

    session_start();
    if (!isset($_SESSION['auser'])) {
        header("Location: index.php");
        exit();
    }

    // Generate QR Code
    use Endroid\QrCode\QrCode;
    use Endroid\QrCode\Writer\PngWriter;

    $spreadsheetId = '1X98yCqOZAK_LDEVKWWpyBeMlBePPZyIKfMYMMBLivmg';
    $spreadsheetId3 = '1tX2ZWXrXxfIG6cGKcMr2hDRLi6NZeT0_fsEfqoSsNKk';

    $rangeSheet1 = 'Property List!A2:F';
    $dataSheet1 = getData($spreadsheetId, $rangeSheet1);

    $rangeSheet2 = 'Room List!A2:K';
    $dataSheet2 = getData($spreadsheetId, $rangeSheet2);

    $rangeSheet3 = 'Agent List!A2:D';
    $dataSheet3 = getData($spreadsheetId, $rangeSheet3);

    $rangeSheet4 = 'Row&Col Reference 2024!A2:K';
    $dataSheet4 = getData($spreadsheetId3,$rangeSheet4);
    
    $agentID = function ($dataSheet3, $user) {
        foreach ($dataSheet3 as $row) {
            if ($row[2] == $user) {
                return $row[0];
            }
        }
        return '';
    };

    $agentName = function ($dataSheet3, $user) {
        foreach ($dataSheet3 as $row) {
            if ($row[2] == $user) {
                return $row[1];
            }
        }
        return '';
    };

    $roomDeposits = [];
    foreach ($dataSheet2 as $row) {
        $roomDeposits[$row[0]] = $row[5]; // Assuming the deposit amount is in the 8th column (index 7)
    }


    \Stripe\Stripe::setApiKey('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');
    //\Stripe\Stripe::setApiKey('sk_test_51KQ9mIAzYk65hFefMIQR5Q53nJnuC6YGJOngj1wABkJcU3Htmg97XeonE4N59CIEWd9SBdwj23mnjJBTdFFsXzbq00TB5RBhMg');

    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Extract Metadata
        $bookingAmount = $_POST['bookingAmount'];
        $advancedRentalAmount = $_POST['advancedRentalAmount'];
        $processingFee = $_POST['processingFee'];
        $depositAmount = $_POST['depositAmount'];
        $agentID = $_POST['AgentID'];
        $agentName = $_POST['AgentName'];
        $tenantName = $_POST['TenantName'];
        $propertyId = $_POST['propertyId'];
        $roomId = $_POST['roomId'];
        $roomName = $_POST['roomName'];
        $reference = $_POST['reference'];
        $months = $_POST['months'];
        $roomIDCellRef = $_POST['RoomIDCellRef'];
        $monthIDCellRef = $_POST['MonthIDCellRef'];
        $customNumber = $_POST['customNumber'];

        // Create a Checkout Session
        // Create a Checkout Session
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card','fpx'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'myr',
                        'product_data' => [
                            'name' => 'Booking for ' . $roomName,
                        ],
                        'unit_amount' => $bookingAmount * 100,
                    ],
                    'quantity' => 1,
                ],
                [
                    'price_data' => [
                        'currency' => 'myr',
                        'product_data' => [
                            'name' => 'Deposit for ' . $roomName,
                        ],
                        'unit_amount' => $depositAmount * 100,
                    ],
                    'quantity' => 1,
                ],
                [
                    'price_data' => [
                        'currency' => 'myr',
                        'product_data' => [
                            'name' => 'Advanced Rental for ' . $roomName,
                        ],
                        'unit_amount' => $advancedRentalAmount * 100,
                    ],
                    'quantity' => 1,
                ],
                [
                    'price_data' => [
                        'currency' => 'myr',
                        'product_data' => [
                            'name' => 'Processing Fee for ' . $roomName,
                        ],
                        'unit_amount' => $processingFee * 100,
                    ],
                    'quantity' => 1,
                ],
                [
                    'price_data' => [
                        'currency' => 'myr',
                        'product_data' => [
                            'name' => 'Agent : ' . $agentName,
                        ],
                        'unit_amount' => $customNumber * 100,
                    ],
                    'quantity' => 1,
                ],
                [
                    'price_data' => [
                        'currency' => 'myr',
                        'product_data' => [
                            'name' => 'Tenant : ' . $tenantName,
                        ],
                        'unit_amount' => $customNumber * 100,
                    ],
                    'quantity' => 1,
                ],
            ],
            'metadata' => [
                "Booking Amount" => $bookingAmount,
                "Deposit Amount" => $depositAmount,
                "Advanced Rental Amount" => $advancedRentalAmount,
                "Processing Fee" => $processingFee,
                "Agent ID" => $agentID,
                "Property ID" => $propertyId,
                "Room ID" => $roomId,
                "Room Name" => $roomName,
                "Agent Name" => $agentName,
                "Tenant Name" => $tenantName,
                "Reference" => $reference,
                "Month" => $months,
                "RoomIDCellRef" => $roomIDCellRef,
                "MonthIDCellRef" => $monthIDCellRef,
            ],
            'mode' => 'payment',
            'success_url' => 'https://rentronics-ez.com/success.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'https://yourwebsite.com/cancel.php',
        ]);

        // Get the Checkout Session URL
        $checkoutUrl = $session->url;

        // Generate QR Code with the Checkout Session URL
        $qrCode = new QrCode($checkoutUrl);

        // Use PngWriter to create a result object
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        // Retrieve the MIME type and content
        $mimeType = $result->getMimeType();
        $content = $result->getString();

        // Save the QR code to a file (optional)
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'QRCode' . DIRECTORY_SEPARATOR . 'qrcode.png';
        file_put_contents($filePath, $content);

        // Redirect to a new page with the QR code file path
        header('Location: qrpage.php');
        //header('Location:'. $checkoutUrl);
        exit();
    }
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>
        Rentronics
    </title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Feathericon CSS -->
    <link rel="stylesheet" href="assets/css/feathericon.min.css">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">

    <style>
    #RoomIDCellRef, #MonthIDCellRef {
        display: none; /* Hide elements */
    }
    .nav-bar {
        position: sticky;
    }
    </style>
</head>

<body>
    <div class="container-fluid bg-white p-0" style="">
        <!-- Navbar and Sidebar Start-->
        <?php include('nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <div class="page-wrapper-payment">
            <h1 class="depo-wrapper">Property Deposit Payment</h1>
            <form method="POST" action="" class="paymentform" name="paymentform" id="paymentForm">
                
                <label for="AgentID">Agent Name:</label>
                    <input type="hidden" name="AgentID" id="AgentID" value="<?php echo $agentID($dataSheet3, $_SESSION['auser']); ?>">
                    <input type="text" name="AgentName" id="AgentName" value="<?php echo $agentName($dataSheet3, $_SESSION['auser']); ?>" readonly>

                <label for="TenantName">Tenant Name:</label>
                <input type="text" name="TenantName" id="TenantName" required>

                <label for="propertyId">Property ID:</label>
                <select name="propertyId" id="propertyId" required onchange="filterRooms()">
                    <option class="" value=""></option>
                    <?php foreach ($dataSheet1 as $row): ?>
                        <option value="<?php echo $row[0]; ?>"><?php echo $row[0]; ?> - <?php echo $row[1]; ?></option>
                    <?php endforeach; ?>
                </select>


                <label for="roomId">Room ID:</label>
                <select name="roomId" id="roomId" required>
                    <option class="roomOption property" value=""></option>
                    <?php foreach ($dataSheet2 as $row): ?>
                        <option class="roomOption property-<?php echo $row[1]; ?>" value="<?php echo $row[0]; ?>" data-related-value="<?php echo $row[6];?>" data-deposit="<?php echo $row[5]; ?>"><?php echo $row[2]; ?> - <?php echo $row[3]; ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="roomName">Room Name:</label>
                <input type="text" name="roomName" id="roomName" readonly>

                <datalist id="roomNameOptions">
                    <?php foreach ($dataSheet2 as $row): ?>
                        <option data-room-id="<?php echo $row[0]; ?>" value="<?php echo $row[3]; ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <label for="reference">Reference:</label>
                <select name="reference" id="reference" required>
                  <option value=""></option>
                  <option value="Booking">Booking</option>
                  <option value="Half Deposit">Half Deposit</option>
                  <option value="Full Deposit">Full Deposit</option>
                </select>


                <label for="">Entry Month: </label>
                <select name="months" id="months" required>
                    <option class="" value=""></option>
                    <option class="" value="Jan">January</option>
                    <option class="" value="Feb">February</option>
                    <option class="" value="Mar">March</option>
                    <option class="" value="Apr">April</option>
                    <option class="" value="May">May</option>
                    <option class="" value="Jun">June</option>
                    <option class="" value="Jul">July</option>
                    <option class="" value="Aug">August</option>
                    <option class="" value="Sep">September</option>
                    <option class="" value="Oct">October</option>
                    <option class="" value="Nov">November</option>
                    <option class="" value="Dec">December</option>
                </select>

                <select name="RoomIDCellRef" id="RoomIDCellRef">
                    <?php foreach ($dataSheet4 as $row): ?>
                        <option class="" value="<?php echo $row[1]; ?>" data-related-value="<?php echo $row[0]; ?>"></option>
                    <?php endforeach; ?>
                </select>
                <select name="MonthIDCellRef" id="MonthIDCellRef">
                    <?php foreach ($dataSheet4 as $row): ?>
                        <option class="" value="<?php echo $row[4]; ?>" data-related-value="<?php echo $row[3]; ?>"></option>
                    <?php endforeach; ?>
                </select>
                <!--Price-->
                <label for="advancedRentalAmount">Rental / Advanced Rental Amount:</label>
                    <div class="input-group">
                        <span class="currency">RM</span>
                        <select name="advancedRentalAmount" id="advancedRentalAmount" required>
                            <option class="advancedRentalAmount" id="advancedRentalAmount" value="">  </option>
                            <option class="advancedRentalAmount" id="advancedRentalAmount" value="0"> 0 </option>
                            <option class="advancedRentalAmount" id="advancedRentalAmount" value="100"> 100 </option>
                        </select>
                    </div>
                <label for="depositAmount">Deposit Amount:</label>
                    <div class="input-group">
                        <span class="currency">RM</span>
                        <select name="depositAmount" id="depositAmount" required>
                            <option class="depositAmount" id="depositAmount" value="">  </option>
                            <option class="depositAmount" id="depositAmount" value="0"> 0 </option>
                        </select>
                    </div>
                <label for="bookingAmount">Booking Amount:</label>
                    <div class="input-group">
                        <span class="currency">RM</span>
                        <select name="bookingAmount" id="bookingAmount" required>
                            <option class="bookingAmount" id="bookingAmount" value="">  </option>
                            <option class="bookingAmount" id="bookingAmount" value="0"> 0 </option>
                            <option class="bookingAmount" id="bookingAmount" value="100"> 100 </option>
                        </select>
                    </div>
                <label for="processingFee">Processing Fee:</label>
                    <div class="input-group">
                        <span class="currency">RM</span>
                        <select name="processingFee" id="processingFee" required>
                            <option class="processingFee" id="processingFee" value="">  </option>
                            <option class="processingFee" id="processingFee" value="0"> 0 </option>
                            <option class="processingFee" id="processingFee" value="50"> 50 </option>
                        </select>
                    </div>

                    <input type="hidden" name="customNumber" id="customNumber" value="0">
                <button type="submit">Proceed to Payment</button>
            </form>
        </div>
    </div>
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js"
        integrity="sha512-4lykFR6C2W55I60sYddEGjieC2fU79R7GUtaqr3DzmNbo0vSaO1MfUjMoTFYYuedjfEix6uV9jVTtRCSBU/Xiw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>
</html>

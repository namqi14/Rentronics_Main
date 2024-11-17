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
    // $spreadsheetId2 = '1saIMUxbothIXVgimL9EMgnGIZ7lNWN1d_YnjvK1Znyw';
    $spreadsheetId3 = '1tX2ZWXrXxfIG6cGKcMr2hDRLi6NZeT0_fsEfqoSsNKk';

    $rangeSheet1 = 'Property List!A2:F';
    $dataSheet1 = getData($spreadsheetId, $rangeSheet1);

    $rangeSheet2 = 'Room List!A2:K';
    $dataSheet2 = getData($spreadsheetId, $rangeSheet2);

    $rangeSheet3 = 'Agent List!A2:D';
    $dataSheet3 = getData($spreadsheetId, $rangeSheet3);

    $rangeSheet4 = 'Row&Col Reference 2024!A2:K';
    $dataSheet4 = getData($spreadsheetId3,$rangeSheet4);


    \Stripe\Stripe::setApiKey('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');

    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Extract Metadata
        $amount = $_POST['depositAmount'];
        $agentID = $_POST['AgentID'];
        $agentName = $_POST['AgentName'];
        $propertyId = $_POST['propertyId'];
        $roomId = $_POST['roomId'];
        $reference = $_POST['reference'];
        $months = $_POST['months'];

        // Create a Checkout Session
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card','fpx'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'myr', // adjust to your currency code
                    'product_data' => [
                        'name' => 'Property Deposit Payment ' . $roomId,
                    ],
                    'unit_amount' => $amount * 100, // convert to cents
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                "Agent ID" => $agentID,
                "Property ID" => $propertyId,
                "Room ID" => $roomId,
                "Agent Name" => $agentName,
                "Reference" => $reference,
                "Month" => $months,
            ],
            'mode' => 'payment',
            'success_url' => 'https://rentronics-ez.com/success.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'https://yourwebsite.com/cancel.php',
        ]);

        // Get the Checkout Session URL
        $checkoutUrl = $session->url;

        //Generate QR Code with the Checkout Session URL
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

        $scriptUrl = 'https://script.google.com/macros/s/AKfycbxO3wAO8OJGmo44zH9jCNUy71wnS6iAYTPTuoRO2tlWoZj_OPDhjYvkORBIPTThRkmD/exec';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $scriptUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($_POST));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        // Execute the cURL session and ignore the script's response
        $response = curl_exec($curl);
        curl_close($curl);

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
    </style>
</head>

<body>
    <div class="container-fluid bg-white p-0" style="">
        <!--Navbar Start-->
        <div class="container-fluid nav-bar bg-transparent">
            <nav class="navbar navbar-expand-lg bg-white navbar-light py-0 px-4">
                <a href="index.php" class="navbar-brand d-flex align-items-center text-center">
                    <div class="icon p-2 me-2">
                    </div>
                    <h1 class="m-0 text-primary">Rentronic</h1>
                </a>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                </div>
                <!-- Place this in the header or near the top of your body tag -->
                <button class="mobile_btn" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="logout.php" class="btn btn-primary px-3 d-lg-flex">Logout</a>
            </nav>
        </div>
        <!--Navbar End-->

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-inner slimscroll">
                <div id="sidebar-menu" class="sidebar-menu">
                    <ul>
                        <li class="menu-title">
                            <span>Main</span>
                        </li>
                        <li>
                            <a href="dashboard.php"><i class="fe fe-home"></i> <span>Dashboard</span></a>
                        </li>

                        <li class="menu-title">
                            <span>Payment</span>
                        </li>
                        <li class="submenu">
                            <a href="stripepayment.php"><i class="fe fe-map"></i> <span> Deposit Payment</span> <span
                                    class="menu-arrow"></i></span></a>
                            <a href="https://docs.google.com/forms/d/e/1FAIpQLSfkX_DIqLVlXLu_ujt8FGqZJjET_1GQLwWhNZmTO9jHl50aWg/viewform" target="_blank"><i class="fe fe-map"></i> <span> Tenant Agreement</span> <span
                                    class="menu-arrow"></i></span></a>
                        </li>

                        <!-- <li class="menu-title">
                            <span>Property Management</span>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-map"></i> <span> Property</span> <span
                                    class="menu-arrow"></i></span></a>
                            <ul>
                                <li><a href="propertyadd.php"> Add Property</a></li>
                                <li><a href="propertyview.php"> View Property </a></li>
                            </ul>
                        </li> -->
                    </ul>
                </div>
            </div>
        </div>
        <!-- /Sidebar -->

        <div class="page-wrapper-payment">
            <h1 class="depo-wrapper">Property Deposit Payment</h1>
            <form method="POST" action="" class="paymentform" name="paymentform" id="paymentForm">
                <label for="depositAmount">Deposit Amount:</label>
                <div class="input-group">
                    <span class="currency">RM</span>
                    <input type="number" name="depositAmount" id="depositAmount" required>
                </div>

                <label for="AgentID">Agent Name:</label>
                <select name="AgentID" id="AgentID" required onchange="updateRelatedValue()">
                    <option class="" value=""></option>
                    <?php foreach ($dataSheet3 as $row): ?>
                        <option value="<?php echo $row[0]; ?>" data-related-value="<?php echo $row[1]; ?>"><?php echo $row[0]; ?> - <?php echo $row[1]; ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="hidden" name="AgentName" id="AgentName">

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
                        <option class="roomOption property-<?php echo $row[1]; ?>" value="<?php echo $row[0]; ?>" data-related-value="<?php echo $row[6];?>">( <?php echo $row[1]; ?> ) <?php echo $row[0]; ?> - <?php echo $row[3]; ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="reference">Reference:</label>
                <select name="reference" id="reference" required>
                  <option value=""></option>
                  <option value="Booking">Booking</option>
                  <option value="Half Deposit">Half Deposit</option>
                  <option value="Full Deposit">Full Deposit</option>
                </select>

                <label for="">First Month of Rental : </label>
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

                <button type="submit">Proceed to Payment</button>
            </form>
        </div>
    </div>
</body>
<script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        var sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('show');
    });
</script>
<script>
function updateRelatedValue() {
    var selector = document.getElementById('AgentID');
    var relatedValue = selector.options[selector.selectedIndex].getAttribute('data-related-value');
    document.getElementById('AgentName').value = relatedValue;
}
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function filterRooms() {
            var propertyId = document.getElementById("propertyId").value;
            var rooms = document.querySelectorAll(".roomOption");
    
            rooms.forEach(function(room) {
                var roomProperty = room.classList.contains("property-" + propertyId);
                var isRelatedValueTrue = room.getAttribute('data-related-value') === 'TRUE';
    
                if (roomProperty && isRelatedValueTrue) {
                    room.style.display = "block"; // Show the room
                } else {
                    room.style.display = "none"; // Hide the room
                }
            });
        }
        document.getElementById('propertyId').addEventListener('change', filterRooms);
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var roomIdSelect = document.getElementById('roomId');
        var monthsSelect = document.getElementById('months');
        var roomIDCellRefSelect = document.getElementById('RoomIDCellRef');
        var monthIDCellRefSelect = document.getElementById('MonthIDCellRef');

        // Function to synchronize Room ID selects
        function syncRoomID() {
            var selectedRoomID = roomIdSelect.value;
            // Find the option in RoomIDCellRefSelect that has a data-related-value matching selectedRoomID
            Array.from(roomIDCellRefSelect.options).forEach(option => {
                if (option.getAttribute('data-related-value') === selectedRoomID) {
                    roomIDCellRefSelect.value = option.value;
                }
            });
        }

        // Function to synchronize Month selects
        function syncMonths() {
            var selectedMonth = monthsSelect.value;
            // Find the option in MonthIDCellRefSelect that has a data-related-value matching selectedMonth
            Array.from(monthIDCellRefSelect.options).forEach(option => {
                if (option.getAttribute('data-related-value') === selectedMonth) {
                    monthIDCellRefSelect.value = option.value;
                }
            });
        }

        // Event listeners for changes
        roomIdSelect.addEventListener('change', syncRoomID);
        monthsSelect.addEventListener('change', syncMonths);

        // Initial synchronization in case of pre-selected values
        syncRoomID();
        syncMonths();
    });
</script>
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
</html>

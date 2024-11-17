<?php
session_start();
require_once('dbconnection.php');  // Include your database connection file

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

// Fetch data from database
$query1 = "SELECT UnitID, UnitNo FROM Unit";
$result1 = $conn->query($query1);

$query2 = "SELECT RoomID, UnitID, RoomNo, RoomRentAmount FROM Room"; // Adjusted query
$result2 = $conn->query($query2);

$query3 = "SELECT AgentID, AgentName, AgentEmail FROM Agent";
$result3 = $conn->query($query3);

$unitNos = [];
$agentID = $agentName = '';

if ($result1->num_rows > 0) {
    while ($row = $result1->fetch_assoc()) {
        $unitNos[$row['UnitID']] = $row['UnitNo'];
    }
}

if ($result3->num_rows > 0) {
    while ($row = $result3->fetch_assoc()) {
        if ($row['AgentEmail'] == $_SESSION['auser']) {
            $agentID = $row['AgentID'];
            $agentName = $row['AgentName'];
        }
    }
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $customNumber = $_POST['customNumber'];

    // Create Billplz payment
    $api_key = 'your_billplz_api_key';
    $collection_id = 'your_collection_id';

    $amount = ($bookingAmount + $advancedRentalAmount + $processingFee + $depositAmount + $customNumber) * 100; // Amount in cents

    $data = [
        'collection_id' => $collection_id,
        'email' => $_SESSION['auser'],
        'name' => $tenantName,
        'amount' => $amount,
        'callback_url' => 'https://yourwebsite.com/callback.php',
        'description' => 'Payment for ' . $roomName,
        'redirect_url' => 'https://yourwebsite.com/success.php',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.billplz.com/api/v3/bills');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $api_key . ':');

    $headers = [];
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    $response = json_decode($result, true);
    $checkoutUrl = $response['url'];

    // Redirect to Billplz payment page
    header('Location: ' . $checkoutUrl);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Rentronics</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Feathericon CSS -->
    <link rel="stylesheet" href="assets/css/feathericon.min.css">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

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
    <div class="container-fluid bg-white p-0">
        <!-- Navbar and Sidebar Start-->
        <?php include('nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <div class="page-wrapper-payment">
            <h1 class="depo-wrapper">Property Deposit Payment</h1>
            <form method="POST" action="" class="paymentform" name="paymentform" id="paymentForm">
                
                <label for="AgentID">Agent Name:</label>
                    <input type="hidden" name="AgentID" id="AgentID" value="<?php echo $agentID; ?>">
                    <input type="text" name="AgentName" id="AgentName" value="<?php echo $agentName; ?>" readonly>

                <label for="TenantName">Tenant Name:</label>
                <input type="text" name="TenantName" id="TenantName" required>

                <label for="propertyId">Property ID:</label>
                <select name="propertyId" id="propertyId" required onchange="filterRooms()">
                    <option class="" value=""></option>
                    <?php while ($row = $result1->fetch_assoc()): ?>
                        <option value="<?php echo $row['UnitID']; ?>"><?php echo $row['UnitID']; ?> - <?php echo $row['UnitNo']; ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="roomId">Room ID:</label>
                <select name="roomId" id="roomId" required>
                    <option class="roomOption property" value=""></option>
                    <?php while ($row = $result2->fetch_assoc()): ?>
                        <option class="roomOption property-<?php echo $row['UnitID']; ?>" value="<?php echo $row['RoomID']; ?>" data-related-value="<?php echo $row['RoomRentAmount']; ?>"><?php echo $row['RoomNo']; ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="roomName">Room Name:</label>
                <input type="text" name="roomName" id="roomName" readonly>

                <label for="reference">Reference:</label>
                <select name="reference" id="reference" required>
                  <option value=""></option>
                  <option value="Booking">Booking</option>
                  <option value="Half Deposit">Half Deposit</option>
                  <option value="Full Deposit">Full Deposit</option>
                </select>

                <label for="months">Entry Month: </label>
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

                <!-- Price -->
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

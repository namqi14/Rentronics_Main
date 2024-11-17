<?php
require_once('vendor/autoload.php');
require_once('google_sheets_integration.php');

// Start the session
session_start();
if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

// Include necessary Stripe library
\Stripe\Stripe::setApiKey('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');

// Get data from Google Sheets
$spreadsheetId = '1X98yCqOZAK_LDEVKWWpyBeMlBePPZyIKfMYMMBLivmg';
$spreadsheetId3 = '1tX2ZWXrXxfIG6cGKcMr2hDRLi6NZeT0_fsEfqoSsNKk';

$rangeSheet1 = 'Property List!A2:F';
$dataSheet1 = getData($spreadsheetId, $rangeSheet1);

$rangeSheet2 = 'Room List!A2:K';
$dataSheet2 = getData($spreadsheetId, $rangeSheet2);

$rangeSheet3 = 'Agent List!A2:D';
$dataSheet3 = getData($spreadsheetId, $rangeSheet3);

$rangeSheet4 = 'Row&Col Reference 2024!A2:K';
$dataSheet4 = getData($spreadsheetId3, $rangeSheet4);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract metadata from the form
    $monthlyRentalAmount = $_POST['monthlyRentalAmount'];
    $propertyId = $_POST['propertyId'];
    $propertyName = $_POST['propertyName'];
    $roomId = $_POST['roomId'];
    $roomName = $_POST['roomName'];
    $numBeds = $_POST['numBeds'];

    $paymentLinks = [];

    // Check if the room name already contains a bed number
    if (preg_match('/-B(\d+)$/', $roomName, $matches)) {
        $bedNumber = (int)$matches[1];
        $baseRoomName = preg_replace('/-B\d+$/', '', $roomName);
    } else {
        $bedNumber = 1;
        $baseRoomName = $roomName;
    }

    for ($i = 0; $i < $numBeds; $i++) {
        $newRoomName = $baseRoomName . '-B' . ($bedNumber + $i);

        // Create a product on Stripe
        $product = \Stripe\Product::create([
            'name' => $propertyName.', '.$newRoomName,
            'metadata' => [
                "Property ID" => $propertyId,
                "Room ID" => $roomId + $i,
            ],
        ]);

        // Create a price for the monthly rental payment
        $price = \Stripe\Price::create([
            'product' => $product->id,
            'unit_amount' => $monthlyRentalAmount * 100,
            'currency' => 'myr',
        ]);

        // Create a payment link on Stripe
        $paymentLink = \Stripe\PaymentLink::create([
            'line_items' => [
                ['price' => $price->id, 'quantity' => 1],
            ],
            'custom_text' => [
                'submit' => [
                    'message' => 'Sila tulis bulan & tahun sewaan (cth: Jan 2024)',
                ],
            ],
            'phone_number_collection' => [
                'enabled' => true,
            ],
            'metadata' => [
                "Property ID" => $propertyId,
                "Room ID" => $roomId + $i,
            ],
        ]);

        // Add the payment link URL to the array
        $paymentLinks[] = $paymentLink->url;
    }

    // Output the payment link URLs as JSON
    echo json_encode(['paymentLinkUrls' => $paymentLinks]);
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
    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .nav-bar {
            position: sticky;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            padding-top: 100px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .modal-content {
                width: 90%;
            }
        }
        @media (max-width: 480px) {
            .modal-content {
                width: 95%;
            }
        }
        .page-wrapper {
            padding: 30px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
        .input-group .currency {
            padding: 6px 12px;
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            border-right: 0;
            border-radius: .25rem 0 0 .25rem;
            display: flex;
            align-items: center;
        }
        .form-control, .input-group, .input-group-append {
            flex: 1 1 auto;
            width: 70%;
            margin-bottom: 0;
        }
        .input-group .form-control {
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container-fluid bg-white p-0">
        <?php include('nav_sidebar.php'); ?>
        <div class="page-wrapper">
            <div class="content container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="page-title">Monthly Rental Payment Registry</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Payment</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Add Payment Details</h4>
                            </div>
                            <form method="post" action="" name="payment-form" id="paymentForm">
                                <div class="card-body">
                                    <h5 class="card-title">Payment Details</h5>
                                    <div class="row">
                                        <div class="col-xl-6">
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Property ID</label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" name="propertyId" id="propertyId" required onchange="updatePropertyName()">
                                                        <option value="">-- Select --</option>
                                                        <?php foreach ($dataSheet1 as $row): ?>
                                                            <option value="<?php echo $row[0]; ?>" data-property-name="<?php echo $row[1]; ?>"><?php echo $row[1]; ?> - <?php echo $row[0]; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <input type="hidden" name="propertyName" id="propertyName">
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Room ID</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="roomId" id="roomId" required placeholder="Enter Room ID">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Room Name</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="roomName" id="roomName" required placeholder="Enter Room Name">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Number of Beds</label>
                                                <div class="col-lg-9">
                                                    <input type="number" class="form-control" name="numBeds" id="numBeds" required placeholder="Enter Number of Beds">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Monthly Rental Amount</label>
                                                <div class="col-lg-9">
                                                    <div class="input-group">
                                                        <span class="currency">RM</span>
                                                        <input type="number" class="form-control" name="monthlyRentalAmount" id="monthlyRentalAmount" required placeholder="Enter Amount" style="height:70%">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <div class="col-lg-9 offset-lg-3">
                                                    <button type="submit" class="btn btn-primary">Proceed to Payment</button>
                                                    <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- The Modal -->
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p>Payment Link: <a id="paymentLinkUrl" href="" target="_blank"></a></p>
        </div>
    </div>

    <script>
    function updatePropertyName() {
        var propertySelect = document.getElementById('propertyId');
        var selectedOption = propertySelect.options[propertySelect.selectedIndex];
        var propertyName = selectedOption.getAttribute('data-property-name');
        document.getElementById('propertyName').value = propertyName;
    }

    function resetForm() {
        document.getElementById('paymentForm').reset();
    }

    document.getElementById('sidebarToggle').addEventListener('click', function() {
        var sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('show');
    });

    document.getElementById('paymentForm').addEventListener('submit', function(event) {
        event.preventDefault();
        var form = document.getElementById('paymentForm');
        var formData = new FormData(form);

        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            var modal = document.getElementById("myModal");
            var paymentLinkUrl = document.getElementById("paymentLinkUrl");
            paymentLinkUrl.href = data.paymentLinkUrls[0];
            paymentLinkUrl.textContent = data.paymentLinkUrls.join(', ');
            modal.style.display = "block";
        })
        .catch(error => console.error('Error:', error));
    });

    var modal = document.getElementById("myModal");
    var span = document.getElementsByClassName("close")[0];

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
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
    <script src="js/main.js"></script>
</body>
</html>

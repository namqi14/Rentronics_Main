<?php
    require_once('vendor/autoload.php');

    // Generate QR Code
    use Endroid\QrCode\QrCode;
    use Endroid\QrCode\Writer\PngWriter;

    \Stripe\Stripe::setApiKey('sk_test_51KQ9mIAzYk65hFefMIQR5Q53nJnuC6YGJOngj1wABkJcU3Htmg97XeonE4N59CIEWd9SBdwj23mnjJBTdFFsXzbq00TB5RBhMg');

    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Extract Metadata
        $amount = $_POST['depositAmount'];
        $agentName = $_POST['agentName'];
        $propertyId = $_POST['propertyId'];
        $roomId = $_POST['roomId'];

        // Create a Checkout Session
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'myr', // adjust to your currency code
                    'product_data' => [
                        'name' => 'Property Deposit Payment',
                    ],
                    'unit_amount' => $amount * 100, // convert to cents
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'agent_name' => $agentName,
                'property_id' => $propertyId,
                'room_id' => $roomId,
            ],
            'mode' => 'payment',
            'success_url' => 'https://yourwebsite.com/success.php',
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
</head>

<body>
    <div class="container-fluid bg-white p-0" style="">
        <!--Navbar Start-->
        <div class="container-fluid nav-bar bg-transparent">
            <nav class="navbar navbar-expand-lg bg-white navbar-light py-0 px-4">
                <a href="index.php" class="navbar-brand d-flex align-items-center text-center">
                    <div class="icon p-2 me-2">
                        <!-- <img class="img-fluid" src="img/icon-deal.png" alt="Icon" style="width: 30px; height: 30px;"> -->
                    </div>
                    <h1 class="m-0 text-primary">Rentronics</h1>
                </a>
                <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav ms-auto">
                        <!-- <a href="index.php" class="nav-item nav-link active">Home</a> -->
                        <!-- <a href="#abt" class="nav-item nav-link">About</a>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Property</a>
                            <div class="dropdown-menu rounded-0 m-0">
                                <a href="property-list.html" class="dropdown-item">Property List</a>
                                <a href="property-type.html" class="dropdown-item">Property Type</a>
                                <a href="property-agent.html" class="dropdown-item">Property Agent</a>
                            </div>
                        </div>
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                            <div class="dropdown-menu rounded-0 m-0">
                                <a href="testimonial.html" class="dropdown-item">Testimonial</a>
                                <a href="404.html" class="dropdown-item">404 Error</a>
                            </div>
                        </div>
                        <a href="contact.html" class="nav-item nav-link">Contact</a> -->
                    </div>
                    <a href="logout.php" class="btn btn-primary px-3 d-none d-lg-flex">Logout</a>
                </div>
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
                            <span>All Users</span>
                        </li>

                        <li class="submenu">
                            <a href=""><i class="fe fe-user"></i> <span> All Users </span> <span
                                    class="menu-arrow"></i></span></a>
                            <ul>
                                <li><a href="adminlist.php"> Admin </a></li>
                                <!-- <li><a href="userlist.php"> Users </a></li>
                                <li><a href="useragent.php"> Agent </a></li>
                                <li><a href="userbuilder.php"> Builder </a></li> -->
                            </ul>
                        </li>

                        <li class="menu-title">
                            <span>Payment</span>
                        </li>
                        <li class="submenu">
                            <a href="stripepayment.php"><i class="fe fe-map"></i> <span> Deposit Payment</span> <span
                                    class="menu-arrow"></i></span></a>
                            <!-- <ul>
                                <li><a href="propertyadd.php"> Add Property</a></li>
                            </ul> -->
                        </li>



                        <!-- <li class="menu-title">
                            <span>State & City</span>
                        </li>

                        <li class="submenu">
                            <a href=""><i class="fe fe-location"></i> <span>State & City</span> <span
                                    class="menu-arrow"></i></span></a>
                            <ul>
                                <li><a href="stateadd.php"> State </a></li>
                                <li><a href="cityadd.php"> City </a></li>
                            </ul>
                        </li> -->

                        <li class="menu-title">
                            <span>Property Management</span>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-map"></i> <span> Property</span> <span
                                    class="menu-arrow"></i></span></a>
                            <ul>
                                <li><a href="propertyadd.php"> Add Property</a></li>
                                <li><a href="propertyview.php"> View Property </a></li>

                            </ul>
                        </li>



                        <!-- <li class="menu-title">
                            <span>Query</span>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-comment"></i> <span> Contact,Feedback </span> <span
                                    class="menu-arrow"></i></span></a>
                            <ul>
                                <li><a href="contactview.php"> Contact </a></li>
                                <li><a href="feedbackview.php"> Feedback </a></li>
                            </ul>
                        </li>
                        <li class="menu-title">
                            <span>About</span>
                        </li>
                        <li class="submenu">
                            <a href="#"><i class="fe fe-browser"></i> <span> About Page </span>
                                <span class="menu-arrow"></i></span></a>
                            <ul>
                                <li><a href="aboutadd.php"> Add About Content </a></li>
                                <li><a href="aboutview.php"> View About </a></li>
                            </ul>
                        </li> -->

                    </ul>
                </div>
            </div>
        </div>
        <!-- /Sidebar -->

        <div class="page-wrapper-payment">
            <h1>Property Deposit Payment</h1>
            <form action="" method="post" class="paymentform">
                <label for="depositAmount">Deposit Amount:</label>
                <div class="input-group">
                    <span class="currency">RM</span>
                    <input type="number" name="depositAmount" id="depositAmount" required>
                </div>

                <label for="agentName">Agent Name:</label>
                <input type="text" name="agentName" id="agentName" required>

                <label for="propertyId">Property ID:</label>
                <input type="text" name="propertyId" id="propertyId" required>

                <label for="roomId">Room ID:</label>
                <input type="text" name="roomId" id="roomId" required>

                <button type="submit">Proceed to Payment</button>
            </form>
        </div>
    </div>
</body>

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
<script src="js/main.js">
</html>


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
                            </ul>
                        </li>

                        <li class="menu-title">
                            <span>Payment</span>
                        </li>
                        <li class="submenu">
                            <a href="stripepayment.php"><i class="fe fe-map"></i> <span> Deposit Payment</span> <span
                                    class="menu-arrow"></i></span></a>
                        </li>

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
                    </ul>
                </div>
            </div>
        </div>
        <!-- /Sidebar -->

        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh;">
            <h2 style="margin-bottom: 20px;">Payment QR Code</h2>
            <p>Please scan the QR code below to proceed with your payment.</p>
            <img src="img/QRCode/qrcode.png" alt="QR Code" style="max-width: 100%; max-height: 80vh;">
            <p>If you encounter any issues, please contact our support team.</p>
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
<script src="js/main.js"></script>
</html>

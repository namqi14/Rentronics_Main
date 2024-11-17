<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';

$error = "";

if (isset($_POST['login'])) {
    $user = $_POST['user'];
    $pass = $_POST['pass'];

    if (!empty($user) && !empty($pass)) {
        // Use a prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT AgentEmail, Password, AccessLevel FROM agent WHERE AgentEmail = ? AND Password = ?");
        $stmt->bind_param("ss", $user, $pass);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($agentEmail, $password, $accessLevel);

        if ($stmt->fetch()) {
            session_regenerate_id(true); // Regenerate session ID
            $_SESSION['auser'] = $user;
            $_SESSION['access_level'] = $accessLevel; // Store access level in session
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['LAST_ACTIVITY'] = time();
            if ($accessLevel == 2) {
                header("Location: ../dashboardagent.php");
            } else {
                header("Location: ../dashboard.php");
            }
            exit();
        } else {
            $error = '* Invalid User Name and Password';
        }

        $stmt->close();
    } else {
        $error = "* Please Fill all the Fields!";
    }
}

$conn->close();
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
    <link href="/rentronics/img/favicon.ico" rel="icon">

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

    <!-- Inline CSS from the uploaded style.css -->
    <link rel="stylesheet" href="/rentronics/css/login.css">
    <link rel="stylesheet" href="/rentronics/css/navbar.css">
    <link rel="stylesheet" href="/rentronics/css/flashpay.css">
    <link href="/rentronics/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .navbar {
            margin-left: 0 !important;
            background-color: #1c2f59 !important;
        }

        .icon img {
            width: 25px !important;
            height: 25px !important;
            border-radius: 50%;
            /* Optional, if you want the image itself rounded */
            max-width: none !important;
        }

        .btn {
            display: none !important;
        }
    </style>

</head>

<body>
    <div class="container-fluid p-0">
        <?php include('../../header.php'); ?>
        <div class="page-wrapper">
            <div class="flashpay">
                <div class="card-image">
                    <img src="/rentronics/img/credit-card.png" alt="Card Image" width="100">
                </div>
                <div class="greeting">Hello!</div>
                <div class="name">Muhammad Karim Bin Zeemar</div>
                <div class="id">ID: 960511104456</div>

                <!-- Added property details -->
                <div class="property-details">
                    <p><strong>Property Name:</strong> PV3 Apartment</p>
                    <p><strong>Unit No:</strong> A-3-8</p>
                    <p><strong>Bed No:</strong> B12</p>
                </div>

                <div class="payment-section">
                    <p>Total Outstanding Payment (RM):</p>
                    <div class="payment-amount">RM 250.00</div>
                    <p>Payment Amount (RM):</p>
                    <div class="input-section">
                        <input type="text" placeholder="Enter amount">
                    </div>
                </div>

                <label class="terms">
                    <input type="checkbox"> I understand and accept the
                    <a href="#">Term & Conditions</a>
                </label>
                <div class="pay-btn">
                    <button class="pay-button">PAY</button>
                </div>
            </div>

        </div>
    </div>

    <!-- Inline JavaScript from the uploaded script.js -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js" crossorigin="anonymous"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>
    <script src="/rentronics/js/main.js"></script>

</body>

</html>
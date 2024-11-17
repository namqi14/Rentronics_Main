<?php
session_start();
require_once 'dbconnection.php';

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

    <link href="/rentronics/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .navbar {
            margin-left: 0 !important;
            background-color: #1c2f59 !important;
        }

        .icon img {
            width: 25px !important;
            height: 25px !important;
            border-radius: 50%; /* Optional, if you want the image itself rounded */
            max-width: none !important;
        }

        .btn {
            background-color: #5678aa !important;
            border-color: #5678aa !important;
        }
    </style>

</head>

<body>
<?php include('../header.php'); ?>
    <section class="container forms">
        <div class="form login">
            <div class="form-content">
                <div class="logo">
                    <a href="../index.php">
                        <img src="../img/rentronics.jpg" alt="">
                    </a>
                </div>
                <header>Login</header>
                <p style="color:red;"><?php echo $error; ?></p>
                <form method="post">
                    <div class="field input-field">
                        <input type="text" name="user" placeholder="User Name" class="input" required>
                    </div>

                    <div class="field input-field">
                        <input type="password" name="pass" placeholder="Password" class="password" required>
                        <i class='bx bx-hide eye-icon'></i>
                    </div>

                    <div class="form-link">
                        <a href="#" class="forgot-pass">Forgot password?</a>
                    </div>

                    <div class="field button-field">
                        <button type="submit" name="login">Login</button>
                    </div>
                </form>
                <div class="line"></div>
                <div class="form-link">
                    <span>Want to make a payment? <a href="#" class="link signup-link">Guest</a></span>
                </div>
            </div>
        </div>

        <div class="form signup">
            <div class="form-content">
                <div class="logo">
                    <img src="../img/rentronics.jpg" alt="">
                </div>
                <header>Flash Pay</header>
                <form action="/rentronics/module-property/tenant/flashpay.php" method="post">
                    <div class="field input-field">
                        <input type="text" name="id" placeholder="ID" class="input" required>
                    </div>
                    <div class="form-tag">
                        <span>* IC / Passport will represent as your ID</span>
                    </div>
                    <div class="field button-field">
                        <button type="submit">Login</button>
                    </div>
                </form>
                <div class="line"></div>
                <div class="form-link">
                    <span><a href="#" class="link login-link">Back</a></span>
                </div>
            </div>
        </div>
    </section>

    <!-- Inline JavaScript from the uploaded script.js -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js" crossorigin="anonymous"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>
    <script>
        const forms = document.querySelector(".forms"),
            pwShowHide = document.querySelectorAll(".eye-icon"),
            links = document.querySelectorAll(".link");

        pwShowHide.forEach(eyeIcon => {
            eyeIcon.addEventListener("click", () => {
                let pwFields = eyeIcon.parentElement.parentElement.querySelectorAll(".password");

                pwFields.forEach(password => {
                    if (password.type === "password") {
                        password.type = "text";
                        eyeIcon.classList.replace("bx-hide", "bx-show");
                        return;
                    }
                    password.type = "password";
                    eyeIcon.classList.replace("bx-show", "bx-hide");
                })

            })
        });

        links.forEach(link => {
            link.addEventListener("click", e => {
                e.preventDefault(); //preventing form submit
                forms.classList.toggle("show-signup");
            })
        });
    </script>
    <script src="/rentronics/js/main.js"></script>

</body>

</html>
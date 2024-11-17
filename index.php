<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Rentronic - Your Properties Experts</title>
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

    <!-- Libraries Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/index.css" rel="stylesheet">

    <style>
        .navbar {
            margin-left: 0 !important;
        }
    </style>
</head>

<body>
    <?php
        require_once __DIR__ . '/module-auth/google_sheets_integration.php';

        $spreadsheetId = '1X98yCqOZAK_LDEVKWWpyBeMlBePPZyIKfMYMMBLivmg';
        // Fetch data from Sheet1 (starting from A2)
        $rangeSheet1 = 'Property List!A2:E';
        $dataSheet1 = getData($spreadsheetId, $rangeSheet1);
    ?>
    <!-- Top Bar Start -->
    <div class="container-xxl bg-white p-0">
 
        <!-- Navbar Start -->
        <!-- Include the navbar -->
        <?php include('header.php'); ?>
        <!-- Navbar End -->

        <!-- Header Start -->
        <div class="container-fluid header bg-white p-0" style="margin-top: 30px;">
            <div class="row g-0 align-items-center flex-column-reverse flex-md-row">
                <div class="col-md-6 p-5 mt-lg-5 wow fadeIn">
                    <h1 class="display-5 animated fadeIn mb-4">Find A <span class="text-primary">Perfect Home</span> To Live With Your Family</h1>
                    <a href="#location" class="btn btn-primary py-3 px-5 me-3 animated fadeIn">Get Started</a>
                </div>
                <div class="col-md-6 animated fadeIn">
                    <div class="owl-carousel header-carousel wow fadeIn">
                        <div class="owl-carousel-item">
                            <img class="img-fluid" src="img/example1.jpg" alt="">
                        </div>
                        <div class="owl-carousel-item">
                            <img class="img-fluid" src="img/example2.jpg" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Header End -->

        <!-- Category Start -->
        <div class="container-xxl py-5" id="location">
            <div class="container">
                <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                    <h1 class="mb3">Property Locations</h1>
                </div>
                <div class="row g-4">
                    <?php foreach ($dataSheet1 as $row): ?>
                    <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.1s">
                        <a class="cat-item d-block bg-light text-center rounded p-3" href="propertylisting.php?id=<?= $row[0] ?>">
                            <div class="rounded p-4">
                                <div class="icon mb-3">
                                    <img class="img-fluid" src="img/icon-apartment.png" alt="Icon">
                                </div>
                                <h6><?= $row[1]?>, <?= $row[4]?></h6>
                                <span><?= $row[3]?> Properties</span>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <!-- Category End -->

        <!-- About Start -->
        <div class="container-xxl py-5">
            <div class="container">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.1s">
                        <div class="about-img position-relative overflow-hidden p-5 pe-0">
                            <img class="img-fluid w-100" src="img/example3.png">
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
                        <h1 class="mb-4">#1 Place To Find The Perfect Property</h1>
                        <!--<p class="mb-4">Tempor erat elitr rebum at clita. Diam dolor diam ipsum sit. Aliqu diam amet diam et eos. Clita erat ipsum et lorem et sit, sed stet lorem sit clita duo justo magna dolore erat amet</p>-->
                        <p><i class="fa fa-check text-primary me-3"></i>Lease your property with 5x speed compared to conventional rental platforms, hassle-free!</p>
                        <p><i class="fa fa-check text-primary me-3"></i>Experience transparency with no hidden fees. Direct dealings between tenants and landlords, no surprises.</p>
                        <p><i class="fa fa-check text-primary me-3"></i>Ensure peace of mind our lawyer-approved digital service for tenancy agreement signing.</p>
                        <!--<a class="btn btn-primary py-3 px-5 mt-3" href="">Read More</a>-->
                    </div>
                </div>
            </div>
        </div>
        <!-- About End -->
        
        <!-- Include the footer -->
        <?php include('footer.php'); ?>

        <!-- Back to Top -->
        <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/wow/1.1.2/wow.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/waypoints/4.0.1/jquery.waypoints.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

    <!-- Initialize WOW.js -->
    <script>
      new WOW().init();
    </script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>

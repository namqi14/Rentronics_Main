<?php
require_once 'google_sheets_integration.php';

$spreadsheetId = '1saIMUxbothIXVgimL9EMgnGIZ7lNWN1d_YnjvK1Znyw';

$rangeSheet2 = 'Sheet2!A2:K';
$dataSheet2 = getData($spreadsheetId, $rangeSheet2);

if (isset($_GET['id'])) {
    $propertyId = $_GET['id'];

    $filteredData = array_filter($dataSheet2, function ($row) use ($propertyId) {
        return $row[3] == $propertyId;
    });

    if (count($filteredData) > 0) {
        $selectedProperty = reset($filteredData);
        $propertyTitle = $selectedProperty[3];
        $propertyLocation = $selectedProperty[2];
        $propertyType = $selectedProperty[8];
        $propertyRoom = $selectedProperty[4];
        $propertyPriceRaw = $selectedProperty[6];
        $propertyPriceNumeric = floatval(str_replace(['RM', ' '], '', $propertyPriceRaw));
        $propertyId = $selectedProperty[1];
        $propertyMaps = $selectedProperty[9];
        $propertyBed = $selectedProperty[10];


        function sanitizeFolderName($folderName)
        {
            // Replace unwanted characters with underscores
            $folderName = preg_replace("/[^a-zA-Z0-9]/", "-", $folderName);

            // Remove multiple underscores
            $folderName = preg_replace("/-+/", "-", $folderName);

            // Trim underscores from the beginning and end
            $folderName = trim($folderName, "-");

            return $folderName;
        }

        $propertyImageFolder = 'img\RentronicsImage';
        $propertyImageDirectory = $propertyImageFolder . DIRECTORY_SEPARATOR . $propertyLocation . DIRECTORY_SEPARATOR . sanitizeFolderName($propertyTitle);

        // Debug statements

        $validExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $imageFiles = [];

        if (is_dir($propertyImageDirectory)) {
            $directoryIterator = new DirectoryIterator($propertyImageDirectory);

            foreach ($directoryIterator as $fileInfo) {
                if (!$fileInfo->isDot() && $fileInfo->isFile()) {
                    $extension = strtolower($fileInfo->getExtension());

                    if (in_array($extension, $validExtensions)) {
                        $imageFiles[] = $fileInfo->getPathname();
                    }
                }
            }
        }

        // Debug statements

        if (!empty($imageFiles)) {
            $propertyImage = $imageFiles[0];
        } else {
            $propertyImage = '';
        }

        // Debug statements
        error_reporting(E_ALL);
        ini_set('display_errors', '1');

        // Calculate the deposit (twice the property price)
        $deposit = $propertyPriceNumeric * 2;

        $SignFee = 0.00; // Replace with the actual IQMANSIGN Fee value
        $totalPrice = $propertyPriceNumeric + $deposit + $SignFee;

        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="utf-8">
            <title>
                <?= $propertyTitle ?>-Rentronics
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
                <!-- Navbar Start -->
                <div class="container-fluid nav-bar bg-transparent">
                    <nav class="navbar navbar-expand-lg bg-white navbar-light py-0 px-4">
                        <a href="index.php" class="navbar-brand d-flex align-items-center text-center">
                            <div class="icon p-2 me-2">
                                <img class="img-fluid" src="img/icon-deal.png" alt="Icon" style="width: 30px; height: 30px;">
                            </div>
                            <h1 class="m-0 text-primary">Rentronics</h1>
                        </a>
                        <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarCollapse">
                            <div class="navbar-nav ms-auto">
                                <a href="index.html" class="nav-item nav-link active">Home</a>
                                <a href="#abt" class="nav-item nav-link">About</a>
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
                                <a href="contact.html" class="nav-item nav-link">Contact</a>
                            </div>
                            <a href="" class="btn btn-primary px-3 d-none d-lg-flex">Add Property</a>
                        </div>
                    </nav>
                </div>
                <!-- Details Start -->
                <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="container-fluid" id="abt">
                        <?php
                        if (!empty($imageFiles)): ?>
                            <div class="owl-carousel testimonial-carousel wow fadeInUp position-sticky" data-wow-delay="0.1s">
                                <?php foreach ($imageFiles as $imageFile): ?>
                                    <a href="<?= $imageFile ?>" class="popup-link">
                                        <img class="img-fluid" src="<?= $imageFile ?>" alt="Property Image"
                                            style="width: 100%; height: 400px; object-fit: cover;">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- Handle the case when no image is found -->
                            <p>No images available</p>
                        <?php endif; ?>

                        <div class="row py-5 wow fadeInUp" data-wow-delay="0.1s" style="padding: 2% 10% 0px 10% !important;">
                            <div class="col-sm-8 order-md-1">
                                <div class="PropertyDetails">
                                    <div class="breadCrumbDetail" style="--bs-breadcrumb-divider: '|' ">
                                        <ul class="breadcrumb">
                                            <li class="fs-5 breadcrumb-item"><a href="index.php">Home</a></li>
                                            <li class="fs-5 breadcrumb-item"><a
                                                    href="propertylisting.php?id=<?= $propertyId ?>">
                                                    <?= $propertyLocation ?>
                                                </a></li>
                                            <li class="fs-5 breadcrumb-item">
                                                <?= $propertyType ?>
                                            </li>
                                            <li class="fs-5 breadcrumb-item">
                                                <?= $propertyTitle ?>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                <section>
                                    <h1 class="fs-2">
                                        <?= $propertyPriceNumeric ?> / Month
                                    </h1><br>
                                </section>
                                <section id="location">
                                    <p class="text-muted fs-5 mb-4">
                                        <?= $propertyLocation ?>,
                                        <?= $propertyTitle ?>
                                    <h5 class="badge bg-success fs-6">
                                        <?= $propertyRoom ?>
                                    </h5>
                                    <section class="place-desc">
                                        </br>
                                        <hr class="line">
                                        <p class="fw-bold">Description</p>
                                        <p>
                                            <?= $propertyLocation ?>
                                        </p>
                                        <p>ROOM RENTAL / SEWA BILIK<br>
                                            Co-Living Concepts applied<br>
                                            Furnished Designed rooms for rent.
                                        </p>
                                        <p>Aut enim animi id ut numquam nesciunt repellat odit maiores! Facere vero nulla sequi
                                            architecto possimus, eaque, voluptate ullam rerum, aliquam id repudiandae?
                                            Temporibus facere blanditiis porro, harum optio sunt.</p>
                                        <p>Iusto, similique soluta? Inventore alias ab explicabo odit, quisquam, mollitia nemo
                                            animi quidem deserunt molestiae voluptas laudantium sit possimus ipsam nihil.
                                            Deleniti voluptatibus soluta totam tenetur corporis numquam unde corrupti.</p>
                                    </section>
                                    <section class="place-furnish">
                                        <p class="fw-bold">Furnishing</p>
                                        <p><?= $propertyBed ?> Bed</p>
                                        <p>Tenetur iusto similique illum officia.</p>
                                        <p>Laudantium fuga blanditiis perspiciatis unde.</p>
                                    </section>
                                    <section class="place-facilities">
                                        <p class="fw-bold">Facilities</p>
                                        <p>- Tennis court<br>
                                        - Security<br>
                                        - Minimart
                                        </p>
                                    </section>
                                    <section class="place-access">
                                        <p class="fw-bold">Accessibility</p>
                                    </section>
                                </section>
                                <section class="maps">
                                    <p class="fw-bold">Location</p>
                                    <div class="embed-responsive embed-responsive-16by9">
                                        <iframe class="embed-responsive-item" frameborder="0"
                                            style="border:0; width: 100%; height: 350px;"
                                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3983.5263817176474!2d101.72417240000001!3d3.2181805!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc39d0e7f7b02b%3A0x7007f0b71c0cd886!2sResidensi%20Vista%20Wirajaya%202!5e0!3m2!1sen!2smy!4v1700414639324!5m2!1sen!2smy"
                                            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                                            allowfullscreen></iframe>
                                    </div>
                                </section>
                            </div>
                            <div class="col-sm-4 order-md-2">
                                <div class="propertyDetailsCard position-sticky" id="move">
                                    <div class="propertyDetails">
                                        <div class="propertyDetailsTerms">
                                            <div class="propertyDetailsTerm-title">
                                                <h4>Rental Terms</h4>
                                            </div>
                                            <ul class="propertyDetailsTerms-item custom-padding">
                                                <li class="propertyDetailsTerm-list">
                                                    <span>1 Year Contract</span>
                                                </li>
                                                <li class="propertyDetailsTerm-list">
                                                    <span>Deposit 1+1 (Advance Rental)</span>
                                                </li>
                                                <li class="propertyDetailsTerm-list">
                                                    <span>A surcharge is charged for rentals of less than 12 months.</span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="propertyDetailsPrice">
                                            <div class="propertyDetailsPrice-title">
                                                <h4>To Move In</h4>
                                            </div>
                                            <ul class="propertyDetailsPrice-item custom-padding">
                                                <li class="propertyDetailsPrice-list">
                                                    <span>1st month rental</span>
                                                    <span id="propertyPrice">RM
                                                        <?= $propertyPriceNumeric ?>.00
                                                    </span>
                                                </li>
                                                <li class="propertyDetailsPrice-list">
                                                    <span>Deposit</span>
                                                    <span id="deposit">RM
                                                        <?= $deposit ?>.00
                                                    </span>
                                                </li>
                                                <li class="propertyDetailsPrice-list">
                                                    <span>Rentronics Fee</span>
                                                    <span id="SignFee">RM
                                                        <?= $SignFee ?>.00
                                                    </span>
                                                </li>
                                                <li class="propertyDetailsPrice-listTotal">
                                                    <span>Total</span>
                                                    <span id="total">RM
                                                        <?= $totalPrice ?>.00
                                                    </span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="propertyDetailsDate"></div>
                                    </div>
                                    <br>
                                    <p style="text-align: left; font-size: 12px;"><em>* All Prices Subject to Change</em></p>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#exampleModal" data-bs-whatever="@mdo">Chat with Agent</button>
                                    <!--Modal Start-->

                                    <!--Modal End-->

                                </div>
                                <!-- Details End -->
                            </div>
                        </div>
                    </div>
                    <!-- Footer Start -->
                    <div class="container-fluid bg-dark text-white-50 footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
                        <div class="container py-5">
                            <div class="row g-5">
                                <div class="col-lg-3 col-md-6">
                                    <h5 class="text-white mb-4">Get In Touch</h5>
                                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>123 Street, New York,
                                        USA</p>
                                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+012 345 67890</p>
                                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>info@example.com</p>
                                    <div class="d-flex pt-2">
                                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-twitter"></i></a>
                                        <a class="btn btn-outline-light btn-social" href=""><i
                                                class="fab fa-facebook-f"></i></a>
                                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-youtube"></i></a>
                                        <a class="btn btn-outline-light btn-social" href=""><i
                                                class="fab fa-linkedin-in"></i></a>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <h5 class="text-white mb-4">Quick Links</h5>
                                    <a class="btn btn-link text-white-50" href="">About Us</a>
                                    <a class="btn btn-link text-white-50" href="">Contact Us</a>
                                    <a class="btn btn-link text-white-50" href="">Our Services</a>
                                    <a class="btn btn-link text-white-50" href="">Privacy Policy</a>
                                    <a class="btn btn-link text-white-50" href="">Terms & Condition</a>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <h5 class="text-white mb-4">Photo Gallery</h5>
                                    <div class="row g-2 pt-2">
                                        <div class="col-4">
                                            <img class="img-fluid rounded bg-light p-1" src="img/property-1.jpg" alt="">
                                        </div>
                                        <div class="col-4">
                                            <img class="img-fluid rounded bg-light p-1" src="img/property-2.jpg" alt="">
                                        </div>
                                        <div class="col-4">
                                            <img class="img-fluid rounded bg-light p-1" src="img/property-3.jpg" alt="">
                                        </div>
                                        <div class="col-4">
                                            <img class="img-fluid rounded bg-light p-1" src="img/property-4.jpg" alt="">
                                        </div>
                                        <div class="col-4">
                                            <img class="img-fluid rounded bg-light p-1" src="img/property-5.jpg" alt="">
                                        </div>
                                        <div class="col-4">
                                            <img class="img-fluid rounded bg-light p-1" src="img/property-6.jpg" alt="">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <h5 class="text-white mb-4">Newsletter</h5>
                                    <p>Dolor amet sit justo amet elitr clita ipsum elitr est.</p>
                                    <div class="position-relative mx-auto" style="max-width: 400px;">
                                        <input class="form-control bg-transparent w-100 py-3 ps-4 pe-5" type="text"
                                            placeholder="Your email">
                                        <button type="button"
                                            class="btn btn-primary py-2 position-absolute top-0 end-0 mt-2 me-2">SignUp</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="container">
                            <div class="copyright">
                                <div class="row">
                                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                                        &copy; <a class="border-bottom" href="#">Your Site Name</a>, All Right
                                        Reserved.


                                        Designed By <a class="border-bottom" href="">your Site name</a>
                                    </div>
                                    <div class="col-md-6 text-center text-md-end">
                                        <div class="footer-menu">
                                            <a href="">Home</a>
                                            <a href="">Cookies</a>
                                            <a href="">Help</a>
                                            <a href="">FQAs</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Footer End -->
                    <!-- Back to Top -->
                    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
                </div>

                <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" style="margin-top: 10%">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">Chat with Agent</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <!-- Form Start -->
                            <div class="modal-body">
                                <form method="post"
                                    action="https://script.google.com/macros/s/AKfycbwVTqOtHwzhhUmB08rLEPpw-pz09baw_58Lie_6G-H57X4qWwl7wNWBLtl0dTPNsSr_iQ/exec"
                                    name="tenant-form">
                                    <table class="table" id="tbl1">
                                        <thead id="tbl1">
                                        </thead>
                                        <tbody id="tbl1">
                                            <input type="hidden" placeholder="" id="dateInput" readonly>
                                            <tr id="tbl1">
                                                <td id="tbl1" style="text-align: right;">
                                                    <label for="recipient-name" class="col-form-label"
                                                        style="text-align: right;">Name:</label>
                                                </td>
                                                <td id="tbl1">
                                                    <input type="text" class="form-control" id="Name" name="Name" placeholder=""
                                                        required>
                                                </td>
                                            </tr>
                                            <tr id="tbl1">
                                                <td id="tbl1" style="text-align: right;">
                                                    <label for="nationality" class="col-form-label">Nationality</label>
                                                </td>
                                                <td id="tbl1">
                                                    <select class="form-control" name="Nationality" id="Nationality"
                                                        placeholder="" required>
                                                        <option value="">Select Nationality</option>
                                                        <option value="Malaysian">Malaysian</option>
                                                        <option value="Foreigner">Foreigner</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr id="tbl1">
                                                <td id="tbl1" style="text-align: right;">
                                                    <label for="occupation" class="col-form-label">Occupation</label>
                                                </td>
                                                <td id="tbl1">
                                                    <input type="text" class="form-control" id="Occupation" name="Occupation"
                                                        placeholder="" required>
                                                </td>
                                            </tr>
                                            <tr id="tbl1">
                                                <td id="tbl1" style="text-align: right;">
                                                    <label for="email" class="col-form-label">Email</label>
                                                </td>
                                                <td id="tbl1">
                                                    <input type="email" class="form-control" id="Email" name="Email"
                                                        placeholder="" required>
                                                </td>
                                            </tr>
                                            <tr id="tbl1">
                                                <td id="tbl1" style="text-align: right;">
                                                    <label for="phone" class="col-form-label">No
                                                        Phone</label>
                                                </td>
                                                <td id="tbl1">
                                                    <input type="text" class="form-control" id="Whatsapp No."
                                                        name="Whatsapp No." placeholder="" required>
                                                </td>
                                            </tr>
                                            <tr id="tbl1">
                                                <td id="tbl1" style="text-align: right;">
                                                    <label for="UnitToRent" class="col-form-label">Place to
                                                        Rent</label>
                                                </td>
                                                <td id="tbl1" style="font-weight: bold;">
                                                    <input type="text" class="form-control" id="UnitToRent" name="UnitToRent"
                                                        value="<?= $propertyTitle ?>" readonly>
                                                </td>
                                            </tr>
                                            <tr id="tbl1">
                                                <td id="tbl1" style="text-align: right;">
                                                    <label for="start-rent" class="col-form-label">House
                                                        Viewing Date</label>
                                                </td>
                                                <td id="tbl1">
                                                    <input type="date" class="form-control" id="House Viewing Date"
                                                        name="House Viewing Date" placeholder="" required>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <input type="submit" class="btn btn-primary" value="Send message" id="submit">
                                    </div>
                                </form>
                            </div>
                            <!-- Form End -->
                        </div>
                    </div>
                </div>
                <!-- JavaScript Libraries -->
                <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
                <script src="lib/wow/wow.min.js"></script>
                <script src="lib/easing/easing.min.js"></script>
                <script src="lib/waypoints/waypoints.min.js"></script>
                <script src="lib/owlcarousel/owl.carousel.min.js"></script>
                <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

                <!-- Template Javascript -->
                <script src="js/main.js">

                </script>
            </div>
        </body>

        </html>
        <?php
    } else {
        echo "Property not found!";
    }
} else {
    echo "Invalid request!";
}
?>
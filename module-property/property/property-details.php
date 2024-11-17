<?php
require_once 'google_sheets_integration.php';

$spreadsheetId = '1X98yCqOZAK_LDEVKWWpyBeMlBePPZyIKfMYMMBLivmg';

$rangeSheet2 = 'Room List!A2:L';
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
        $propertyPriceRaw = $selectedProperty[5];
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

        $propertyImageFolder = 'img/RentronicsImage';
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
        $deposit = $propertyPriceNumeric;

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
                <!-- Include the navbar -->
                <?php include('header.php'); ?>
                <!-- Navbar End -->

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
                                        <p>Rental Included :-
                                        - Furnished unit<br>
                                        - Free utilities fee (water, electricity, high speed fiber internet)<br>
                                        - Add On Service ++ eg: Aircond, Home Laundry service, Cleaner (contact for more detail)
                                        </p>
                                    </section>
                                    <section class="place-furnish">
                                        <p class="fw-bold">Furnishing</p>
                                        <p><?= $propertyBed ?> Bed</p>
                                        <p>1 Wall Fan.</p>
                                        <p>Shelf.</p>
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
                                            src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d1182.9307993648636!2d103.3993267597314!3d4.241850432370395!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31c887ab9332ec8f%3A0xca8aba7fb0947d79!2s50310%2C%20Lorong%20Penaga%202%2C%20Taman%20Desa%20Jaya%2C%2024000%20Chukai%2C%20Terengganu!5e0!3m2!1sen!2smy!4v1701324553118!5m2!1sen!2smy" width="600" height="450" style="border:0;" 
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
                                                    <span>Deposit + One Month (Advance Rental)</span>
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
                                    action="https://script.google.com/macros/s/AKfycbxoTS2Q9BMalDJZkJR9UOz7K_josr7eAAsh1lSekB9aAQCcsqChrK6Ps6nbjhAsPQ5INg/exec"
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

                <!-- Include the footer -->
                <?php include('footer.php'); ?>
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
            <script src="js/main.js"></script>
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
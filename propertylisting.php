<?php
require_once 'google_sheets_integration.php';

$spreadsheetId = '1saIMUxbothIXVgimL9EMgnGIZ7lNWN1d_YnjvK1Znyw';

// Fetch data from Sheet2 (starting from A2)
$rangeSheet2 = 'Sheet2!A2:J';
$dataSheet2 = getData($spreadsheetId, $rangeSheet2);

// Check if the 'id' parameter is set in the URL
if (isset($_GET['id'])) {
    $propertyId = $_GET['id'];

    // Filter data based on the Property ID
    $filteredData = array_filter($dataSheet2, function ($row) use ($propertyId) {
        // Assuming Property ID is in the second column (index 1)
        return $row[1] == $propertyId;
    });

    // Check if a property with the given ID was found
    if (count($filteredData) > 0) {

        $selectedProperty = null;

        foreach ($filteredData as $row) {
            if ($row[1] == $propertyId) {
                $selectedProperty = $row;
                break;
            }
        }

        if ($selectedProperty) {
            $propertyArea = $selectedProperty[2];

            usort($filteredData, function ($a, $b) {
                // If both are 'Occupied', keep their order
                if ($a[7] === 'Occupied' && $b[7] === 'Occupied') {
                    return 0;
                }

                // If $a is 'Occupied', move it to the end
                if ($a[7] === 'Occupied') {
                    return 1;
                }

                // If $b is 'Occupied', move it to the end
                if ($b[7] === 'Occupied') {
                    return -1;
                }

                // For non-'Occupied' properties, keep their order
                return 0;
            });

            // Now you can use $filteredData to display the details in your HTML
            ?>
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
                <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap"
                    rel="stylesheet">

                <!-- Icon Font Stylesheet -->
                <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

                <!-- Libraries Stylesheet -->
                <link href="lib/animate/animate.min.css" rel="stylesheet">
                <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

                <!-- Customized Bootstrap Stylesheet -->
                <link href="css/bootstrap.min.css" rel="stylesheet">

                <!-- Template Stylesheet -->
                <link href="css/style.css" rel="stylesheet">
                <style>
                    .property-item.occupied-property {
                        filter: grayscale(100%) !important;
                    }

                    .property-item.occupied-property h5,
                    .property-item.occupied-property span,
                    .property-item.occupied-property a,
                    .property-item.occupied-property p {
                        color: #666666 !important;
                        /* Change text color to a muted gray */
                    }
                </style>
            </head>

            <body>
                <!-- Navbar -->
                <div class="container-fluid nav-bar bg-transparent">
                    <nav class="navbar navbar-expand-lg bg-white navbar-light py-0 px-4">
                        <a href="index.php" class="navbar-brand d-flex align-items-center text-center">
                            <div class="icon p-2 me-2">
                                <!-- <img class="img-fluid" src="img/icon-deal.png" alt="Icon" style="width: 30px; height: 30px;"> -->
                            </div>
                            <h1 class="m-0 text-primary">Rentronic</h1>
                        </a>
                        <button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarCollapse">
                            <div class="navbar-nav ms-auto">
                                <a href="index.php" class="nav-item nav-link active">Home</a>
                                <a href="about.html" class="nav-item nav-link">About</a>
                                <div class="nav-item dropdown">
                                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Property</a>
                                    <div class="dropdown-menu rounded-0 m-0">
                                        <a href="propertylisting.php" class="dropdown-item">Property List</a>
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
                                <a href="#contact" class="nav-item nav-link">Contact</a>
                            </div>
                            <a href="" class="btn btn-primary px-3 d-none d-lg-flex">Add Property</a>
                        </div>
                    </nav>
                </div>
                <!-- End of Navbar -->

                <div class="container">
                    <div class="container-sm px-5 py-5">
                        <!-- Option For Sale -->
                        <div class="dropdown-container">
                            <div class="row g-0 align-items-end">
                                <div class="col-lg-6">
                                    <div class="text-start mx-auto mb-5">
                                        <h1 class="mb-3">
                                            <?= $propertyArea ?>
                                        </h1>
                                        <p>Eirmod sed ipsum dolor sit rebum labore magna erat. Tempor ut dolore lorem kasd vero
                                            ipsum
                                            sit eirmod sit diam justo sed rebum.</p>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- Box House -->
                        <div class="tab-content mx-2 px-2"> <!-- Reduce the margin and padding for smaller screens -->
                            <div id="tab-1" class="tab-pane fade show p-0 active">
                                <div class="row g-4">
                                    <!-- Individual Box -->
                                    <!-- Individual Box -->
                                    <?php foreach ($filteredData as $row): ?>
                                        <?php
                                        // Check if the property is Occupied
                                        $isOccupied = $row[7] === 'Occupied';

                                        // Generate the link for the property details with a condition for Occupied properties
                                        $detailsLink = $isOccupied ? '#' : "property-details.php?id={$row[3]}";
                                        ?>
                                        <div class="col-lg-4 col-md-6 col-sm-12 px-2 wow fadeInUp" data-wow-delay="0.1s">
                                            <?php
                                            // Add a class for occupied status
                                            $propertyClass = $isOccupied ? 'occupied-property' : '';
                                            ?>
                                            <div class="property-item rounded overflow-hidden <?= $propertyClass ?>">
                                                <div class="position-relative overflow-hidden">
                                                    <a href="<?= $detailsLink ?>"><img class="img-fluid" src="img/property-1.jpg"
                                                            alt=""></a>
                                                    <div
                                                        class="<?= $isOccupied ? 'bg-danger' : 'bg-primary' ?> rounded text-white position-absolute start-0 top-0 m-2 py-1 px-2">
                                                        <?= $row[7] ?>
                                                    </div>
                                                    <div
                                                        class="bg-white rounded-top text-primary position-absolute start-0 bottom-0 mx-2 pt-1 px-2 <?= $propertyClass ?>">
                                                        <?= $row[8] ?>
                                                    </div>
                                                </div>
                                                <div class="p-3 pb-0">
                                                    <h5 class="text-primary mb-3">
                                                        <?= $row[6] ?>
                                                    </h5>
                                                    <?php if ($isOccupied): ?>
                                                        <span class="d-block h5 mb-2 text-muted">
                                                            <?= $row[3] ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <a class="d-block h5 mb-2" href="<?= $detailsLink ?>">
                                                            <?= $row[3] ?>
                                                        </a>
                                                    <?php endif; ?>
                                                    <p><i class="fa fa-map-marker-alt text-primary me-2"></i>
                                                        <?= $row[2] ?>
                                                    </p>
                                                </div>
                                                <div class="d-flex border-top">
                                                    <small class="flex-fill text-center border-end py-2"><i
                                                            class="fa fa-ruler-combined text-primary me-2"></i>
                                                        <?= $row[5] ?>
                                                    </small>
                                                    <small class="flex-fill text-center border-end py-2"><i
                                                            class="fa fa-bed text-primary me-2"></i>3 Bed</small>
                                                    <small class="flex-fill text-center py-2"><i
                                                            class="fa fa-bath text-primary me-2"></i>2 Bath</small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>



                                    <div class="col-12 text-center wow fadeInUp" data-wow-delay="0.1s">
                                        <a class="btn btn-primary py-3 px-4" href="index.php#location">Browse More Property</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End of Box House -->
                    </div>
                </div>
                <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>

                <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
                <script src="lib/wow/wow.min.js"></script>
                <script src="lib/easing/easing.min.js"></script>
                <script src="lib/waypoints/waypoints.min.js"></script>
                <script src="lib/owlcarousel/owl.carousel.min.js"></script>

                <!-- Template Javascript -->
                <script src="js/main.js"></script>
            </body>

            </html>
            <?php
        } else {
            // Handle the case when the property with the given ID was not found
            echo '<div class="more-place-incoming">More Place Incoming</div>';
        }
    } else {
        // Handle the case when 'id' parameter is not set in the URL
        echo "Invalid request!";
    }
}

?>
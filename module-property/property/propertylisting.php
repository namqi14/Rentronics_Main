<?php
require_once '../../module-auth/google_sheets_integration.php';

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

$spreadsheetId = '1X98yCqOZAK_LDEVKWWpyBeMlBePPZyIKfMYMMBLivmg';

// Fetch data from Sheet2 (starting from A2)
$rangeSheet2 = 'Room List!A2:L';
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

        $firstMatchedProperty = reset($filteredData);

        $propertyArea = $firstMatchedProperty[2];
        $propertyTitle = $firstMatchedProperty[3];
        $propertyLocation = $firstMatchedProperty[8];
        
        usort($filteredData, function ($a, $b) {
            // Move Vacant properties (TRUE) to the beginning
            if ($a[6] === 'TRUE' && $b[6] === 'FALSE') {
                return -1;
            }
            if ($a[6] === 'FALSE' && $b[6] === 'TRUE') {
                return 1;
            }
            return 0;
        });

        // Debug statements
        error_reporting(E_ALL);
        ini_set('display_errors', '1');

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
            <link href="../../img/favicon.ico" rel="icon">

            <!-- Google Web Fonts -->
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap"
                rel="stylesheet">

            <!-- Icon Font Stylesheet -->
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

            <!-- Libraries Stylesheet -->
            <link href="../../lib/animate/animate.min.css" rel="stylesheet">
            <link href="../../lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

            <!-- Customized Bootstrap Stylesheet -->
            <link href="../../css/bootstrap.min.css" rel="stylesheet">

            <!-- Template Stylesheet -->
            <link href="../../css/style.css" rel="stylesheet">
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
            </style>
        </head>

        <body>
            <!-- Navbar -->
            <!-- Include the navbar -->
            <?php include('../../header.php'); ?>
            <!-- Navbar End -->

            <div class="container">
                <div class="container-sm px-5 py-5">
                    <!-- Option For Sale -->
                    <div class="dropdown-container">
                        <div class="row g-0 align-items-end">
                            <div class="col-lg-6">
                                <div class="text-start mx-auto mb-5">
                                    <h1 class="mb3"><?= $propertyArea ?></h1>
                                    <p><?= $propertyLocation ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Box House -->
                    <div class="tab-content mx-2 px-2"> <!-- Reduce the margin and padding for smaller screens -->
                        <div id="tab-1" class="tab-pane fade show p-0 active">
                            <div class="row g-4">
                                <!-- Individual Box -->
                                <?php foreach ($filteredData as $row): ?>
                                    <?php
                                    // Check if the property is Occupied
                                    $isOccupied = $row[6] === 'FALSE';

                                    // Generate the link for the property details with a condition for Occupied properties
                                    $detailsLink = $isOccupied ? '#' : "property-details.php?id={$row[3]}";

                                    $propertyImageFolder = 'img/RentronicsImage';
                                    $propertyImageDirectory = $propertyImageFolder . DIRECTORY_SEPARATOR . $propertyArea . DIRECTORY_SEPARATOR . sanitizeFolderName($row[3]);

                                    $validExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                                    $imageFiles = [];

                                    if (is_dir($propertyImageDirectory)) {
                                        $directoryIterator = new DirectoryIterator($propertyImageDirectory);

                                        foreach ($directoryIterator as $fileInfo) {
                                            if (!$fileInfo->isDot() && $fileInfo->isFile()) {
                                                $extension = strtolower($fileInfo->getExtension());

                                                if (in_array($extension, $validExtensions) && stripos($fileInfo->getBasename(), '2') !== false) {
                                                    $imageFiles[] = $fileInfo->getPathname();
                                                }
                                            }
                                        }
                                    }

                                    if (!empty($imageFiles)) {
                                        $secondImage = reset($imageFiles);
                                    } else {
                                        $secondImage = 'img/incoming.jpg'; // Replace with the path to a default image
                                    }
                                    ?>
                                    <div class="col-lg-4 col-md-6 col-sm-12 px-2 wow fadeInUp" data-wow-delay="0.1s">
                                        <?php
                                        // Add a class for occupied status
                                        $propertyClass = $isOccupied ? 'occupied-property' : '';
                                        ?>
                                        <div class="property-item rounded overflow-hidden <?= $propertyClass ?>">
                                            <div class="position-relative overflow-hidden">
                                                <?php
                                                // Check if there is a second image in the folder
                                                ?>
                                                <a href="<?= $detailsLink ?>" class="d-block"
                                                    style="position: relative; overflow: hidden; height: 0; padding-bottom: 70%;">
                                                    <img class="img-fluid position-absolute top-0 start-0 w-100 h-100"
                                                        src="<?= $secondImage ?>" alt="">
                                                </a>
                                                <div
                                                    class="<?= $isOccupied ? 'bg-danger' : 'bg-primary' ?> rounded text-white position-absolute start-0 top-0 m-2 py-1 px-2">
                                                    <?= $isOccupied ? 'Occupied' : 'Vacant' ?>
                                                </div>
                                                <div
                                                    class="bg-white rounded-top text-primary position-absolute start-0 bottom-0 mx-2 pt-1 px-2 <?= $propertyClass ?>">
                                                    <?= $row[8] ?>
                                                </div>
                                            </div>
                                            <div class="p-3 pb-0">
                                                <h5 class="text-primary mb-3">
                                                    <?= $row[5] ?>
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
                                                    <?= $row[4] ?>
                                                </small>
                                                <small class="flex-fill text-center border-end py-2"><i
                                                        class="fa fa-bed text-primary me-2"></i><?= $row[10]?> Bed</small>
                                                <small class="flex-fill text-center py-2"><i
                                                        class="fa fa-bath text-primary me-2"></i><?= $row[11]?> Bath</small>
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
            <!-- Footer Start -->
            <!-- Include the footer -->
            <?php include('../../footer.php'); ?>
            <!-- Footer End -->

            <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="../../lib/wow/wow.min.js"></script>
            <script src="../../lib/easing/easing.min.js"></script>
            <script src="../../lib/waypoints/waypoints.min.js"></script>
            <script src="../../lib/owlcarousel/owl.carousel.min.js"></script>

            <!-- Template Javascript -->
            <script src="../../js/main.js"></script>
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

?>

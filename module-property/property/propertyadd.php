<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';  // Include your database connection file

$error = '';  // Initialize $error variable
$msg = '';    // Initialize $msg variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $property_id = $_POST['property_id'];
    $property_name = $_POST['property_name'];
    $property_type = $_POST['property_type'];
    $location = $_POST['location'];
    $maps_link = $_POST['maps_link'];

    // Set PropertyOwn as NULL
    $property_own = NULL;  // This will insert NULL into the database for PropertyOwn

    // Check if all required fields are set
    if (!empty($property_id) && !empty($property_name) && !empty($property_type) && !empty($location) && !empty($maps_link)) {
        // Prepare SQL query to insert data into the property table
        $stmt = $conn->prepare("INSERT INTO property (PropertyID, PropertyName, PropertyType, PropertyOwn, Location, Maps) VALUES (?, ?, ?, ?, ?, ?)");

        if ($stmt === false) {
            $error = "<p class='alert alert-danger'>Error preparing statement: " . $conn->error . "</p>";
        } else {
            // Bind the form data to the query parameters
            $stmt->bind_param("ssssss", $property_id, $property_name, $property_type, $property_own, $location, $maps_link);

            // Execute the query
            if ($stmt->execute()) {
                $msg = "<p class='alert alert-success'>Property inserted successfully</p>";
            } else {
                $error = "<p class='alert alert-danger'>Error inserting property into database: " . $stmt->error . "</p>";
            }

            // Close the statement
            $stmt->close();
        }
    } else {
        $error = "<p class='alert alert-warning'>Please fill in all required fields</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Rentronics</title>

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome & Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Feather Icons & Owl Carousel & Magnific Popup -->
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">

    <!-- Bootstrap & Custom Stylesheet -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC4EY4OJ1heDaiCKzYtRvUelvDQF1Rku1k&libraries=places" async defer></script>
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <link href="../css/property.css" rel="stylesheet">

    <!-- Custom styles -->
    <style>
        .nav-bar {
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        .place-picker-container {
            padding: 20px;
        }

        #map {
            height: 400px;
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="container-fluid bg-white p-0">
        <!-- Navbar and Sidebar Start-->
        <?php include('../../nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="content container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="page-title">Property Registry</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Property</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Add Property Details</h4>
                            </div>
                            <form method="post" action="" name="property-form">
                                <div class="card-body">
                                    <h5 class="card-title">Property Details</h5>
                                    <?php echo $error; ?>
                                    <?php echo $msg; ?>
                                    <div class="row">
                                        <div class="col-xl-6">
                                            <!-- Property ID -->
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Property ID</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="property_id" required placeholder="Enter Property ID">
                                                </div>
                                            </div>

                                            <!-- Property Name -->
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Property Name</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="property_name" required placeholder="Enter Property Name">
                                                </div>
                                            </div>

                                            <!-- Property Type -->
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Property Type</label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" name="property_type" required>
                                                        <option value="">-- Select --</option>
                                                        <option value="Condominium">Condominium</option>
                                                        <option value="Terrace">Terrace</option>
                                                        <!-- Add more property types if needed -->
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Manual Location Input -->
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Location</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="location" required placeholder="Enter Location">
                                                </div>
                                            </div>

                                            <!-- Google Maps Integration with Check Button -->
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Location Search</label>
                                                <div class="col-lg-6">
                                                    <input id="location-input" type="text" class="form-control" name="location_search" required placeholder="Search for Location">
                                                </div>
                                                <div class="col-lg-3">
                                                    <button type="button" class="btn btn-primary" id="check-location-btn">Check</button>
                                                </div>
                                            </div>
                                            <div id="map"></div>

                                            <!-- Maps Link (auto-populated) -->
                                            <br>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Maps Link</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" id="maps_link" name="maps_link" readonly>
                                                </div>
                                            </div>

                                            <input type="submit" value="Submit" class="btn btn-primary" name="add" style="margin-left: 200px;">
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

    <!-- Ensure jQuery is loaded first -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js" integrity="sha512-4lykFR6C2W55I60sYddEGjieC2fU79R7GUtaqr3DzmNbo0vSaO1MfUjMoTFYYuedjfEix6uV9jVTtRCSBU/Xiw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

    <!-- Custom JavaScript -->
    <script src="../../js/main.js"></script>

    <!-- Google Maps Script with AdvancedMarkerElement -->
    <script>
        var map;
        var marker;
        var geocoder;
        var infowindow;

        function initMap() {
            var initialLatLng = {
                lat: 3.1390,
                lng: 101.6869
            }; // Default to Kuala Lumpur
            map = new google.maps.Map(document.getElementById('map'), {
                center: initialLatLng,
                zoom: 13
            });

            geocoder = new google.maps.Geocoder();
            infowindow = new google.maps.InfoWindow();

            var input = document.getElementById('location-input');
            var autocomplete = new google.maps.places.Autocomplete(input, {
                types: ['(cities)'] // Restrict results to cities only
            });
            autocomplete.bindTo('bounds', map);

            // Use the more widely supported google.maps.Marker
            marker = new google.maps.Marker({
                map: map,
                draggable: true,
                position: initialLatLng
            });

            autocomplete.addListener('place_changed', function() {
                var place = autocomplete.getPlace();
                if (!place.geometry) {
                    window.alert("No details available for the selected location.");
                    return;
                }

                // Set marker position and map center to the selected place
                map.setCenter(place.geometry.location);
                marker.setPosition(place.geometry.location);
                map.setZoom(15);

                var latLng = marker.getPosition();
                var mapsLink = 'https://www.google.com/maps?q=' + latLng.lat() + ',' + latLng.lng();
                document.getElementById('maps_link').value = mapsLink;

                infowindow.setContent(place.formatted_address);
                infowindow.open(map, marker);
            });

            // Allow users to drag the marker to select a custom position
            marker.addListener('dragend', function() {
                var latLng = marker.getPosition();
                var mapsLink = 'https://www.google.com/maps?q=' + latLng.lat() + ',' + latLng.lng();
                document.getElementById('maps_link').value = mapsLink;
            });
        }

        // Check button to trigger location check
        document.getElementById('check-location-btn').addEventListener('click', function() {
            var input = document.getElementById('location-input').value;
            if (input) {
                var request = {
                    query: input,
                    fields: ['name', 'geometry'],
                };

                var service = new google.maps.places.PlacesService(map);
                service.findPlaceFromQuery(request, function(results, status) {
                    if (status === google.maps.places.PlacesServiceStatus.OK) {
                        map.setCenter(results[0].geometry.location);
                        marker.setPosition(results[0].geometry.location);
                        map.setZoom(15);

                        var latLng = marker.getPosition();
                        var mapsLink = 'https://www.google.com/maps?q=' + latLng.lat() + ',' + latLng.lng();
                        document.getElementById('maps_link').value = mapsLink;

                        infowindow.setContent(results[0].name);
                        infowindow.open(map, marker);
                    } else {
                        window.alert("No results found. Please try a different location.");
                    }
                });
            } else {
                window.alert("Please enter a location to check.");
            }
        });

        // Initialize the map
        window.addEventListener('load', initMap);
    </script>
</body>

</html>
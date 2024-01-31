<?php
session_start();
require_once 'google_sheets_integration.php';

$spreadsheetId = '1saIMUxbothIXVgimL9EMgnGIZ7lNWN1d_YnjvK1Znyw';

$range = 'Sheet2!B2';

$error = '';  // Initialize $error variable
$msg = '';    // Initialize $msg variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $property_id = $_POST['property_id'];
    $property_name = $_POST['property_name'];
    $room_no = $_POST['room_no'];
    $room_type = $_POST['room_type'];
    $rent_amount = $_POST['rent_amount'];
    $vacant_status = $_POST['vacant_status'];
    $property_type = $_POST['property_type'];
    $location = $_POST['location'];
    $katil = $_POST['katil'];
    $tandas = $_POST['tandas'];

    // ... (retrieve other form fields)

    if (isset($property_id, $property_name, $room_no, $room_type, $rent_amount, $vacant_status /* Add other fields */)) {
        // Database insertion successful, now insert into Google Sheet

        $data = [
            'Property ID' => $property_id,
            'Property Name' => $property_name,
            'Room No' => $room_no,
            'Room Type' => $room_type,
            'Rent Amount' => $rent_amount,
            'Vacant Status' => $vacant_status,
            'Property Type' => $property_type,
            'Location' => $location,
            'Katil' => $katil,
            'Tandas' => $tandas,
        ];

        try {
            // Call the writeData function to insert data into Google Sheet
            writeData($spreadsheetId, $range, $data);
            $msg = "<p class='alert alert-success'>Property Inserted Successfully</p>";
        } catch (Exception $e) {
            $error = "<p class='alert alert-warning'>Error inserting data into Google Sheet: " . $e->getMessage() . "</p>";
        }
    } else {
        $error = "<p class='alert alert-warning'>Incomplete form data. Please fill in all required fields</p>";
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
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap"
        rel="stylesheet">

    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">

    <!-- Feathericon CSS -->
    <link rel="stylesheet" href="assets/css/feathericon.min.css">

    <!-- Main CSS -->
    <!-- <link rel="stylesheet" href="assets/css/style.css"> -->
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
        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="content container-fluid">

                <!-- Page Header -->
                <div class="page-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="page-title">Property</h3>
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
                            <form method="post" enctype="multipart/form-data"
                                action="https://script.google.com/macros/s/AKfycby-vFPw8j9sQCUsVYwfCSKxq4KpYTAHW9Rx0u_2D8OlCcoe8qXTDZcr8vJQB-HKwmMRsg/exec"
                                name="property-form">
                                <div class="card-body">
                                    <h5 class="card-title">Property Details</h5>
                                    <?php echo $error; ?>
                                    <?php echo $msg; ?>

                                    <div class="row">
                                        <div class="col-xl-6">
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Property ID</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="property_id" required
                                                        placeholder="Enter Property ID">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Property Name</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="property_name"
                                                        required placeholder="Enter Property Name">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Room No</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="room_no" required
                                                        placeholder="Enter Room No">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Room Type</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="room_type" required
                                                        placeholder="Enter Room Type">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Size</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="size"
                                                        placeholder="Enter Size">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-6">
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Rent Amount</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="rent_amount" required
                                                        placeholder="Enter Rent Amount">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Vacant Status</label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" required name="vacant_status">
                                                        <option value="">Select Vacant Status</option>
                                                        <option value="occupied">Occupied</option>
                                                        <option value="vacant">Vacant</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <!-- Add other fields here -->
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Property Type</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="property_type"
                                                        placeholder="Enter Property Type">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Location</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="location"
                                                        placeholder="Enter Location">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Katil</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="katil"
                                                        placeholder="Enter Katil">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Tandas</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="tandas"
                                                        placeholder="Enter Tandas">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="card-title">Other Sections</h5>
                                    <!-- ... (add other sections) ... -->

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label"><b>Is Featured?</b></label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" required name="isFeatured">
                                                        <option value="">Select...</option>
                                                        <option value="0">No</option>
                                                        <option value="1">Yes</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="submit" value="Submit" class="btn btn-primary" name="add"
                                        style="margin-left: 200px;">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- /Main Wrapper -->


    <!-- jQuery -->
    <script src="assets/js/jquery-3.2.1.min.js"></script>
    <script src="assets/plugins/tinymce/tinymce.min.js"></script>
    <script src="assets/plugins/tinymce/init-tinymce.min.js"></script>
    <!-- Bootstrap Core JS -->
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>

    <!-- Slimscroll JS -->
    <script src="assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>

    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>

</body>

</html>
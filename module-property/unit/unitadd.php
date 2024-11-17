<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';

if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

$error = '';  // Initialize $error variable
$msg = '';    // Initialize $msg variable

// Fetch Property IDs from the Property table
$propertyOptions = '';
$result = $conn->query("SELECT PropertyID, PropertyName FROM Property");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $propertyOptions .= "<option value='" . $row['PropertyID'] . "'>" . $row['PropertyID'] . " - " . $row['PropertyName'] . "</option>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $unit_id = $_POST['unit_id'];
    $property_id = $_POST['property_id'];
    $unit_no = $_POST['unit_no'];
    $investor = $_POST['investor'];
    $floor_plan = '';

    // Handle file upload
    if (isset($_FILES['floor_plan']) && $_FILES['floor_plan']['error'] == 0) {
        $target_dir = "img/floorplan/";
        $target_file = $target_dir . basename($_FILES["floor_plan"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if file is an actual image or fake image
        $check = getimagesize($_FILES["floor_plan"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["floor_plan"]["tmp_name"], $target_file)) {
                $floor_plan = $target_file;
            } else {
                $error = "<p class='alert alert-warning'>Sorry, there was an error uploading your file.</p>";
            }
        } else {
            $error = "<p class='alert alert-warning'>File is not an image.</p>";
        }
    }

    if (isset($unit_id, $property_id, $unit_no, $investor)) {
        // Prepare SQL query to insert data into the database
        $stmt = $conn->prepare("INSERT INTO Unit (UnitID, PropertyID, UnitNo, FloorPlan, Investor) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $unit_id, $property_id, $unit_no, $floor_plan, $investor);

        if ($stmt->execute()) {
            $msg = "<p class='alert alert-success'>Unit inserted successfully</p>";
        } else {
            $error = "<p class='alert alert-warning'>Error inserting data into database: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        $error = "<p class='alert alert-warning'>Incomplete form data. Please fill in all required fields</p>";
    }
    $conn->close();
}
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
    <!-- Main CSS -->
    <link href="../../css/style.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <link href="../../css/bootstrap.min.css" rel="stylesheet">

    <style>
        .nav-bar {
            position: sticky;
            top: 0;
            z-index: 1000; /* Ensures it stays on top of other elements */
        }
        html, body {
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
                            <h3 class="page-title">Unit Registry</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Unit</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Add Unit Details</h4>
                            </div>
                            <form method="post" action="" enctype="multipart/form-data" name="unit-form">
                                <div class="card-body">
                                    <h5 class="card-title">Unit Details</h5>
                                    <?php echo $error; ?>
                                    <?php echo $msg; ?>
                                    <div class="row">
                                        <div class="col-xl-6">
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Unit ID</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="unit_id" required placeholder="Enter Unit ID">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Property ID</label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" name="property_id" required>
                                                        <option value="">Select Property</option>
                                                        <?php echo $propertyOptions; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Unit No</label>
                                                <div class="col-lg-9">
                                                    <input type="text" class="form-control" name="unit_no" required placeholder="Enter Unit No">
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Investor</label>
                                                <div class="col-lg-9">
                                                    <select class="form-control" name="investor" required>
                                                        <option value="">Select Investor</option>
                                                        <option value="Internal">Internal</option>
                                                        <option value="External">External</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group row">
                                                <label class="col-lg-3 col-form-label">Floor Plan</label>
                                                <div class="col-lg-9">
                                                    <input type="file" class="form-control" name="floor_plan" accept="image/*">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="submit" value="Submit" class="btn btn-primary" name="add" style="margin-left: 200px;">
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
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js" crossorigin="anonymous">
    </script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>

    <!-- Template Javascript -->
    <script src="../../js/main.js"></script>

</body>
</html>

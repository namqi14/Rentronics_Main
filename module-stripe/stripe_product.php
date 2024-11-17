<?php
require_once('vendor/autoload.php');
require_once('google_sheets_integration.php');

// Start the session
session_start();
if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

// Include necessary Stripe library
\Stripe\Stripe::setApiKey('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');

// Get data from Google Sheets
$spreadsheetId = '1X98yCqOZAK_LDEVKWWpyBeMlBePPZyIKfMYMMBLivmg';
$rangeSheet1 = 'Property List!A2:F';
$dataSheet1 = getData($spreadsheetId, $rangeSheet1);

// Retrieve all products from Stripe
$products = \Stripe\Product::all(['limit' => 100]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extract product ID from the form
    $productId = $_POST['productId'];

    try {
        // Deactivate the product on Stripe
        $product = \Stripe\Product::update(
            $productId,
            ['active' => false]
        );

        $response = ['status' => 'success', 'message' => 'Product deactivated successfully'];
    } catch (Exception $e) {
        error_log("Stripe error: " . $e->getMessage());
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }

    // Output the response as JSON
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Rentronics - Manage Products</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">
    <link href="img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/magnific-popup/dist/magnific-popup.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <style>
        .nav-bar {
            position: sticky;
        }
        .page-wrapper {
            padding: 30px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            padding: 20px;
            border-radius: 10px 10px 0 0;
        }
        .input-group .currency {
            padding: 6px 12px;
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            border-right: 0;
            border-radius: .25rem 0 0 .25rem;
            display: flex;
            align-items: center;
        }
        .form-control, .input-group, .input-group-append {
            flex: 1 1 auto;
            width: 70%;
            margin-bottom: 0;
        }
        .input-group .form-control {
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container-fluid bg-white p-0">
        <?php include('nav_sidebar.php'); ?>
        <div class="page-wrapper">
            <div class="content container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="page-title">Manage Products</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Manage Products</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Product List</h4>
                            </div>
                            <div class="card-body">
                                <table id="productTable" class="display">
                                    <thead>
                                        <tr>
                                            <th>Product ID</th>
                                            <th>Name</th>
                                            <th>Active</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products->data as $product): ?>
                                            <tr>
                                                <td><?php echo $product->id; ?></td>
                                                <td><?php echo $product->name; ?></td>
                                                <td class="product-status"><?php echo $product->active ? 'Yes' : 'No'; ?></td>
                                                <td>
                                                    <?php if ($product->active): ?>
                                                        <button class="btn btn-danger deactivate-btn" data-product-id="<?php echo $product->id; ?>">Deactivate</button>
                                                    <?php else: ?>
                                                        <button class="btn btn-secondary" disabled>Inactive</button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var table = $('#productTable').DataTable();

        $('#productTable tbody').on('click', '.deactivate-btn', function() {
            var button = $(this);
            var productId = button.data('product-id');
            var formData = new FormData();
            formData.append('productId', productId);

            console.log('Deactivate button clicked for product ID:', productId); // Debugging log

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Response from server:', data); // Debugging log
                if (data.status === 'success') {
                    var row = button.closest('tr');
                    row.find('.product-status').text('No');
                    button.prop('disabled', true);
                    button.removeClass('btn-danger').addClass('btn-secondary').text('Inactive');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred: ' + error.message);
            });
        });
    });
    </script>
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.1/feather.min.js"
        integrity="sha512-4lykFR6C2W55I60sYddEGjieC2fU79R7GUtaqr3DzmNbo0vSaO1MfUjMoTFYYuedjfEix6uV9jVTtRCSBU/Xiw=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/magnific-popup/dist/jquery.magnific-popup.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>



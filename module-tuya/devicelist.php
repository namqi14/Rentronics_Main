<?php
namespace MyTuyaAPI;
session_start();
require 'vendor/autoload.php'; // Include the Composer autoload file

use tuyapiphp\TuyaApi;

date_default_timezone_set('UTC'); // Set timezone to UTC

class TestTuyaAPI {
    private $clientId;
    private $clientSecret;
    private $baseUrl;
    private $accessToken;
    private $client;
    private $uid;

    public function __construct($clientId, $clientSecret, $uid) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->uid = $uid;
        $this->baseUrl = "https://openapi.tuyaus.com";

        // Initialize Tuya client
        $config = [
            'accessKey' => $this->clientId,
            'secretKey' => $this->clientSecret,
            'baseUrl' => $this->baseUrl,
        ];
        $this->client = new TuyaApi($config);
    }

    public function getAccessToken() {
        $response = $this->client->token->get_new();
        if (!$response->success) {
            throw new \Exception("Failed to get access token: " . $response->msg);
        }
        $this->accessToken = $response->result->access_token;
        return $this->accessToken;
    }

    public function getDevices() {
        $this->getAccessTokenIfNecessary();
        $response = $this->client->devices($this->accessToken)->get_app_list($this->uid);
        if (!$response->success) {
            if ($response->msg == 'permission deny') {
                throw new \Exception("Permission Denied: Ensure your API token has the required permissions.");
            }
            throw new \Exception("Failed to get devices: " . $response->msg);
        }
        if (!isset($response->result)) {
            throw new \Exception("Unexpected response structure: " . json_encode($response));
        }
        return $response->result;
    }

    private function getAccessTokenIfNecessary() {
        if (!$this->accessToken) {
            $this->getAccessToken();
        }
    }
}

// Example usage
try {
    $clientId = "vw55nknx8twhrqu78wyd";
    $clientSecret = "555bb62672f840159d97dbed9a3c6e91";
    $uid = "az1643603772183o0x0S"; // Your UID
    $api = new \MyTuyaAPI\TestTuyaAPI($clientId, $clientSecret, $uid);

    // Get devices
    $devices = $api->getDevices();
} catch (\Exception $e) {
    die('Error: ' . $e->getMessage());
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
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <!-- Feathericon CSS -->
    <link rel="stylesheet" href="assets/css/feathericon.min.css">
    <!-- Main CSS -->
    <link href="css/style.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.dataTables.min.css">
    <!-- jQuery (Ensure this is before DataTables JS) -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 3px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            color: #333;
            text-transform: uppercase;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .nav-bar {
        position: sticky;
        top: 0;
        z-index: 1000; /* Ensures it stays on top of other elements */
        }
    </style>
</head>
<body>
    <!-- Navbar and Sidebar Start-->
    <?php include('nav_sidebar.php'); ?>
    <!-- Navbar and Sidebar End -->
    <div class="container">
        <h1>Door Lock List</h1>
        <?php if (!empty($message)) : ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <table id="deviceListTable" class="display responsive nowrap" style="width:70%">
            <thead>
                <tr>
                    <th>Device Name</th>
                    <th>Device ID</th>
                    <th>Product Name</th>
                    <th>Online Status</th>
                    <th>IP</th>
                    <th>Model</th>
                    <th>Category</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($devices as $device) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($device->name); ?></td>
                    <td><?php echo htmlspecialchars($device->id); ?></td>
                    <td><?php echo htmlspecialchars($device->product_name); ?></td>
                    <td><?php echo $device->online ? 'Online' : 'Offline'; ?></td>
                    <td><?php echo htmlspecialchars($device->ip); ?></td>
                    <td><?php echo htmlspecialchars($device->model); ?></td>
                    <td><?php echo htmlspecialchars($device->category); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- jQuery -->
    <script src="assets/js/jquery-3.2.1.min.js"></script>
    <!-- Bootstrap Core JS -->
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#deviceListTable').DataTable({
                responsive: true,
                columnDefs: [
                    { targets: [1], visible: false } // Hides the second column (Device ID)
                ]
            });

            $('#filterToggle').click(function() {
                $('#filterOptions').toggle();
            });

            $('.filter-button').click(function() {
                $(this).toggleClass('active').siblings().removeClass('active');
                var filterType = $(this).data('filter');
                var filterValue = $(this).data('value');

                if (filterType == 'productName') {
                    table.column(2).search(filterValue).draw();
                } else if (filterType == 'onlineStatus') {
                    table.column(3).search(filterValue).draw();
                } else if (filterType == 'category') {
                    table.column(6).search(filterValue).draw();
                }
            });
            document.getElementById('sidebarToggle').addEventListener('click', function() {
            var sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
        });
        });
    </script>
</body>
</html>

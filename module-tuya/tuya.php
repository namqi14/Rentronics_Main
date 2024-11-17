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

    private function generateSignature($httpMethod, $url, $body, $timestamp, $nonce) {
        $stringToSign = $httpMethod . "\n" .
                        hash('sha256', $body) . "\n" .
                        "client_id:{$this->clientId}\n" .
                        "access_token:{$this->accessToken}\n" .
                        "t:{$timestamp}\n" .
                        "nonce:{$nonce}\n" .
                        $url;
        return strtoupper(hash_hmac('sha256', $stringToSign, $this->clientSecret));
    }

    public function changeLockPassword($deviceId, $newPassword) {
        $this->getAccessTokenIfNecessary();
        $timestamp = round(microtime(true) * 1000); // Current time in milliseconds
        $nonce = bin2hex(random_bytes(16)); // Generate a random nonce

        $url = "/v1.0/devices/{$deviceId}/door-lock/advanced-password";
        $fullUrl = $this->baseUrl . $url;

        $body = json_encode([
            'password' => $newPassword
        ]);
        $signature = $this->generateSignature("POST", $url, $body, $timestamp, $nonce);

        $headers = [
            "Content-Type: application/json",
            "client_id: {$this->clientId}",
            "access_token: {$this->accessToken}",
            "t: {$timestamp}",
            "nonce: {$nonce}",
            "sign: {$signature}"
        ];

        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        $response = json_decode($response, true);

        if (!$response['success']) {
            throw new \Exception("Failed to change lock password: " . $response['msg']);
        }

        return $response;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $clientId = "vw55nknx8twhrqu78wyd";
        $clientSecret = "555bb62672f840159d97dbed9a3c6e91";
        $uid = "az1643603772183o0x0S"; // Your UID
        $api = new \MyTuyaAPI\TestTuyaAPI($clientId, $clientSecret, $uid);

        // Get access token
        $accessToken = $api->getAccessToken();

        // Get posted values
        $deviceId = $_POST['deviceId'];
        $newPassword = $_POST['newPassword'];

        // Change password for the specified device
        $response = $api->changeLockPassword($deviceId, $newPassword);
        $message = "Password change response: " . json_encode($response, JSON_PRETTY_PRINT);

    } catch (\Exception $e) {
        $message = 'Error: ' . $e->getMessage();
    }
}

// Fetch device list for dropdown
try {
    $clientId = "vw55nknx8twhrqu78wyd";
    $clientSecret = "555bb62672f840159d97dbed9a3c6e91";
    $uid = "az1643603772183o0x0S"; // Your UID
    $api = new \MyTuyaAPI\TestTuyaAPI($clientId, $clientSecret, $uid);

    // Get devices
    $devices = $api->getDevices();

} catch (\Exception $e) {
    $devices = [];
    $message = 'Error fetching devices: ' . $e->getMessage();
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
        .device-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
        }
        .device-box {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            cursor: pointer;
            text-align: center;
        }
        .device-box:hover {
            background-color: #f0f0f0;
        }
        #passwordForm {
            display: none;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group select, .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .form-group button {
            padding: 10px 15px;
            background-color: #28a745;
            border: none;
            color: #fff;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Navbar and Sidebar Start-->
    <?php include('nav_sidebar.php'); ?>
    <!-- Navbar and Sidebar End -->
    <div class="container">
        <h1>Change Lock Password</h1>
        <?php if (!empty($message)) : ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <div class="device-grid">
            <?php foreach ($devices as $device) : ?>
                <div class="device-box" onclick="showPasswordForm('<?= $device->id ?>')">
                    <?= $device->name ?>
                </div>
            <?php endforeach; ?>
        </div>

        <form id="passwordForm" action="tuya.php" method="post">
            <div class="form-group">
                <input type="hidden" id="deviceId" name="deviceId">
                <label for="newPassword">New Password</label>
                <input type="password" id="newPassword" name="newPassword" required>
            </div>
            <div class="form-group">
                <button type="submit">Change Password</button>
            </div>
        </form>
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
        function showPasswordForm(deviceId) {
            document.getElementById('deviceId').value = deviceId;
            document.getElementById('passwordForm').style.display = 'block';
            window.scrollTo(0, document.getElementById('passwordForm').offsetTop);
        }

        $(document).ready(function() {
            $('#sidebarToggle').on('click', function() {
                $('.sidebar').toggleClass('show');
            });
        });
    </script>
</body>
</html>




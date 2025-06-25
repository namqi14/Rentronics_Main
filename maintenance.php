<?php
// Set maintenance mode
$maintenance_mode = true;

// Allow specific IP addresses to bypass maintenance mode
$allowed_ips = array(
    '127.0.0.1',    // localhost
    // Add more IPs here
);

// Check if visitor's IP is allowed
if ($maintenance_mode && !in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    header('Status: 503 Service Temporarily Unavailable');
    header('Retry-After: 3600'); // Retry after 1 hour
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Maintenance</title>
    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/index.css" rel="stylesheet">
    <style>
        body {
            text-align: center;
            padding: 150px;
            background: #f8f8f8;
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }

        .maintenance-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
            margin-top: 200px;
        }dd

        h1 {
            color: #333;
            font-size: 36px;
            margin-bottom: 20px;
        }

        p {
            color: #666;
            font-size: 18px;
        }

        .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .navbar {
            margin-left: 0 !important;
            background-color: #1c2f59 !important;
        }

        .icon img {
            width: 25px !important;
            height: 25px !important;
            border-radius: 50%;
            /* Optional, if you want the image itself rounded */
            max-width: none !important;
        }
    </style>
</head>

<body>
    <?php include('header.php'); ?>
    <?php if ($maintenance_mode && !in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)): ?>
        <div class="maintenance-container">
            <div class="icon">🔧</div>
            <h1>We'll Be Back Soon!</h1>
            <p>Sorry for the inconvenience. We're performing some maintenance at the moment.</p>
            <p>Please check back later.</p>
        </div>
    <?php else: ?>
        <div class="maintenance-container">
            <h1>Site is running normally</h1>
            <p>You're seeing this because you're either in the allowed IPs list or maintenance mode is off.</p>
        </div>
    <?php endif; ?>
</body>

</html>
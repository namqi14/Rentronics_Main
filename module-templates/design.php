<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan for Pay</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #ff9f47;
            font-family: Arial, sans-serif;
        }
        .container {
            text-align: center;
            background-color: #ff9f47;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 320px;
            height:500px;
        }
        .text {
            color: #fff;
            font-weight: bold;
            font-size: 1.5em;
        }
        .qr-code-container {
            position: relative;
            width: 300px;
            height: 300px;
            margin: 70px auto 20px;
        }
        .qr-code {
            position: absolute;
            top: 10%;   
            left: 50%;
            transform: translate(-50%, -50%);
            width: 120px;
            height: 120px;
            border-radius: 10px;
        }
        .border-image {
            position: absolute;
            top: 10.5%;
            left: 50.5%;
            transform: translate(-50%, -50%);
            width: auto;
            height: auto;
            max-width: 65%;
            max-height: 65%;
            border-radius: 15px;
        }
        .phone-illustration {
            position: absolute;
            bottom: 10%;
            left: 50%;
            transform: translateX(-50%);
            width: 175px;
            height: auto;
        }
        .phone-illustration img {
            width: 100%;
        }
        .scan-text {
            position: absolute;
            bottom: 55px; /* Adjust this value to position the text */
            left: 50%;
            transform: translate(-50%, 50%);
            font-size: 1.5em; /* Adjust this value to fit text in one line */
            font-weight: bold;
            color: #fff;
            white-space: nowrap; /* Ensure text stays on one line */
        }
        .text {
            font-size: 1.2em;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="text">NIN-TW-A <br>R1</div>
        <div class="qr-code-container">
            <img src="img/QR Code Border.jpg" alt="QR Code Border" class="border-image"> <!-- Update this path to the actual location of your border image -->
            <?php
            // Path to the QR code image
            $qrCodePath = 'img/QRCode/qrcode.png'; // Update this path to the actual location of your QR code image

            // Display the QR code
            echo "<img src='$qrCodePath' alt='QR Code' class='qr-code'>";
            ?>
            <div class="phone-illustration">
                <img src="img/QR Code Icon.png" alt="Phone Illustration"> <!-- Update this path to the actual location of your phone illustration image -->
                <div class="scan-text">SCAN FOR PAY</div>
            </div>
        </div>
    </div>
</body>
</html>

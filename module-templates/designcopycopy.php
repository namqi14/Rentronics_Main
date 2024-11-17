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
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 794px; /* A4 width */
            height: 1123px; /* A4 height */
            position: relative;
        }
        .text {
            color: #fff;
            font-weight: bold;
            font-size: 1.8em; /* Increased font size */
        }
        .qr-code-container {
            position: relative;
            width: 350px; /* Increased width */
            height: 350px; /* Increased height */
            margin: 70px auto 20px;
        }
        .qr-code {
            position: absolute;
            top: 10%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 150px; /* Increased width */
            height: 150px; /* Increased height */
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
            width: 200px; /* Increased width */
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
            font-size: 1.8em; /* Increased font size */
            font-weight: bold;
            color: #fff;
            white-space: nowrap; /* Ensure text stays on one line */
        }
        .text {
            font-size: 1.5em; /* Increased font size */
            margin-bottom: 10px;
        }
        .download-btn {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #fff;
            color: #ff9f47;
            border: none;
            padding: 12px 24px; /* Increased padding */
            font-size: 1.2em; /* Increased font size */
            font-weight: bold;
            cursor: pointer;
            border-radius: 5px;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
</head>
<body>
    <div class="container" id="pdf-content">
        <div class="text">NIN-TW-A <br>R1</div>
        <div class="qr-code-container">
            <img src="img/QR Code Border.jpg" alt="QR Code Border" class="border-image"> <!-- Update this path to the actual location of your border image -->
            <img src="img/QRCode/qrcode.png" alt="QR Code" class="qr-code"> <!-- Update this path to the actual location of your QR code image -->
            <div class="phone-illustration">
                <img src="img/QR Code Icon.png" alt="Phone Illustration"> <!-- Update this path to the actual location of your phone illustration image -->
                <div class="scan-text">SCAN FOR PAY</div>
            </div>
        </div>
        <button class="download-btn" id="download-btn" onclick="downloadPDF()">Download to PDF</button>
    </div>

    <script>
        function downloadPDF() {
            const element = document.getElementById('pdf-content');
            const opt = {
                margin: 0,
                filename: 'ScanForPay.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'px', format: 'a4', orientation: 'portrait' }
            };
            
            // New Promise-based usage:
            html2pdf().from(element).set(opt).save();
        }
    </script>
</body>
</html>

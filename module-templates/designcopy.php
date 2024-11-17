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
            width: 210mm; /* A4 width */
            height: 297mm; /* A4 height */
            position: relative;
            overflow: hidden;
        }
        .text {
            color: #fff;
            font-weight: bold;
            font-size: 3em;
            margin-top: 20px;
        }
        .qr-code-container {
            position: relative;
            width: 300px;
            height: 300px;
            margin: 0 auto 20px auto;
        }
        .qr-code {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 240px;
            height: 240px;
        }
        .border-image {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            height: 300px;
        }
        .illustration {
            position: absolute;
            bottom: 150px;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: auto;
        }
        .scan-text {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2em;
            font-weight: bold;
            color: #fff;
            white-space: nowrap;
        }
        .save-button {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 20px;
            font-size: 1em;
            font-weight: bold;
            color: #ff9f47;
            background-color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container" id="content">
        <div class="text">NIN-TW-A <br>R1</div>
        <div class="qr-code-container">
            <img src="img/QR Code Border.jpg" alt="QR Code Border" class="border-image">
            <img src="img/QRCode/qrcode.png" alt="QR Code" class="qr-code">
        </div>
        <img src="img/QR Code Icon.png" alt="Illustration" class="illustration"> <!-- Update this path to the actual location of your illustration image -->
        <div class="scan-text">SCAN FOR PAY</div>
        <button class="save-button" id="saveButton" onclick="saveToPDF()">Save to PDF</button>
    </div>

    <!-- Include html2pdf.js library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
    <script>
        function saveToPDF() {
            const element = document.getElementById('content');
            const button = document.getElementById('saveButton');
            button.style.display = 'none'; // Hide the button after clicking
            html2pdf(element, {
                margin:       [0, 0, 0, 0],
                filename:     'scan_for_pay.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            }).then(() => {
                button.style.display = 'block'; // Show the button again after PDF is saved (optional)
            });
        }
    </script>
</body>
</html>

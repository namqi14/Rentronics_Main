<!DOCTYPE html>
<html>
<head>
    <title>Error</title>
    <style>
        .error-container {
            margin: 50px auto;
            max-width: 600px;
            padding: 20px;
            text-align: center;
            border: 1px solid #f5c6cb;
            background-color: #f8d7da;
            border-radius: 4px;
            color: #721c24;
        }
        .back-link {
            margin-top: 20px;
            display: block;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h2>Error</h2>
        <p><?php echo htmlspecialchars($error ?? 'An unknown error occurred.'); ?></p>
        <a href="../index.php" class="back-link">Return to Home</a>
    </div>
</body>
</html>

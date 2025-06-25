<?php
$servername = "localhost";  // Change if needed
$username = "root";         // Change if needed
$password = "";            // Change if needed
$dbname = "rentronic";    // Change to your database name

try {
    // PDO Connection
    $pdo = new PDO(
        "mysql:host=$servername;dbname=$dbname",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // MySQLi Connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception("MySQLi Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

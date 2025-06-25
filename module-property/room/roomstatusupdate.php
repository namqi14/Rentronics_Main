<?php
require_once __DIR__ . '/../../module-auth/dbconnection.php';
session_start();

class RoomStatusUpdate {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function updateAllRoomStatuses() {
        try {
            // Get all rooms
            $query = "SELECT RoomID FROM room";
            $stmt = $this->conn->query($query);
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $success = true;
            foreach($rooms as $room) {
                // Check if all beds in each room are rented
                $query = "SELECT COUNT(*) as total_beds, 
                         SUM(CASE WHEN BedStatus = 'Rented' THEN 1 ELSE 0 END) as rented_beds 
                         FROM bed WHERE RoomID = ?";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(1, $room['RoomID']);
                $stmt->execute();
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // If total beds equals rented beds, update room status to 'Full'
                if ($result['total_beds'] > 0 && $result['total_beds'] == $result['rented_beds']) {
                    $updateQuery = "UPDATE room SET RoomStatus = 'Full' WHERE RoomID = ?";
                } else {
                    $updateQuery = "UPDATE room SET RoomStatus = 'Available' WHERE RoomID = ?";
                }
                
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(1, $room['RoomID']);
                
                if(!$updateStmt->execute()) {
                    $success = false;
                }
            }
            
            if($success) {
                $_SESSION['success'] = "All room statuses have been updated successfully.";
                return true;
            } else {
                $_SESSION['error'] = "Some room statuses failed to update.";
                return false;
            }
        } catch(PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
            return false;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Connect to database using the existing connection file
    require_once __DIR__ . '/../../module-auth/dbconnection.php';
    $db = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    
    $roomStatus = new RoomStatusUpdate($db);
    $roomStatus->updateAllRoomStatuses();
    
    // Redirect back to the previous page
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}
?>

<!-- Simple form with just a submit button -->
<!DOCTYPE html>
<html>
<head>
    <title>Update All Room Statuses</title>
</head>
<body>
    <?php
    if(isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
    if(isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
    ?>

    <form method="POST">
        <button type="submit">Update All Room Statuses</button>
    </form>
</body>
</html>

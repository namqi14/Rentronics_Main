<?php
require_once __DIR__ . '/../../module-auth/dbconnection.php';

session_start();
if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

// Fetch agent name from the database
$user = $_SESSION['auser']['AgentEmail']; // Updated to use correct session structure
$stmt = $conn->prepare("SELECT AgentName FROM agent WHERE AgentEmail = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$stmt->bind_result($agentName);
$stmt->fetch();
$stmt->close();

// Fetch property data
$properties = [];
$result = $conn->query("SELECT PropertyID, PropertyName, PropertyType, Location FROM Property");
while ($row = $result->fetch_assoc()) {
    $properties[$row['PropertyID']] = $row;
}
$result->close();

$propertyID = isset($_GET['propertyID']) ? $_GET['propertyID'] : null;
$propertyName = '';

if ($propertyID) {
    $stmt_property = $conn->prepare("SELECT PropertyName FROM Property WHERE PropertyID = ?");
    $stmt_property->bind_param("s", $propertyID);
    $stmt_property->execute();
    $stmt_property->bind_result($propertyName);
    $stmt_property->fetch();
    $stmt_property->close();
}

// Fetch unit data including UnitNo
$unitID = isset($_GET['unitID']) ? $_GET['unitID'] : null;
$unitNo = ''; // Initialize the variable for UnitNo

if ($unitID) {
    $stmt_unit = $conn->prepare("SELECT UnitNo FROM Unit WHERE UnitID = ?");
    $stmt_unit->bind_param("s", $unitID);
    $stmt_unit->execute();
    $stmt_unit->bind_result($unitNo);
    $stmt_unit->fetch();
    $stmt_unit->close();
}

// Fetch room data for the selected UnitID
$rooms = [];
$result = $conn->query("SELECT RoomID, UnitID, RoomNo FROM Room WHERE UnitID = '$unitID'");
while ($row = $result->fetch_assoc()) {
    $rooms[$row['UnitID']][] = $row;
}
$result->close();

// Fetch bed data only for the rooms in the selected unit
$beds = [];
$result = $conn->query("SELECT BedID, RoomID, BedNo, BedStatus FROM Bed WHERE RoomID IN (SELECT RoomID FROM Room WHERE UnitID = '$unitID')");
while ($row = $result->fetch_assoc()) {
    $beds[$row['RoomID']][] = $row;
}
$result->close();

// Count available beds using the function
function countAvailableBeds($unitID, $rooms, $beds) {
    $availableBeds = 0;

    if (isset($rooms[$unitID])) {
        foreach ($rooms[$unitID] as $room) {
            $roomID = $room['RoomID'];
            if (isset($beds[$roomID])) {
                foreach ($beds[$roomID] as $bed) {
                    if ($bed['BedStatus'] === 'Available' || $bed['BedStatus'] === 'Vacant' || $bed['BedStatus'] === '' || is_null($bed['BedStatus'])) {
                        $availableBeds++;
                    }
                }
            }
        }
    }

    return $availableBeds;
}

// Get the count of available beds
$availableBedsCount = countAvailableBeds($unitID, $rooms, $beds);
?>

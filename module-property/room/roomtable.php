<?php
session_start();
require_once __DIR__ . '/../../module-auth/dbconnection.php';  // Include your database connection file

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['auser'])) {
    header("Location: index.php");
    exit();
}

$access_level = $_SESSION['access_level']; // Fetch the user's access level

$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';  // Fetch the error message from the session
$msg = isset($_SESSION['msg']) ? $_SESSION['msg'] : '';  // Fetch the success message from the session

// Clear the messages after displaying them
unset($_SESSION['error']);
unset($_SESSION['msg']);

// Fetch Unit IDs and UnitNos from the Unit table
$unitOptions = '';
$result = $conn->query("SELECT UnitID, UnitNo FROM Unit");
$unitNos = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $unitOptions .= "<option value='" . $row['UnitID'] . "' data-unitno='" . $row['UnitNo'] . "'>" . $row['UnitID'] . " - " . $row['UnitNo'] . "</option>";
        $unitNos[$row['UnitID']] = $row['UnitNo'];
    }
}

// Fetch Agent IDs from the Agent table
$agentOptions = '';
$result = $conn->query("SELECT AgentID, AgentName FROM Agent");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $agentOptions .= "<option value='" . $row['AgentID'] . "'>" . $row['AgentID'] . " - " . $row['AgentName'] . "</option>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/rentronics/img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Inter:wght@700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/rentronics/assets/css/feathericon.min.css">
    <link href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css" rel="stylesheet">
    <link href="../../css/bootstrap.min.css" rel="stylesheet">
    <link href="../../css/navbar.css" rel="stylesheet">
    <link href="../css/room.css" rel="stylesheet">

    <style>
    .nav-bar {
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .details {
        display: grid;
        gap: 10px;
        padding: 10px;
        border: 1px solid #ccc;
        margin: 10px 0;
    }

    .details-row {
        display: flex;
        align-items: center;
    }

    .label {
        flex: 1;
        padding-right: 10px;
    }

    .value {
        flex: 2;
        border-left: 1px solid #ccc;
        padding-left: 10px;
    }

    .details p {
        margin: 0;
    }
    </style>
</head>

<body>
    <div class="container-fluid bg-white p-0">
        <!-- Navbar and Sidebar Start-->
        <?php include('../../nav_sidebar.php'); ?>
        <!-- Navbar and Sidebar End -->

        <!-- Page Wrapper -->
        <div class="page-wrapper">
            <div class="content container-fluid">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="row">
                        <div class="col">
                            <h3 class="page-title">Room Registry</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">Room</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Page Header -->

                <?php if (!empty($msg)) : ?>
                <div class="alert alert-success"><?php echo $msg; ?></div>
                <?php endif; ?>
                <?php if (!empty($error)) : ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="row">
                    <!-- Room Table -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Room Table</h4>
                                <a href="roomaddinnerjoin.php" class="btn btn-primary">Add</a>
                            </div>
                            <div class="card-body">
                                <table id="roomTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Room ID</th>
                                            <th>Unit No</th>
                                            <th>Room No</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $result = $conn->query("
                                            SELECT Room.RoomID, Room.UnitID, Unit.UnitNo, Property.PropertyName, Room.RoomNo, Room.RoomRentAmount, Room.Katil, Room.RoomStatus, Room.AgentID 
                                            FROM Room 
                                            INNER JOIN Unit ON Room.UnitID = Unit.UnitID 
                                            INNER JOIN Property ON Unit.PropertyID = Property.PropertyID
                                        ");
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $room_id = $row['RoomID'];
                                                $unit_no = $row['UnitNo'];
                                                $room_no = $row['RoomNo'];
                                                $room_rent = $row['RoomRentAmount'];
                                                $katil = $row['Katil'];
                                                $room_status = $row['RoomStatus'];
                                                $agent_id = $row['AgentID'];

                                                // Main row with data attributes for details
                                                echo "
                                                <tr class='main-row' data-room-id='$room_id' data-room-rent='$room_rent' data-katil='$katil' data-room-status='$room_status' data-agent-id='$agent_id'>
                                                    <td>
                                                        <button class='btn btn-link toggle-details'>
                                                            <i class='fas fa-chevron-down'></i>
                                                        </button>
                                                        $room_id
                                                    </td>
                                                    <td>{$row['PropertyName']} $unit_no</td>
                                                    <td>$room_no</td>
                                                    <td>
                                                        <a href='roomedit.php?room_id=$room_id' class='btn btn-sm btn-warning'><i class='fas fa-pencil-alt'></i></a>
                                                        <a href='roomdelete.php?delete_room_id=$room_id' class='btn btn-sm btn-danger' onclick='return confirm(\"Are you sure you want to delete this room?\");'><i class='fa fa-trash'></i></a>
                                                    </td>
                                                </tr>
                                                ";
                                            }
                                        } else {
                                            echo "<tr><td colspan='4'>No rooms found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- /Room Table -->
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery, Bootstrap, and DataTables scripts -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

    <script>
    $(document).ready(function() {
        var table = $('#roomTable').DataTable({
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50, 100],
            language: {
                search: "Filter records:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            },
            scrollX: true,
            responsive: true,
            order: [
                [0, 'asc']
            ],
            autoWidth: false
        });

        // Toggle details using row().child()
        $('#roomTable tbody').on('click', 'button.toggle-details', function() {
            var tr = $(this).closest('tr');
            var row = table.row(tr);

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                $(this).find('i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
            } else {
                var roomId = tr.data('room-id');
                var roomRent = tr.data('room-rent');
                var katil = tr.data('katil');
                var roomStatus = tr.data('room-status');
                var agentId = tr.data('agent-id');

                var detailsHtml = `
                        <div class='details'>
                            <div class='details-row'><p class='label'><strong>Rent Amount:</strong></p><p class='value'>RM ${roomRent}</p></div>
                            <div class='details-row'><p class='label'><strong>Katil:</strong></p><p class='value'>${katil}</p></div>
                            <div class='details-row'><p class='label'><strong>Status:</strong></p><p class='value'>${roomStatus}</p></div>
                            <div class='details-row'><p class='label'><strong>Agent ID:</strong></p><p class='value'>${agentId}</p></div>
                        </div>
                    `;

                row.child(detailsHtml).show();
                tr.addClass('shown');
                $(this).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
            }
        });
    });
    </script>
    <script src="../../js/main.js"></script>

</body>

</html>
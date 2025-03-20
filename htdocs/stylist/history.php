<?php
session_start();
include "db.php";

if (!isset($_SESSION['stylist_id'])) {
    header("location: index.php");
    exit;
}

$stylist_id = $_SESSION['stylist_id'];

// Fetch only completed appointments
$sql = "SELECT A.AppointmentID, A.AppointmentDate, A.StartTime, A.EndTime, 
               A.Status, C.Name AS CustomerName, C.Lastname AS CustomerLastname
        FROM Appointment A
        JOIN Customer C ON A.CustomerID = C.CustomerID
        WHERE A.StylistID = ? AND A.Status = 'Completed'
        ORDER BY A.AppointmentDate DESC, A.EndTime DESC";

$appointments = [];
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $stylist_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content= "width=device-width, initial-scale=1.0">
    <title>Completed Appointments</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebars.css">
    <style>
        .appointments-table {
            width: 100%;
            max-width: 900px;
            margin: 20px auto;
            border-collapse: collapse;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            overflow-x: auto;
        }
        .appointments-table th, .appointments-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
            white-space: nowrap;
        }
        .appointments-table thead {
            background-color: black;
            color: white;
        }
        .status {
            font-weight: bold;
            padding: 6px 10px;
            border-radius: 5px;
            display: inline-block;
            background: green;
            color: white;
        }
        @media screen and (max-width: 768px) {
            .appointments-table {
                margin: 10px;
                font-size: 14px;
            }
            .appointments-table th, .appointments-table td {
                padding: 8px;
            }
            h1 {
                font-size: 24px;
                margin: 10px;
                text-align: center;
            }
        }
        /* Add wrapper for table responsiveness */
        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            padding: 0 10px;
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    <h1>Completed Appointments</h1>
    <div class="table-wrapper">
        <table class="appointments-table" border="1">
            <thead>
                <tr>
                    <th>Appointment Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Customer</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($appointments)) { 
                    foreach ($appointments as $appointment) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appointment['AppointmentDate']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['StartTime']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['EndTime']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['CustomerName'] . " " . $appointment['CustomerLastname']); ?></td>
                            <td><span class="status">Completed</span></td>
                        </tr>
                    <?php } 
                } else { ?>
                    <tr><td colspan="5">No completed appointments.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>

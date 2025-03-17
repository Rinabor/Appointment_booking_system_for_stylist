<?php 
session_start();

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include "db.php";

// Get customer data from the session
$customer_id = $_SESSION["CustomerID"];
$email = $_SESSION["email"];

// Fetch customer info from the database
$sql = "SELECT Name, Lastname, ContactNumber, Address, DateOfBirth FROM Customer WHERE CustomerID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $customer_id);
    
    if ($stmt->execute()) {
        $stmt->store_result();
        $stmt->bind_result($name, $lastname, $contactNumber, $address, $dateOfBirth);
        $stmt->fetch();
    }
    $stmt->close();
}

// Fetch only completed appointments
$appointments_sql = "SELECT AppointmentID, AppointmentDate, StartTime, EndTime, Status 
                     FROM Appointment 
                     WHERE CustomerID = ? AND Status = 'Completed' 
                     ORDER BY AppointmentDate DESC";
$appointments_stmt = $conn->prepare($appointments_sql);
$appointments_stmt->bind_param("i", $customer_id);
$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment History</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="topbar.css">
    <style>
        .appointments-table {
            width: 90%;
            max-width: 900px;
            margin: 20px auto;
            border-collapse: collapse;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            overflow: hidden;
        }
        .appointments-table th, .appointments-table td {
            padding: 14px;
            text-align: center;
            border: 1px solid #ddd;
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
    </style>
</head>
<body>
<?php include 'menu.php'; ?>

    <h3>Your Appointment History</h3>
    <table class="appointments-table" border="1">
        <thead>
            <tr>
                <th>Appointment Date</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($appointments_result->num_rows > 0) {
                while ($appointment = $appointments_result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars(date("F d, Y", strtotime($appointment['AppointmentDate']))) . "</td>";
                    echo "<td>" . htmlspecialchars(date("h:i A", strtotime($appointment['StartTime']))) . "</td>";
                    echo "<td>" . htmlspecialchars(date("h:i A", strtotime($appointment['EndTime']))) . "</td>";
                    echo "<td><span class='status'>Completed</span></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No completed appointments.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <br>
</body>
</html>

<?php
$appointments_stmt->close();
$conn->close();
?>
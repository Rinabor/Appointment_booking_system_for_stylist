<?php
session_start();
include "db.php";

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if stylist is logged in
if (!isset($_SESSION['stylist_id'])) {
    header("location: index.php");
    exit;
}

$stylist_id = $_SESSION['stylist_id'];

// Fetch stylist's information from the database
$sql = "SELECT Name FROM Stylist WHERE StylistID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $stylist_id);
    if ($stmt->execute()) {
        $stmt->store_result();
        $stmt->bind_result($stylist_name);
        $stmt->fetch();
    }
    $stmt->close();
}

// Fetch pending appointments for the stylist
$sql = "SELECT A.AppointmentID, A.CustomerID, A.AppointmentDate, A.StartTime, A.EndTime, A.Status,
               C.Name AS CustomerName, C.Lastname AS CustomerLastname, C.ContactNumber, C.Email, C.Address
        FROM Appointment A
        JOIN Customer C ON A.CustomerID = C.CustomerID
        WHERE A.StylistID = ? AND A.Status = 'Pending'
        ORDER BY A.AppointmentDate, A.StartTime";

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

// Handle accept/reject actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["appointment_id"], $_POST["action"])) {
    $appointment_id = $_POST["appointment_id"];
    $action = $_POST["action"];
    $new_status = ($action === "accept") ? "Accepted" : "Rejected";

    // Check for time conflicts if accepting the appointment
    if ($new_status === "Accepted") {
        $sql = "SELECT AppointmentDate, StartTime, EndTime FROM Appointment WHERE AppointmentID = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $stmt->bind_result($appointment_date, $start_time, $end_time);
            $stmt->fetch();
            $stmt->close();

            // Check for time conflicts
            $conflict_sql = "SELECT COUNT(*) FROM Appointment WHERE StylistID = ? AND AppointmentDate = ? AND Status IN ('Pending', 'Accepted') AND ((StartTime < ? AND EndTime > ?) OR (StartTime < ? AND EndTime > ?))";
            if ($stmt = $conn->prepare($conflict_sql)) {
                $stmt->bind_param("isssss", $stylist_id, $appointment_date, $end_time, $end_time, $start_time, $start_time);
                $stmt->execute();
                $stmt->bind_result($conflict_count);
                $stmt->fetch();
                $stmt->close();

                if ($conflict_count > 0) {
                    echo "<p style='color: red; font-weight: bold;'>Error: The selected time conflicts with another appointment. Please choose a different time.</p>";
                    exit;
                }
            }
        }
    }

    // Update appointment status
    $update_sql = "UPDATE Appointment SET Status = ? WHERE AppointmentID = ? AND StylistID = ?";
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param("sii", $new_status, $appointment_id, $stylist_id);
        if ($stmt->execute()) {
            // Fetch customer ID for notification
            if ($stmt = $conn->prepare("SELECT CustomerID FROM Appointment WHERE AppointmentID = ?")) {
                $stmt->bind_param("i", $appointment_id);
                $stmt->execute();
                $stmt->bind_result($customer_id);
                $stmt->fetch();
                $stmt->close();

                // Send notification to customer
                $notif_sql = "INSERT INTO AppointmentNotification (AppointmentID, CustomerID, NotificationMessage, NotificationType, SentTime, StylistID) VALUES (?, ?, ?, ?, NOW(), ?)";
                if ($notif_stmt = $conn->prepare($notif_sql)) {
                    $message = ($new_status === "Accepted") ? "Your appointment has been confirmed by the stylist." : "Your appointment request has been declined.";
                    $notification_type = ($new_status === "Accepted") ? "Accepted" : "Rejected";
                    $notif_stmt->bind_param("iissi", $appointment_id, $customer_id, $message, $notification_type, $stylist_id);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }
            }
        }
    }

    // Redirect to refresh the page
    header("Location: manage_appointment.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebars.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <h2>Manage Appointments</h2>
    <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;"> <!-- Add scrollable container -->
        <table class="appointments-table" border="1">
            <thead>
            <tr>
                <th>Customer Name</th>
                <th>Date</th>
                <th>Start Time</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
            <?php if (!empty($appointments)) { 
            foreach ($appointments as $appointment) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($appointment["CustomerName"] . " " . $appointment["CustomerLastname"]); ?></td>
                    <td><?php echo htmlspecialchars(date("F d, Y", strtotime($appointment["AppointmentDate"]))); ?></td>
                    <td><?php echo htmlspecialchars(date("h:i A", strtotime($appointment["StartTime"])), ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($appointment["Status"]); ?></td>
                    <td>
                        <form action="manage_appointment.php" method="POST" style="display:flex; justify-content:center; gap: 5px;">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['AppointmentID']; ?>">
                            <button type="submit" name="action" value="accept" class="accept-button">Accept</button>
                            <button type="submit" name="action" value="reject" class="reject-button">Reject</button>
                            <button type="button" onclick="viewCustomerInfo(<?php echo $appointment['CustomerID']; ?>)" class="view-button" style="display:block; margin: 5px auto;">View</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div> <!-- End scrollable container -->
    <?php } ?>
    <script>
        function viewCustomerInfo(customerId) {
            $.get("fetch_customer_info.php", { customer_id: customerId }, function(data) {
                alert(data);
            });
        }
    </script>
</body>
</html>
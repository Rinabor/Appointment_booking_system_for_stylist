<?php 
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include "db.php";

// Get customer ID from session
$customer_id = $_SESSION["CustomerID"];

// Fetch customer details
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

// Fetch customer's appointments (Pending, Accepted, Completed)
$appointments_sql = "SELECT a.AppointmentID, a.AppointmentDate, a.StartTime, a.Status, a.StylistID, 
                            s.Name AS StylistName, s.Lastname AS StylistLastname
                     FROM Appointment a
                     JOIN Stylist s ON a.StylistID = s.StylistID
                     WHERE a.CustomerID = ? AND a.Status IN ('Pending', 'Accepted') 
                     ORDER BY a.AppointmentDate DESC";

$appointments_stmt = $conn->prepare($appointments_sql);
$appointments_stmt->bind_param("i", $customer_id);
$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();

// Update status of expired pending appointments and notify customer
$current_time = time();
while ($appointment = $appointments_result->fetch_assoc()) {
    if ($appointment['Status'] === 'Pending') {
        $appointment_datetime = strtotime($appointment['AppointmentDate'] . ' ' . $appointment['StartTime']);
        $time_difference = $appointment_datetime - $current_time;

        if ($current_time > $appointment_datetime) {
            $update_sql = "UPDATE Appointment SET Status = 'Rejected' WHERE AppointmentID = ?";
            if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param("i", $appointment['AppointmentID']);
                $update_stmt->execute();
                $update_stmt->close();
            }
        } else {
            // Notify customer if the appointment is within 24 hours or less
            for ($i = 24; $i > 0; $i--) {
                if ($time_difference <= $i * 3600 && $time_difference > ($i - 1) * 3600) {
                    $notif_sql = "INSERT INTO AppointmentNotification (AppointmentID, CustomerID, NotificationMessage, NotificationType, SentTime, StylistID) VALUES (?, ?, ?, ?, NOW(), ?)";
                    if ($notif_stmt = $conn->prepare($notif_sql)) {
                        $message = "Reminder - $i hours before appointment.";
                        $notification_type = "Reminder";
                        $stylist_id = $appointment['StylistID'];
                        $notif_stmt->bind_param("iissi", $appointment['AppointmentID'], $customer_id, $message, $notification_type, $stylist_id);
                        $notif_stmt->execute();
                        $notif_stmt->close();
                    }
                }
            }
        }
    }
}

// Re-fetch updated appointments
$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="topbar.css">
</head>
<body>

<?php include 'menu.php'; ?>

<?php
if (isset($_SESSION["message"])) {
    echo "<p style='color: green; font-weight: bold;'>" . $_SESSION["message"] . "</p>";
    unset($_SESSION["message"]);
}
?>

<h3>Your Appointments</h3>
<table border="1">
    <thead>
        <tr>
            <th>Appointment Date</th>
            <th>Start Time</th>
            <th>Stylist</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($appointments_result->num_rows > 0) {
            while ($appointment = $appointments_result->fetch_assoc()) {
                echo "<tr>";
                
                // Format date for better readability
                $formatted_date = date("F d, Y", strtotime($appointment['AppointmentDate']));
                echo "<td>" . htmlspecialchars($formatted_date) . "</td>";
                
                // Format start time for better readability
                $formatted_start_time = date("h:i A", strtotime($appointment['StartTime']));
                echo "<td>" . htmlspecialchars($formatted_start_time) . "</td>";
                
                // Display stylist's full name
                $stylist_full_name = htmlspecialchars($appointment['StylistName'] . " " . $appointment['StylistLastname']);
                echo "<td>" . $stylist_full_name . "</td>";
                
                // Status with color
                $status = htmlspecialchars($appointment['Status']);
                $status_color = ($status === 'Completed') ? 'status-completed' : (($status === 'Pending') ? 'status-pending' : 'status-accepted');
                echo "<td><span class='$status_color'>$status</span></td>";

                echo "<td>";
                // Allow rescheduling/canceling only if appointment is Pending or Accepted
                if ($status === 'Pending' || $status === 'Accepted') {
                    $appointment_datetime = strtotime($appointment['AppointmentDate'] . ' ' . $appointment['StartTime']);
                    $time_difference = $appointment_datetime - $current_time;

                    if ($status === 'Pending' || ($status === 'Accepted' && $time_difference > 86400)) {
                        echo "<a href='cancel_appointment.php?appointmentID=" . $appointment['AppointmentID'] . "'>Cancel</a>";
                        if ($status === 'Pending' || ($status === 'Accepted' && $time_difference > 86400)) {
                            echo " | <a href='reschedule_appointment.php?appointmentID=" . $appointment['AppointmentID'] . "'>Reschedule</a>";
                        }
                    } else {
                        echo "Can't cancel or reschedule within 24 hours of appointment.";
                    }
                } else {
                    echo "Can't cancel or reschedule a completed appointment.";
                }
                echo "</td>";

                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No appointments found.</td></tr>";
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
<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include "db.php";

// Get customer data from session
$customer_id = $_SESSION["CustomerID"];

// Fetch customer details
$sql = "SELECT Name, Lastname FROM Customer WHERE CustomerID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $customer_id);
    if ($stmt->execute()) {
        $stmt->store_result();
        $stmt->bind_result($name, $lastname);
        $stmt->fetch();
    }
    $stmt->close();
}

// Fetch customer's appointment notifications
$notifications_sql = "
    SELECT 
        an.AppointmentID, 
        an.NotificationMessage, 
        an.NotificationType, 
        an.SentTime, 
        s.Name AS StylistName
    FROM 
        AppointmentNotification an
    JOIN 
        Appointment a ON an.AppointmentID = a.AppointmentID
    JOIN 
        Stylist s ON a.StylistID = s.StylistID
    WHERE 
        an.CustomerID = ? AND an.NotificationType IN ('Accepted', 'Rejected', 'Reminder', 'Completed')
    ORDER BY 
        an.SentTime DESC
";

$notifications_stmt = $conn->prepare($notifications_sql);
$notifications_stmt->bind_param("i", $customer_id);
$notifications_stmt->execute();
$notifications_result = $notifications_stmt->get_result();

// Notifications array
$notifications = [];

while ($notification = $notifications_result->fetch_assoc()) {
    $notifications[] = [
        'appointment_id' => $notification['AppointmentID'],
        'sent_time' => $notification['SentTime'],
        'stylist_name' => $notification['StylistName'],
        'notification_type' => $notification['NotificationType'],
        'message' => $notification['NotificationMessage']
    ];
}

// Fetch upcoming accepted appointments for notifications
$appointments_sql = "SELECT AppointmentID, AppointmentDate, StartTime, StylistID 
                     FROM Appointment 
                     WHERE CustomerID = ? AND Status = 'Accepted' 
                     AND AppointmentDate >= CURDATE()";

$appointments_stmt = $conn->prepare($appointments_sql);
$appointments_stmt->bind_param("i", $customer_id);
$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();

$current_time = time();
while ($appointment = $appointments_result->fetch_assoc()) {
    $appointment_datetime = strtotime($appointment['AppointmentDate'] . ' ' . $appointment['StartTime']);
    $time_difference = $appointment_datetime - $current_time;

    // Notify customer if the appointment is within 24 hours, 3 hours, 2 hours, or 1 hour
    $notification_times = [24, 3, 2, 1];
    foreach ($notification_times as $hours) {
        if ($time_difference <= $hours * 3600 && $time_difference > ($hours - 1) * 3600) {
            $notif_sql = "INSERT INTO AppointmentNotification (AppointmentID, CustomerID, NotificationMessage, NotificationType, SentTime, StylistID) VALUES (?, ?, ?, ?, NOW(), ?)";
            if ($notif_stmt = $conn->prepare($notif_sql)) {
                $message = "Reminder - $hours hour(s) before appointment.";
                $notification_type = "Reminder";
                $stylist_id = $appointment['StylistID'];
                $notif_stmt->bind_param("iissi", $appointment['AppointmentID'], $customer_id, $message, $notification_type, $stylist_id);
                $notif_stmt->execute();
                $notif_stmt->close();
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Notifications</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="topbar.css">
    <style>
        .notification-type {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 5px;
            color: white;
        }
        .Accepted {
            background-color: green;
        }
        .Rejected {
            background-color: red;
        }
        .Reminder {
            background-color: blue;
        }
        .Completed {
            background-color: gray;
        }
        table {
            width: 90%;
            max-width: 800px;
            margin: 20px auto;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            overflow: hidden;
        }
        thead {
            background-color: #000;
            color: white;
            text-transform: uppercase;
        }
        td, th {
            padding: 14px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 16px;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        @media screen and (max-width: 768px) {
            table {
                width: 100%;
                font-size: 14px;
            }
            td, th {
                padding: 12px;
            }
        }
        @media screen and (max-width: 480px) {
            table {
                width: 100%;
            }
            td, th {
                padding: 10px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
<?php include 'menu.php'; ?>

<h2>Appointment Notifications</h2>

<?php if (count($notifications) > 0): ?>
    <h3>Recent Appointment Updates</h3>
    
    <table>
        <thead>
            <tr>
                <th>Sent Time</th>
                <th>Stylist</th>
                <th>Type</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notifications as $notification): ?>
                <tr>
                    <td><?php echo htmlspecialchars(date("F d, Y h:i A", strtotime($notification['sent_time']))); ?></td>
                    <td><?php echo htmlspecialchars($notification['stylist_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><span class="notification-type <?php echo htmlspecialchars($notification['notification_type']); ?>"><?php echo htmlspecialchars($notification['notification_type']); ?></span></td>
                    <td><?php echo htmlspecialchars($notification['message']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php else: ?>
    <p>No new appointment updates at the moment.</p>
<?php endif; ?>

</body>
</html>

<?php
$notifications_stmt->close();
$conn->close();
?>
<?php
session_start();
include "db.php";
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['stylist_id'])) {
    header("location: index.php");
    exit;
}

$stylist_id = $_SESSION['stylist_id'];

// Fetch stylist's name
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

// Fetch appointment notifications for stylist
$notifications_sql = "
    SELECT 
        an.AppointmentID, 
        an.NotificationMessage, 
        an.NotificationType, 
        an.SentTime, 
        c.Name AS CustomerName
    FROM 
        AppointmentNotification an
    JOIN 
        Appointment a ON an.AppointmentID = a.AppointmentID
    JOIN 
        Customer c ON a.CustomerID = c.CustomerID
    WHERE 
        an.StylistID = ? AND an.NotificationType IN ('Cancelled', 'Rescheduled Appointment', 'New Appointment', 'Reminder')
    ORDER BY 
        an.SentTime DESC
";

$notifications_stmt = $conn->prepare($notifications_sql);
$notifications_stmt->bind_param("i", $stylist_id);
$notifications_stmt->execute();
$notifications_result = $notifications_stmt->get_result();

$notifications = [];
while ($notification = $notifications_result->fetch_assoc()) {
    $notifications[] = [
        'appointment_id' => $notification['AppointmentID'],
        'sent_time' => $notification['SentTime'],
        'customer_name' => $notification['CustomerName'],
        'notification_type' => $notification['NotificationType'],
        'message' => $notification['NotificationMessage']
    ];
}

// Fetch upcoming accepted appointments for notifications
$appointments_sql = "SELECT AppointmentID, AppointmentDate, StartTime, CustomerID 
                     FROM Appointment 
                     WHERE StylistID = ? AND Status = 'Accepted' 
                     AND AppointmentDate >= CURDATE()";

$appointments_stmt = $conn->prepare($appointments_sql);
$appointments_stmt->bind_param("i", $stylist_id);
$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();

$current_time = time();
while ($appointment = $appointments_result->fetch_assoc()) {
    $appointment_datetime = strtotime($appointment['AppointmentDate'] . ' ' . $appointment['StartTime']);
    $time_difference = $appointment_datetime - $current_time;

    // Notify stylist if the appointment is within 2 hours or 1 hour
    $notification_times = [2, 1];
    foreach ($notification_times as $hours) {
        if ($time_difference <= $hours * 3600 && $time_difference > ($hours - 1) * 3600) {
            $notif_sql = "INSERT INTO AppointmentNotification (AppointmentID, CustomerID, NotificationMessage, NotificationType, SentTime, StylistID) VALUES (?, ?, ?, ?, NOW(), ?)";
            if ($notif_stmt = $conn->prepare($notif_sql)) {
                $message = "Reminder - $hours hour(s) before appointment.";
                $notification_type = "Reminder";
                $customer_id = $appointment['CustomerID'];
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
    <title>Stylist Notifications</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebars.css">
    <style>
        .notification-type {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 5px;
            color: white;
            display: inline-block;
            margin: 2px;
        }
        .Cancelled { background-color: red; }
        .Rescheduled { background-color: orange; }
        .New { background-color: green; }
        .Reminder { background-color: blue; }

        table {
            width: 100%;
            max-width: 1000px;
            margin: 20px auto;
            border-collapse: collapse;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #000;
            color: white;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            padding: 0 10px;
        }

        h2 {
            text-align: center;
            margin: 20px 0;
        }

        @media screen and (max-width: 768px) {
            table {
                font-size: 14px;
            }
            th, td {
                padding: 8px;
            }
            .notification-type {
                padding: 4px 8px;
            }
            h2 {
                font-size: 20px;
                margin: 15px 0;
            }
        }
    </style>
</head>
<body>
<?php include 'menu.php'; ?>
<h2>Appointment Notifications</h2>
<div class="table-wrapper">
<?php if (count($notifications) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Sent Time</th>
                <th>Customer</th>
                <th>Type</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($notifications as $notification): ?>
                <tr>
                    <td><?php echo htmlspecialchars(date("F d, Y h:i A", strtotime($notification['sent_time']))); ?></td>
                    <td><?php echo htmlspecialchars($notification['customer_name']); ?></td>
                    <td><span class="notification-type <?php echo htmlspecialchars($notification['notification_type']); ?>"><?php echo htmlspecialchars($notification['notification_type']); ?></span></td>
                    <td><?php echo htmlspecialchars($notification['message']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <p>No new notifications.</p>
<?php endif; ?>
</body>
</html>

<?php
$notifications_stmt->close();
$conn->close();
?>
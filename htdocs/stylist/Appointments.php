<?php
session_start();
include "db.php";

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// Handle search input
$search = $_GET['search'] ?? '';

// Fetch today's appointments
$sql_today = "SELECT A.AppointmentID, A.AppointmentDate, A.StartTime, A.Status, 
                     C.Name AS CustomerName, C.Lastname AS CustomerLastname, C.ContactNumber, C.Email, C.Address
              FROM Appointment A
              JOIN Customer C ON A.CustomerID = C.CustomerID
              WHERE A.StylistID = ? 
              AND A.AppointmentDate = CURDATE() 
              AND A.Status = 'Accepted'";

$today_appointments = [];
if ($stmt = $conn->prepare($sql_today)) {
    $stmt->bind_param("i", $stylist_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $today_appointments[] = $row;
    }
    $stmt->close();
}

// Fetch other days' appointments
$sql_other_days = "SELECT A.AppointmentID, A.AppointmentDate, A.StartTime, A.Status, 
                          C.Name AS CustomerName, C.Lastname AS CustomerLastname, C.ContactNumber, C.Email, C.Address
                   FROM Appointment A
                   JOIN Customer C ON A.CustomerID = C.CustomerID
                   WHERE A.StylistID = ? 
                   AND A.AppointmentDate > CURDATE() 
                   AND A.Status = 'Accepted'";

$other_appointments = [];
if ($stmt = $conn->prepare($sql_other_days)) {
    $stmt->bind_param("i", $stylist_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $other_appointments[] = $row;
    }
    $stmt->close();
}

// Handle "Mark as Done" action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["appointment_id"])) {
    $appointment_id = $_POST["appointment_id"];

    $update_sql = "UPDATE Appointment SET Status = 'Completed', EndTime = NOW() WHERE AppointmentID = ? AND StylistID = ?";
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param("ii", $appointment_id, $stylist_id);
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
                    $message = "Your appointment has been marked as completed by the stylist.";
                    $notification_type = "Completed";
                    $notif_stmt->bind_param("iissi", $appointment_id, $customer_id, $message, $notification_type, $stylist_id);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                } else {
                    echo "Error preparing notification statement: " . $conn->error;
                }
            } else {
                echo "Error preparing select statement: " . $conn->error;
            }
        } else {
            echo "Error executing update statement: " . $stmt->error;
        }
    } else {
        echo "Error preparing update statement: " . $conn->error;
    }

    header("Location: Appointments.php");
    exit;
}

// Initialize $appointments to avoid undefined variable warnings
$appointments = [];

// Send reminders for upcoming appointments
$current_time = time();
if (!empty($appointments)) { // Ensure $appointments is an array before iterating
    foreach ($appointments as $appointment) {
        $appointment_datetime = strtotime($appointment['AppointmentDate'] . ' ' . $appointment['StartTime']);
        $time_difference = $appointment_datetime - $current_time;

        // Notify stylist if the appointment is within 3 hours or less
        for ($i = 3; $i > 0; $i--) {
            if ($time_difference <= $i * 3600 && $time_difference > ($i - 1) * 3600) {
                $notif_sql = "INSERT INTO AppointmentNotification (AppointmentID, CustomerID, NotificationMessage, NotificationType, SentTime, StylistID) VALUES (?, ?, ?, ?, NOW(), ?)";
                if ($notif_stmt = $conn->prepare($notif_sql)) {
                    $message = "Reminder - $i hours before appointment.";
                    $notification_type = "Reminder";
                    $customer_id = $appointment['CustomerID'];
                    $notif_stmt->bind_param("iissi", $appointment['AppointmentID'], $customer_id, $message, $notification_type, $stylist_id);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }
            }
        }

        // Notify stylist if the appointment is within minutes
        if ($time_difference <= 3600 && $time_difference > 0) {
            $notif_sql = "INSERT INTO AppointmentNotification (AppointmentID, CustomerID, NotificationMessage, NotificationType, SentTime, StylistID) VALUES (?, ?, ?, ?, NOW(), ?)";
            if ($notif_stmt = $conn->prepare($notif_sql)) {
                $minutes = ceil($time_difference / 60);
                $message = "Reminder - $minutes minutes before appointment.";
                $notification_type = "Reminder";
                $customer_id = $appointment['CustomerID'];
                $notif_stmt->bind_param("iissi", $appointment['AppointmentID'], $customer_id, $message, $notification_type, $stylist_id);
                $notif_stmt->execute();
                $notif_stmt->close();
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stylist Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebars.css">
    <style>
        .appointments-table {
            width: 100%;
            margin: 10px 0;
            border-collapse: collapse;
            overflow-x: auto;
        }

        .appointments-table h3 {
            text-align: center;
            margin-bottom: 10px;
            font-size: 18px;
            color: #333;
        }

        .search-container {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
        }

        .search-input {
            width: calc(100% - 100px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .search-button {
            width: 80px;
            padding: 10px;
            margin-left: 10px;
        }

        /* Responsive styles */
        @media screen and (max-width: 768px) {
            body {
                padding: 0;
                margin: 0;
                width: 100vw;
                overflow-x: hidden;
            }

            h1 {
                font-size: 1.5rem;
                padding: 10px;
                margin: 10px 0;
            }

            .appointments-table {
                margin: 0;
                font-size: 14px;
            }

            .appointments-table th,
            .appointments-table td {
                padding: 8px 4px;
                word-break: break-word;
            }

            /* Make table scrollable horizontally */
            .appointments-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
            }

            /* Stack form elements */
            .search-container {
                display: flex;
                flex-direction: column;
                gap: 10px;
                padding: 10px;
            }

            .search-input {
                width: 100%;
            }

            .search-button {
                width: 100%;
                margin-left: 0;
            }

            /* Adjust action buttons */
            form[action="Appointments.php"] {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }

            .accept-button,
            .reject-button {
                width: 100%;
                padding: 8px;
                margin: 2px 0;
            }
        }

        /* Small phone screens */
        @media screen and (max-width: 480px) {
            h1 {
                font-size: 1.2rem;
            }

            .appointments-table th,
            .appointments-table td {
                font-size: 12px;
                padding: 6px 2px;
            }
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>

    <h1>Today's Appointments</h1>

    <!-- Search Form -->
    <form method="GET" action="Appointments.php" class="search-form">
        <div class="search-container">
            <input type="text" id="search" name="search" placeholder="🔍 Search Customer" 
                value="<?php echo htmlspecialchars($search); ?>" autocomplete="off" class="search-input">
            <button type="submit" class="search-button">Search</button>
        </div>
    </form>

    <div class="suggestions" id="suggestions"></div>

    <table class="appointments-table">
        <thead>
            <tr>
                <th>Customer Name</th>
                <th>Contact Number</th>
                <th>Email</th>
                <th>Address</th>
                <th>Date</th>
                <th>Start Time</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($today_appointments)) { 
                foreach ($today_appointments as $appointment) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($appointment['CustomerName'] . " " . $appointment['CustomerLastname']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['ContactNumber']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['Email']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['Address']); ?></td>
                        <td><?php echo htmlspecialchars(date("F d, Y", strtotime($appointment['AppointmentDate']))); ?></td>
                        <td><?php echo htmlspecialchars(date("h:i A", strtotime($appointment['StartTime'])), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($appointment['Status']); ?></td>
                        <td>
                            <form action="Appointments.php" method="POST" style="display:flex; justify-content:center; gap: 5px;">
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['AppointmentID']; ?>">
                                <button type="submit" class="accept-button">Done</button>
                                <button type="submit" name="action" value="no_show" class="reject-button">No Show</button>
                            </form>
                        </td>
                    </tr>
                <?php } 
            } else { ?>
                <tr><td colspan="8">No appointments for today.</td></tr>
            <?php } ?>
        </tbody>
    </table>

    <h1>Upcoming Appointments</h1>
    <table class="appointments-table">
        <thead>
            <tr>
                <th>Customer Name</th>
                <th>Contact Number</th>
                <th>Email</th>
                <th>Address</th>
                <th>Date</th>
                <th>Start Time</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($other_appointments)) { 
                foreach ($other_appointments as $appointment) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($appointment['CustomerName'] . " " . $appointment['CustomerLastname']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['ContactNumber']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['Email']); ?></td>
                        <td><?php echo htmlspecialchars($appointment['Address']); ?></td>
                        <td><?php echo htmlspecialchars(date("F d, Y", strtotime($appointment['AppointmentDate']))); ?></td>
                        <td><?php echo htmlspecialchars(date("h:i A", strtotime($appointment['StartTime'])), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($appointment['Status']); ?></td>
                        <td>
                            <form action="Appointments.php" method="POST" style="display:flex; justify-content:center; gap: 5px;">
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['AppointmentID']; ?>">
                                <button type="submit" class="accept-button">Done</button>
                                <button type="submit" name="action" value="no_show" class="reject-button">No Show</button>
                            </form>
                        </td>
                    </tr>
                <?php } 
            } else { ?>
                <tr><td colspan="8">No upcoming appointments.</td></tr>
            <?php } ?>
        </tbody>
    </table>

    <script src="jquery_yawa.js"></script>
    <script>
        $(document).ready(function(){
            // Search functionality
            $("#search").on("keyup", function(){
                let searchQuery = $(this).val();
                if (searchQuery.length > 1) {
                    $.get("fetch_customers.php", { search: searchQuery }, function(data){
                        let customers = JSON.parse(data);
                        let suggestions = $("#suggestions");
                        suggestions.empty().show();
                        customers.forEach(function(customer){
                            suggestions.append("<div onclick='selectCustomer(\"" + customer.Name + " " + customer.Lastname + "\")'>" + customer.Name + " " + customer.Lastname + "</div>");
                        });
                    });
                } else {
                    $("#suggestions").hide();
                }
            });
        });

        function selectCustomer(name) {
            $("#search").val(name);
            $("#suggestions").hide();
        }

        function viewCustomerInfo(customerId) {
            $.get("fetch_customer_info.php", { customer_id: customerId }, function(data) {
                alert(data);
            });
        }
    </script>
</body>
</html>
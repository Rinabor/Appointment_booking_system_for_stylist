<?php
session_start();
include "db.php";

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ensure the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Ensure an appointment ID is provided
if (!isset($_GET["appointmentID"]) || !is_numeric($_GET["appointmentID"])) {
    $_SESSION["message"] = "Invalid appointment ID.";
    header("location: appointments.php");
    exit;
}

$customer_id = $_SESSION["CustomerID"];
$appointment_id = intval($_GET["appointmentID"]);

// Fetch appointment details
$sql = "SELECT AppointmentDate, StartTime, StylistID, Status FROM Appointment WHERE AppointmentID = ? AND CustomerID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $appointment_id, $customer_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($appointment_date, $start_time, $stylist_id, $status);
    $stmt->fetch();
    $stmt->close();

    $appointment_datetime = strtotime($appointment_date . ' ' . $start_time);
    $current_time = time();
    $time_difference = $appointment_datetime - $current_time;

    // Ensure rescheduling is only allowed if more than 24 hours remain
    if ($time_difference <= 86400) {
        $_SESSION["message"] = "You cannot reschedule an appointment within 24 hours.";
        header("location: appointments.php");
        exit;
    }
} else {
    $_SESSION["message"] = "Appointment not found.";
    header("location: appointments.php");
    exit;
}

// Handle appointment rescheduling
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["appointment_date"], $_POST["start_time"])) {
    $new_appointment_date = $_POST["appointment_date"];
    $new_start_time = $_POST["start_time"];

    // Check if the stylist is available at the new date and time
    $sql = "SELECT COUNT(*) FROM Appointment WHERE StylistID = ? AND AppointmentDate = ? AND StartTime = ? AND Status IN ('Pending', 'Accepted')";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iss", $stylist_id, $new_appointment_date, $new_start_time);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $_SESSION["message"] = "Error: The selected stylist is not available at the chosen date and time. Please choose a different time.";
        } else {
            // Update appointment with new date and time
            $sql = "UPDATE Appointment SET AppointmentDate = ?, StartTime = ?, Status = 'Pending' WHERE AppointmentID = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssi", $new_appointment_date, $new_start_time, $appointment_id);
                if ($stmt->execute()) {
                    // Insert notification for the rescheduled appointment
                    $sql = "INSERT INTO AppointmentNotification (NotificationType, SentTime, AppointmentID, CustomerID, StylistID, NotificationMessage) 
                            VALUES ('Rescheduled Appointment', NOW(), ?, ?, ?, 'An appointment has been rescheduled.')";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("iii", $appointment_id, $customer_id, $stylist_id);
                        $stmt->execute();
                        $stmt->close();
                    }

                    $_SESSION["message"] = "Appointment rescheduled successfully.";
                } else {
                    $_SESSION["message"] = "Error rescheduling appointment: " . $stmt->error;
                }
            }
        }
        header("location: appointments.php");
        exit;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule Appointment</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="topbar.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    <h2>Reschedule Appointment</h2>
    
    <form action="reschedule_appointment.php?appointmentID=<?php echo $appointment_id; ?>" method="POST">
        <label for="appointment_date">New Appointment Date:</label>
        <input type="date" id="appointment_date" name="appointment_date" value="<?php echo $appointment_date; ?>" required><br><br>
        
        <label for="start_time">New Start Time:</label>
        <input type="time" id="start_time" name="start_time" value="<?php echo $start_time; ?>" required><br><br>

        <button type="submit">Reschedule Appointment</button>
    </form>
</body>
</html>
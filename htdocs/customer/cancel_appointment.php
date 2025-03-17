<?php
session_start();
include "db.php";

// Ensure the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Ensure an appointment ID is provided
if (!isset($_GET["appointmentID"]) || !is_numeric($_GET["appointmentID"])) {
    header("location: appointments.php?error=invalid_id");
    exit;
}

$customer_id = $_SESSION["CustomerID"];
$appointment_id = intval($_GET["appointmentID"]);

try {
    // Fetch appointment details
    $sql = "SELECT AppointmentDate, StartTime, Status, StylistID FROM Appointment WHERE AppointmentID = ? AND CustomerID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $appointment_id, $customer_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($appointment_date, $start_time, $status, $stylist_id);

        if ($stmt->fetch()) {
            $appointment_datetime = strtotime($appointment_date . ' ' . $start_time);
            $current_time = time();
            $time_difference = $appointment_datetime - $current_time;

            // Ensure cancellation is only allowed if more than 24 hours remain or if the appointment is still pending
            if ($status === 'Pending' || $time_difference > 86400) {
                $update_sql = "UPDATE Appointment SET Status = 'Cancelled' WHERE AppointmentID = ?";
                if ($update_stmt = $conn->prepare($update_sql)) {
                    $update_stmt->bind_param("i", $appointment_id);
                    if ($update_stmt->execute()) {
                        // Insert notification for the cancellation
                        $notif_sql = "INSERT INTO AppointmentNotification (AppointmentID, CustomerID, NotificationMessage, NotificationType, SentTime, StylistID) VALUES (?, ?, ?, ?, NOW(), ?)";
                        if ($notif_stmt = $conn->prepare($notif_sql)) {
                            $message = "Your appointment has been cancelled.";
                            $notification_type = "Cancelled";
                            $notif_stmt->bind_param("iissi", $appointment_id, $customer_id, $message, $notification_type, $stylist_id);
                            $notif_stmt->execute();
                            $notif_stmt->close();
                        }

                        $_SESSION["message"] = "Appointment cancelled successfully.";
                    } else {
                        $_SESSION["message"] = "Error cancelling appointment.";
                    }
                    $update_stmt->close();
                }
            } else {
                $_SESSION["message"] = "You cannot cancel an appointment within 24 hours.";
            }
        } else {
            $_SESSION["message"] = "Appointment not found.";
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $_SESSION["message"] = "Error: " . $e->getMessage();
}

$conn->close();
header("location: appointments.php");
exit;
?>
<?php
session_start();
include "db.php";

// Check if the stylist is logged in
if (!isset($_SESSION['stylist_id'])) {
    header("location: index.php");
    exit;
}

$stylist_id = $_SESSION['stylist_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["appointment_id"], $_POST["action"])) {
    $appointment_id = $_POST["appointment_id"];
    $action = $_POST["action"];

    // Determine new status
    if ($action === "accept") {
        $new_status = "Accepted";
    } elseif ($action === "reject") {
        $new_status = "Rejected";
    } elseif ($action === "done") {
        $new_status = "Done";
    } else {
        exit("Invalid action.");
    }

    // Update appointment status in database
    $update_sql = "UPDATE Appointment SET Status = ? WHERE AppointmentID = ? AND StylistID = ?";
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param("sii", $new_status, $appointment_id, $stylist_id);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect back
    header("Location: appointment.php");
    exit;
}
?>

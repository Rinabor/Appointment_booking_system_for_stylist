<?php
session_start();
include 'db.php';

// Check if stylist is logged in
if (!isset($_SESSION['stylist_id'])) {
    header("location: index.php");
    exit;
}

$stylist_id = $_SESSION['stylist_id'];
$appointment_id = $_GET['appointment_id'] ?? null;

if ($appointment_id) {
    // Update the appointment status to 'Accepted'
    $sql = "UPDATE Appointment SET Status = 'Accepted' WHERE AppointmentID = ? AND StylistID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $appointment_id, $stylist_id);
        if ($stmt->execute()) {
            echo "Appointment has been accepted!";
        } else {
            echo "Error updating appointment status: " . $conn->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
} else {
    echo "Appointment ID is missing.";
}

$conn->close();
?>

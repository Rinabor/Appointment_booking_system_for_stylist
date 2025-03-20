<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);

    $schedule_id = $data['schedule_id'];

    $delete_sql = "DELETE FROM StylistSchedule WHERE ScheduleID = ?";
    if ($stmt = $conn->prepare($delete_sql)) {
        $stmt->bind_param("i", $schedule_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>

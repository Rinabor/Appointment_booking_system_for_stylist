<?php
session_start();
include "db.php";

header('Content-Type: application/json');

if (!isset($_GET['date']) || !isset($_GET['stylist_id'])) {
    echo json_encode([]);
    exit;
}

$date = $_GET['date'];
$stylist_id = $_GET['stylist_id'];
$day_of_week = date('l', strtotime($date));

// Get stylist's schedule for the given day
$sql = "SELECT StartTime, EndTime FROM StylistSchedule 
        WHERE StylistID = ? AND DayOfWeek = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $stylist_id, $day_of_week);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();

if (!$schedule) {
    echo json_encode([]);
    exit;
}

// Function to check if a time conflicts with booked slots
function isTimeSlotAvailable($time, $booked_slots) {
    $slot_start = strtotime($time);
    $slot_end = $slot_start + 3600; // 1 hour appointment
    $buffer_time = 7200; // 2 hour buffer (in seconds)

    foreach ($booked_slots as $booked) {
        $booked_start = strtotime($booked['StartTime']);
        $booked_end = strtotime($booked['EndTime']);

        // Check if slots overlap including buffer time
        if (
            // Check if slot starts during a booked appointment or buffer
            ($slot_start >= $booked_start - $buffer_time && $slot_start < $booked_end + $buffer_time) ||
            // Check if slot ends during a booked appointment or buffer
            ($slot_end > $booked_start - $buffer_time && $slot_end <= $booked_end + $buffer_time) ||
            // Check if slot completely encompasses a booked appointment
            ($slot_start <= $booked_start - $buffer_time && $slot_end >= $booked_end + $buffer_time)
        ) {
            return false;
        }
    }
    return true;
}

// Get existing appointments with exact times
$sql = "SELECT DATE_FORMAT(StartTime, '%H:%i:%s') as StartTime, 
               DATE_FORMAT(EndTime, '%H:%i:%s') as EndTime 
        FROM Appointment 
        WHERE StylistID = ? 
        AND AppointmentDate = ? 
        AND Status IN ('Pending', 'Accepted')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $stylist_id, $date);
$stmt->execute();
$result = $stmt->get_result();
$booked_slots = [];
while ($row = $result->fetch_assoc()) {
    $booked_slots[] = $row;
}

// Generate available time slots
$available_slots = [];
$start = strtotime($schedule['StartTime']);
$end = strtotime($schedule['EndTime']);
$interval = 1800; // 30-minute intervals

for ($time = $start; $time <= $end - 3600; $time += $interval) {
    $current_time = date('H:i:s', $time);
    
    if (isTimeSlotAvailable($current_time, $booked_slots)) {
        $available_slots[] = [
            'value' => $current_time,
            'display' => date('h:i A', $time)
        ];
    }
}

echo json_encode($available_slots);
?>

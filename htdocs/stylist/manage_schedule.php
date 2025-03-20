<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/workspaces/test/logs/php_errors.log');

include "db.php";

// Check if the stylist is logged in, if not redirect to login page
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

// Handle schedule update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['new_day']) && isset($_POST['new_start_time']) && isset($_POST['new_end_time'])) {
        // Insert new schedule entry
        $new_day = $_POST['new_day'];
        $new_start_time = $_POST['new_start_time'];
        $new_end_time = $_POST['new_end_time'];

        $insert_sql = "INSERT INTO StylistSchedule (StylistID, DayOfWeek, StartTime, EndTime) VALUES (?, ?, ?, ?)";
        if ($insert_stmt = $conn->prepare($insert_sql)) {
            $insert_stmt->bind_param("isss", $stylist_id, $new_day, $new_start_time, $new_end_time);
            if ($insert_stmt->execute()) {
                $_SESSION['message'] = 'Schedule added successfully!';
            } else {
                $_SESSION['message'] = 'Error adding schedule: ' . $insert_stmt->error;
            }
            $insert_stmt->close();
        } else {
            $_SESSION['message'] = 'Error preparing insert query: ' . $conn->error;
        }
    } elseif (isset($_POST['schedule_id']) && isset($_POST['day']) && isset($_POST['start_time']) && isset($_POST['end_time'])) {
        // Update existing schedule entry
        $schedule_id = $_POST['schedule_id'];
        $day = $_POST['day'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        $update_sql = "UPDATE StylistSchedule SET DayOfWeek = ?, StartTime = ?, EndTime = ? WHERE ScheduleID = ?";
        if ($stmt = $conn->prepare($update_sql)) {
            $stmt->bind_param("sssi", $day, $start_time, $end_time, $schedule_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Schedule updated successfully!';
            } else {
                $_SESSION['message'] = 'Error updating schedule: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = 'Error preparing update query: ' . $conn->error;
        }
    } elseif (isset($_POST['delete_schedule_id'])) {
        // Delete schedule entry
        $schedule_id = $_POST['delete_schedule_id'];

        $delete_sql = "DELETE FROM StylistSchedule WHERE ScheduleID = ?";
        if ($stmt = $conn->prepare($delete_sql)) {
            $stmt->bind_param("i", $schedule_id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Schedule deleted successfully!';
            } else {
                $_SESSION['message'] = 'Error deleting schedule: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = 'Error preparing delete query: ' . $conn->error;
        }
    }

    header("location: manage_schedule.php");
    exit;
}

// Fetch the stylist's schedule
$schedule = [];
$sql = "SELECT ScheduleID, DayOfWeek, StartTime, EndTime FROM StylistSchedule WHERE StylistID = ? ORDER BY FIELD(DayOfWeek, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), StartTime";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $stylist_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($schedule_id, $day_of_week, $start_time, $end_time);
    
    while ($stmt->fetch()) {
        $schedule[] = [
            'id' => $schedule_id,
            'day' => $day_of_week,
            'start_time' => $start_time,
            'end_time' => $end_time
        ];
    }
    $stmt->close();
} else {
    echo "Error fetching schedule: " . $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebars.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    <h2>Manage Your Schedule</h2>

    <?php
    if (isset($_SESSION['message'])) {
        echo '<p>' . $_SESSION['message'] . '</p>';
        unset($_SESSION['message']);
    }
    ?>

    <h3>Add New Schedule</h3>
    <form action="manage_schedule.php" method="post">
        <label for="new_day">Day:</label>
        <select id="new_day" name="new_day" required>
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
            <option value="Saturday">Saturday</option>
            <option value="Sunday">Sunday</option>
        </select>
        <br><br>

        <label for="new_start_time">Start Time:</label>
        <input type="time" id="new_start_time" name="new_start_time" required>
        <br><br>

        <label for="new_end_time">End Time:</label>
        <input type="time" id="new_end_time" name="new_end_time" required>
        <br><br>

        <button type="submit">Add Schedule</button>
    </form>


    <br>
    <a href="schedule.php">Back to schedule</a>
</body>
</html>

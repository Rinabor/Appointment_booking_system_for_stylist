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
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['schedule_id'], $data['day'], $data['start_time'], $data['end_time'])) {
        $schedule_id = $data['schedule_id'];
        $day = $data['day'];
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];

        $update_sql = "UPDATE StylistSchedule SET DayOfWeek = ?, StartTime = ?, EndTime = ? WHERE ScheduleID = ?";
        if ($stmt = $conn->prepare($update_sql)) {
            $stmt->bind_param("sssi", $day, $start_time, $end_time, $schedule_id);
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
        echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
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
    <title>Update Schedule</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebars.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    <h2>Update Your Schedule</h2>

    <?php
    if (isset($_SESSION['message'])) {
        echo '<p>' . $_SESSION['message'] . '</p>';
        unset($_SESSION['message']);
    }
    ?>

    <h3>Current Schedule</h3>
    <table class="schedule-table">
        <thead>
            <tr>
                <th>Day</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($schedule as $entry) { ?>
                <tr>
                    <form action="update_schedule.php" method="post">
                        <td>
                            <input type="hidden" name="schedule_id" value="<?php echo $entry['id']; ?>">
                            <select name="day" required>
                                <option value="Monday" <?php echo ($entry['day'] == 'Monday') ? 'selected' : ''; ?>>Monday</option>
                                <option value="Tuesday" <?php echo ($entry['day'] == 'Tuesday') ? 'selected' : ''; ?>>Tuesday</option>
                                <option value="Wednesday" <?php echo ($entry['day'] == 'Wednesday') ? 'selected' : ''; ?>>Wednesday</option>
                                <option value="Thursday" <?php echo ($entry['day'] == 'Thursday') ? 'selected' : ''; ?>>Thursday</option>
                                <option value="Friday" <?php echo ($entry['day'] == 'Friday') ? 'selected' : ''; ?>>Friday</option>
                                <option value="Saturday" <?php echo ($entry['day'] == 'Saturday') ? 'selected' : ''; ?>>Saturday</option>
                                <option value="Sunday" <?php echo ($entry['day'] == 'Sunday') ? 'selected' : ''; ?>>Sunday</option>
                            </select>
                        </td>
                        <td><input type="time" name="start_time" value="<?php echo $entry['start_time']; ?>" required></td>
                        <td><input type="time" name="end_time" value="<?php echo $entry['end_time']; ?>" required></td>
                        <td>
                            <button type="submit">Update</button>
                            <button type="submit" name="delete_schedule_id" value="<?php echo $entry['id']; ?>">Delete</button>
                        </td>
                    </form>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <br>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>

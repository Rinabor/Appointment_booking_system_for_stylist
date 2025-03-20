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
    <title>View Schedule</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebars.css">
    <style>
        /* Ensure Table Responsiveness */
        .schedule-table {
            width: 100%; /* Full width */
            border-collapse: collapse;
            margin: 0 auto; /* Center the table */
            overflow-x: auto; /* Enable horizontal scrolling */
        }

        .schedule-table th, .schedule-table td {
            padding: 10px;
            text-align: center;
            font-size: 14px;
            white-space: nowrap; /* Prevent text wrapping */
        }

        .schedule-table th {
            background-color: #333;
            color: white;
        }

        .schedule-table td input, .schedule-table td select {
            width: 100%; /* Ensure inputs fit within the cell */
            padding: 5px;
            font-size: 14px;
        }

        .schedule-table td button {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* Responsive Design */
        @media screen and (max-width: 768px) {
            .schedule-table th, .schedule-table td {
                font-size: 12px;
                padding: 8px;
            }

            .schedule-table td input, .schedule-table td select {
                font-size: 12px;
            }

            .schedule-table td button {
                font-size: 10px;
                padding: 4px 8px;
            }
        }

        @media screen and (max-width: 480px) {
            .schedule-table th, .schedule-table td {
                font-size: 11px;
                padding: 6px;
            }

            .schedule-table td input, .schedule-table td select {
                font-size: 11px;
            }

            .schedule-table td button {
                font-size: 9px;
                padding: 3px 6px;
            }
        }
    </style>
    <script src="jquery.js"></script>
</head>
<body>
    <?php include 'menu.php'; ?>
    <h2>Your Schedule</h2>
    <div style="overflow-x: auto;"> <!-- Add horizontal scrolling container -->
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Update</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedule as $entry) { ?>
                    <tr>
                        <td>
                            <select name="day" class="day" data-id="<?php echo $entry['id']; ?>" required>
                                <option value="Monday" <?php echo ($entry['day'] == 'Monday') ? 'selected' : ''; ?>>Monday</option>
                                <option value="Tuesday" <?php echo ($entry['day'] == 'Tuesday') ? 'selected' : ''; ?>>Tuesday</option>
                                <option value="Wednesday" <?php echo ($entry['day'] == 'Wednesday') ? 'selected' : ''; ?>>Wednesday</option>
                                <option value="Thursday" <?php echo ($entry['day'] == 'Thursday') ? 'selected' : ''; ?>>Thursday</option>
                                <option value="Friday" <?php echo ($entry['day'] == 'Friday') ? 'selected' : ''; ?>>Friday</option>
                                <option value="Saturday" <?php echo ($entry['day'] == 'Saturday') ? 'selected' : ''; ?>>Saturday</option>
                                <option value="Sunday" <?php echo ($entry['day'] == 'Sunday') ? 'selected' : ''; ?>>Sunday</option>
                            </select>
                        </td>
                        <td><input type="time" class="start_time" data-id="<?php echo $entry['id']; ?>" value="<?php echo $entry['start_time']; ?>" required></td>
                        <td><input type="time" class="end_time" data-id="<?php echo $entry['id']; ?>" value="<?php echo $entry['end_time']; ?>" required></td>
                        <td>
                            <button class="update-btn" data-id="<?php echo $entry['id']; ?>">Update</button>
                </td>             <td>
                            <button class="delete-btn" data-id="<?php echo $entry['id']; ?>">Delete</button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <br>
    <a href="manage_schedule.php">Manage Schedule</a>
    <script>
        $(document).ready(function() {
            $('.update-btn').on('click', function() {
                var id = $(this).data('id');
                var day = $('.day[data-id="' + id + '"]').val();
                var start_time = $('.start_time[data-id="' + id + '"]').val();
                var end_time = $('.end_time[data-id="' + id + '"]').val();

                $.ajax({
                    url: 'update_schedule.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        schedule_id: id,
                        day: day,
                        start_time: start_time,
                        end_time: end_time
                    }),
                    success: function(response) {
                        if (response.success) {
                            alert('Schedule updated successfully!');
                        } else {
                            alert('Error updating schedule: ' + response.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error updating schedule: ' + error);
                    }
                });
            });

            $('.delete-btn').on('click', function() {
                var id = $(this).data('id');

                $.ajax({
                    url: 'deleteschedule.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        schedule_id: id
                    }),
                    success: function(response) {
                        if (response.success) {
                            alert('Schedule deleted successfully!');
                            location.reload();
                        } else {
                            alert('Error deleting schedule: ' + response.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error deleting schedule: ' + error);
                    }
                });
            });
        });
    </script>
</body>
</html>

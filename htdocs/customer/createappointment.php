<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if the customer is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include "db.php";

// Fetch customer info from the session
$customer_id = $_SESSION["CustomerID"];

// Fetch customer preferences
$preferences = null;
$sql = "SELECT PreferredDate, PreferredTime, StylistID FROM CustomerPreferences WHERE CustomerID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $stmt->bind_result($preferred_date, $preferred_time, $preferred_stylist_id);
    if ($stmt->fetch()) {
        $preferences = [
            'date' => $preferred_date,
            'time' => $preferred_time,
            'stylist_id' => $preferred_stylist_id
        ];
    }
    $stmt->close();
}

// Fetch available stylists based on specialization
$stylists = [];
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["specialization"])) {
    $specialization = $_POST["specialization"];
    $sql = "SELECT StylistID, Name, Picture FROM Stylist WHERE Specialization = ? ORDER BY Name";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $specialization);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($stylist_id, $stylist_name, $stylist_picture);
        
        while ($stmt->fetch()) {
            $stylists[] = [
                'id' => $stylist_id, 
                'name' => $stylist_name,
                'picture' => $stylist_picture
            ];
        }
        $stmt->close();
    }
}

// Handle appointment booking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["stylist"], $_POST["appointment_date"], $_POST["start_time"])) {
    $stylist_id = $_POST["stylist"];
    $appointment_date = $_POST["appointment_date"];
    $start_time = $_POST["start_time"];
    $end_time = date("H:i:s", strtotime($start_time) + 3600); // Assuming 1-hour appointments

    // Check if the stylist is available at the selected date and time
    $sql = "SELECT ScheduleID FROM StylistSchedule WHERE StylistID = ? AND DayOfWeek = ? AND StartTime <= ? AND EndTime >= ?";
    $day_of_week = date('l', strtotime($appointment_date));
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isss", $stylist_id, $day_of_week, $start_time, $end_time);
        $stmt->execute();
        $stmt->bind_result($schedule_id);
        $stmt->fetch();
        $stmt->close();

        if (empty($schedule_id)) {
            $message = "<p style='color: red; font-weight: bold;'>Error: The selected stylist is not available at the chosen date and time. Please choose a different time or stylist.</p>";
        } else {
            // Check if there is a conflict with another appointment
            $sql = "SELECT COUNT(*) FROM Appointment WHERE StylistID = ? AND AppointmentDate = ? AND ((StartTime <= ? AND EndTime > ?) OR (StartTime < ? AND EndTime >= ?)) AND Status IN ('Pending', 'Accepted')";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("isssss", $stylist_id, $appointment_date, $start_time, $start_time, $end_time, $end_time);
                $stmt->execute();
                $stmt->bind_result($conflict_count);
                $stmt->fetch();
                $stmt->close();

                if ($conflict_count > 0) {
                    $message = "<p style='color: red; font-weight: bold;'>Error: The selected time conflicts with another appointment. Please choose a different time.</p>";
                } else {
                    // Insert appointment with "Pending" status
                    $sql = "INSERT INTO Appointment (CustomerID, StylistID, AppointmentDate, StartTime, EndTime, Status, ScheduleID) VALUES (?, ?, ?, ?, ?, 'Pending', ?)";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("iisssi", $customer_id, $stylist_id, $appointment_date, $start_time, $end_time, $schedule_id);
                        if ($stmt->execute()) {
                            $appointment_id = $stmt->insert_id; // Get the inserted appointment ID
                            $stmt->close();

                            // Insert notification for the appointment
                            $sql = "INSERT INTO AppointmentNotification (NotificationType, SentTime, AppointmentID, CustomerID, StylistID, NotificationMessage) 
                                    VALUES ('New Appointment', NOW(), ?, ?, ?, 'A new appointment is pending.')";
                            if ($stmt = $conn->prepare($sql)) {
                                $stmt->bind_param("iii", $appointment_id, $customer_id, $stylist_id);
                                $stmt->execute();
                                $stmt->close();
                            }

                            $message = "<p style='color: green; font-weight: bold;'>Success: Your appointment has been booked and is pending approval.</p>";
                        } else {
                            $message = "<p style='color: red; font-weight: bold;'>Error booking appointment: " . $stmt->error . "</p>";
                        }
                    }
                }
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
    <title>Create Appointment</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="topbar.css">
    <style>
        .container {
            max-width: 100%; /* Full width */
            padding: 10px; /* Adjust padding */
        }

        @media screen and (max-width: 480px) {
            body {
                margin: 0;
                width: 100%;
                overflow-x: hidden; /* Prevent horizontal scrolling */
            }

            form {
                width: 100%; /* Full width for form */
            }

            input, select, button {
                width: 100%; /* Full width for inputs */
                font-size: 14px;
                padding: 10px;
            }

            .container {
                padding: 10px; /* Adjust padding */
            }

            h2 {
                font-size: 20px; /* Adjust heading size */
            }

            .message {
                font-size: 14px; /* Adjust message font size */
            }
        }
    </style>
    <style>
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        .container h2 {
            text-align: center;
        }
        .container form {
            display: flex;
            flex-direction: column;
        }
        .container form label {
            margin: 10px 0 5px;
        }
        .container form select,
        .container form input,
        .container form button {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .container form button {
            background-color: #28a745;
            color: white;
            cursor: pointer;
        }
        .container form button:hover {
            background-color: #218838;
        }
        .message {
            text-align: center;
            margin-bottom: 20px;
        }
        .stylist-table {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        .stylist-card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            margin: 10px;
            padding: 20px;
            text-align: center;
            width: 250px;
            cursor: pointer;
        }
        .stylist-card img {
            border-radius: 50%; /* Make the image circular */
            height: 120px; /* Adjust height */
            width: 120px; /* Adjust width */
            object-fit: cover;
            margin-bottom: 10px;
            border: 2px solid #ddd; /* Add border for better visibility */
        }
        .stylist-card h3 {
            margin: 10px 0;
        }
        .stylist-card p {
            margin: 5px 0;
        }
        .stylist-info {
            text-align: left;
        }
        .stylist-info ul {
            list-style-type: none;
            padding: 0;
        }
        .stylist-info ul li {
            margin: 5px 0;
        }
        .stylist-card.selected {
            border: 2px solid #28a745;
            background-color: #e6ffe6;
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    <div class="container">
        <h2>Book an Appointment</h2>
        
        <?php if (isset($message)) echo "<div class='message'>$message</div>"; ?>

        <form id="specializationForm" action="createappointment.php" method="POST">
            <label for="specialization">Select Specialization:</label>
            <select name="specialization" id="specialization" required onchange="submitSpecializationForm()">
                <option value="" disabled selected>Select Specialization</option>
                <option value="Stylist" <?php echo (isset($specialization) && $specialization == 'Stylist') ? 'selected' : ''; ?>>Stylist</option>
                <option value="Barber" <?php echo (isset($specialization) && $specialization == 'Barber') ? 'selected' : ''; ?>>Barber</option>
            </select>
        </form>

        <?php if (!empty($stylists)) { ?>
            <form action="createappointment.php" method="POST">
                <input type="hidden" name="specialization" value="<?php echo htmlspecialchars($specialization); ?>">
                <input type="hidden" id="selectedStylist" name="stylist" value="<?php echo isset($preferences['stylist_id']) ? $preferences['stylist_id'] : ''; ?>">
                
                <label for="appointment_date">Appointment Date:</label>
                <input type="date" id="appointment_date" name="appointment_date" value="<?php echo isset($preferences['date']) ? $preferences['date'] : ''; ?>" onchange="updateTimeSlots()" required>

                <div id="timeSlotContainer" style="display: none;">
                    <label for="start_time">Available Time Slots:</label>
                    <select id="start_time" name="start_time" required>
                        <option value="">Select a time slot</option>
                    </select>
                </div>

                <div class="stylist-table">
                    <?php foreach ($stylists as $stylist) { ?>
                        <div class="stylist-card <?php echo (isset($preferences['stylist_id']) && $preferences['stylist_id'] == $stylist['id']) ? 'selected' : ''; ?>" 
                             id="stylist-<?php echo $stylist['id']; ?>" 
                             onclick="selectStylist(<?php echo $stylist['id']; ?>)">
                            <img src="../stylist/uploads/<?php echo htmlspecialchars($stylist['picture']); ?>" alt="Stylist Image">
                            <h3><?php echo $stylist['name']; ?></h3>
                            <p><strong>Specialization:</strong> <?php echo htmlspecialchars($specialization); ?></p>
                            <p><strong>Contact:</strong> <?php 
                                // Fetch stylist contact details
                                $contact_sql = "SELECT Phone, Email, ExperienceYears FROM Stylist WHERE StylistID = ?";
                                if ($contact_stmt = $conn->prepare($contact_sql)) {
                                    $contact_stmt->bind_param("i", $stylist['id']);
                                    $contact_stmt->execute();
                                    $contact_stmt->bind_result($phone, $email, $experience_years);
                                    if ($contact_stmt->fetch()) {
                                        echo "Phone: $phone, Email: $email, Experience: $experience_years years";
                                    }
                                    $contact_stmt->close();
                                }
                            ?></p>
                            <div class="stylist-info">
                                <p><strong>Schedule:</strong></p>
                                <ul>
                                    <?php
                                    // Fetch stylist schedule
                                    $schedule_sql = "SELECT DayOfWeek, StartTime, EndTime FROM StylistSchedule WHERE StylistID = ?";
                                    if ($schedule_stmt = $conn->prepare($schedule_sql)) {
                                        $schedule_stmt->bind_param("i", $stylist['id']);
                                        $schedule_stmt->execute();
                                        $schedule_stmt->bind_result($day_of_week, $start_time, $end_time);
                                        while ($schedule_stmt->fetch()) {
                                            echo "<li>$day_of_week: $start_time - $end_time</li>";
                                        }
                                        $schedule_stmt->close();
                                    }
                                    ?>
                                </ul>
                            </div>
                            <button type="button">Select</button>
                        </div>
                    <?php } ?>
                </div>

                <button type="submit">Book Appointment</button>
            </form>
        <?php } ?>
    </div>
    <script>
        function submitSpecializationForm() {
            document.getElementById('specializationForm').submit();
        }

        function updateTimeSlots() {
            const date = document.getElementById('appointment_date').value;
            const stylistId = document.getElementById('selectedStylist').value;
            const timeSlotContainer = document.getElementById('timeSlotContainer');
            const timeSelect = document.getElementById('start_time');

            if (!date || !stylistId) {
                timeSlotContainer.style.display = 'none';
                return;
            }

            // Fetch available time slots
            fetch(`get_available_slots.php?date=${date}&stylist_id=${stylistId}`)
                .then(response => response.json())
                .then(slots => {
                    timeSelect.innerHTML = '<option value="">Select a time slot</option>';
                    slots.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot.value;
                        option.textContent = slot.display;
                        timeSelect.appendChild(option);
                    });
                    timeSlotContainer.style.display = 'block';
                });
        }

        function selectStylist(stylistId) {
            // Remove the 'selected' class from all stylist cards
            document.querySelectorAll('.stylist-card').forEach(card => card.classList.remove('selected'));

            // Add the 'selected' class to the clicked stylist card
            const selectedCard = document.getElementById(`stylist-${stylistId}`);
            selectedCard.classList.add('selected');

            // Set the selected stylist ID in the hidden input field
            document.getElementById('selectedStylist').value = stylistId;

            // Update time slots when stylist is selected
            updateTimeSlots();
        }
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Appointment</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'menu.php'; ?>

    <h2>Create Appointment</h2>
    <form action="create_appointment.php" method="POST">
        <label for="stylist_id">Stylist:</label>
        <select name="stylist_id" id="stylist_id" required>
            <!-- Populate with stylists from the database -->
            <?php
            include "db.php";
            $sql = "SELECT StylistID, Name, Lastname FROM Stylist";
            $result = $conn->query($sql);
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . $row['StylistID'] . "'>" . htmlspecialchars($row['Name'] . " " . $row['Lastname']) . "</option>";
            }
            $conn->close();
            ?>
        </select>
        <br>
        <label for="appointment_date">Date:</label>
        <input type="date" name="appointment_date" id="appointment_date" required>
        <br>
        <label for="start_time">Start Time:</label>
        <input type="time" name="start_time" id="start_time" required>
        <br>
        <label for="end_time">End Time:</label>
        <input type="time" name="end_time" id="end_time" required>
        <br>
        <button type="submit">Create Appointment</button>
    </form>
</body>
</html>

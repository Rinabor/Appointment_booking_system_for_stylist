<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include "db.php";

// Get customer data from the session
$customer_id = $_SESSION["CustomerID"];

// Default values for the preferences
$preferred_stylist = '';
$preferred_date = '';
$preferred_time = '';

// Fetch the customer's existing preferences
$sql = "SELECT StylistID, PreferredDate, PreferredTime FROM CustomerPreferences WHERE CustomerID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $customer_id);
    
    if ($stmt->execute()) {
        $stmt->store_result();
        $stmt->bind_result($preferred_stylist, $preferred_date, $preferred_time);
        $stmt->fetch();
    } else {
        echo "Error executing query: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error;
}

// Fetch all stylists to populate the cards
$stylists = [];
$sql_stylists = "SELECT StylistID, Name, Lastname, Specialization, Phone, Email, ExperienceYears, Picture FROM Stylist";
if ($stmt = $conn->prepare($sql_stylists)) {
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($stylist_id, $stylist_name, $stylist_lastname, $specialization, $phone, $email, $experience_years, $picture);
    
    while ($stmt->fetch()) {
        $stylists[] = [
            'id' => $stylist_id,
            'name' => $stylist_name,
            'lastname' => $stylist_lastname,
            'specialization' => $specialization,
            'phone' => $phone,
            'email' => $email,
            'experience_years' => $experience_years,
            'picture' => $picture
        ];
    }
    $stmt->close();
} else {
    echo "Error fetching stylists: " . $conn->error;
}

// Check if form is submitted to update preferences
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_preferred_stylist = $_POST['preferred_stylist'];
    $new_preferred_date = $_POST['preferred_date'];
    $new_preferred_time = $_POST['preferred_time'];

    // Check if preferences exist, if not, insert them
    if (empty($preferred_stylist) && empty($preferred_date) && empty($preferred_time)) {
        $insert_sql = "INSERT INTO CustomerPreferences (CustomerID, StylistID, PreferredDate, PreferredTime) VALUES (?, ?, ?, ?)";
        if ($insert_stmt = $conn->prepare($insert_sql)) {
            $insert_stmt->bind_param("iiss", $customer_id, $new_preferred_stylist, $new_preferred_date, $new_preferred_time);
            if ($insert_stmt->execute()) {
                $_SESSION['message'] = 'Preference Saved Successfully!';
            } else {
                $_SESSION['message'] = 'Error saving preferences: ' . $insert_stmt->error;
            }
            $insert_stmt->close();
        } else {
            $_SESSION['message'] = 'Error preparing insert query: ' . $conn->error;
        }
    } else {
        $update_sql = "UPDATE CustomerPreferences SET StylistID = ?, PreferredDate = ?, PreferredTime = ? WHERE CustomerID = ?";
        if ($update_stmt = $conn->prepare($update_sql)) {
            $update_stmt->bind_param("issi", $new_preferred_stylist, $new_preferred_date, $new_preferred_time, $customer_id);
            if ($update_stmt->execute()) {
                $_SESSION['message'] = 'Preference Saved Successfully!';
            } else {
                $_SESSION['message'] = 'Error updating preferences: ' . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $_SESSION['message'] = 'Error preparing update query: ' . $conn->error;
        }
    }
    
    header("location: preference.php");
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Preferences</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="topbar.css">
    <style>
        .stylist-table {
            width: 100%;
            border-collapse: collapse;
        }
        .stylist-table td {
            padding: 10px;
            vertical-align: top;
        }
        .stylist-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            margin: 10px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s;
            width: 100%;
        }
        .stylist-card:hover {
            transform: scale(1.05);
        }
        .stylist-card img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 4px solid #28282B;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stylist-card img:hover {
            transform: scale(1.1);
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.3);
        }
        .stylist-info {
            text-align: left;
            margin-top: 10px;
        }
        .stylist-info ul {
            list-style-type: none;
            padding: 0;
        }
        .stylist-info ul li {
            margin-bottom: 5px;
        }
        .stylist-info h4 {
            margin-top: 10px;
        }
        .stylist-card {
            width: 100%; /* Full width */
            margin: 10px 0; /* Adjust margin */
        }

        @media screen and (max-width: 480px) {
            .stylist-table {
                width: 100%; /* Ensure table fits */
            }

            .stylist-card {
                margin: 5px 0; /* Reduce margin */
            }

            input, button {
                width: 100%; /* Full width for inputs */
            }
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    <h2>Manage Your Preferences</h2>

    <?php
    if (isset($_SESSION['message'])) {
        echo '<p>' . $_SESSION['message'] . '</p>';
        unset($_SESSION['message']);
    }
    ?>

    <form action="manage_preference.php" method="post">
        <input type="hidden" id="preferred_stylist" name="preferred_stylist" value="<?php echo $preferred_stylist; ?>">
        <label for="preferred_date">Preferred Date:</label>
        <input type="date" id="preferred_date" name="preferred_date" value="<?php echo $preferred_date; ?>" required>
        <br><br>

        <label for="preferred_time">Preferred Time:</label>
        <input type="time" id="preferred_time" name="preferred_time" value="<?php echo $preferred_time; ?>" required>
        <br><br>

        <h3>Select Preferred Stylist:</h3>
        <table class="stylist-table">
            <?php foreach (array_chunk($stylists, 2) as $stylist_row): ?>
                <tr>
                    <?php foreach ($stylist_row as $stylist): ?>
                        <td>
                            <div class="stylist-card" onclick="selectStylist(<?php echo $stylist['id']; ?>)">
                                <img src="../stylist/uploads/<?php echo htmlspecialchars($stylist['picture']); ?>" alt="Stylist Photo">
                                <h3><?php echo htmlspecialchars($stylist['name'] . ' ' . $stylist['lastname']); ?></h3>
                                <p><?php echo htmlspecialchars($stylist['specialization']); ?></p>
                                <div class="stylist-info">
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($stylist['phone']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($stylist['email']); ?></p>
                                    <p><strong>Experience:</strong> <?php echo htmlspecialchars($stylist['experience_years']); ?> years</p>
                                    <h4>Schedule</h4>
                                    <ul>
                                        <?php
                                        $schedule_sql = "SELECT DayOfWeek, StartTime, EndTime FROM StylistSchedule WHERE StylistID = ?";
                                        if ($schedule_stmt = $conn->prepare($schedule_sql)) {
                                            $schedule_stmt->bind_param("i", $stylist['id']);
                                            $schedule_stmt->execute();
                                            $schedule_result = $schedule_stmt->get_result();
                                            while ($schedule = $schedule_result->fetch_assoc()) {
                                                echo '<li>' . htmlspecialchars($schedule['DayOfWeek']) . ': ' . htmlspecialchars(date("h:i A", strtotime($schedule['StartTime']))) . ' - ' . htmlspecialchars(date("h:i A", strtotime($schedule['EndTime']))) . '</li>';
                                            }
                                            $schedule_stmt->close();
                                        }
                                        ?>
                                    </ul>
                                </div>
                                <button type="button" onclick="selectStylist(<?php echo $stylist['id']; ?>)">Select</button>
                            </div>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>

        <button type="submit">Save Preferences</button>
    </form>

    <br>

    <script>
        function selectStylist(stylistId) {
            document.getElementById('preferred_stylist').value = stylistId;
            const cards = document.querySelectorAll('.stylist-card');
            cards.forEach(card => {
                card.style.border = 'none';
            });
            document.querySelector('.stylist-card[onclick="selectStylist(' + stylistId + ')"]').style.border = '2px solid green';
        }
    </script>
</body>
</html>

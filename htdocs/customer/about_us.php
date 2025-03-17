<?php 
session_start();
include "db.php";

// Fetch stylists/barbers information
$stylists = [];
$sql = "SELECT StylistID, Name, Lastname, Specialization, ExperienceYears, Email, Phone, Picture FROM Stylist";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $stylists[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="topbar.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1200px;
            margin: 80px auto 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .hero-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s;
        }
        .hero-image:hover {
            transform: scale(1.02);
        }
        .features {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 0;
            list-style: none;
            margin-top: 20px;
        }
        .features li {
            background:#28282B;
            color: white;
            padding: 15px;
            margin: 10px;
            border-radius: 5px;
            flex: 1 1 calc(50% - 20px);
            max-width: 400px;
            text-align: center;
        }
        .description {
            text-align: left;
            margin-top: 30px;
            padding: 10px;
        }
        @media (max-width: 768px) {
            .features {
                flex-direction: column;
                align-items: center;
            }
            .features li {
                flex: 1 1 100%;
                max-width: 100%;
                text-align: center;
            }
            .container {
                padding: 10px;
            }
        }
        @media (max-width: 480px) {
            body {
                margin: 0;
                width: 100%;
                overflow-x: hidden; /* Prevent horizontal scrolling */
            }

            .features {
                flex-direction: column;
                align-items: center;
            }

            .features li {
                flex: 1 1 100%; /* Full width */
                max-width: 100%;
                margin: 10px 0;
            }

            .container {
                padding: 10px; /* Adjust padding */
            }

            h1, h2 {
                font-size: 20px; /* Adjust heading sizes */
            }

            p {
                font-size: 14px; /* Adjust paragraph font size */
            }

            .stylist-card {
                margin: 10px 0;
            }

            .stylist-card img {
                max-width: 80px; /* Adjust image size */
                height: auto;
            }

            .stylist-info {
                font-size: 14px; /* Adjust font size */
            }
        }
        li {
            font-size: 18px;
            text-align: center;
        }
        h4 {
            color: white;
        }
        .stylist-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 15px;
        }
        .stylist-table tr {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stylist-table td {
            flex: 1;
            padding: 0;
        }
        .stylist-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s;
            height: 100%;
        }
        
        @media (max-width: 768px) {
            .stylist-table tr {
                flex-direction: column;
                gap: 15px;
            }
            .stylist-table td {
                width: 100%;
            }
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
            display: none;
            text-align: left;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    
<?php include 'menu.php'; ?>
    
<div class="container">
    <h1>About Our System</h1>

    <p>Welcome to the Simple Appointment Booking System, designed specifically for stylists and barbers in Barangay Poblacion.</p>
    <p>Our system makes booking, rescheduling, and canceling appointments easy, while helping business owners manage their schedules efficiently.</p>
    <h2>Why Choose Our System?</h2>
    <ul class="features">
        <li><h4>Online booking for customers</h4></li>
        <li><h4>Automated reminders to reduce no-shows</h4></li>
        <li><h4>Personalized stylist preferences</h4></li>
        <li><h4>Efficient scheduling for business owners</h4></li>
        <li><h4>Improved customer satisfaction and service</h4></li>
    </ul>
    <div class="description">
        <h2>Our Mission</h2>
        <p>We are committed to helping local salons and barbershops modernize their appointment management and improve their services.</p>
    </div>

    <h2>Meet Our Stylists and Barbers</h2>
    <?php 
    $chunks = array_chunk($stylists, 3);
    foreach ($chunks as $chunk): 
    ?>
    <table class="stylist-table">
        <tr>
            <?php foreach ($chunk as $stylist): ?>
                <td>
                    <div class="stylist-card" onclick="toggleStylistInfo(<?php echo $stylist['StylistID']; ?>)">
                        <img src="../stylist/uploads/<?php echo htmlspecialchars($stylist['Picture']); ?>" alt="Stylist Photo">
                        <h3><?php echo htmlspecialchars($stylist['Name'] . ' ' . $stylist['Lastname']); ?></h3>
                        <p><?php echo htmlspecialchars($stylist['Specialization']); ?></p>
                        <div class="stylist-info" id="stylist-info-<?php echo $stylist['StylistID']; ?>">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($stylist['Email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($stylist['Phone']); ?></p>
                            <p><strong>Experience:</strong> <?php echo htmlspecialchars($stylist['ExperienceYears']); ?> years</p>
                            <h4>Schedule</h4>
                            <ul>
                                <?php
                                $schedule_sql = "SELECT DayOfWeek, StartTime, EndTime FROM StylistSchedule WHERE StylistID = ?";
                                if ($schedule_stmt = $conn->prepare($schedule_sql)) {
                                    $schedule_stmt->bind_param("i", $stylist['StylistID']);
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
                    </div>
                </td>
            <?php endforeach; ?>
        </tr>
    </table>
    <?php endforeach; ?>
</div>

<script>
function toggleStylistInfo(stylistId) {
    const infoDiv = document.getElementById('stylist-info-' + stylistId);
    if (infoDiv.style.display === 'none' || infoDiv.style.display === '') {
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}
</script>

</body>
</html>

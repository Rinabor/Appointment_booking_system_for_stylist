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
    <link rel="stylesheet" href="all.min.css">
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
            background: linear-gradient(145deg, #2d2d30, #28282B);
            color: white;
            padding: 20px;
            margin: 15px;
            border-radius: 15px;
            flex: 1 1 calc(50% - 30px);
            max-width: 400px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .features li:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
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
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid #eee;
        }

        .stylist-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
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
        .social-links {
            margin-top: 40px;
            padding: 30px;
            background: linear-gradient(145deg, #2d2d30, #28282B);
            border-radius: 15px;
            color: white;
        }

        .social-links a {
            color: white;
            text-decoration: none;
            margin: 0 20px;
            font-size: 20px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            border-radius: 25px;
            background: rgba(255,255,255,0.1);
        }

        .social-links a:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-3px);
        }

        .social-links i {
            margin-right: 10px;
            font-size: 24px;
        }

        .contact-info {
            margin-top: 20px;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            display: inline-block;
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
    
    <div class="social-links">
        <h2><i class="fas fa-users"></i> Connect With Us</h2>
        <p>Follow us on social media and get in touch!</p>
        <div>
            <a href="https://www.facebook.com/YourPage" target="_blank">
                <i class="fab fa-facebook"></i> Facebook
            </a>
            <a href="https://www.instagram.com/YourPage" target="_blank">
                <i class="fab fa-instagram"></i> Instagram
            </a>
        </div>
        <div class="contact-info">
            <i class="fas fa-phone"></i> Contact us: 
            <a href="tel:09061754863">0906-175-4863</a>
        </div>
    </div>
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

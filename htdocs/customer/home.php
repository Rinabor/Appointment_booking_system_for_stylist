<?php
session_start();
include "db.php";

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

// Get customer ID from session
$customer_id = $_SESSION["CustomerID"];

// Fetch customer details
$sql = "SELECT Name, Lastname, ContactNumber, Address, DateOfBirth FROM Customer WHERE CustomerID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $customer_id);
    if ($stmt->execute()) {
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($name, $lastname, $contactNumber, $address, $dateOfBirth);
            $stmt->fetch();
        } else {
            echo "No customer found.";
        }
    } else {
        echo "Error executing query: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Our Salon & Barbershop Booking System</title>
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
            max-width: 100%; /* Full width */
            margin: 80px auto 20px auto;
            padding: 10px; /* Adjust padding */
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
            background-color: #28282B;
            color: white;
            padding: 15px;
            margin: 10px;
            border-radius: 5px;
            flex: 1 1 calc(50% - 20px);
            max-width: 400px;
            text-align: center;
        }
        .cta-button {
            display: inline-block;
            padding: 12px 24px;
            background:#28282B;
            color: white;
            text-decoration: none;
            font-size: 18px;
            border-radius: 5px;
            margin-top: 20px;
            transition: background 0.3s;
            
        }
        .cta-button:hover {
            background:rgb(255, 0, 0);
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

            .cta-button {
                width: 100%; /* Full width for buttons */
                font-size: 16px;
                padding: 10px;
            }
        }
        h4 {
            color: white;
        }
    </style>
</head>
<body>

<?php include 'menu.php'; ?>

<div class="container">
    <h1>Welcome to the Simple Appointment Booking System</h1>
    <p>Book your next appointment hassle-free with our easy-to-use online booking system.</p>
    
    <h2>Why Choose Our System?</h2>
    <ul class="features">
        <li><h4>Easy online appointment scheduling</h4></li>
        <li><h4>Automated notifications and reminders</h4></li>
        <li><h4>Choose your preferred stylist or barber</h4></li>
        <li><h4>View and manage your appointment history</h4></li>
        <li><h4>Reschedule or cancel appointments with ease</h4></li>
    </ul>
    
    <a href="createappointment.php" class="cta-button">Book an Appointment Now</a>

    <div class="description">
        <h2>About Our System</h2>
        <p>Our Simple Appointment Booking System is designed to make your life easier. Whether you're looking for a quick trim or a complete makeover, our system allows you to book your appointments with just a few clicks. No more waiting on hold or trying to find a time that works for both you and your stylist.</p>
        <p>We offer easy rescheduling and cancellation options. Plus, with automated reminders, you'll never forget an appointment again.</p>
    </div>
</div>

</body>
</html>
<?php
session_start();

// Include database connection
include "db.php";

// Check if stylist is logged in
if (!isset($_SESSION['stylist_id'])) {
    header("location: index.php");
    exit;
}

$stylist_id = $_SESSION['stylist_id'];

// Fetch stylist's information from the database
$sql = "SELECT * FROM Stylist WHERE StylistID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $stylist_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            // Fetch stylist data
            $stylist = $result->fetch_assoc();
        } else {
            echo "No stylist found.";
            exit;
        }
    } else {
        echo "Error executing query: " . $conn->error;
        exit;
    }
    $stmt->close();
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebars.css">
    <style>
        .profile-info {
            width: 100%;
            border-collapse: collapse;
            margin: 20px auto;
        }

        .profile-info th, .profile-info td {
            padding: 10px;
            text-align: left;
            font-size: 16px;
        }

        .profile-info img {
            max-width: 100px;
            height: auto;
            border-radius: 50%;
        }

        @media (max-width: 480px) {
            body {
                margin: 0;
                width: 100%;
                overflow-x: hidden; /* Prevent horizontal scrolling */
            }

            .profile-info th, .profile-info td {
                font-size: 14px; /* Adjust font size */
                padding: 8px;
            }

            .profile-info img {
                max-width: 80px; /* Adjust image size */
            }

            a {
                display: block;
                width: 100%; /* Full width for links */
                text-align: center;
                padding: 10px;
                font-size: 16px;
                margin-top: 10px;
                background-color: black;
                color: white;
                text-decoration: none;
                border-radius: 5px;
            }

            a:hover {
                background-color: red;
            }
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>

    <h2>Your Profile Information</h2>
    <table class="profile-info">
        <tr>
            <th>Profile Picture</th>
            <td>
                <?php if (!empty($stylist['Picture'])): ?>
                    <img src="uploads/<?php echo htmlspecialchars($stylist['Picture']); ?>" alt="Profile Picture" width="100">
                <?php else: ?>
                    No picture uploaded.
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>First Name</th>
            <td><?php echo htmlspecialchars($stylist['Name']); ?></td>
        </tr>
        <tr>
            <th>Last Name</th>
            <td><?php echo htmlspecialchars($stylist['Lastname']); ?></td>
        </tr>
        <tr>
            <th>Specialization</th>
            <td><?php echo htmlspecialchars($stylist['Specialization']); ?></td>
        </tr>
        <tr>
            <th>Phone Number</th>
            <td><?php echo htmlspecialchars($stylist['Phone']); ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?php echo htmlspecialchars($stylist['Email']); ?></td>
        </tr>
        <tr>
            <th>Years of Experience</th>
            <td><?php echo htmlspecialchars($stylist['ExperienceYears']); ?></td>
        </tr>
    </table>
    <a href="manage_profile.php">Update Profile</a>
</body>
</html>

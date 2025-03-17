<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include "db.php";

// Get customer data from the session
$customer_id = $_SESSION["CustomerID"];

// Fetch customer info from the database
$sql = "SELECT Name, Lastname, ContactNumber, Address, DateOfBirth FROM Customer WHERE CustomerID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $customer_id);
    
    if ($stmt->execute()) {
        $stmt->store_result();
        $stmt->bind_result($name, $lastname, $contactNumber, $address, $dateOfBirth);
        $stmt->fetch();
    }
    $stmt->close();
} else {
    echo "Error preparing SQL: " . $conn->error;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="topbar.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    
    <div class="container">
        <h2>Profile Information</h2>
        <table>
            <tr>
                <th>First Name</th>
                <td><?php echo htmlspecialchars($name); ?></td>
            </tr>
            <tr>
                <th>Last Name</th>
                <td><?php echo htmlspecialchars($lastname); ?></td>
            </tr>
            <tr>
                <th>Contact Number</th>
                <td><?php echo htmlspecialchars($contactNumber); ?></td>
            </tr>
            <tr>
                <th>Address</th>
                <td><?php echo htmlspecialchars($address); ?></td>
            </tr>
            <tr>
                <th>Date of Birth</th>
                <td><?php echo htmlspecialchars(date("F d, Y", strtotime($appointment_date ?? ''))); ?></td>

            </tr>
        </table>
        <div class="btn-container">
            <a href="manage_profile.php" class="btn">Edit Profile</a>
            <a href="home.php" class="btn">Back to Home</a>
        </div>
    </div>
</body>
</html>

<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include "db.php";

// Get customer data from session
$customer_id = $_SESSION["CustomerID"];

// Default values
$preferred_stylist = '';
$service_preferences = '';
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
    }
    $stmt->close();
}

// Fetch all stylists
$stylists = [];
$sql_stylists = "SELECT StylistID, Name FROM Stylist";
if ($stmt = $conn->prepare($sql_stylists)) {
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($stylist_id, $stylist_name);
    while ($stmt->fetch()) {
        $stylists[] = ['id' => $stylist_id, 'name' => $stylist_name];
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Preferences</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="topbar.css">
    <style>
        .preferences-table {
            width: 80%;
            max-width: 600px;
            margin: 20px auto;
            border-collapse: collapse;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            overflow: hidden;
        }
        .preferences-table th, .preferences-table td {
            padding: 14px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .preferences-table thead {
            background-color: black;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    <h2>Your Preferences</h2>

    <table class="preferences-table">
        <thead>
            <tr>
                <th>Preference</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Preferred Stylist/Barber</strong></td>
                <td>
                    <?php 
                    $stylist_name = 'None';
                    foreach ($stylists as $stylist) {
                        if ($stylist['id'] == $preferred_stylist) {
                            $stylist_name = htmlspecialchars($stylist['name']);
                            break;
                        }
                    }
                    echo $stylist_name;
                    ?>
                </td>
            </tr>
            <tr>
                <td><strong>Preferred Date</strong></td>
                <td><?php echo htmlspecialchars($preferred_date ? date("F d, Y", strtotime($preferred_date)) : 'None'); ?></td>
            </tr>
            <tr>
                <td><strong>Preferred Time</strong></td>
                <td><?php echo htmlspecialchars($preferred_time ? date("g:i A", strtotime($preferred_time)) : 'None'); ?></td>
            </tr>
        </tbody>
    </table>

    <br>
    <div style="text-align: center;">
        <a href="manage_preference.php">Update Preferences</a> 
    </div>
</body>
</html>
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
if (!isset($_SESSION["CustomerID"])) {
    die("Error: Customer ID not found in session.");
}

$customer_id = $_SESSION["CustomerID"];

// Fetch customer info from the database
$sql = "SELECT Name, Lastname, ContactNumber, Address, DateOfBirth FROM Customer WHERE CustomerID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $customer_id);
    
    if ($stmt->execute()) {
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($name, $lastname, $contactNumber, $address, $dateOfBirth);
            $stmt->fetch();
        } else {
            die("Error: Customer not found.");
        }
    } else {
        die("Error executing query: " . $stmt->error);
    }
    $stmt->close();
}

// Handle form submission to update profile
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_name = trim($_POST['name']);
    $new_lastname = trim($_POST['lastname']);
    $new_contactNumber = trim($_POST['contact_number']);
    $new_address = trim($_POST['address']);
    $new_dateOfBirth = trim($_POST['date_of_birth']);

    // Validate inputs
    if (empty($new_name) || empty($new_lastname) || empty($new_contactNumber) || empty($new_address) || empty($new_dateOfBirth)) {
        $error_message = "All fields are required.";
    } else {
        // Update customer info in the database
        $update_sql = "UPDATE Customer SET Name = ?, Lastname = ?, ContactNumber = ?, Address = ?, DateOfBirth = ? WHERE CustomerID = ?";
        if ($stmt = $conn->prepare($update_sql)) {
            $stmt->bind_param("sssssi", $new_name, $new_lastname, $new_contactNumber, $new_address, $new_dateOfBirth, $customer_id);
            
            if ($stmt->execute()) {
                $success_message = "Profile updated successfully!";
                // Refresh the page to show updated data
                header("Location: manage_profile.php?success=1");
                exit;
            } else {
                $error_message = "Error updating profile: " . $stmt->error;
            }
            $stmt->close();
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
    <title>Manage Profile</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="topbar.css">
</head>
<body>
    <?php include 'menu.php'; ?>
    <div class="profile-container">
        <h2>Manage Your Profile</h2>

        <?php if (isset($_GET['success'])): ?>
            <p>Profile updated successfully!</p>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form class="profile-form" action="manage_profile.php" method="POST">
            <label for="name">First Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>

            <label for="lastname">Last Name:</label>
            <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname ?? ''); ?>" required>

            <label for="contact_number">Contact Number:</label>
            <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($contactNumber ?? ''); ?>" required>

            <label for="address">Address:</label>
            <textarea id="address" name="address" required><?php echo htmlspecialchars($address ?? ''); ?></textarea>

            <label for="date_of_birth">Date of Birth:</label>
            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($dateOfBirth ?? ''); ?>" required>

            <button type="submit">Update Profile</button>
        </form>
    </div>
</body>

</html>

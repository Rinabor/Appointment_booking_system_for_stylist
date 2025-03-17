<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the stylist is logged inac, if not, redirect to login page
if (!isset($_SESSION["stylist_id"])) {
    header("location: index.php");
    exit;
}

// Include the database connection
include "db.php";

// Get stylist ID from session
$stylist_id = $_SESSION["stylist_id"];

// Fetch stylist's current information
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $sql = "SELECT Name, Lastname, Specialization, Phone, Email, ExperienceYears, Picture FROM Stylist WHERE StylistID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $stylist_id);
        if ($stmt->execute()) {
            $stmt->store_result();
            $stmt->bind_result($name, $lastname, $specialization, $phone, $email, $experience_years, $picture);
            $stmt->fetch();
        } else {
            echo "Error executing select query.";
        }
        $stmt->close();
    } else {
        echo "Error preparing select query.";
    }
}

// Handle form submission to update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data and validate it
    $name = !empty($_POST['name']) ? $_POST['name'] : null;
    $lastname = !empty($_POST['lastname']) ? $_POST['lastname'] : null;
    $specialization = !empty($_POST['specialization']) ? $_POST['specialization'] : null;
    $phone = !empty($_POST['phone']) ? $_POST['phone'] : null;
    $email = !empty($_POST['email']) ? $_POST['email'] : null;
    $experience_years = !empty($_POST['experience_years']) ? $_POST['experience_years'] : null;

    // Handle file upload
    $upload_dir = 'uploads/';
    $uploaded_file = $_FILES['profile_picture']['name'];
    $target_file = $upload_dir . basename($uploaded_file);
    $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES['profile_picture']['tmp_name']);
    if ($check !== false) {
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            $picture = $uploaded_file;
        } else {
            $error_message = "Sorry, there was an error uploading your file.";
        }
    } else {
        $error_message = "File is not an image.";
    }

    // Check if all required fields are filled
    if ($name && $lastname && $specialization && $phone && $email && $experience_years) {
        // Update stylist's information
        $update_sql = "UPDATE Stylist SET Name = ?, Lastname = ?, Specialization = ?, Phone = ?, Email = ?, ExperienceYears = ?, Picture = ? WHERE StylistID = ?";
        if ($stmt = $conn->prepare($update_sql)) {
            $stmt->bind_param("sssssssi", $name, $lastname, $specialization, $phone, $email, $experience_years, $picture, $stylist_id);
            if ($stmt->execute()) {
                $success_message = "Profile updated successfully!";
            } else {
                $error_message = "Error updating profile.";
            }
            $stmt->close();
        } else {
            $error_message = "Error preparing update query.";
        }
    } else {
        $error_message = "Please fill all required fields.";
    }
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
</head>
<body>
    <?php include 'menu.php'; ?>
    <div class="container">
        <div class="header">

        </div>

        <!-- Display success or error message -->
        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php elseif (isset($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <!-- Profile form -->
         
        <div class="profile-form-container">
    <h2>Manage Profile</h2>
    <form class="profile-form" action="manage_profile.php" method="POST" enctype="multipart/form-data">
        <label for="name">First Name</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>

        <label for="lastname">Last Name</label>
        <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>

        <label for="specialization">Specialization</label>
        <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($specialization); ?>" required>

        <label for="phone">Phone</label>
        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

        <label for="experience_years">Experience (Years)</label>
        <input type="number" id="experience_years" name="experience_years" value="<?php echo htmlspecialchars($experience_years); ?>" required>

        <label for="profile_picture">Profile Picture</label>
        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">

        <input type="submit" value="Update Profile">

    </form>
    <a href="profile.php">Back to Profile</a>    

</div>

    </div>
</body>
</html>

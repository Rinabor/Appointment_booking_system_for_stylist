<?php
session_start(); // Start the session
include "db.php";

// Define variables and initialize with empty values
$name = $lastname = $contactNumber = $email = $address = $dateOfBirth = $password = "";
$name_err = $lastname_err = $contactNumber_err = $email_err = $password_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter your name.";
    } else {
        $name = trim($_POST["name"]);
    }

    // Validate last name
    if (empty(trim($_POST["lastname"]))) {
        $lastname_err = "Please enter your last name.";
    } else {
        $lastname = trim($_POST["lastname"]);
    }

    // Validate contact number
    if (empty(trim($_POST["contactNumber"]))) {
        $contactNumber_err = "Please enter your contact number.";
    } else {
        $contactNumber = trim($_POST["contactNumber"]);
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        $email = trim($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Invalid email format.";
        } else {
            // Check if email already exists
            $sql = "SELECT CustomerID FROM Customer WHERE Email = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $email);
                if ($stmt->execute()) {
                    $stmt->store_result();
                    if ($stmt->num_rows > 0) {
                        $email_err = "This email is already registered.";
                    }
                } else {
                    $_SESSION['error_message'] = "Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
            }
        }
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Check if there are no errors
    if (empty($name_err) && empty($lastname_err) && empty($contactNumber_err) && empty($email_err) && empty($password_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO Customer (Name, Lastname, ContactNumber, Email, Address, DateOfBirth, Password) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement
            $stmt->bind_param("sssssss", $param_name, $param_lastname, $param_contactNumber, $param_email, $param_address, $param_dateOfBirth, $param_password);

            // Set parameters
            $param_name = $name;
            $param_lastname = $lastname;
            $param_contactNumber = $contactNumber;
            $param_email = $email;
            $param_address = isset($_POST["address"]) ? $_POST["address"] : null;
            $param_dateOfBirth = isset($_POST["dateOfBirth"]) ? $_POST["dateOfBirth"] : null;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Encrypt password

            // Execute the statement and check if it was successful
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Signup successful! You can now log in.";
                header("Location: signup.php"); // Redirect to avoid form resubmission
                exit();
            } else {
                $_SESSION['error_message'] = "Error: " . $stmt->error;
            }

            // Close the statement
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
        }
    }

    // Close connection
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Signup</title>
    <style>
    /* Basic Reset */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Body */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(to bottom right, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.3)), url('image/yawa.jpg') no-repeat center center fixed;
        background-size: cover;
        color: #fff;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        text-align: center;
        position: relative;
    }

    /* Dark Overlay */
    body::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5); /* Dark overlay */
    }

    /* Signup Container */
    .login-container {
        position: relative;
        background-color: rgba(0, 0, 0, 0.8); /* Dark transparent background */
        padding: 40px;
        border-radius: 12px;
        width: 100%;
        max-width: 450px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.6); /* Add shadow for depth */
        animation: fadeIn 1s ease-out;
    }

    /* Fade-in animation */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    /* Form Title */
    h1 {
        font-size: 40px;
        margin-bottom: 30px;
        color: #fff;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
        animation: slideIn 1s ease-out;
    }

    /* Slide-in animation for the title */
    @keyframes slideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Input Fields */
    input[type="text"],
    input[type="password"],
    input[type="email"],
    input[type="date"] {
        width: 100%;
        padding: 14px;
        margin: 12px 0;
        border: 1px solid #333;
        border-radius: 8px;
        font-size: 16px;
        background-color: #fff;
        color: #333;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    input[type="text"]:focus,
    input[type="password"]:focus,
    input[type="email"]:focus,
    input[type="date"]:focus {
        border-color: #ff4d4d; /* Highlight border on focus */
        box-shadow: 0 0 8px rgba(255, 77, 77, 0.5); /* Add subtle glow */
    }

    /* Submit Button */
    button[type="submit"] {
        padding: 14px;
        width: 100%;
        background-color: #ff4d4d;
        color: #fff;
        font-size: 18px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.3s ease;
    }

    button[type="submit"]:hover {
        background-color: #e63946;
        transform: translateY(-2px); /* Subtle lift effect on hover */
    }

    /* Login Link */
    .login-link {
        color: #ff4d4d;
        font-size: 16px;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }

    .login-link:hover {
        color: #e63946;
    }

    /* Error Message */
    .error-message {
        color: #ff4d4d;
        font-size: 14px;
        margin-top: 5px;
        font-weight: bold;
    }

    /* Footer text */
    p {
        font-size: 16px;
        margin-top: 20px;
        color: #ff4d4d;
        font-weight: 600;
    }

    /* Responsive Design */
    @media screen and (max-width: 768px) {
        body {
            padding: 10px;
        }

        .login-container {
            padding: 25px;
            width: 80%;
            max-width: 320px;
        }

        h1 {
            font-size: 32px;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="date"],
        button[type="submit"] {
            font-size: 14px;
        }
    }

    @media screen and (max-width: 480px) {
        .login-container {
            max-width: 90%;
            padding: 20px;
        }

        h1 {
            font-size: 28px;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="date"],
        button[type="submit"] {
            font-size: 12px;
            padding: 12px;
        }
    }
    /* Success Message */
.success-message {
    color: #28a745; /* Green color for success */
    font-size: 14px;
    font-weight: bold;
    text-align: center;
    padding: 12px;
    background: rgba(40, 167, 69, 0.1); /* Light green background */
    border-left: 4px solid #28a745; /* Green left border */
    border-radius: 5px;
    margin-top: 10px;
    opacity: 0;
    transform: translateY(-5px);
    animation: fadeIn 0.5s ease-in-out forwards, pop 0.3s ease-in-out 0.2s;
}

/* Error Message */
.error-message {
    color: #ff4d4d; /* Red color for errors */
    font-size: 14px;
    font-weight: bold;
    text-align: left;
    padding: 8px;
    background: rgba(255, 77, 77, 0.1); /* Light red background */
    border-left: 4px solid #ff4d4d; /* Red left border */
    border-radius: 5px;
    margin-top: 5px;
    opacity: 0;
    transform: translateY(-5px);
    animation: fadeIn 0.5s ease-in-out forwards, shake 0.3s ease-in-out 0.2s;
}

/* Fade-in Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Pop Animation for Success */
@keyframes pop {
    0% {
        transform: scale(0.9);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

/* Shake Animation for Error */
@keyframes shake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    50% { transform: translateX(5px); }
    75% { transform: translateX(-5px); }
    100% { transform: translateX(0); }
}

    </style>
</head>
<body>
    <div class="login-container">
        <h1>Customer Signup</h1>
        
        <!-- Success & Error Messages -->
        <div class="message-container">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
        </div>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <input type="text" name="name" placeholder="First Name" value="<?php echo $name; ?>" required>
                <span class="error-message"><?php echo $name_err; ?></span>
            </div>
            <div class="form-group">
                <input type="text" name="lastname" placeholder="Last Name" value="<?php echo $lastname; ?>" required>
                <span class="error-message"><?php echo $lastname_err; ?></span>
            </div>
            <div class="form-group">
                <input type="text" name="contactNumber" placeholder="Contact Number" value="<?php echo $contactNumber; ?>" required>
                <span class="error-message"><?php echo $contactNumber_err; ?></span>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" value="<?php echo $email; ?>" required>
                <span class="error-message"><?php echo $email_err; ?></span>
            </div>
            <div class="form-group">
                <input type="text" name="address" placeholder="Address" value="<?php echo $address; ?>">
            </div>
            <div class="form-group">
                <input type="date" name="dateOfBirth" value="<?php echo $dateOfBirth; ?>">
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
                <span class="error-message"><?php echo $password_err; ?></span>
            </div>
            <button type="submit">Sign Up</button>
            <p>Already have an account? <a href="index.php" class="login-link">Login</a></p> 
        </form>
    </div>
</body>

</html>


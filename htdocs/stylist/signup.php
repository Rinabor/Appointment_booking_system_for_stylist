<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

include "db.php";

// Initialize variables for form data
$name = $lastname = $specialization = $phone = $email = $experienceYears = $password = $confirm_password = "";
$name_err = $lastname_err = $specialization_err = $phone_err = $email_err = $experienceYears_err = $password_err = $confirm_password_err = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter your first name.";
    } else {
        $name = trim($_POST["name"]);
    }

    // Validate last name
    if (empty(trim($_POST["lastname"]))) {
        $lastname_err = "Please enter your last name.";
    } else {
        $lastname = trim($_POST["lastname"]);
    }

    // Validate specialization
    if (empty(trim($_POST["specialization"]))) {
        $specialization_err = "Please select your specialization.";
    } else {
        $specialization = trim($_POST["specialization"]);
    }

    // Validate phone number
    if (empty(trim($_POST["phone"]))) {
        $phone_err = "Please enter your phone number.";
    } else {
        $phone = trim($_POST["phone"]);
    }

    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter your email.";
    } else {
        // Check if email already exists
        $sql = "SELECT StylistID FROM Stylist WHERE Email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $email_err = "This email is already registered.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Error executing query: " . $conn->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing SQL: " . $conn->error;
        }
    }

    // Validate years of experience
    if (empty(trim($_POST["experienceYears"]))) {
        $experienceYears_err = "Please enter your years of experience.";
    } elseif (!is_numeric($_POST["experienceYears"]) || $_POST["experienceYears"] < 0) {
        $experienceYears_err = "Please enter a valid number for years of experience.";
    } else {
        $experienceYears = trim($_POST["experienceYears"]);
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must be at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm your password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if ($password != $confirm_password) {
            $confirm_password_err = "Passwords do not match.";
        }
    }

    // If no errors, proceed with account creation
    if (empty($name_err) && empty($lastname_err) && empty($specialization_err) && empty($phone_err) && empty($email_err) && empty($experienceYears_err) && empty($password_err) && empty($confirm_password_err)) {
        
        // Prepare the SQL query to insert the new stylist into the database
        $sql = "INSERT INTO Stylist (Name, Lastname, Specialization, Phone, Email, ExperienceYears, Password) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Bind parameters and execute the query
            $stmt->bind_param("sssssis", $name, $lastname, $specialization, $phone, $email, $experienceYears, $hashed_password);
            
            if ($stmt->execute()) {
                // Redirect to the login page after successful registration
                header("location: index.php");
                exit;
            } else {
                echo "Error executing query: " . $conn->error;
            }
            $stmt->close();
        } else {
            echo "Error preparing SQL: " . $conn->error;
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
    <title>Stylist Signup</title>
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
    background: linear-gradient(to bottom right, rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.3)), 
                url('image/yawa.jpg') no-repeat center center fixed;
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
    background: rgba(0, 0, 0, 0.5);
}

/* Signup Container */
.login-container {
    position: relative;
    background-color: rgba(0, 0, 0, 0.8);
    padding: 30px;
    border-radius: 10px;
    width: 100%;
    max-width: 400px; /* Reduced width */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.6);
    animation: fadeIn 1s ease-out;
}

/* Fade-in Animation */
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
    font-size: 32px; /* Reduced font size */
    margin-bottom: 20px;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
    animation: slideIn 1s ease-out;
}

/* Slide-in Animation */
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

/* Form Group */
.form-group {
    margin-bottom: 12px;
    text-align: left;
}

/* Labels */
.form-group label {
    font-weight: bold;
    font-size: 14px; /* Slightly smaller labels */
    color: #fff;
}

/* Input Fields */
input[type="text"],
input[type="password"],
input[type="email"],
input[type="date"],
input[type="number"],
select {
    width: 100%;
    padding: 10px; /* Reduced padding */
    margin-top: 5px;
    border: 1px solid #333;
    border-radius: 6px;
    font-size: 14px;
    background-color: #fff;
    color: #333;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

/* Input Focus Effect */
input:focus,
select:focus {
    border-color: #ff4d4d;
    box-shadow: 0 0 6px rgba(255, 77, 77, 0.5);
}

/* Submit Button */
button[type="submit"] {
    padding: 12px; /* Reduced padding */
    width: 100%;
    background-color: #ff4d4d;
    color: #fff;
    font-size: 16px; /* Slightly smaller */
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.3s ease;
}

/* Button Hover */
button[type="submit"]:hover {
    background-color: #e63946;
    transform: translateY(-2px);
}

/* Login Link */
.login-link {
    color: #ff4d4d;
    font-size: 14px;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.3s ease;
}

.login-link:hover {
    color: #e63946;
}

/* Error Message */
.error {
    color: #ff4d4d;
    font-size: 12px;
    margin-top: 3px;
    font-weight: bold;
}

/* Footer Text */
p {
    font-size: 14px;
    margin-top: 15px;
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
        max-width: 350px;
    }

    h1 {
        font-size: 28px;
    }

    input,
    button {
        font-size: 14px;
    }
}

@media screen and (max-width: 480px) {
    .login-container {
        padding: 20px;
        max-width: 90%;
    }

    h1 {
        font-size: 24px;
    }

    input,
    button {
        font-size: 12px;
        padding: 8px;
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

/* Input Fields */
input[type="text"],
input[type="password"],
input[type="email"] {
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
input[type="email"]:focus {
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
</style>
</head>
<body>
    <div class="login-container">
        
            <h2>Stylist Signup</h2>

            <form action="signup.php" method="POST">
                <div class="form-group">
                    <label for="name">First Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                    <span class="error"><?php echo $name_err; ?></span>
                </div>

                <div class="form-group">
                    <label for="lastname">Last Name</label>
                    <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
                    <span class="error"><?php echo $lastname_err; ?></span>
                </div>

                <div class="form-group">
                    <label for="specialization">Specialization</label>
                    <select id="specialization" name="specialization" required>
                        <option value="" disabled selected>Select your specialization</option>
                        <option value="Stylist" <?php echo $specialization == 'Stylist' ? 'selected' : ''; ?>>Stylist</option>
                        <option value="Barber" <?php echo $specialization == 'Barber' ? 'selected' : ''; ?>>Barber</option>
                    </select>
                    <span class="error"><?php echo $specialization_err; ?></span>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                    <span class="error"><?php echo $phone_err; ?></span>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    <span class="error"><?php echo $email_err; ?></span>
                </div>

                <div class="form-group">
                    <label for="experienceYears">Years of Experience</label>
                    <input type="number" id="experienceYears" name="experienceYears" value="<?php echo htmlspecialchars($experienceYears); ?>" required>
                    <span class="error"><?php echo $experienceYears_err; ?></span>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <span class="error"><?php echo $password_err; ?></span>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <span class="error"><?php echo $confirm_password_err; ?></span>
                </div>

                <button type="submit" class="btn">Create Account</button>
            </form>

            <p>Already have an account? <a href="index.php" class="login-link">Login</a></p> 
        </div>
    </div>
</body>
</html>
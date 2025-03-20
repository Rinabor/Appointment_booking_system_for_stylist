<?php
session_start();
include "db.php";

$error_message = $success_message = "";

// Check if a success message is set (e.g., after registration)
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Remove after displaying
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error_message = "Both fields are required.";
    } else {
        // Query to check if the stylist exists
        $sql = "SELECT * FROM Stylist WHERE Email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $stylist = $result->fetch_assoc();
                if (password_verify($password, $stylist['Password'])) {
                    $_SESSION['stylist_id'] = $stylist['StylistID'];
                    $_SESSION['stylist_name'] = $stylist['Name'];
                    header("location: dashboard.php");
                    exit;
                } else {
                    $error_message = "Invalid password.";
                }
            } else {
                $error_message = "No stylist found with this email.";
            }
            $stmt->close();
        } else {
            $error_message = "Database error: " . $conn->error;
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
    <title>Stylist Login</title>
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

        /* Login Container */
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
        <h1>Login</h1>

        <?php if (!empty($success_message)) : ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)) : ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit">Log in</button>
            <p>No account? <a href="signup.php" class="login-link">Sign Up</a></p>
        </form>
    </div>
</body>
</html>

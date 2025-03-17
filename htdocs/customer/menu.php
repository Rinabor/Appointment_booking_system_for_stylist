<?php

include "db.php";

$notif_count = 0;

if (isset($_SESSION["CustomerID"])) {
    $customer_id = $_SESSION["CustomerID"];

    // Count new notifications for the customer
    $notif_sql = "
        SELECT COUNT(*) 
        FROM AppointmentNotification 
        WHERE CustomerID = ? 
        AND NotificationType IN ('Accepted', 'Rejected', 'Reminder', 'Completed')
    ";

    if ($notif_stmt = $conn->prepare($notif_sql)) {
        $notif_stmt->bind_param("i", $customer_id);
        $notif_stmt->execute();
        $notif_stmt->bind_result($notif_count);
        $notif_stmt->fetch();
        $notif_stmt->close();
    }
}

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
?>

<nav>
    <div style="display: flex; align-items: center;">
        <div class="menu-icon" onclick="toggleMenu()">☰</div>
        <div class="logo">CUSTOMER</div>
    </div>
    <ul class="nav-links">
        <li><a href="home.php">🏠Home</a></li>
        <li><a href="about_us.php">📝About Us</a></li>
        <li><a href="appointments.php">📊Appointments</a></li>
        <li><a href="createappointment.php">📅Book Appointment</a></li>
        <li><a href="preference.php">⚙️Preference</a></li>
        <li><a href="appointmenthistory.php">📜History</a></li>
        
        <li class="notification-menu">
            <a href="appointmentnotification.php">
            🔔Notifications
                <?php if ($notif_count > 0): ?>
                    <span class="notif-badge"><?php echo $notif_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li><a href="profile.php">👤Profile</a></li>
        <li><a href="logout.php" class="logout-btn">🚪Logout</a></li>
    </ul>
</nav>

<style>
    .notification-menu {
        position: relative;
    }
    .notif-badge {
        background: red;
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 3px 7px;
        border-radius: 50%;
        position: absolute;
        top: -5px;
        right: -10px;
    }
    .menu-icon {
        cursor: pointer;
    }
</style>

<script>
    function toggleMenu() {
        document.querySelector('.nav-links').classList.toggle('active');
    }

    // Auto-refresh every 30 seconds to update notifications
    setInterval(function() {
        location.reload();
    }, 30000);
</script>

<?php
include "db.php";

$notif_count = 0;

if (isset($_SESSION["stylist_id"])) {
    $stylist_id = $_SESSION["stylist_id"];

    // Count new notifications for the stylist
    $notif_sql = "
        SELECT COUNT(*) 
        FROM AppointmentNotification 
        WHERE StylistID = ? 
        AND NotificationType IN ('Cancelled', 'Rescheduled Appointment', 'New Appointment', 'Reminder')
    ";

    if ($notif_stmt = $conn->prepare($notif_sql)) {
        $notif_stmt->bind_param("i", $stylist_id);
        $notif_stmt->execute();
        $notif_stmt->bind_result($notif_count);
        $notif_stmt->fetch();
        $notif_stmt->close();
    }
}

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
?>
<!-- Topbar with hamburger menu and logo -->
<div class="topbar">
    <div class="menu-icon" onclick="toggleMenu()">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="profile-menu" onclick="toggleProfileMenu()">
        <div class="logo">
            🠟<?php echo htmlspecialchars($stylist['Name']); ?>
            <i class="fas fa-chevron-down" style="color: white; margin-left: 10px;"></i>
        </div>
        <div class="profile-dropdown">
            <a href="overall.php" >Over All</a>
            <a href="profile.php" >Profile</a>
            <a href="logout.php" >Logout</a>         
        </div>
    </div>
</div>
<!-- Overlay for mobile view -->
<div class="overlay" onclick="toggleMenu()"></div>
<!-- Sidebar Navigation -->
<div class="sidebar">
    <ul class="nav-links">
        <div class="logo">
            STYLIST
            <i class="fas fa-chevron-down" style="color: white; margin-left: 10px;"></i>
        </div>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="Appointments.php">Appointments</a></li>
        <li><a href="manage_appointment.php">Manage Appointments</a></li>
        <li><a href="schedule.php">Schedule</a></li>
        <li><a href="history.php">History</a></li>
        <li class="notification-menu">
            <a href="stylist_notification.php">
                Notifications
                <?php if ($notif_count > 0): ?>
                    <span class="notif-badge"><?php echo $notif_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>
</div>
<script>
function toggleMenu() {
    const sidebar = document.querySelector('.sidebar');
    const menuIcon = document.querySelector('.menu-icon');
    const overlay = document.querySelector('.overlay');
    sidebar.classList.toggle('active');
    menuIcon.classList.toggle('active');
    overlay.classList.toggle('active');
}

function toggleProfileMenu() {
    const profileDropdown = document.querySelector('.profile-dropdown');
    profileDropdown.classList.toggle('active');
}

// Auto-refresh every 30 seconds to update notifications
setInterval(function() {
    location.reload();
}, 30000);
</script>
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
        top: 10px;
        right: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
    }
    /* Profile Menu */
.profile-menu {
    display: flex;
    align-items: center;
    cursor: pointer;
    position: relative;
    margin-left: auto;
    padding: 10px;
    background: #111;
    border-radius: 5px;
    transition: background 0.3s;
}

.profile-menu:hover {
    background: #222;
}

.profile-menu .profile-dropdown {
    position: absolute;
    top: 60px;
    right: 0;
    background: #222;
    border-radius: 5px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
    display: none;
    width: 200px;
    text-align: center;
    z-index: 1001;
}

.profile-menu .profile-dropdown.active {
    display: block;
}

.profile-dropdown a {
    display: block;
    color: white;
    padding: 15px;
    text-decoration: none;
    font-size: 16px;
    transition: background 0.3s;
}

.profile-dropdown a:hover {
    background: red;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .profile-menu {
        padding: 8px;
    }
    
    .profile-menu .profile-dropdown {
        top: 50px;
        width: 180px;
    }
    
    .profile-dropdown a {
        padding: 12px;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .profile-menu {
        padding: 6px;
    }

    .profile-menu .profile-dropdown {
        top: 45px;
        width: 160px;
    }

    .profile-dropdown a {
        padding: 10px;
        font-size: 12px;
    }
}

</style>

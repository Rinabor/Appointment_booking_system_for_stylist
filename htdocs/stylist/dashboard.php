<?php
session_start();
include "db.php";

if (!isset($_SESSION['stylist_id'])) {
    header("location: index.php");
    exit;
}

$stylist_id = $_SESSION['stylist_id'];

// Fetch stylist's name
$sql = "SELECT Name FROM Stylist WHERE StylistID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $stylist_id);
    if ($stmt->execute()) {
        $stmt->store_result();
        $stmt->bind_result($stylist_name);
        $stmt->fetch();
    }
    $stmt->close();
}

// Fetch appointment counts
$query = "SELECT 
            SUM(CASE WHEN Status = 'Accepted' THEN 1 ELSE 0 END) AS accepted_count,
            SUM(CASE WHEN Status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN Status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_count,
            SUM(CASE WHEN Status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
            SUM(CASE WHEN Status = 'Rescheduled' THEN 1 ELSE 0 END) AS rescheduled_count,
            SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) AS completed_count
          FROM Appointment WHERE StylistID = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $stylist_id);
$stmt->execute();
$result = $stmt->get_result();
$counts = $result->fetch_assoc();

$accepted_count = $counts['accepted_count'] ?? 0;
$pending_count = $counts['pending_count'] ?? 0;
$rejected_count = $counts['rejected_count'] ?? 0;
$cancelled_count = $counts['cancelled_count'] ?? 0;
$rescheduled_count = $counts['rescheduled_count'] ?? 0;
$completed_count = $counts['completed_count'] ?? 0;

$stmt->close();

// Fetch appointment trends for completed and cancelled appointments
$trend_query = "SELECT 
                    MONTH(AppointmentDate) AS month, 
                    SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) AS completed_count,
                    SUM(CASE WHEN Status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_count
                FROM Appointment 
                WHERE StylistID = ? AND YEAR(AppointmentDate) = YEAR(CURDATE())
                GROUP BY MONTH(AppointmentDate)";

$stmt = $conn->prepare($trend_query);
$stmt->bind_param("i", $stylist_id);
$stmt->execute();
$result = $stmt->get_result();

$months = [];
$completed_counts = [];
$cancelled_counts = [];

while ($row = $result->fetch_assoc()) {
    $months[] = date("F", mktime(0, 0, 0, $row['month'], 10));
    $completed_counts[] = $row['completed_count'];
    $cancelled_counts[] = $row['cancelled_count'];
}

$stmt->close();

// Fetch stylist users
$stylists = $conn->query("SELECT StylistID, Name, Email, Picture FROM Stylist");

$conn->close();

// Set the timezone to Philippines
date_default_timezone_set('Asia/Manila');
$current_date = date("h:i:s A, l F j, Y");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="sidebars.css">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding-top: 80px;
        }
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 30px;
            max-width: 1400px;
            margin: auto;
        }
        .card {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            transition: 0.3s;
        }
        .card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        .card h2 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #000;
        }
        .card p {
            font-size: 2.5rem;
            font-weight: bold;
            color: #000;
        }
        .card i {
            font-size: 4rem;
            margin-bottom: 15px;
            color: #000;
        }
        .card.accepted {
            background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%);
        }
        .card.pending {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
        }
        .card.rejected {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
        }
        .card.completed {
            background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%);
        }
        .status-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        .status-cards .card {
            flex: 1 1 calc(25% - 30px);
        }
        .stylist-table {
            width: 100%;
            max-width: 1000px;
            margin: 30px auto;
            background: #fff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 15px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background: #f4f4f4;
        }
        table img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
        }
        /* Dashboard Layout */
        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            padding: 30px;
            max-width: 1400px;
            margin: auto;
        }

        /* Line Chart Styling */
        .chart-container {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            height: 300px;
            overflow: hidden;
        }

        #appointmentChart {
            width: 100% !important;
            height: 270px !important;
        }

        /* Pie Chart Styling */
        .piechart-container {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #appointmentPieChart {
            max-width: 90%;
            height: auto;
        }

        /* Calendar Styling */
        .calendar-container {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            margin: 30px auto;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            .chart-container, .piechart-container, .calendar-container {
                width: 100%;
            }
            .status-cards .card {
                flex: 1 1 100%;
            }
        }

        @media (max-width: 480px) {
            .card h2 {
                font-size: 1.2rem;
            }
            .card p {
                font-size: 2rem;
            }
            .card i {
                font-size: 3rem;
            }
            .status-cards .card {
                flex: 1 1 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'menu.php'; ?>
    <h1> Dashboard</h1>
    
    <div class="dashboard">
        <div class="status-cards">
            <div class="card accepted">
                <i class="fas fa-check-circle"></i>
                <h2>Accepted</h2>
                <p><?php echo $accepted_count; ?></p>
            </div>
            <div class="card pending">
                <i class="fas fa-hourglass-half"></i>
                <h2>Pending</h2>
                <p><?php echo $pending_count; ?></p>
            </div>
            <div class="card rejected">
                <i class="fas fa-times-circle"></i>
                <h2>Rejected</h2>
                <p><?php echo $rejected_count; ?></p>
            </div>
            <div class="card completed">
                <i class="fas fa-check-double"></i>
                <h2>Completed</h2>
                <p><?php echo $completed_count; ?></p>
            </div>
        </div>
        <div class="card calendar-container">
            <h2><i class="fas fa-calendar-alt"></i> 📅Current Date</h2>
            <p><?php echo $current_date; ?></p>
        </div>
    </div>
    <div class="dashboard-container">
        <!-- Line Chart Card -->
        <div class="card chart-container">
            <h2>Appointments Trend</h2>
            <canvas id="appointmentChart"></canvas>
        </div>

        <!-- Pie Chart Card -->
        <div class="card piechart-container">
            <h2>Appointment Status</h2>
            <canvas id="appointmentPieChart"></canvas>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="chart.js"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        // Line Chart (Appointment Trends)
        let ctx = document.getElementById("appointmentChart").getContext("2d");
        new Chart(ctx, {
            type: "line",
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [
                    {
                        label: "Completed Appointments",
                        data: <?php echo json_encode($completed_counts); ?>,
                        borderColor: "#4CAF50", // Green for completed
                        backgroundColor: "rgba(76, 175, 80, 0.2)",
                        fill: true
                    },
                    {
                        label: "Cancelled Appointments",
                        data: <?php echo json_encode($cancelled_counts); ?>,
                        borderColor: "#F44336", // Red for cancelled
                        backgroundColor: "rgba(244, 67, 54, 0.2)",
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Pie Chart (Appointment Status Breakdown)
        let pieCtx = document.getElementById("appointmentPieChart").getContext("2d");
        new Chart(pieCtx, {
            type: "pie",
            data: {
                labels: ["Accepted", "Pending", "Rejected", "Cancelled", "Completed"],
                datasets: [{
                    data: <?php echo json_encode([$accepted_count, $pending_count, $rejected_count, $cancelled_count, $completed_count]); ?>,
                    backgroundColor: [
                        "#4CAF50", // Green - Accepted
                        "#FFC107", // Yellow - Pending
                        "#9E9E9E", // Grey - Rejected
                        "#F44336", // Red - Cancelled
                        "#8BC34A"  // Light Green - Completed
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
    </script>

    <div class="stylist-table">
        <h2>Stylist Users</h2>
        <table>
            <tr>
                <th>Picture</th>
                <th>Name</th>
                <th>Email</th>
            </tr>
            <?php while ($stylist = $stylists->fetch_assoc()) { ?>
                <tr>
                    <td><img src="uploads/<?php echo htmlspecialchars($stylist['Picture']); ?>" alt="Stylist Image"></td>
                    <td><?php echo htmlspecialchars($stylist['Name']); ?></td>
                    <td><?php echo htmlspecialchars($stylist['Email']); ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>

</body>
</html>
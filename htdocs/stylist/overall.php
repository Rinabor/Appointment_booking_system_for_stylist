<?php
session_start();
include "db.php";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if the stylist is logged in, if not redirect to login page
if (!isset($_SESSION['stylist_id'])) {
    header("location: index.php");
    exit;
}

// Fetch appointment trends for all stylists
$trend_query = "SELECT 
                    MONTH(AppointmentDate) AS month, 
                    SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) AS completed_count,
                    SUM(CASE WHEN Status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_count
                FROM Appointment 
                WHERE YEAR(AppointmentDate) = YEAR(CURDATE())
                GROUP BY MONTH(AppointmentDate)";

$stmt = $conn->prepare($trend_query);
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
    <title>Overall Appointment Trends</title>
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
        .status-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }
        .status-cards .card {
            flex: 1 1 calc(50% - 30px);
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            .chart-container {
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
    <h1>Overall Appointment Trends</h1>
    
    <div class="dashboard">
        <div class="status-cards">
            <div class="card">
                <i class="fas fa-check-circle"></i>
                <h2>Completed Appointments</h2>
                <p><?php echo array_sum($completed_counts); ?></p>
            </div>
            <div class="card">
                <i class="fas fa-ban"></i>
                <h2>Cancelled Appointments</h2>
                <p><?php echo array_sum($cancelled_counts); ?></p>
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
    });
    </script>
</body>
</html>

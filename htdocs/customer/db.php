<?php
$servername = "localhost";
$username = "mariadb";
$password = "mariadb";
$dbname = "mariadb";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
//echo "Connected successfully";
?>
<?php
include "db.php";

$customer_id = $_GET['customer_id'] ?? 0;

if ($customer_id) {
    $sql = "SELECT Name, Lastname, ContactNumber, Email, Address FROM Customer WHERE CustomerID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $stmt->bind_result($name, $lastname, $contact_number, $email, $address);
        $stmt->fetch();
        $stmt->close();

        echo "Name: $name $lastname\nContact Number: $contact_number\nEmail: $email\nAddress: $address";
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
} else {
    echo "Invalid customer ID.";
}

$conn->close();
?>

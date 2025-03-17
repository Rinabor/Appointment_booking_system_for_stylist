<?php
include "db.php";

$search = $_GET['search'] ?? '';

if (!empty($search)) {
    $search_param = "%$search%";
    $sql = "SELECT DISTINCT C.Name, C.Lastname 
            FROM Appointment A
            JOIN Customer C ON A.CustomerID = C.CustomerID
            WHERE (C.Name LIKE ? OR C.Lastname LIKE ?) AND A.StylistID = ? AND A.AppointmentDate >= CURDATE() AND A.Status = 'Accepted'";
    
    if ($stmt = $conn->prepare($sql)) {
        $stylist_id = $_SESSION['stylist_id'];
        $stmt->bind_param("ssi", $search_param, $search_param, $stylist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $customers = [];
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row['Name'] . " " . $row['Lastname'];
        }
        
        echo json_encode($customers);
    }
}
?>
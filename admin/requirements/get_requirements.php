<?php
session_start();
require_once "../../backend/config.php";

header('Content-Type: application/json');

try {
    $query = "SELECT * FROM tbl_requirements ORDER BY requirement_id";
    $result = $conn->query($query);
    
    $requirements = [];
    while ($row = $result->fetch_assoc()) {
        $requirements[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'requirements' => $requirements
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching requirements: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
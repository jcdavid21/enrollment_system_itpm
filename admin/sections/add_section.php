<?php
session_start();
header('Content-Type: application/json');
require_once "../../backend/config.php";
include "../../backend/audit_logs.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Validate required fields
    $required_fields = ['sec_name', 'level_id', 'sec_capacity'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $sec_name = trim($_POST['sec_name']);
    $level_id = (int)$_POST['level_id'];
    $sec_capacity = (int)$_POST['sec_capacity'];
    $sec_adviser = !empty($_POST['sec_adviser']) ? trim($_POST['sec_adviser']) : null;
    
    // Validate data
    if (strlen($sec_name) < 3) {
        throw new Exception('Section name must be at least 3 characters long');
    }
    
    if ($sec_capacity < 1 || $sec_capacity > 50) {
        throw new Exception('Section capacity must be between 1 and 50');
    }
    
    // Check if level_id exists
    $level_check = $conn->prepare("SELECT fee_id FROM tbl_fees WHERE fee_id = ?");
    $level_check->bind_param("i", $level_id);
    $level_check->execute();
    if ($level_check->get_result()->num_rows === 0) {
        throw new Exception('Invalid grade level selected');
    }
    
    // Check if section name already exists for this level
    $name_check = $conn->prepare("SELECT sec_id FROM tbl_sections WHERE sec_name = ? AND level_id = ?");
    $name_check->bind_param("si", $sec_name, $level_id);
    $name_check->execute();
    if ($name_check->get_result()->num_rows > 0) {
        throw new Exception('Section name already exists for this grade level');
    }
    
    // Insert new section
    $stmt = $conn->prepare("INSERT INTO tbl_sections (sec_name, level_id, sec_capacity, sec_adviser) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siis", $sec_name, $level_id, $sec_capacity, $sec_adviser);
    
    if ($stmt->execute()) {
        // Log the action
        $admin_id = $_SESSION["user_id"] ?? null;
        $action = "Added new section: $sec_name (Level ID: $level_id, Capacity: $sec_capacity)";
        logAction($conn, $admin_id, $action);

        echo json_encode([
            'success' => true,
            'message' => 'Section added successfully',
            'section_id' => $conn->insert_id
        ]);
    } else {
        throw new Exception('Failed to add section');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
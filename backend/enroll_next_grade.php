<?php
session_start();
require_once "./config.php";

header('Content-Type: application/json');

// Check if user is logged in and is a student
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Student") {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION["user_id"];
$next_level_id = isset($_POST['next_level_id']) ? intval($_POST['next_level_id']) : 0;
$student_type = isset($_POST['student_type']) ? $_POST['student_type'] : '';

// Validate inputs
if ($next_level_id <= 0 || $next_level_id > 8) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid grade level']);
    exit();
}

if (!in_array($student_type, ['new_old', 'transferee'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid student type']);
    exit();
}

try {
    $conn->begin_transaction();
    
    // Get personal_id
    $query = "SELECT personal_id FROM tbl_personal_details WHERE acc_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $personal_data = $result->fetch_assoc();
    
    if (!$personal_data) {
        throw new Exception('Student record not found');
    }
    
    $personal_id = $personal_data['personal_id'];
    
    // Update the level_id in the appropriate table
    if ($student_type === 'new_old') {
        $update_query = "UPDATE tbl_new_old_students SET level_id = ?, section_id = NULL WHERE personal_id = ?";
    } else {
        $update_query = "UPDATE tbl_student_transferee SET level_id = ?, section_id = NULL WHERE personal_id = ?";
    }
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $next_level_id, $personal_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update grade level');
    }
    
    // Update enrollment status to Pending
    $status_query = "UPDATE tbl_account SET enrollment_status = 'Pending' WHERE acc_id = ?";
    $stmt = $conn->prepare($status_query);
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update enrollment status');
    }
    
    // Get the new grade level name
    $level_query = "SELECT level FROM tbl_fees WHERE fee_id = ?";
    $stmt = $conn->prepare($level_query);
    $stmt->bind_param("i", $next_level_id);
    $stmt->execute();
    $level_result = $stmt->get_result();
    $level_data = $level_result->fetch_assoc();
    
    $conn->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Successfully enrolled for ' . $level_data['level'] . '. Your enrollment is now pending approval.'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>
<?php
// get_section_capacities.php
header('Content-Type: application/json');
require_once "../../backend/config.php";

if (!isset($_GET['level_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Level ID is required']);
    exit;
}

$level_id = intval($_GET['level_id']);

try {
    // Get all sections for this level
    $sections_query = "SELECT sec_id, sec_name, sec_capacity FROM tbl_sections WHERE level_id = ?";
    $stmt = $conn->prepare($sections_query);
    $stmt->bind_param("i", $level_id);
    $stmt->execute();
    $sections_result = $stmt->get_result();
    
    $section_counts = [];
    
    while ($section = $sections_result->fetch_assoc()) {
        $sec_id = $section['sec_id'];
        
        // Count students currently enrolled in this section from both tables
        // New students table
        $new_students_query = "
            SELECT COUNT(*) as count 
            FROM tbl_new_old_students nos
            INNER JOIN tbl_personal_details pd ON nos.personal_id = pd.personal_id
            INNER JOIN tbl_account acc ON pd.acc_id = acc.acc_id
            WHERE nos.section_id = ? 
            AND acc.enrollment_status = 'Enrolled'
            AND nos.level_id = ?
        ";
        
        $stmt_new = $conn->prepare($new_students_query);
        $stmt_new->bind_param("ii", $sec_id, $level_id);
        $stmt_new->execute();
        $new_count = $stmt_new->get_result()->fetch_assoc()['count'];
        
        // Transferee students table
        $transferee_query = "
            SELECT COUNT(*) as count 
            FROM tbl_student_transferee st
            INNER JOIN tbl_personal_details pd ON st.personal_id = pd.personal_id
            INNER JOIN tbl_account acc ON pd.acc_id = acc.acc_id
            WHERE st.section_id = ? 
            AND acc.enrollment_status = 'Enrolled'
            AND st.level_id = ?
        ";
        
        $stmt_transferee = $conn->prepare($transferee_query);
        $stmt_transferee->bind_param("ii", $sec_id, $level_id);
        $stmt_transferee->execute();
        $transferee_count = $stmt_transferee->get_result()->fetch_assoc()['count'];
        
        // Total current enrollment for this section
        $total_enrolled = $new_count + $transferee_count;
        $section_counts[$sec_id] = $total_enrolled;
    }
    
    echo json_encode([
        'success' => true,
        'section_counts' => $section_counts
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch section capacities: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
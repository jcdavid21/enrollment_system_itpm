<?php
header('Content-Type: application/json');
require_once "../../backend/config.php";

try {
    // Get all sections with enrolled student count
    $sections_query = "
        SELECT 
            s.sec_id,
            s.sec_name,
            s.level_id,
            s.sec_capacity,
            s.sec_adviser,
            f.level as grade_level,
            COALESCE(enrolled_new.count, 0) + COALESCE(enrolled_transferee.count, 0) as enrolled_count
        FROM tbl_sections s
        LEFT JOIN tbl_fees f ON s.level_id = f.fee_id
        LEFT JOIN (
            SELECT 
                ns.section_id,
                COUNT(*) as count
            FROM tbl_new_old_students ns
            INNER JOIN tbl_personal_details pd ON ns.personal_id = pd.personal_id
            INNER JOIN tbl_account a ON pd.acc_id = a.acc_id
            WHERE a.enrollment_status = 'Enrolled'
            GROUP BY ns.section_id
        ) enrolled_new ON s.sec_id = enrolled_new.section_id
        LEFT JOIN (
            SELECT 
                st.section_id,
                COUNT(*) as count
            FROM tbl_student_transferee st
            INNER JOIN tbl_personal_details pd ON st.personal_id = pd.personal_id
            INNER JOIN tbl_account a ON pd.acc_id = a.acc_id
            WHERE a.enrollment_status = 'Enrolled'
            GROUP BY st.section_id
        ) enrolled_transferee ON s.sec_id = enrolled_transferee.section_id
        ORDER BY s.level_id, s.sec_name
    ";
    
    $sections_result = $conn->query($sections_query);
    $sections = [];
    
    if ($sections_result) {
        while ($row = $sections_result->fetch_assoc()) {
            $sections[] = $row;
        }
    }
    
    // Calculate statistics
    $total_sections = count($sections);
    $total_students = 0;
    $total_capacity = 0;
    $available_slots = 0;
    
    foreach ($sections as $section) {
        $enrolled = (int)$section['enrolled_count'];
        $capacity = (int)$section['sec_capacity'];
        
        $total_students += $enrolled;
        $total_capacity += $capacity;
        $available_slots += max(0, $capacity - $enrolled);
    }
    
    $avg_capacity = $total_capacity > 0 ? round(($total_students / $total_capacity) * 100) : 0;
    
    $stats = [
        'total_sections' => $total_sections,
        'total_students' => $total_students,
        'avg_capacity' => $avg_capacity,
        'available_slots' => $available_slots
    ];
    
    echo json_encode([
        'success' => true,
        'sections' => $sections,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching sections: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
<?php
session_start();
require_once "../../backend/config.php";
require_once "../../vendor/autoload.php"; // If using PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $deadline_date = $input['deadline_date'];
    $custom_message = isset($input['custom_message']) ? $input['custom_message'] : '';
    
    if (!$deadline_date) {
        throw new Exception('Deadline date is required');
    }
    
    // Get students with incomplete requirements
    $students_query = "
        SELECT DISTINCT
            a.acc_id,
            a.email,
            CONCAT(pd.first_name, ' ', COALESCE(pd.middle_name, ''), ' ', pd.last_name) as full_name,
            student_info.level,
            student_info.section,
            GROUP_CONCAT(
                CASE WHEN sr.requirement_status IS NULL OR sr.requirement_status IN ('Pending', 'Declined') 
                THEN r.requirement_name END 
                SEPARATOR ', '
            ) as missing_requirements
        FROM tbl_account a
        INNER JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
        LEFT JOIN (
            SELECT 
                nst.personal_id, 
                f.level, 
                s.sec_name as section
            FROM tbl_new_old_students nst 
            LEFT JOIN tbl_fees f ON nst.level_id = f.fee_id
            LEFT JOIN tbl_sections s ON nst.section_id = s.sec_id
            UNION
            SELECT 
                st.personal_id, 
                f.level, 
                s.sec_name as section
            FROM tbl_student_transferee st 
            LEFT JOIN tbl_fees f ON st.level_id = f.fee_id
            LEFT JOIN tbl_sections s ON st.section_id = s.sec_id
        ) student_info ON pd.personal_id = student_info.personal_id
        CROSS JOIN tbl_requirements r
        LEFT JOIN tbl_student_requirements sr ON a.acc_id = sr.acc_id AND r.requirement_id = sr.requirement_id
        WHERE a.role = 'Student'
        GROUP BY a.acc_id, a.email, pd.first_name, pd.middle_name, pd.last_name, student_info.level, student_info.section
        HAVING missing_requirements IS NOT NULL
    ";
    
    $students_result = $conn->query($students_query);
    $emails_sent = 0;
    
    if ($students_result->num_rows > 0) {
        // Configure PHPMailer (adjust these settings according to your email setup)
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Change to your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'donotreply.fh.qc@gmail.com'; // Your email
        $mail->Password   = 'degu hdcj rids bfrx';    // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('donotreply.fh.qc@gmail.com', 'Fonthills Christian School');

        while ($student = $students_result->fetch_assoc()) {
            try {
                $mail->clearAddresses();
                $mail->addAddress($student['email'], $student['full_name']);
                
                $mail->Subject = 'Requirements Submission Deadline - Action Required';
                
                // Email body with student-specific information
                $email_body = "
                <html>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <h2 style='color: #d32f2f; border-bottom: 2px solid #d32f2f; padding-bottom: 10px;'>
                            Requirements Submission Deadline
                        </h2>
                        
                        <p>Dear <strong>{$student['full_name']}</strong>,</p>
                        
                        <p>This is an important reminder regarding your enrollment requirements submission.</p>
                        
                        <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                            <h3 style='margin-top: 0; color: #856404;'>Deadline Notice</h3>
                            <p style='margin-bottom: 0;'><strong>Submission Deadline: " . date('F j, Y', strtotime($deadline_date)) . "</strong></p>
                        </div>
                        
                        <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <h4 style='margin-top: 0; color: #495057;'>Your Information:</h4>
                            <p style='margin: 5px 0;'><strong>Name:</strong> {$student['full_name']}</p>
                            <p style='margin: 5px 0;'><strong>Grade & Section:</strong> {$student['level']} - {$student['section']}</p>
                        </div>
                        
                        <div style='background-color: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0;'>
                            <h4 style='margin-top: 0; color: #721c24;'>Missing Requirements:</h4>
                            <p style='margin-bottom: 0; color: #721c24;'>{$student['missing_requirements']}</p>
                        </div>";
                        
                if (!empty($custom_message)) {
                    $email_body .= "
                        <div style='background-color: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0;'>
                            <h4 style='margin-top: 0; color: #0c5460;'>Additional Message:</h4>
                            <p style='margin-bottom: 0; color: #0c5460;'>" . htmlspecialchars($custom_message) . "</p>
                        </div>";
                }
                
                $email_body .= "
                        <div style='margin: 30px 0; padding: 20px; border: 2px solid #28a745; border-radius: 5px; background-color: #d4edda;'>
                            <h4 style='margin-top: 0; color: #155724;'>What You Need To Do:</h4>
                            <ol style='color: #155724; margin-bottom: 0;'>
                                <li>Login to your student portal</li>
                                <li>Navigate to the requirements section</li>
                                <li>Submit all missing requirements listed above</li>
                                <li>Ensure all documents are clear and properly formatted</li>
                            </ol>
                        </div>
                        
                        <div style='margin: 30px 0; text-align: center;'>
                            <a href='#' style='background-color: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                                Access Student Portal
                            </a>
                        </div>
                        
                        <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 14px; color: #6c757d;'>
                            <p><strong>Important:</strong> Failure to submit all requirements by the deadline may affect your enrollment status.</p>
                            <p>If you have any questions or need assistance, please contact the school administration immediately.</p>
                            <br>
                            <p>Best regards,<br>
                            <strong>School Administration</strong><br>
                            Email: admin@school.com<br>
                            Phone: (123) 456-7890</p>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                $mail->isHTML(true);
                $mail->Body = $email_body;
                
                if ($mail->send()) {
                    $emails_sent++;
                }
                
            } catch (Exception $e) {
                // Log email error but continue with other emails
                error_log("Email error for {$student['email']}: " . $e->getMessage());
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Deadline set successfully',
        'emails_sent' => $emails_sent
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
<?php
session_start();
require_once "../../backend/config.php";
include "../../backend/audit_logs.php";

require_once "../../backend/config.php";
require_once "../../vendor/autoload.php"; // If using PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed'
    ]);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $acc_id = intval($input['acc_id'] ?? 0);
    $status = intval($input['status'] ?? -1);
    
    // Validate input
    if ($acc_id <= 0) {
        throw new Exception('Invalid account ID');
    }
    
    if (!in_array($status, [0, 1, 2])) {
        throw new Exception('Invalid status value. Must be 0 (declined), 1 (pending), or 2 (accepted)');
    }
    
    // Check if the account exists and is a student
    $check_query = "SELECT ta.acc_id, ta.email, ta.reg_acc_status, CONCAT(td.first_name, ' ', td.last_name) as full_name FROM tbl_account ta 
    LEFT JOIN tbl_personal_details td ON ta.acc_id = td.acc_id
     WHERE ta.acc_id = ? AND ta.role = 'Student'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('i', $acc_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception('Student account not found');
    }
    
    $current_data = $check_result->fetch_assoc();
    $current_status = $current_data['reg_acc_status'];
    
    // Update the registration status
    $update_query = "UPDATE tbl_account SET reg_acc_status = ? WHERE acc_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('ii', $status, $acc_id);
    
    if ($update_stmt->execute()) {
        // Log the action
        $admin_id = $_SESSION["user_id"] ?? null;
        $action = "Updated registration status for student (Acc ID: $acc_id) from $current_status to $status";
        logAction($conn, $admin_id, $action);

        $status_text = [
            0 => 'declined',
            1 => 'pending',
            2 => 'accepted'
        ];

        // Email body based on status
        if ($status == 2) {
            // Accepted
            $email_body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #28a745; border-bottom: 2px solid #28a745; padding-bottom: 10px;'>
                        Registration Accepted - Welcome to Foothills Christian School!
                    </h2>

                    <p>Dear <strong>{$current_data['full_name']}</strong>,</p>

                    <p>Congratulations! We are pleased to inform you that your registration has been <strong style='color: #28a745;'>ACCEPTED</strong>.</p>
                    
                    <div style='background-color: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0;'>
                        <h4 style='margin-top: 0; color: #155724;'>What's Next?</h4>
                        <ul style='color: #155724;'>
                            <li>Complete your enrollment by submitting all required documents</li>
                            <li>Pay the enrollment fees (if applicable)</li>
                            <li>Attend the orientation session (date will be announced)</li>
                            <li>Access your student portal for class schedules and updates</li>
                        </ul>
                    </div>

                    <p><strong>Note:</strong> Please complete the enrollment process within 7 days to secure your slot.</p>
                    
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 14px; color: #6c757d;'>
                        <p>This is an automated message. Please do not reply directly to this email.</p>
                        <p>If you have any questions or need assistance, please don't hesitate to contact us.</p>
                        <br>
                        <p>Best regards,<br>
                        <strong>Foothills Christian School Administration</strong><br>
                        Email: admin@foothills.edu<br>
                        Phone: (123) 456-7890</p>
                    </div>
                </div>
            </body>
            </html>
            ";
        } elseif ($status == 1) {
            // Pending
            $email_body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #ffc107; border-bottom: 2px solid #ffc107; padding-bottom: 10px;'>
                        Registration Pending - Action Required
                    </h2>

                    <p>Dear <strong>{$current_data['full_name']}</strong>,</p>

                    <p>Thank you for your interest in Foothills Christian School. Your registration is currently <strong style='color: #856404;'>PENDING</strong> review.</p>
                    
                    <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                        <h4 style='margin-top: 0; color: #856404;'>Action Required:</h4>
                        <p style='color: #856404; margin-bottom: 0;'>
                            Please ensure all required documents are submitted. Our admissions team is reviewing your application and may contact you for additional information.
                        </p>
                    </div>

                    <p><strong>Note:</strong> You will receive another notification once your registration has been reviewed. This typically takes 3-5 business days.</p>
                    
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 14px; color: #6c757d;'>
                        <p>This is an automated message. Please do not reply directly to this email.</p>
                        <p>If you have any questions or need assistance, please don't hesitate to contact us.</p>
                        <br>
                        <p>Best regards,<br>
                        <strong>Foothills Christian School Administration</strong><br>
                        Email: admin@Foothills.edu<br>
                        Phone: (123) 456-7890</p>
                    </div>
                </div>
            </body>
            </html>
            ";
        } else {
            // Declined (status == 0)
            $email_body = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 10px;'>
                        Registration Status Update
                    </h2>

                    <p>Dear <strong>{$current_data['full_name']}</strong>,</p>

                    <p>Thank you for your interest in Foothills Christian School. After careful review, we regret to inform you that your registration has been <strong style='color: #721c24;'>DECLINED</strong>.</p>
                    
                    <div style='background-color: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0;'>
                        <h4 style='margin-top: 0; color: #721c24;'>Reasons may include:</h4>
                        <ul style='color: #721c24;'>
                            <li>Incomplete or missing required documents</li>
                            <li>Enrollment capacity has been reached</li>
                            <li>Application does not meet admission requirements</li>
                        </ul>
                    </div>

                    <p><strong>Note:</strong> You may reapply in the next enrollment period or contact our admissions office for more information about your application status.</p>
                    
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 14px; color: #6c757d;'>
                        <p>This is an automated message. Please do not reply directly to this email.</p>
                        <p>If you have any questions or need clarification, please don't hesitate to contact us.</p>
                        <br>
                        <p>Best regards,<br>
                        <strong>Foothills Christian School Administration</strong><br>
                        Email: admin@Foothills.edu<br>
                        Phone: (123) 456-7890</p>
                    </div>
                </div>
            </body>
            </html>
            ";
        }

        // Configure PHPMailer
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'donotreply.fh.qc@gmail.com';
            $mail->Password   = 'degu hdcj rids bfrx';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('donotreply.fh.qc@gmail.com', 'Foothills Christian School');
            $mail->addAddress($current_data['email']);
            
            $mail->Subject = $status == 2 
                ? 'Registration Accepted - Next Steps' 
                : ($status == 1 
                    ? 'Registration Pending - Action Required' 
                    : 'Registration Declined - Important Notice');
            
            $mail->isHTML(true);
            $mail->Body = $email_body;
            
            $mail->send();
            
            // SINGLE response here - after both database update and email send
            echo json_encode([
                'success' => true,
                'message' => "Registration status updated to {$status_text[$status]} and notification email sent successfully",
                'data' => [
                    'acc_id' => $acc_id,
                    'old_status' => $current_status,
                    'new_status' => $status
                ]
            ]);
            
        } catch (Exception $e) {
            // Email failed but database was updated
            echo json_encode([
                'success' => true,
                'message' => "Registration status updated to {$status_text[$status]}, but email notification failed: " . $mail->ErrorInfo,
                'data' => [
                    'acc_id' => $acc_id,
                    'old_status' => $current_status,
                    'new_status' => $status
                ]
            ]);
        }
    } else {
        throw new Exception('Failed to update registration status');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
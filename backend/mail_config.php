<?php
// backend/mail_config.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';

function sendVerificationEmail($email, $firstName, $verificationCode) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Change to your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'donotreply.fh.qc@gmail.com'; // Your email
        $mail->Password   = 'degu hdcj rids bfrx';    // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Enable debugging (optional - comment out in production)
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

        // Recipients
        $mail->setFrom('donotreply.fh.qc@gmail.com', 'Fonthills Christian School');
        $mail->addAddress($email, $firstName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification - Fonthills Christian School';
        $mail->Body    = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Email Verification</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0; 
                    padding: 0; 
                    background-color: #f5f5f5; 
                }
                .container { 
                    max-width: 600px; 
                    margin: 0 auto; 
                    background-color: white;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header { 
                    background-color: #0c7c51; 
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header h2 {
                    margin: 0;
                    font-size: 24px;
                }
                .header p {
                    margin: 5px 0 0 0;
                    font-size: 16px;
                    opacity: 0.9;
                }
                .content { 
                    padding: 30px 20px; 
                }
                .greeting {
                    font-size: 18px;
                    color: #333;
                    margin-bottom: 20px;
                }
                .message {
                    font-size: 16px;
                    color: #666;
                    line-height: 1.6;
                    margin-bottom: 30px;
                }
                .verification-code { 
                    font-size: 32px; 
                    font-weight: bold; 
                    color: #0c7c51; 
                    text-align: center; 
                    padding: 25px; 
                    background-color: #f8f9fa; 
                    margin: 25px 0; 
                    border-radius: 8px; 
                    border: 2px dashed #0c7c51;
                    letter-spacing: 5px;
                }
                .expiry-info {
                    background-color: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 5px;
                    padding: 15px;
                    margin: 20px 0;
                    color: #856404;
                    text-align: center;
                }
                .footer {
                    background-color: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    color: #666;
                    font-size: 14px;
                    border-top: 1px solid #eee;
                }
                .security-note {
                    font-size: 14px;
                    color: #888;
                    font-style: italic;
                    margin-top: 20px;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Fonthills Christian School Q.C.</h2>
                    <p>Email Verification Required</p>
                </div>
                <div class='content'>
                    <div class='greeting'>Dear " . htmlspecialchars($firstName) . ",</div>
                    
                    <div class='message'>
                        Thank you for registering at Fonthills Christian School. To complete your enrollment application, please verify your email address using the verification code below:
                    </div>
                    
                    <div class='verification-code'>{$verificationCode}</div>
                    
                    <div class='expiry-info'>
                        <strong>⏰ This code will expire in 15 minutes</strong>
                    </div>
                    
                    <div class='message'>
                        Please enter this code on the verification page to continue with your enrollment process.
                    </div>
                    
                    <div class='security-note'>
                        If you didn't request this verification, please ignore this email or contact our administration if you have concerns.
                    </div>
                </div>
                <div class='footer'>
                    <p><strong>Fonthills Christian School Q.C.</strong></p>
                    <p>Enrollment Department</p>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email send failed. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
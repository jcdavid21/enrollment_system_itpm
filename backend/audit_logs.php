<?php
    
    function logAction($conn, $userId, $activity) {
        date_default_timezone_set('Asia/Manila');
        $timestamp = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO tbl_audit_log (acc_id, activity, log_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $userId, $activity, $timestamp);
        $stmt->execute();
        $stmt->close();
    }

?>
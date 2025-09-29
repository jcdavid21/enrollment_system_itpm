<?php
// File: accounting/get_chart_data.php
session_start();
require_once "../../backend/config.php";

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $type = $_GET['type'] ?? 'monthly';
    $year = $_GET['year'] ?? date('Y');
    
    $labels = [];
    $data = [];
    
    if ($type === 'monthly') {
        // Monthly data for the selected year
        $months = [
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
        ];
        
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = $months[$i - 1];
            
            $sql = "SELECT IFNULL(SUM(amount), 0) as total FROM tbl_payments 
                    WHERE YEAR(payment_date) = ? AND MONTH(payment_date) = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $year, $i);
            $stmt->execute();
            $result = $stmt->get_result();
            $data[] = floatval($result->fetch_assoc()['total']);
        }
    } else {
        // Yearly data - get all years that have payments
        $sql = "SELECT YEAR(payment_date) as year, SUM(amount) as total 
                FROM tbl_payments 
                GROUP BY YEAR(payment_date) 
                ORDER BY year";
        $result = $conn->query($sql);
        
        if ($result->num_rows === 0) {
            // If no payments exist, show current year with 0
            $labels[] = date('Y');
            $data[] = 0;
        } else {
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['year'];
                $data[] = floatval($row['total']);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'data' => $data,
        'type' => $type,
        'year' => $year
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
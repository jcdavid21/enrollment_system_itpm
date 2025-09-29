<!DOCTYPE html>
<html lang="en">
<?php
session_start();
require_once "../backend/config.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Student") {
    header("Location: ./logout.php");
    exit();
}

// Get current school year dynamically
$current_date = new DateTime();
$current_month = (int)$current_date->format('n'); // 1-12
$current_year = (int)$current_date->format('Y');

// School year typically starts in June, so:
// June-December = current year to next year (e.g., 2025-2026)
// January-May = previous year to current year (e.g., 2024-2025)
if ($current_month >= 6) {
    $school_year = $current_year . '-' . ($current_year + 1);
} else {
    $school_year = ($current_year - 1) . '-' . $current_year;
}

// Get student data
$user_id = $_SESSION["user_id"];

// Get personal details and account info
$student_query = "SELECT 
            pd.*, 
            acc.username, 
            acc.email, 
            acc.enrollment_status,
            acc.date_registered,
            acc.date_enrolled
        FROM tbl_personal_details pd 
        LEFT JOIN tbl_account acc ON pd.acc_id = acc.acc_id 
        WHERE pd.acc_id = ?";

$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student_data = $student_result->fetch_assoc();

// Initialize variables
$new_old_student_data = null;
$transferee_data = null;
$is_transferee = false;
$is_new_old = false;
$has_enrollment_data = false;
$current_level = null;
$enrollments_data = [];
$payment_history_by_level = [];

// Check if student has data in new/old or transferee tables
if ($student_data && isset($student_data['personal_id'])) {
    // Check new/old students table
    $new_old_query = "SELECT nos.*, f.level, f.registration_fee, f.miscellaneous_fee, f.books_fee, f.tuition_fee, f.monthly_fee
                      FROM tbl_new_old_students nos 
                      LEFT JOIN tbl_fees f ON nos.level_id = f.fee_id 
                      WHERE nos.personal_id = ?";
    $stmt = $conn->prepare($new_old_query);
    $stmt->bind_param("i", $student_data['personal_id']);
    $stmt->execute();
    $new_old_result = $stmt->get_result();
    $new_old_student_data = $new_old_result->fetch_assoc();
    
    if ($new_old_student_data) {
        $is_new_old = true;
        $has_enrollment_data = true;
        $current_level = $new_old_student_data['level'];
    }
    
    // Check transferee table
    $transferee_query = "SELECT st.*, f.level, f.registration_fee, f.miscellaneous_fee, f.books_fee, f.tuition_fee, f.monthly_fee
                         FROM tbl_student_transferee st 
                         LEFT JOIN tbl_fees f ON st.level_id = f.fee_id 
                         WHERE st.personal_id = ?";
    $stmt = $conn->prepare($transferee_query);
    $stmt->bind_param("i", $student_data['personal_id']);
    $stmt->execute();
    $transferee_result = $stmt->get_result();
    $transferee_data = $transferee_result->fetch_assoc();
    
    if ($transferee_data) {
        $is_transferee = true;
        $has_enrollment_data = true;
        $current_level = $transferee_data['level'];
    }
}

// Get enrollment data and payment history
if ($has_enrollment_data && $student_data['enrollment_status'] == 'Enrolled') {
    // Get enrollments
    $enrollment_query = "SELECT e.*, f.level, 
                           (f.registration_fee + f.miscellaneous_fee + f.books_fee + f.tuition_fee) as total_expected_fee
                        FROM tbl_enrollments e
                        LEFT JOIN tbl_fees f ON e.current_level_id = f.fee_id
                        WHERE e.student_id = ?
                        ORDER BY e.school_year DESC";
    
    $stmt = $conn->prepare($enrollment_query);
    $stmt->bind_param("i", $student_data['personal_id']);
    $stmt->execute();
    $enrollment_result = $stmt->get_result();
    
    while ($enrollment = $enrollment_result->fetch_assoc()) {
        $enrollments_data[] = $enrollment;
        
        // Get payments for this enrollment
        $payment_query = "SELECT p.*, pd.fee_type, pd.amount as detail_amount
                         FROM tbl_payments p
                         LEFT JOIN tbl_payment_details pd ON p.payment_id = pd.payment_id
                         WHERE p.enrollment_id = ?
                         ORDER BY p.payment_date DESC";
        
        $stmt = $conn->prepare($payment_query);
        $stmt->bind_param("i", $enrollment['enrollment_id']);
        $stmt->execute();
        $payment_result = $stmt->get_result();
        
        $payments = [];
        $total_paid = 0;
        while ($payment = $payment_result->fetch_assoc()) {
            $payments[] = $payment;
            $total_paid += $payment['detail_amount'] ?? $payment['amount'];
        }
        
        $payment_history_by_level[$enrollment['level']] = [
            'enrollment' => $enrollment,
            'payments' => $payments,
            'total_paid' => $total_paid,
            'balance' => $enrollment['total_expected_fee'] - $total_paid
        ];
    }
}

// Calculate account statistics
$total_payments = 0;
$total_balance = 0;
foreach ($payment_history_by_level as $level_data) {
    $total_payments += $level_data['total_paid'];
    $total_balance += max(0, $level_data['balance']);
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../styles/general.css">
    <link rel="stylesheet" href="../styles/sidebar.css">
    <title>Fonthills Christian School - My Account</title>
    <style>
        body{
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            padding: 2rem;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(2, 84, 27, 0.3);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .welcome-text {
            position: relative;
            z-index: 2;
        }

        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .status-card {
            background: white;
            padding: 1.8rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
        }

        .status-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .profile-card .card-icon {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .payments-card .card-icon {
            background: linear-gradient(135deg, var(--success-color), #229954);
            color: white;
        }

        .balance-card .card-icon {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
            color: white;
        }

        .status-card .card-icon {
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            color: white;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
        }

        .status-enrolled {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
        }

        .status-pending {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }

        .status-not-enrolled {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .status-newly-registered {
            background: rgba(52, 152, 219, 0.1);
            color: var(--accent-color);
        }

        .profile-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .info-item {
            padding: 1rem;
            background: rgba(248, 249, 250, 1);
            border-radius: 10px;
        }

        .info-label {
            font-size: 0.85rem;
            color: #229954;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .payment-table-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .payment-level-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .payment-level-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .payment-level-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .table-responsive {
            border-radius: 0 0 12px 12px;
        }

        .table th {
            background: rgba(248, 249, 250, 1);
            border: none;
            font-weight: 600;
            color: var(--muted-color);
            font-size: 0.85rem;
        }

        .table td {
            border: none;
            border-bottom: 1px solid #f8f9fa;
            vertical-align: middle;
        }

        .alert-note {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: none;
            border: 1px solid var(--warning-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .alert-important {
            background: linear-gradient(135deg, #d1ecf1, #bee5eb);
            border: none;
            border: 1px solid var(--accent-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: none;
            border: 1px solid var(--success-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .action-button {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 84, 27, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-view-details {
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: white;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-view-details:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
            color: white;
        }

        .payment-status {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
        }

        .status-paid {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
        }

        .status-partial {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
        }

        .status-unpaid {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .balance-summary {
            background: rgba(248, 249, 250, 1);
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .status-cards {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include_once "./sidebar.php"; ?>

    <div class="main-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="welcome-text">
                <h1 class="mb-2">My Account</h1>
                <p class="mb-0 opacity-75">
                    Welcome back, <?php echo htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']); ?>! 
                    Manage your profile and view your payment history for Academic Year <?php echo $school_year; ?>
                </p>
            </div>
        </div>

        <!-- Status Messages based on Enrollment Status -->
        <?php if ($student_data['enrollment_status'] == 'Newly Registered' && !$has_enrollment_data): ?>
            <div class="alert alert-important">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-3 text-info fs-4"></i>
                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-bold">Complete Your Enrollment Process</h6>
                        <p class="mb-2">To view payment information and complete your enrollment, you need to fill out your enrollment form first.</p>
                        <a href="enrollment_form.php" class="action-button">
                            <i class="fas fa-edit"></i>
                            Complete Enrollment Form
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif ($student_data['enrollment_status'] == 'Pending'): ?>
            <div class="alert alert-note">
                <div class="d-flex align-items-center">
                    <i class="fas fa-hourglass-half me-3 text-warning fs-4"></i>
                    <div>
                        <h6 class="mb-1 fw-bold">Enrollment Under Review</h6>
                        <p class="mb-0">Your enrollment application is currently being reviewed by the school administration. Payment information will be available once your enrollment is approved.</p>
                    </div>
                </div>
            </div>
        <?php elseif ($student_data['enrollment_status'] == 'Enrolled'): ?>
            <div class="alert alert-success">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3 text-success fs-4"></i>
                    <div>
                        <h6 class="mb-1 fw-bold">Successfully Enrolled</h6>
                        <p class="mb-0">Your enrollment has been approved! View your payment history and account details below.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Account Statistics -->
        <div class="status-cards">
            <!-- Profile Status -->
            <div class="status-card profile-card">
                <div class="card-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h5 class="fw-bold mb-2">Account Status</h5>
                <div class="mb-2">
                    <span class="status-badge <?php
                        switch($student_data['enrollment_status']) {
                            case 'Enrolled': echo 'status-enrolled'; break;
                            case 'Pending': echo 'status-pending'; break;
                            case 'Newly Registered': echo 'status-newly-registered'; break;
                            default: echo 'status-not-enrolled';
                        }
                    ?>">
                        <?php echo $student_data['enrollment_status']; ?>
                    </span>
                </div>
                <p class="text-muted mb-0">
                    <?php if ($current_level): ?>
                        Current Level: <?php echo $current_level; ?>
                    <?php else: ?>
                        Complete enrollment form to view level
                    <?php endif; ?>
                </p>
            </div>

            <!-- Total Payments -->
            <div class="status-card payments-card">
                <div class="card-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h5 class="fw-bold mb-2">Total Payments</h5>
                <h2 class="mb-1">₱<?php echo number_format($total_payments, 2); ?></h2>
                <p class="text-muted mb-0">
                    <?php echo count($payment_history_by_level); ?> payment record<?php echo count($payment_history_by_level) != 1 ? 's' : ''; ?>
                </p>
            </div>

            <!-- Outstanding Balance -->
            <div class="status-card balance-card">
                <div class="card-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <h5 class="fw-bold mb-2">Outstanding Balance</h5>
                <h2 class="mb-1 <?php echo $total_balance > 0 ? 'text-warning' : 'text-success'; ?>">
                    ₱<?php echo number_format($total_balance, 2); ?>
                </h2>
                <p class="text-muted mb-0">
                    <?php echo $total_balance > 0 ? 'Payment pending' : 'All payments up to date'; ?>
                </p>
            </div>

            <!-- Student Type -->
            <div class="status-card">
                <div class="card-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h5 class="fw-bold mb-2">Student Type</h5>
                <h3 class="mb-1">
                    <?php if ($is_new_old): ?>
                        <span class="text-primary">New/Continuing</span>
                    <?php elseif ($is_transferee): ?>
                        <span class="text-warning">Transferee</span>
                    <?php else: ?>
                        <span class="text-muted">Not Specified</span>
                    <?php endif; ?>
                </h3>
                <p class="text-muted mb-0">
                    <?php if ($is_transferee && $transferee_data): ?>
                        From: <?php echo htmlspecialchars($transferee_data['prev_school']); ?>
                    <?php else: ?>
                        Student classification
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Personal Information Section -->
        <div class="profile-section">
            <h4 class="fw-bold mb-4">
                <i class="fas fa-id-card me-2 text-primary"></i>
                Personal Information
            </h4>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($student_data['first_name'] . ' ' . 
                                  ($student_data['middle_name'] ? $student_data['middle_name'] . ' ' : '') . 
                                  $student_data['last_name']); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Username</div>
                    <div class="info-value"><?php echo htmlspecialchars($student_data['username']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($student_data['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value"><?php echo date('F j, Y', strtotime($student_data['date_of_birth'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Gender</div>
                    <div class="info-value"><?php echo htmlspecialchars($student_data['gender']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($student_data['address']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Date Registered</div>
                    <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($student_data['date_registered'])); ?></div>
                </div>
                <?php if ($student_data['date_enrolled']): ?>
                <div class="info-item">
                    <div class="info-label">Date Enrolled</div>
                    <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($student_data['date_enrolled'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Emergency Contact Information (if enrolled) -->
        <?php if ($student_data['enrollment_status'] == 'Enrolled' && ($is_new_old || $is_transferee)): ?>
            <div class="profile-section">
                <h4 class="fw-bold mb-4">
                    <i class="fas fa-school me-2 text-info"></i>
                    Enrollment Information
                </h4>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Student Type</div>
                        <div class="info-value">
                            <?php if ($is_new_old): ?>
                                New/Continuing Student
                            <?php elseif ($is_transferee): ?>
                                Transferee Student
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($is_transferee && $transferee_data): ?>
                    <div class="info-item">
                        <div class="info-label">Previous School</div>
                        <div class="info-value"><?php echo htmlspecialchars($transferee_data['prev_school']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Previous School Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($transferee_data['prev_address_school']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <div class="info-label">Current Level</div>
                        <div class="info-value"><?php echo htmlspecialchars($current_level); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">School Year</div>
                        <div class="info-value"><?php echo $school_year; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Total Enrollments</div>
                        <div class="info-value"><?php echo count($enrollments_data); ?> enrollment<?php echo count($enrollments_data) != 1 ? 's' : ''; ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Payment History Section -->
        <?php if ($student_data['enrollment_status'] == 'Enrolled' && !empty($payment_history_by_level)): ?>
            <div class="payment-table-section">
                <h4 class="fw-bold mb-4">
                    <i class="fas fa-history me-2 text-success"></i>
                    Payment History
                </h4>
                
                <?php foreach ($payment_history_by_level as $level => $level_data): ?>
                    <div class="payment-level-card">
                        <div class="payment-level-header">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-0">
                                        <i class="fas fa-book me-2"></i>
                                        <?php echo htmlspecialchars($level); ?> - <?php echo $level_data['enrollment']['school_year']; ?>
                                    </h6>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <small class="opacity-75">
                                        Balance: ₱<?php echo number_format($level_data['balance'], 2); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Fee Type</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($level_data['payments'])): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-receipt me-2"></i>
                                                No payments recorded for this level yet
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($level_data['payments'] as $payment): ?>
                                            <tr>
                                                <td>
                                                    <small>
                                                        <?php echo date('M j, Y', strtotime($payment['payment_date'])); ?>
                                                        <br>
                                                        <span class="text-muted"><?php echo date('g:i A', strtotime($payment['payment_date'])); ?></span>
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong class="text-success">
                                                        ₱<?php echo number_format($payment['detail_amount'] ?? $payment['amount'], 2); ?>
                                                    </strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo htmlspecialchars($payment['method']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($payment['fee_type']): ?>
                                                        <span class="badge bg-primary">
                                                            <?php echo htmlspecialchars($payment['fee_type']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">General</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="payment-status status-paid">Paid</span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-view-details" onclick="viewPaymentDetails(<?php echo $payment['payment_id']; ?>)">
                                                        <i class="fas fa-eye me-1"></i>Details
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="balance-summary">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Expected Total: </strong>
                                    <span class="text-primary">₱<?php echo number_format($level_data['enrollment']['total_expected_fee'], 2); ?></span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Total Paid: </strong>
                                    <span class="text-success">₱<?php echo number_format($level_data['total_paid'], 2); ?></span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Remaining Balance: </strong>
                                    <span class="<?php echo $level_data['balance'] > 0 ? 'text-danger' : 'text-success'; ?>">
                                        ₱<?php echo number_format($level_data['balance'], 2); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($student_data['enrollment_status'] == 'Enrolled' && empty($payment_history_by_level)): ?>
            <div class="payment-table-section">
                <h4 class="fw-bold mb-4">
                    <i class="fas fa-history me-2 text-success"></i>
                    Payment History
                </h4>
                <div class="alert alert-note">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-3 text-info fs-4"></i>
                        <div>
                            <h6 class="mb-1 fw-bold">No Payment Records Found</h6>
                            <p class="mb-0">Your enrollment is approved but no payment records are available yet. Please contact the school administration for assistance.</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        
    </div>

    <!-- Payment Details Modal -->
    <div class="modal fade" id="paymentDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-receipt me-2"></i>
                        Payment Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="paymentDetailsContent">
                    <!-- Content will be loaded via AJAX -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function viewPaymentDetails(paymentId) {
        const modal = new bootstrap.Modal(document.getElementById('paymentDetailsModal'));
        const content = document.getElementById('paymentDetailsContent');
        
        // Show loading spinner
        content.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        
        modal.show();
        
        // Fetch payment details via AJAX
        fetch(`../backend/get_payment_details.php?payment_id=${paymentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    content.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <div class="info-label">Payment ID</div>
                                    <div class="info-value">#${data.payment.payment_id}</div>
                                </div>
                                <div class="info-item mb-3">
                                    <div class="info-label">Amount</div>
                                    <div class="info-value text-success">₱${parseFloat(data.payment.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                                </div>
                                <div class="info-item mb-3">
                                    <div class="info-label">Payment Method</div>
                                    <div class="info-value">${data.payment.method}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-item mb-3">
                                    <div class="info-label">Date & Time</div>
                                    <div class="info-value">${new Date(data.payment.payment_date).toLocaleString()}</div>
                                </div>
                                <div class="info-item mb-3">
                                    <div class="info-label">School Year</div>
                                    <div class="info-value">${data.enrollment.school_year}</div>
                                </div>
                                <div class="info-item mb-3">
                                    <div class="info-label">Level</div>
                                    <div class="info-value">${data.enrollment.level}</div>
                                </div>
                            </div>
                        </div>
                        
                        ${data.payment.remarks ? `
                            <div class="mt-3">
                                <div class="info-item">
                                    <div class="info-label">Remarks</div>
                                    <div class="info-value">${data.payment.remarks}</div>
                                </div>
                            </div>
                        ` : ''}
                        
                        ${data.payment_details && data.payment_details.length > 0 ? `
                            <div class="mt-4">
                                <h6 class="fw-bold mb-3">Payment Breakdown</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Fee Type</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.payment_details.map(detail => `
                                                <tr>
                                                    <td>${detail.fee_type}</td>
                                                    <td class="text-end">₱${parseFloat(detail.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        ` : ''}
                    `;
                } else {
                    content.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.message || 'Failed to load payment details'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading payment details. Please try again.
                    </div>
                `;
            });
    }
    </script>
</body>
</html>
                                                
<!DOCTYPE html>
<html lang="en">
<?php
session_start();
require_once "../backend/config.php";
if (!isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
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
$fee_data = null;
$parent_data = null;
$requirements_data = [];
$enrollment_data = null;
$student_type = 'new'; // 'new', 'returning', or 'transferee'
$current_grade = null;

// Check if student has data in new/old or transferee tables
if ($student_data && isset($student_data['personal_id'])) {
    $personal_id = $student_data['personal_id'];
    
    // Check new/old students table
    $new_old_query = "SELECT nos.*, f.level, f.registration_fee, f.miscellaneous_fee, f.books_fee, f.tuition_fee, f.monthly_fee
                      FROM tbl_new_old_students nos 
                      LEFT JOIN tbl_fees f ON nos.level_id = f.fee_id 
                      WHERE nos.personal_id = ?";
    $stmt = $conn->prepare($new_old_query);
    $stmt->bind_param("i", $personal_id);
    $stmt->execute();
    $new_old_result = $stmt->get_result();
    $new_old_student_data = $new_old_result->fetch_assoc();
    
    if ($new_old_student_data) {
        $is_new_old = true;
        $has_enrollment_data = true;
        $current_level = $new_old_student_data['level'];
        $fee_data = $new_old_student_data;
        $student_type = 'returning';
        $current_grade = $current_level;
    }
    
    // Check transferee table
    $transferee_query = "SELECT st.*, f.level, f.registration_fee, f.miscellaneous_fee, f.books_fee, f.tuition_fee, f.monthly_fee
                         FROM tbl_student_transferee st 
                         LEFT JOIN tbl_fees f ON st.level_id = f.fee_id 
                         WHERE st.personal_id = ?";
    $stmt = $conn->prepare($transferee_query);
    $stmt->bind_param("i", $personal_id);
    $stmt->execute();
    $transferee_result = $stmt->get_result();
    $transferee_data = $transferee_result->fetch_assoc();
    
    if ($transferee_data) {
        $is_transferee = true;
        $has_enrollment_data = true;
        $current_level = $transferee_data['level'];
        $fee_data = $transferee_data;
        $student_type = 'transferee';
        $current_grade = $current_level;
    }
    
    // If no data in new/old or transferee tables, check enrollment table
    if (!$has_enrollment_data && $student_data['enrollment_status'] != 'Newly Registered') {
        // Check if there's an enrollment record to get the grade level
        $enrollment_check = "SELECT e.*, f.* FROM tbl_enrollments e 
                            INNER JOIN tbl_fees f ON e.current_level_id = f.fee_id 
                            WHERE e.student_id = ? AND e.school_year = ?";
        $stmt = $conn->prepare($enrollment_check);
        $stmt->bind_param("is", $personal_id, $school_year);
        $stmt->execute();
        $enrollment_check_result = $stmt->get_result();
        $enrollment_check_data = $enrollment_check_result->fetch_assoc();

        if ($enrollment_check_data) {
            $fee_data = $enrollment_check_data;
            $current_grade = $fee_data['level'];
            $current_level = $current_grade;
        } else {
            // Default to Grade 1 if no data found
            $default_grade_query = "SELECT * FROM tbl_fees WHERE level = 'Grade 1'";
            $stmt = $conn->prepare($default_grade_query);
            $stmt->execute();
            $default_result = $stmt->get_result();
            $fee_data = $default_result->fetch_assoc();
            $current_grade = 'Grade 1';
            $current_level = $current_grade;
        }
    }

    // Get parent details
    $parent_query = "SELECT * FROM tbl_parents_details WHERE child_id = ?";
    $stmt = $conn->prepare($parent_query);
    $stmt->bind_param("i", $personal_id);
    $stmt->execute();
    $parent_result = $stmt->get_result();
    $parent_data = $parent_result->fetch_assoc();

    // Get enrollment info
    $enrollment_query = "SELECT 
            e.*,
            COALESCE(SUM(p.amount), 0) as total_paid
        FROM tbl_enrollments e 
        LEFT JOIN tbl_payments p ON e.enrollment_id = p.enrollment_id
        WHERE e.student_id = ? AND e.school_year = ?
        GROUP BY e.enrollment_id
        ORDER BY e.enrollment_date DESC, e.enrollment_id DESC
        LIMIT 1";

    $stmt = $conn->prepare($enrollment_query);
    $stmt->bind_param("is", $personal_id, $school_year);
    $stmt->execute();
    $enrollment_result = $stmt->get_result();
    $enrollment_data = $enrollment_result->fetch_assoc();
}

// Get requirements status (always show this)
$requirements_query = "SELECT 
            r.requirement_name,
            sr.requirement_status,
            sr.submitted_at
        FROM tbl_requirements r
        LEFT JOIN tbl_student_requirements sr ON r.requirement_id = sr.requirement_id AND sr.acc_id = ?
        ORDER BY r.requirement_id";

$stmt = $conn->prepare($requirements_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requirements_result = $stmt->get_result();

// Calculate requirements progress
$requirements_submitted = 0;
$total_requirements = 0;

while ($req = $requirements_result->fetch_assoc()) {
    $total_requirements++;
    if ($req['requirement_status'] && $req['requirement_status'] != 'Pending') {
        $requirements_submitted++;
    }
    $requirements_data[] = $req;
}

$progress_percentage = $total_requirements > 0 ? ($requirements_submitted / $total_requirements) * 100 : 0;

// Calculate total fees based on current level
$calculated_total_fees = 0;
if ($fee_data && $student_data['enrollment_status'] != 'Newly Registered') {
    // Always calculate from individual fee components based on current level
    // Don't use enrollment total_fee as it might be outdated or incorrect
    $calculated_total_fees = $fee_data['registration_fee'] + $fee_data['miscellaneous_fee'] +
        $fee_data['books_fee'] + $fee_data['tuition_fee'];
}

// Determine appropriate status messages and display logic
$display_enrollment_info = ($student_data['enrollment_status'] != 'Newly Registered');
$display_payment_info = ($student_data['enrollment_status'] != 'Newly Registered');
$show_next_steps = ($student_data['enrollment_status'] == 'Newly Registered');
$show_pending_notice = ($student_data['enrollment_status'] == 'Pending');
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz6YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../styles/general.css">
    <link rel="stylesheet" href="../styles/sidebar.css">
    <title>Fonthills Christian School - Dashboard</title>
    <style>
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
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

        .enrollment-card .card-icon {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .requirements-card .card-icon {
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            color: white;
        }

        .payment-card .card-icon {
            background: linear-gradient(135deg, var(--success-color), #229954);
            color: white;
        }

        .info-card .card-icon {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
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

        .requirements-grid {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            justify-content: between;
            padding: 1rem;
            background: rgba(248, 249, 250, 0.8);
            border-radius: 10px;
            border: 1px solid transparent;
        }

        .requirement-accepted {
            border-color: var(--success-color);
            background: rgba(39, 174, 96, 0.05);
        }

        .requirement-pending {
            border-color: var(--danger-color);
            background: rgba(231, 76, 60, 0.05);
        }

        .requirement-verifying {
            border-color: var(--warning-color);
            background: rgba(243, 156, 18, 0.05);
        }

        .requirement-status {
            margin-left: auto;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
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

        .progress-bar-custom {
            height: 8px;
            border-radius: 10px;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.1);
        }

        .progress-bar-custom .progress-bar {
            border-radius: 10px;
        }

        .student-info-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .info-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            padding: 1rem;
            background: rgba(245, 245, 245, 1);
            border-radius: 10px;
        }

        .info-label {
            font-size: 0.85rem;
            color: var(--muted-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .next-steps {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .step-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem;
            margin-bottom: 1rem;
            background: rgba(248, 249, 250, 0.8);
            border-radius: 10px;
            border: 1px solid var(--accent-color);
        }

        .step-number {
            background: var(--accent-color);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .status-cards {
                grid-template-columns: 1fr;
            }

            .info-row {
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
                <h1 class="mb-2">Welcome, <?php echo htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']); ?>!</h1>
                <p class="mb-0 opacity-75">
                    <?php if ($student_data['enrollment_status'] == 'Newly Registered'): ?>
                        Complete your enrollment process for Academic Year <?php echo $school_year; ?>
                    <?php elseif ($student_data['enrollment_status'] == 'Pending'): ?>
                        Your enrollment is being processed for Academic Year <?php echo $school_year; ?>
                    <?php else: ?>
                        Here's your enrollment overview for Academic Year <?php echo $school_year; ?>
                        <?php if ($student_type): ?>
                            (<?php echo ucfirst($student_type); ?> Student)
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if ($show_next_steps): ?>
            <!-- Important Notice for Newly Registered -->
            <div class="alert alert-important">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-3 text-info fs-4"></i>
                    <div>
                        <h6 class="mb-1 fw-bold">Welcome! Complete Your Enrollment</h6>
                        <p class="mb-0">As a newly registered student, you need to complete the enrollment form and submit your requirements. Please follow the steps below to complete your enrollment process.</p>
                    </div>
                </div>
            </div>

            <!-- Next Steps Section -->
            <div class="next-steps">
                <h4 class="fw-bold mb-4"><i class="fas fa-list-check me-2 text-info"></i>Next Steps</h4>
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div>
                        <h6 class="fw-bold mb-1">Complete Enrollment Form</h6>
                        <p class="mb-0 text-muted">Fill out the complete enrollment form with your academic and personal information.</p>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div>
                        <h6 class="fw-bold mb-1">Submit Requirements</h6>
                        <p class="mb-0 text-muted">Upload or submit all required documents as listed in the requirements section.</p>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div>
                        <h6 class="fw-bold mb-1">Visit School Office</h6>
                        <p class="mb-0 text-muted">Bring your PSA/Birth Certificate to the school office for physical verification.</p>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-number">4</div>
                    <div>
                        <h6 class="fw-bold mb-1">Payment Processing</h6>
                        <p class="mb-0 text-muted">Once enrolled, you can proceed with fee payments and installment plans.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($show_pending_notice): ?>
            <!-- Important Notice for Pending Status -->
            <div class="alert alert-note">
                <div class="d-flex align-items-center">
                    <i class="fas fa-hourglass-half me-3 text-warning fs-4"></i>
                    <div>
                        <h6 class="mb-1 fw-bold">Enrollment Under Review</h6>
                        <p class="mb-0">Your enrollment form has been submitted and is currently under review by the school administration. Please wait for approval. You will be notified once your enrollment status is updated.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Status Cards -->
        <div class="status-cards">
            <!-- Enrollment Status -->
            <div class="status-card enrollment-card">
                <div class="card-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h5 class="fw-bold mb-2">Enrollment Status</h5>
                <div class="mb-3">
                    <?php
                    $status_class = '';
                    switch ($student_data['enrollment_status']) {
                        case 'Enrolled':
                            $status_class = 'status-enrolled';
                            break;
                        case 'Not Enrolled':
                            $status_class = 'status-not-enrolled';
                            break;
                        case 'Newly Registered':
                            $status_class = 'status-newly-registered';
                            break;
                        default:
                            $status_class = 'status-pending';
                    }
                    ?>
                    <span class="status-badge <?php echo $status_class; ?>">
                        <?php echo htmlspecialchars($student_data['enrollment_status']); ?>
                    </span>
                </div>
                <p class="text-muted mb-1">
                    <?php if ($student_data['enrollment_status'] == 'Newly Registered'): ?>
                        <strong>Please complete enrollment form</strong>
                    <?php elseif ($student_data['enrollment_status'] == 'Pending'): ?>
                        <strong>Your enrollment is being processed</strong>
                    <?php else: ?>
                        Grade Level: <strong><?php echo htmlspecialchars($current_grade ?? 'TBD'); ?></strong>
                        <?php if ($calculated_total_fees > 0): ?>
                            <br>Total Fees: <strong>₱<?php echo number_format($calculated_total_fees, 2); ?></strong>
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
                <p class="text-muted mb-0">Registered: <?php echo date('M j, Y', strtotime($student_data['date_registered'])); ?></p>
            </div>

            <!-- Requirements Status -->
            <div class="status-card requirements-card">
                <div class="card-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h5 class="fw-bold mb-2">Requirements</h5>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Progress</small>
                        <small><?php echo $requirements_submitted; ?>/<?php echo $total_requirements; ?></small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $progress_percentage; ?>%"></div>
                    </div>
                </div>
                <p class="text-muted mb-0"><?php echo $requirements_submitted; ?> of <?php echo $total_requirements; ?> requirements submitted</p>
            </div>

            <!-- Payment Information -->
            <div class="status-card payment-card">
                <div class="card-icon">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h5 class="fw-bold mb-2">Payment Status</h5>
                <?php if (!$display_payment_info): ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Payment Progress</small>
                            <small>₱0.00/₱0.00</small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    <p class="text-muted mb-0"><strong>Complete enrollment first</strong></p>
                <?php elseif ($enrollment_data): ?>
                    <?php
                    $total_fee = $calculated_total_fees;
                    $total_paid = $enrollment_data['total_paid'];
                    $balance = $total_fee - $total_paid;
                    $payment_percentage = $total_fee > 0 ? ($total_paid / $total_fee) * 100 : 0;
                    ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Payment Progress</small>
                            <small>₱<?php echo number_format($total_paid, 2); ?>/₱<?php echo number_format($total_fee, 2); ?></small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $payment_percentage; ?>%"></div>
                        </div>
                    </div>
                    <p class="text-muted mb-0">Balance: <strong>₱<?php echo number_format($balance, 2); ?></strong></p>
                <?php else: ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Payment Progress</small>
                            <small>₱0.00/<?php echo $calculated_total_fees > 0 ? '₱' . number_format($calculated_total_fees, 2) : '₱0.00'; ?></small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    <p class="text-muted mb-0">No payments made yet</p>
                <?php endif; ?>
            </div>

            <!-- Student Information -->
            <div class="status-card info-card">
                <div class="card-icon">
                    <i class="fas fa-user"></i>
                </div>
                <h5 class="fw-bold mb-2">Student Info</h5>
                <?php if ($display_enrollment_info): ?>
                    <p class="mb-1"><strong>Student ID:</strong> <?php echo htmlspecialchars($student_data['personal_id']); ?></p>
                    <p class="mb-1"><strong>Type:</strong> <?php echo ucfirst($student_type); ?> Student</p>
                    <p class="mb-1"><strong>Grade:</strong> <?php echo htmlspecialchars($current_grade ?? 'TBD'); ?></p>
                <?php endif; ?>
                <p class="mb-0"><strong>Gender:</strong> <?php echo htmlspecialchars($student_data['gender']); ?></p>
            </div>
        </div>

        <!-- Student Information Section -->
        <?php if ($display_enrollment_info): ?>
            <div class="student-info-section">
                <h4 class="fw-bold mb-4"><i class="fas fa-user-circle me-2 text-primary"></i>Personal Information</h4>
                <div class="info-row">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['first_name'] . ' ' . $student_data['middle_name'] . ' ' . $student_data['last_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value"><?php echo date('F j, Y', strtotime($student_data['date_of_birth'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['address']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($student_data['email']); ?></div>
                    </div>
                </div>

                <?php if ($parent_data): ?>
                    <h5 class="fw-bold mb-3 mt-5"><i class="fas fa-users me-2 text-success"></i>Parent/Guardian Information</h5>
                    <div class="info-row">
                        <div class="info-item">
                            <div class="info-label">Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($parent_data['parent_full_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Contact Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($parent_data['contact_num']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Relationship</div>
                            <div class="info-value"><?php echo htmlspecialchars($parent_data['relationship']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Requirements Section -->
        <div class="student-info-section">
            <h4 class="fw-bold mb-4"><i class="fas fa-clipboard-list me-2 text-info"></i>Requirements Status</h4>
            <?php if ($student_data['enrollment_status'] == 'Newly Registered'): ?>
                <div class="alert alert-note mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-3 text-warning fs-5"></i>
                        <div>
                            <strong>Note:</strong> Submit these requirements to complete your enrollment process.
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="requirements-grid">
                <?php foreach ($requirements_data as $req): ?>
                    <div class="requirement-item <?php
                                                    if (!$req['requirement_status'] || $req['requirement_status'] == 'Pending') {
                                                        echo 'requirement-pending';
                                                    } elseif ($req['requirement_status'] == 'Accepted') {
                                                        echo 'requirement-accepted';
                                                    } elseif ($req['requirement_status'] == 'Verifying') {
                                                        echo 'requirement-verifying';
                                                    }
                                                    ?>">
                        <div class="d-flex align-items-center flex-grow-1">
                            <i class="fas <?php
                                            if (!$req['requirement_status'] || $req['requirement_status'] == 'Pending') {
                                                echo 'fa-times-circle text-danger';
                                            } elseif ($req['requirement_status'] == 'Accepted') {
                                                echo 'fa-check-circle text-success';
                                            } elseif ($req['requirement_status'] == 'Verifying') {
                                                echo 'fa-clock text-warning';
                                            }
                                            ?> me-3"></i>
                            <div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($req['requirement_name']); ?></div>
                                <?php if ($req['submitted_at']): ?>
                                    <small class="text-muted">Submitted: <?php echo date('M j, Y', strtotime($req['submitted_at'])); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="requirement-status <?php
                                                        $status = $req['requirement_status'] ?: 'Not Submitted';
                                                        switch ($status) {
                                                            case 'Accepted':
                                                                echo 'bg-success text-white';
                                                                break;
                                                            case 'Verifying':
                                                                echo 'bg-warning text-white';
                                                                break;
                                                            case 'Canceled':
                                                                echo 'bg-secondary text-white';
                                                                break;
                                                            default:
                                                                echo 'bg-danger text-white';
                                                                $status = 'Not Submitted';
                                                        }
                                                        ?>"><?php echo $status; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>

</html>
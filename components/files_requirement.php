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
            acc.date_registered
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

// Check if student has data in new/old or transferee tables
if ($student_data && isset($student_data['personal_id'])) {
    // Check new/old students table
    $new_old_query = "SELECT nos.*, f.level FROM tbl_new_old_students nos 
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
    }
    
    // Check transferee table
    $transferee_query = "SELECT st.*, f.level FROM tbl_student_transferee st 
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
    }
}

// Get all requirements
$requirements_query = "SELECT 
            r.requirement_id,
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
$requirements_data = [];
$requirements_submitted = 0;
$requirements_accepted = 0;
$requirements_verifying = 0;
$total_requirements = 0;

while ($req = $requirements_result->fetch_assoc()) {
    $total_requirements++;
    if ($req['requirement_status']) {
        $requirements_submitted++;
        if ($req['requirement_status'] == 'Accepted') {
            $requirements_accepted++;
        } elseif ($req['requirement_status'] == 'Verifying') {
            $requirements_verifying++;
        }
    }
    $requirements_data[] = $req;
}

$progress_percentage = $total_requirements > 0 ? ($requirements_submitted / $total_requirements) * 100 : 0;
$accepted_percentage = $total_requirements > 0 ? ($requirements_accepted / $total_requirements) * 100 : 0;
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
    <title>Foothills Christian School - Files & Requirements</title>
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

        .requirements-card .card-icon {
            background: linear-gradient(135deg, var(--accent-color), #2980b9);
            color: white;
        }

        .submitted-card .card-icon {
            background: linear-gradient(135deg, var(--warning-color), #e67e22);
            color: white;
        }

        .accepted-card .card-icon {
            background: linear-gradient(135deg, var(--success-color), #229954);
            color: white;
        }

        .verifying-card .card-icon {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
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

        .requirements-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            background: rgba(248, 249, 250, 0.8);
            border-radius: 12px;
            border: 1px solid transparent;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .requirement-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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

        .requirement-canceled {
            border-color: var(--muted-color);
            background: rgba(108, 117, 125, 0.05);
        }

        .requirement-status {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            min-width: 100px;
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

        .progress-bar-custom {
            height: 8px;
            border-radius: 10px;
            overflow: hidden;
            background: rgba(0, 0, 0, 0.1);
        }

        .progress-bar-custom .progress-bar {
            border-radius: 10px;
        }

        .enrollment-info-section {
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
                <h1 class="mb-2">Files & Requirements</h1>
                <p class="mb-0 opacity-75">
                    <?php if ($student_data['enrollment_status'] == 'Newly Registered'): ?>
                        Track your enrollment requirements for Academic Year <?php echo $school_year; ?>
                    <?php elseif ($student_data['enrollment_status'] == 'Pending'): ?>
                        Your requirements are being reviewed for Academic Year <?php echo $school_year; ?>
                    <?php else: ?>
                        Manage your academic requirements for Academic Year <?php echo $school_year; ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if ($student_data['enrollment_status'] == 'Newly Registered' && !$has_enrollment_data): ?>
            <!-- Important Notice for Newly Registered without enrollment form -->
            <div class="alert alert-important">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-3 text-info fs-4"></i>
                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-bold">Complete Your Enrollment Form First</h6>
                        <p class="mb-2">Before submitting requirements, you need to complete your enrollment form to specify whether you're a new student or transferee.</p>
                        <a href="enrollment_form.php" class="action-button">
                            <i class="fas fa-edit"></i>
                            Complete Enrollment Form
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif ($student_data['enrollment_status'] == 'Pending'): ?>
            <!-- Notice for Pending Status -->
            <div class="alert alert-note">
                <div class="d-flex align-items-center">
                    <i class="fas fa-hourglass-half me-3 text-warning fs-4"></i>
                    <div>
                        <h6 class="mb-1 fw-bold">Requirements Under Review</h6>
                        <p class="mb-0">Your enrollment form and requirements are currently being reviewed by the school administration. Please wait for approval notification.</p>
                    </div>
                </div>
            </div>
        <?php elseif ($student_data['enrollment_status'] == 'Enrolled'): ?>
            <!-- Success Notice for Enrolled Students -->
            <div class="alert alert-success">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-3 text-success fs-4"></i>
                    <div>
                        <h6 class="mb-1 fw-bold">Enrollment Complete</h6>
                        <p class="mb-0">Congratulations! Your enrollment has been approved. All requirements have been verified and accepted.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Requirements Statistics -->
        <div class="status-cards">
            <!-- Total Requirements -->
            <div class="status-card requirements-card">
                <div class="card-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h5 class="fw-bold mb-2">Total Requirements</h5>
                <h2 class="mb-1"><?php echo $total_requirements; ?></h2>
                <p class="text-muted mb-0">Documents needed for enrollment</p>
            </div>

            <!-- Submitted Requirements -->
            <div class="status-card submitted-card">
                <div class="card-icon">
                    <i class="fas fa-upload"></i>
                </div>
                <h5 class="fw-bold mb-2">Submitted</h5>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Progress</small>
                        <small><?php echo $requirements_submitted; ?>/<?php echo $total_requirements; ?></small>
                    </div>
                    <div class="progress progress-bar-custom">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $progress_percentage; ?>%"></div>
                    </div>
                </div>
                <p class="text-muted mb-0"><?php echo number_format($progress_percentage, 1); ?>% submitted</p>
            </div>

            <!-- Accepted Requirements -->
            <div class="status-card accepted-card">
                <div class="card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h5 class="fw-bold mb-2">Accepted</h5>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Approved</small>
                        <small><?php echo $requirements_accepted; ?>/<?php echo $total_requirements; ?></small>
                    </div>
                    <div class="progress progress-bar-custom">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $accepted_percentage; ?>%"></div>
                    </div>
                </div>
                <p class="text-muted mb-0"><?php echo number_format($accepted_percentage, 1); ?>% verified</p>
            </div>

            <!-- Verifying Requirements -->
            <div class="status-card verifying-card">
                <div class="card-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <h5 class="fw-bold mb-2">Under Review</h5>
                <h2 class="mb-1"><?php echo $requirements_verifying; ?></h2>
                <p class="text-muted mb-0">Documents being verified</p>
            </div>
        </div>

        <!-- Enrollment Type Information -->
        <?php if ($has_enrollment_data): ?>
            <div class="enrollment-info-section">
                <h4 class="fw-bold mb-4">
                    <i class="fas fa-user-graduate me-2 text-primary"></i>
                    Enrollment Information
                </h4>
                
                <?php if ($is_new_old): ?>
                    <div class="mb-3">
                        <span class="status-badge status-enrolled">
                            <i class="fas fa-graduation-cap me-1"></i>
                            New/Continuing Student
                        </span>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Student Type</div>
                            <div class="info-value">New/Continuing Student</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Grade Level</div>
                            <div class="info-value"><?php echo htmlspecialchars($new_old_student_data['level'] ?? 'TBD'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Student Image</div>
                            <div class="info-value">
                                <?php if ($new_old_student_data['student_image']): ?>
                                    <span class="text-success"><i class="fas fa-check me-1"></i>Uploaded</span>
                                <?php else: ?>
                                    <span class="text-danger"><i class="fas fa-times me-1"></i>Not Uploaded</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Parent's Valid ID</div>
                            <div class="info-value">
                                <?php if ($new_old_student_data['parents_valid_id']): ?>
                                    <span class="text-success"><i class="fas fa-check me-1"></i>Uploaded</span>
                                <?php else: ?>
                                    <span class="text-danger"><i class="fas fa-times me-1"></i>Not Uploaded</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($is_transferee): ?>
                    <div class="mb-3">
                        <span class="status-badge status-pending">
                            <i class="fas fa-exchange-alt me-1"></i>
                            Transferee Student
                        </span>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Student Type</div>
                            <div class="info-value">Transferee Student</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Grade Level</div>
                            <div class="info-value"><?php echo htmlspecialchars($transferee_data['level'] ?? 'TBD'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Previous School</div>
                            <div class="info-value"><?php echo htmlspecialchars($transferee_data['prev_school'] ?? 'Not Specified'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Previous School Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($transferee_data['prev_address_school'] ?? 'Not Specified'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Previous School ID</div>
                            <div class="info-value">
                                <?php if ($transferee_data['prev_id_school_file']): ?>
                                    <span class="text-success"><i class="fas fa-check me-1"></i>Uploaded</span>
                                <?php else: ?>
                                    <span class="text-danger"><i class="fas fa-times me-1"></i>Not Uploaded</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Previous School Report Card</div>
                            <div class="info-value">
                                <?php if ($transferee_data['prev_school_card']): ?>
                                    <span class="text-success"><i class="fas fa-check me-1"></i>Uploaded</span>
                                <?php else: ?>
                                    <span class="text-danger"><i class="fas fa-times me-1"></i>Not Uploaded</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Requirements List -->
        <div class="requirements-section">
            <h4 class="fw-bold mb-4">
                <i class="fas fa-file-alt me-2 text-info"></i>
                Required Documents
            </h4>
            
            <?php if ($student_data['enrollment_status'] == 'Newly Registered' && !$has_enrollment_data): ?>
                <div class="alert alert-note mb-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-3 text-warning fs-5"></i>
                        <div>
                            <strong>Note:</strong> Complete your enrollment form first to enable requirement submissions.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="requirements-grid">
                <?php foreach ($requirements_data as $req): ?>
                    <div class="requirement-item <?php
                                                    $status = $req['requirement_status'] ?? 'Not Submitted';
                                                    switch ($status) {
                                                        case 'Accepted':
                                                            echo 'requirement-accepted';
                                                            break;
                                                        case 'Verifying':
                                                            echo 'requirement-verifying';
                                                            break;
                                                        case 'Declined':
                                                            echo 'requirement-canceled';
                                                            break;
                                                        default:
                                                            echo 'requirement-pending';
                                                    }
                                                    ?>">
                        <div class="d-flex align-items-center flex-grow-1">
                            <i class="fas <?php
                                            switch ($status) {
                                                case 'Accepted':
                                                    echo 'fa-check-circle text-success';
                                                    break;
                                                case 'Verifying':
                                                    echo 'fa-clock text-warning';
                                                    break;
                                                case 'Declined':
                                                    echo 'fa-ban text-secondary';
                                                    break;
                                                default:
                                                    echo 'fa-times-circle text-danger';
                                            }
                                            ?> me-3 fs-5"></i>
                            <div>
                                <div class="fw-semibold mb-1"><?php echo htmlspecialchars($req['requirement_name']); ?></div>
                                <?php if ($req['submitted_at']): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt me-1"></i>
                                        Submitted: <?php echo date('M j, Y g:i A', strtotime($req['submitted_at'])); ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">
                                        <i class="fas fa-upload me-1"></i>
                                        Not yet submitted
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="requirement-status <?php
                                                        switch ($status) {
                                                            case 'Accepted':
                                                                echo 'bg-success text-white';
                                                                break;
                                                            case 'Verifying':
                                                                echo 'bg-warning text-white';
                                                                break;
                                                            case 'Declined':
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

        <!-- Additional Information Based on Enrollment Status -->
        <?php if ($student_data['enrollment_status'] == 'Newly Registered' && !$has_enrollment_data): ?>
            <!-- Next Steps for Newly Registered Users -->
            <div class="next-steps">
                <h4 class="fw-bold mb-4"><i class="fas fa-list-check me-2 text-info"></i>Required Information for New and Transferee Students</h4>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-user-plus me-2"></i>New/Continuing Students</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">If you are a new student or continuing from previous year:</p>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Student photograph (2x2 or passport size)</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Parent/Guardian valid ID copy</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>All standard requirements listed above</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card border-warning mb-3">
                            <div class="card-header bg-warning text-white">
                                <h6 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Transferee Students</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-3">If you are transferring from another school:</p>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Previous school name and address</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Previous school ID or any school document</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Previous school report card</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>All standard requirements listed above</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div>
                        <h6 class="fw-bold mb-1">Complete Enrollment Form</h6>
                        <p class="mb-2 text-muted">Fill out the complete enrollment form and specify whether you're a new student or transferee. This will enable the requirements submission system.</p>
                        <a href="enrollment_form.php" class="action-button">
                            <i class="fas fa-edit"></i>
                            Go to Enrollment Form
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif ($student_data['enrollment_status'] == 'Pending'): ?>
            <!-- Information for Pending Students -->
            <div class="requirements-section">
                <h4 class="fw-bold mb-4"><i class="fas fa-hourglass-half me-2 text-warning"></i>What Happens Next?</h4>
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center p-3">
                            <div class="card-icon mx-auto mb-3" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white;">
                                <i class="fas fa-search"></i>
                            </div>
                            <h6 class="fw-bold">Document Review</h6>
                            <p class="text-muted small">School administration is reviewing all your submitted requirements</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3">
                            <div class="card-icon mx-auto mb-3" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white;">
                                <i class="fas fa-phone"></i>
                            </div>
                            <h6 class="fw-bold">Verification Call</h6>
                            <p class="text-muted small">You may receive a call for additional verification if needed</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3">
                            <div class="card-icon mx-auto mb-3" style="background: linear-gradient(135deg, #27ae60, #229954); color: white;">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h6 class="fw-bold">Final Decision</h6>
                            <p class="text-muted small">You'll receive notification about your enrollment status</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($student_data['enrollment_status'] == 'Enrolled'): ?>
            <!-- Information for Enrolled Students -->
            <div class="requirements-section">
                <h4 class="fw-bold mb-4"><i class="fas fa-graduation-cap me-2 text-success"></i>Enrollment Complete</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">Enrollment Status</div>
                            <div class="info-value text-success">
                                <i class="fas fa-check-circle me-1"></i>
                                Successfully Enrolled
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">School Year</div>
                            <div class="info-value"><?php echo $school_year; ?></div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-success mt-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-3 fs-5"></i>
                        <div>
                            <strong>What's Next:</strong> You can now proceed to payment processing and class schedule confirmation. Visit the payments section to complete your enrollment fees.
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Requirements Help Section -->
        <div class="requirements-section">
            <h4 class="fw-bold mb-4"><i class="fas fa-question-circle me-2 text-info"></i>Need Help?</h4>
            <div class="row">
                <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="fas fa-file-alt me-2 text-primary"></i>Document Guidelines</h6>
                            <ul class="list-unstyled small">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>All documents must be clear and legible</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Original documents for verification at school</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Photocopies should be readable</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>PSA Birth Certificate is mandatory</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="fas fa-phone me-2 text-primary"></i>Contact Information</h6>
                            <p class="small mb-2">
                            <i class="fa-solid fa-tty me-1 text-success"></i>  
                            <strong class="text-muted">School Office:</strong> (02) 123-4567</p>
                            <p class="small mb-2"><strong class="text-muted">
                            <i class="fa-solid fa-envelope me-1 text-success"></i>    
                            Email:</strong> enrollment@foothills.edu.ph</p>
                            <p class="small mb-2">
                            <i class="fa-solid fa-clock me-1 text-success"></i>
                                <strong class="text-muted">Office Hours:</strong> Mon-Fri 8:00 AM - 5:00 PM</p>
                            <p class="small mb-0">
                            <i class="fa-solid fa-location-dot me-1 text-success"></i>
                                <strong class="text-muted">Address:</strong> 123 Education St., Quezon City</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Legend -->
        <div class="requirements-section">
            <h4 class="fw-bold mb-4"><i class="fas fa-info-circle me-2 text-info"></i>Status Legend</h4>
            <div class="row">
                <div class="col-md-3 col-6 mb-3">
                    <div class="d-flex align-items-center">
                        <span class="requirement-status bg-danger text-white me-2">Not Submitted</span>
                        <small class="text-muted">Document not yet uploaded</small>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="d-flex align-items-center">
                        <span class="requirement-status bg-warning text-white me-2">Verifying</span>
                        <small class="text-muted">Under school review</small>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="d-flex align-items-center">
                        <span class="requirement-status bg-success text-white me-2">Accepted</span>
                        <small class="text-muted">Approved and verified</small>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="d-flex align-items-center">
                        <span class="requirement-status bg-secondary text-white me-2">Declined</span>
                        <small class="text-muted">Rejected or replaced</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive feedback
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to requirement items
            const requirementItems = document.querySelectorAll('.requirement-item');
            requirementItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>

</html>
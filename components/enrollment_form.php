<!DOCTYPE html>
<html lang="en">
<?php
session_start();
require_once "../backend/config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Student") {
    header("Location: ./logout.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Check if user already has personal details (enrolled)
$check_query = "SELECT ta.enrollment_status,
    tp.first_name, tp.middle_name, tp.last_name, tp.date_of_birth, tp.gender, tp.address, tp.personal_id,
    td.parent_full_name, td.contact_num, td.relationship, td.fb_account, td.parent_temp_id
 FROM tbl_account ta
    LEFT JOIN tbl_personal_details tp ON ta.acc_id = tp.acc_id
    LEFT JOIN tbl_parents_details td ON tp.personal_id = td.child_id
  WHERE ta.acc_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$account_data = $result->fetch_assoc();

// Initialize variables for displaying enrollment data
$new_old_student_data = null;
$transferee_data = null;
$enrollment_data = null;
$is_transferee = false;
$is_new_old = false;
$show_enrollment_data = false;
$can_enroll_next_grade = false;
$current_level_info = null;

// Check if student has enrollment data and is not newly registered
if ($account_data && $account_data['enrollment_status'] != 'Newly Registered' && $account_data['personal_id']) {

    // Check new/old students table
    $new_old_query = "SELECT nos.*, f.level, f.fee_id FROM tbl_new_old_students nos 
                      LEFT JOIN tbl_fees f ON nos.level_id = f.fee_id 
                      WHERE nos.personal_id = ?";
    $stmt = $conn->prepare($new_old_query);
    $stmt->bind_param("i", $account_data['personal_id']);
    $stmt->execute();
    $new_old_result = $stmt->get_result();
    $new_old_student_data = $new_old_result->fetch_assoc();

    if ($new_old_student_data) {
        $is_new_old = true;
        $enrollment_data = $new_old_student_data;
        $current_level_info = [
            'current_level_id' => $new_old_student_data['level_id'],
            'current_level' => $new_old_student_data['level'],
            'type' => 'new_old'
        ];
    }

    // Check transferee table
    $transferee_query = "SELECT st.*, f.level, f.fee_id FROM tbl_student_transferee st 
                         LEFT JOIN tbl_fees f ON st.level_id = f.fee_id 
                         WHERE st.personal_id = ?";
    $stmt = $conn->prepare($transferee_query);
    $stmt->bind_param("i", $account_data['personal_id']);
    $stmt->execute();
    $transferee_result = $stmt->get_result();
    $transferee_data = $transferee_result->fetch_assoc();

    if ($transferee_data) {
        $is_transferee = true;
        $enrollment_data = $transferee_data;
        $current_level_info = [
            'current_level_id' => $transferee_data['level_id'],
            'current_level' => $transferee_data['level'],
            'type' => 'transferee'
        ];
    }

    // Check if student can enroll for next grade
    if ($current_level_info && $account_data['enrollment_status'] == 'Not Enrolled') {
        $can_enroll_next_grade = true;
        $show_enrollment_data = true;
    } elseif ($account_data['enrollment_status'] != 'Not Enrolled') {
        $show_enrollment_data = true;
    }
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
    <title>Foothills Christian School - Enrollment Form</title>
    <style>
        :root {
            --sidebar-width: 280px;
            --primary-color: #02541b;
            --secondary-color: #013d12;
            --accent-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --muted-color: #95a5a6;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            padding: 2rem;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .form-header,
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
            margin-bottom: 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .form-header::before,
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

        .enrollment-form-container,
        .enrollment-data-container {
            background: white;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 10px 30px rgba(2, 84, 27, 0.15);
            overflow: hidden;
            max-width: 900px;
            margin: 0 auto;
        }

        .enrollment-data-container {
            border-radius: 20px;
            margin-bottom: 2rem;
        }

        .form-content,
        .data-content {
            padding: 2rem;
        }

        .form-section,
        .data-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            background: rgba(248, 249, 250, 0.5);
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 0.5rem;
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

        .uploaded-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .uploaded-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .uploaded-image:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .document-link {
            color: var(--primary-color);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .document-link:hover {
            background: var(--primary-color);
            color: white;
        }

        .alert-info {
            background: linear-gradient(135deg, #bee0caff, #beebdcff);
            border: none;
            border: 1px solid var(--primary-color);
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

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: none;
            border: 1px solid var(--warning-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(2, 84, 27, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-floating>.form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-floating>.form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(2, 84, 27, 0.1);
        }

        .form-floating>label {
            padding: 1rem 0.75rem;
            font-weight: 500;
            color: var(--muted-color);
        }

        .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(2, 84, 27, 0.1);
        }

        .grade-selection {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .transferee-section {
            background: linear-gradient(135deg, #02541b, #27ae60);
            border: 2px solid var(--success-color);
        }

        .transferee-section .section-title {
            color: white;
            border-bottom-color: white;
        }

        .kinder-section {
            background: linear-gradient(135deg, #02541b, #27ae60);
            border: 2px solid var(--accent-color);
        }

        .kinder-section .section-title {
            color: white;
            border-bottom-color: white;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .file-upload-container {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .file-upload-container:hover {
            border-color: var(--primary-color);
            background: rgba(2, 84, 27, 0.05);
        }

        .file-upload-container.active {
            border-color: var(--success-color);
            background: rgba(39, 174, 96, 0.05);
        }

        .kinder-section .file-upload-container {
            border-color: white;
            color: white;
        }

        .kinder-section .file-upload-container:hover {
            border-color: rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.1);
        }

        .image-preview {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
            margin: 10px auto;
            display: none;
        }

        .upload-text {
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .form-row,
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        .hidden {
            display: none !important;
        }

        .info-item .grade-level {
            font-size: 18px;
            font-weight: 600;
        }

        #gradeLevelDisplay {
            background: #0a782bff;
            color: white;
        }
    </style>
</head>

<body>
    <?php include_once "./sidebar.php"; ?>

    <div class="main-content">
        <?php if ($show_enrollment_data): ?>
            <!-- Display Enrollment Data -->
            <div class="enrollment-data-container">
                <div class="dashboard-header">
                    <h1><i class="fas fa-user-check me-2"></i>Your Enrollment Information</h1>
                    <p class="mb-0">Academic Year 2025-2026</p>
                </div>

                <div class="data-content">
                    <!-- Status Alert -->
                    <?php if ($account_data['enrollment_status'] == 'Pending'): ?>
                        <div class="alert alert-warning">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock me-3 text-warning fs-4"></i>
                                <div>
                                    <h6 class="mb-1 fw-bold">Enrollment Under Review</h6>
                                    <p class="mb-0">Your enrollment form has been submitted and is currently being reviewed by the school administration. You will be notified once the review process is complete.</p>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($account_data['enrollment_status'] == 'Enrolled'): ?>
                        <div class="alert alert-success">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-3 text-success fs-4"></i>
                                <div>
                                    <h6 class="mb-1 fw-bold">Enrollment Approved</h6>
                                    <p class="mb-0">Congratulations! Your enrollment has been approved. You can now proceed with the next steps in your academic journey.</p>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($account_data['enrollment_status'] == 'Not Enrolled' && $can_enroll_next_grade): ?>
                        <div class="alert alert-warning">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-graduation-cap me-3 text-warning fs-4"></i>
                                <div>
                                    <h6 class="mb-1 fw-bold">Ready to Enroll for Next Grade Level</h6>
                                    <p class="mb-2">You have completed <strong><?php echo htmlspecialchars($current_level_info['current_level']); ?></strong> and are eligible to enroll for the next grade level.</p>
                                    <button class="btn btn-warning btn-sm" onclick="showNextGradeEnrollment()">
                                        <i class="fas fa-arrow-up me-1"></i>Enroll for Next Grade
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Personal Information -->
                    <div class="data-section">
                        <div class="section-title">
                            <i class="fas fa-user"></i>
                            Personal Information
                        </div>

                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($account_data['first_name'] . ' ' . ($account_data['middle_name'] ? $account_data['middle_name'] . ' ' : '') . $account_data['last_name']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Date of Birth</div>
                                <div class="info-value"><?php echo date('F j, Y', strtotime($account_data['date_of_birth'])); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Gender</div>
                                <div class="info-value"><?php echo htmlspecialchars($account_data['gender']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Address</div>
                                <div class="info-value"><?php echo htmlspecialchars($account_data['address']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Enrollment Status</div>
                                <div class="info-value">
                                    <span class="status-badge <?php
                                                                switch ($account_data['enrollment_status']) {
                                                                    case 'Enrolled':
                                                                        echo 'status-enrolled';
                                                                        break;
                                                                    case 'Pending':
                                                                        echo 'status-pending';
                                                                        break;
                                                                    default:
                                                                        echo 'status-not-enrolled';
                                                                }
                                                                ?>">
                                        <?php echo htmlspecialchars($account_data['enrollment_status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Parent/Guardian Information -->
                    <div class="data-section">
                        <div class="section-title">
                            <i class="fas fa-users"></i>
                            Parent/Guardian Information
                        </div>

                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Parent/Guardian Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($account_data['parent_full_name']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Contact Number</div>
                                <div class="info-value"><?php echo htmlspecialchars($account_data['contact_num']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Relationship</div>
                                <div class="info-value"><?php echo htmlspecialchars($account_data['relationship']); ?></div>
                            </div>
                            <?php if ($account_data['fb_account']): ?>
                                <div class="info-item">
                                    <div class="info-label">Facebook Account</div>
                                    <div class="info-value">
                                        <a href="<?php echo htmlspecialchars($account_data['fb_account']); ?>" target="_blank" class="text-primary">
                                            <i class="fab fa-facebook me-1"></i>View Profile
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Student Type and Grade Information -->
                    <?php if ($is_new_old && $new_old_student_data): ?>
                        <div class="data-section">
                            <div class="section-title">
                                <i class="fas fa-graduation-cap"></i>
                                New/Continuing Student Information
                            </div>

                            <div class="mb-3">
                                <span class="status-badge status-enrolled">
                                    <i class="fas fa-user-plus me-1"></i>
                                    New/Continuing Student
                                </span>
                            </div>
                            <div class="info-item" id="gradeLevelDisplay">
                                <div class="info-label">Grade Level</div>
                                <div class="grade-level"><?php echo htmlspecialchars($new_old_student_data['level']); ?></div>
                            </div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Student Photo</div>
                                    <div class="info-value">
                                        <?php if ($new_old_student_data['student_image']): ?>
                                            <div class="mt-2">
                                                <img src="../assets/new_student/<?php echo htmlspecialchars($new_old_student_data['student_image']); ?>"
                                                    alt="Student Photo" class="uploaded-image"
                                                    onclick="window.open(this.src, '_blank')">
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">No image uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Parent's Valid ID</div>
                                    <div class="info-value">
                                        <?php if ($new_old_student_data['parents_valid_id']): ?>
                                            <a href="../assets/new_student/<?php echo htmlspecialchars($new_old_student_data['parents_valid_id']); ?>"
                                                target="_blank" class="document-link">
                                                <i class="fas fa-file-alt"></i>
                                                View Document
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No document uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_transferee && $transferee_data): ?>
                        <div class="data-section">
                            <div class="section-title">
                                <i class="fas fa-exchange-alt"></i>
                                Transferee Student Information
                            </div>

                            <div class="mb-3">
                                <span class="status-badge status-pending">
                                    <i class="fas fa-exchange-alt me-1"></i>
                                    Transferee Student
                                </span>
                            </div>

                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Grade Level</div>
                                    <div class="info-value"><?php echo htmlspecialchars($transferee_data['level']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Previous School</div>
                                    <div class="info-value"><?php echo htmlspecialchars($transferee_data['prev_school']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Previous School Address</div>
                                    <div class="info-value"><?php echo htmlspecialchars($transferee_data['prev_address_school']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Previous School ID/Certificate</div>
                                    <div class="info-value">
                                        <?php if ($transferee_data['prev_id_school_file']): ?>
                                            <a href="../assets/transferee/<?php echo htmlspecialchars($transferee_data['prev_id_school_file']); ?>"
                                                target="_blank" class="document-link">
                                                <i class="fas fa-file-alt"></i>
                                                View Document
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No document uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Previous School Report Card</div>
                                    <div class="info-value">
                                        <?php if ($transferee_data['prev_school_card']): ?>
                                            <a href="../assets/transferee/<?php echo htmlspecialchars($transferee_data['prev_school_card']); ?>"
                                                target="_blank" class="document-link">
                                                <i class="fas fa-file-alt"></i>
                                                View Document
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No document uploaded</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        <button class="btn btn-secondary me-2" onclick="window.location.href='dashboard.php'">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </button>
                        <?php if ($account_data['enrollment_status'] == 'Enrolled'): ?>
                            <button class="btn btn-primary" onclick="window.location.href='./files_requirements.php'">
                                <i class="fas fa-file-alt me-2"></i>View Requirements
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Original Enrollment Form for Newly Registered -->
            <div class="enrollment-form-container">
                <?php if ($account_data['enrollment_status'] != 'Newly Registered'): ?>
                    <div class="p-5 text-center">
                        <h2 class="mb-4" style="color: var(--danger-color);"><i class="fas fa-exclamation-triangle me-2"></i>Access Denied</h2>
                        <p class="mb-4">You have already submitted your enrollment form or your enrollment is being processed. If you believe this is an error, please contact the school administration for assistance.</p>
                        <button class="btn btn-primary" onclick="window.location.href='dashboard.php'">
                            <i class="fas fa-home me-2"></i>Go to Dashboard
                        </button>
                    </div>
                    <?php exit(); ?>
                <?php endif; ?>

                <!-- Form Header -->
                <div class="form-header">
                    <h1><i class="fas fa-user-graduate me-2"></i>Student Enrollment Form</h1>
                    <p class="mb-0">Academic Year 2025-2026</p>
                </div>

                <div class="form-content">
                    <form id="enrollmentForm" method="POST" enctype="multipart/form-data">
                        <!-- Grade Level Selection -->
                        <div class="grade-selection">
                            <h5 class="mb-3"><i class="fas fa-graduation-cap me-2"></i>Select Grade Level</h5>
                            <select class="form-select" id="gradeLevel" name="grade_level" required>
                                <option value="">Choose Grade Level...</option>
                                <?php
                                $select_grade_query = "SELECT level, fee_id FROM tbl_fees";
                                $result = $conn->query($select_grade_query);
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($row['fee_id']) . '" data-level="' . htmlspecialchars($row['level']) . '">' . htmlspecialchars($row['level']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Student Type Alert -->
                        <div id="newStudentAlert" class="alert alert-info hidden">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-star me-3 text-success fs-4"></i>
                                <div>
                                    <h6 class="mb-1 fw-bold text-success">New Student - Kinder 1</h6>
                                    <p class="mb-0">Welcome! As a new student, please fill out the basic information form below and upload the required documents.</p>
                                </div>
                            </div>
                        </div>

                        <div id="transfereeAlert" class="alert alert-info hidden">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exchange-alt me-3 text-success fs-4"></i>
                                <div>
                                    <h6 class="mb-1 fw-bold text-success">Transferee Student</h6>
                                    <p class="mb-0">Please provide your previous school information and upload required transferee documents.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Details Section -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-user"></i>
                                Personal Information
                            </div>

                            <div class="form-row">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="firstName" name="first_name" placeholder="First Name" value="<?php echo htmlspecialchars($account_data['first_name'] ?? ''); ?>" required>
                                    <label for="firstName">First Name *</label>
                                </div>
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="middleName" name="middle_name" placeholder="Middle Name" value="<?php echo htmlspecialchars($account_data['middle_name'] ?? ''); ?>">
                                    <label for="middleName">Middle Name</label>
                                </div>
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="lastName" name="last_name" placeholder="Last Name" value="<?php echo htmlspecialchars($account_data['last_name'] ?? ''); ?>" required>
                                    <label for="lastName">Last Name *</label>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="dateOfBirth" name="date_of_birth" value="<?php echo htmlspecialchars($account_data['date_of_birth'] ?? ''); ?>" required>
                                    <label for="dateOfBirth">Date of Birth *</label>
                                </div>
                                <div>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="" <?php echo ($account_data['gender'] ?? '') === '' ? 'selected' : ''; ?>>Select Gender...</option>
                                        <option value="Male" <?php echo ($account_data['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ($account_data['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo ($account_data['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-floating">
                                <textarea class="form-control" id="address" name="address" placeholder="Address" style="height: 100px" required><?php echo htmlspecialchars($account_data['address'] ?? ''); ?></textarea>
                                <label for="address">Complete Address *</label>
                            </div>
                        </div>

                        <!-- Parent/Guardian Details Section -->
                        <div class="form-section">
                            <div class="section-title">
                                <i class="fas fa-users"></i>
                                Parent/Guardian Information
                            </div>

                            <div class="form-row">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="parentName" name="parent_full_name" value="<?php echo htmlspecialchars($account_data['parent_full_name'] ?? ''); ?>" placeholder="Parent/Guardian Name" required>
                                    <label for="parentName">Full Name *</label>
                                </div>
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="contactNum" name="contact_num" value="<?php echo htmlspecialchars($account_data['contact_num'] ?? ''); ?>" placeholder="Contact Number" required>
                                    <label for="contactNum">Contact Number *</label>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <select class="form-select" id="relationship" name="relationship" required>
                                        <option value="">Select Relationship...</option>
                                        <option value="Mother" <?php echo ($account_data['relationship'] ?? '') === 'Mother' ? 'selected' : ''; ?>>Mother</option>
                                        <option value="Father" <?php echo ($account_data['relationship'] ?? '') === 'Father' ? 'selected' : ''; ?>>Father</option>
                                        <option value="Guardian" <?php echo ($account_data['relationship'] ?? '') === 'Guardian' ? 'selected' : ''; ?>>Guardian</option>
                                    </select>
                                </div>
                                <div class="form-floating">
                                    <input type="url" class="form-control" id="fbAccount" name="fb_account" value="<?php echo htmlspecialchars($account_data['fb_account'] ?? ''); ?>" placeholder="Facebook Account (Optional)">
                                    <label for="fbAccount">Facebook Account (Optional)</label>
                                </div>
                            </div>
                        </div>

                        <!-- Kinder 1 Documents Section -->
                        <div id="kinderSection" class="form-section kinder-section hidden">
                            <div class="section-title">
                                <i class="fas fa-images"></i>
                                Required Documents for Kinder 1
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label text-white fw-bold">Student 2x2 Picture *</label>
                                    <div class="file-upload-container" onclick="document.getElementById('studentPhoto').click()">
                                        <i class="fas fa-camera fs-3 mb-2 text-white"></i>
                                        <div class="upload-text">
                                            <p class="mb-1 text-white">Click to upload student's 2x2 photo</p>
                                            <small class="text-white">JPG, JPEG, PNG (Max 2MB)</small>
                                        </div>
                                        <img class="image-preview" id="studentPhotoPreview" alt="Student Photo Preview">
                                        <input type="file" id="studentPhoto" name="student_photo" class="d-none" accept=".jpg,.jpeg,.png">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white fw-bold">Parent's Valid ID *</label>
                                    <div class="file-upload-container" onclick="document.getElementById('parentId').click()">
                                        <i class="fas fa-id-card fs-3 text-white mb-2"></i>
                                        <div class="upload-text">
                                            <p class="mb-1 text-white">Click to upload parent's valid ID</p>
                                            <small class="text-white">JPG, JPEG, PNG, PDF (Max 3MB)</small>
                                        </div>
                                        <img class="image-preview" id="parentIdPreview" alt="Parent ID Preview">
                                        <input type="file" id="parentId" name="parent_id" class="d-none" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transferee Section (Hidden by default) -->
                        <div id="transfereeSection" class="form-section transferee-section hidden">
                            <div class="section-title">
                                <i class="fas fa-school"></i>
                                Previous School Information
                            </div>

                            <div class="form-row">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="prevSchool" name="prev_school" placeholder="Previous School Name">
                                    <label for="prevSchool">Previous School Name *</label>
                                </div>
                            </div>
                            <div class="form-floating">
                                <textarea class="form-control" id="prevSchoolAddress" name="prev_address_school" placeholder="Previous School Address" style="height: 100px"></textarea>
                                <label for="prevSchoolAddress">Previous School Address *</label>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label" style="color: white;">School ID/Certificate *</label>
                                    <div class="file-upload-container" onclick="document.getElementById('prevIdSchoolFile').click()">
                                        <i class="fas fa-cloud-upload-alt fs-3 mb-2 text-white"></i>
                                        <div class="upload-text">
                                            <p class="mb-1 text-white">Click to upload School ID or Certificate</p>
                                            <small class="text-white">PDF, JPG, PNG (Max 5MB)</small>
                                        </div>
                                        <input type="file" id="prevIdSchoolFile" name="prev_id_school_file" class="d-none" accept=".pdf,.jpg,.jpeg,.png">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-white">Report Card/Grades *</label>
                                    <div class="file-upload-container" onclick="document.getElementById('prevSchoolCard').click()">
                                        <i class="fas fa-cloud-upload-alt fs-3 text-white mb-2"></i>
                                        <div class="upload-text">
                                            <p class="mb-1 text-white">Click to upload Report Card</p>
                                            <small class="text-white">PDF, JPG, PNG (Max 5MB)</small>
                                        </div>
                                        <input type="file" id="prevSchoolCard" name="prev_school_card" class="d-none" accept=".pdf,.jpg,.jpeg,.png">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-secondary me-2" onclick="history.back()">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </button>
                            <button type="button" class="btn btn-primary" id="submitEnrollment">
                                <i class="fas fa-paper-plane me-2"></i>Submit Enrollment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {
            $('#submitEnrollment').on('click', function() {
                const form = document.getElementById('enrollmentForm');
                const formData = new FormData(form);

                // Validate required fields before submission
                const gradeLevel = $('#gradeLevel').val();
                if (!gradeLevel) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Grade Level Required',
                        text: 'Please select a grade level.'
                    });
                    return;
                }

                // Additional validation for file uploads based on grade level
                if (gradeLevel === '1') { // Kinder 1
                    const studentPhoto = $('#studentPhoto')[0].files[0];
                    const parentId = $('#parentId')[0].files[0];

                    if (!studentPhoto) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Student Photo Required',
                            text: 'Please upload the student\'s 2x2 photo.'
                        });
                        return;
                    }

                    if (!parentId) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Parent ID Required',
                            text: 'Please upload the parent\'s valid ID.'
                        });
                        return;
                    }
                } else if (gradeLevel > 1) { // Transferee
                    const prevSchool = $('#prevSchool').val();
                    const prevSchoolAddress = $('#prevSchoolAddress').val();
                    const prevIdSchoolFile = $('#prevIdSchoolFile')[0].files[0];
                    const prevSchoolCard = $('#prevSchoolCard')[0].files[0];

                    if (!prevSchool || !prevSchoolAddress) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Previous School Information Required',
                            text: 'Please fill in all previous school information.'
                        });
                        return;
                    }

                    if (!prevIdSchoolFile) {
                        Swal.fire({
                            icon: 'error',
                            title: 'School ID Required',
                            text: 'Please upload the school ID or certificate.'
                        });
                        return;
                    }

                    if (!prevSchoolCard) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Report Card Required',
                            text: 'Please upload the report card.'
                        });
                        return;
                    }
                }

                // Disable submit button to prevent double submission
                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Submitting...');

                $.ajax({
                    url: '../backend/enrollment_form.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: "json",
                    success: function(response) {
                        $('#submitEnrollment').prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Submit Enrollment');

                        try {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Enrollment Submitted',
                                    text: 'Your enrollment form has been submitted successfully!',
                                    confirmButtonText: 'OK'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = 'dashboard.php';
                                    }
                                });
                            } else {
                                console.log('Server response:', response);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Submission Failed',
                                    text: response.message || 'An error occurred during submission.'
                                });
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            Swal.fire({
                                icon: 'error',
                                title: 'Unexpected Error',
                                text: 'An unexpected error occurred. Please try again later.'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#submitEnrollment').prop('disabled', false).html('<i class="fas fa-paper-plane me-2"></i>Submit Enrollment');

                        console.error('AJAX Error:', {
                            xhr: xhr,
                            status: status,
                            error: error,
                            responseText: xhr.responseText
                        });

                        Swal.fire({
                            icon: 'error',
                            title: 'Server Error',
                            text: 'Could not connect to the server. Please try again later.'
                        });
                    }
                });
            });

            // Handle grade level selection
            $('#gradeLevel').change(function() {
                const selectedOption = $(this).find('option:selected');
                const selectedLevel = selectedOption.data('level');

                const transfereeSection = $('#transfereeSection');
                const kinderSection = $('#kinderSection');
                const newStudentAlert = $('#newStudentAlert');
                const transfereeAlert = $('#transfereeAlert');

                // Hide all sections and alerts first
                newStudentAlert.addClass('hidden');
                transfereeAlert.addClass('hidden');
                transfereeSection.addClass('hidden');
                kinderSection.addClass('hidden');

                // Remove all required attributes first
                $('#prevSchool, #prevSchoolAddress, #prevIdSchoolFile, #prevSchoolCard').prop('required', false);
                $('#studentPhoto, #parentId').prop('required', false);

                if (selectedLevel === 'Kinder 1') {
                    // New student (Kinder 1) - show kinder section
                    kinderSection.removeClass('hidden');
                    newStudentAlert.removeClass('hidden');

                    // Add required attribute to kinder fields
                    $('#studentPhoto, #parentId').prop('required', true);
                } else if (selectedLevel && selectedLevel !== 'Kinder 1') {
                    // Transferee student - show transferee section
                    transfereeSection.removeClass('hidden');
                    transfereeAlert.removeClass('hidden');

                    // Add required attribute to transferee fields
                    $('#prevSchool, #prevSchoolAddress, #prevIdSchoolFile, #prevSchoolCard').prop('required', true);
                }
            });

            // File upload handlers
            $('#prevIdSchoolFile').change(function() {
                handleFileUpload(this, $(this).closest('.file-upload-container'));
            });

            $('#prevSchoolCard').change(function() {
                handleFileUpload(this, $(this).closest('.file-upload-container'));
            });

            $('#studentPhoto').change(function() {
                handleImageUpload(this, $(this).closest('.file-upload-container'), '#studentPhotoPreview', 2);
            });

            $('#parentId').change(function() {
                handleImageUpload(this, $(this).closest('.file-upload-container'), '#parentIdPreview', 3);
            });

            function handleFileUpload(input, container) {
                if (input.files && input.files[0]) {
                    const file = input.files[0];
                    const maxSize = 5 * 1024 * 1024; // 5MB

                    if (file.size > maxSize) {
                        Swal.fire({
                            icon: 'error',
                            title: 'File Too Large',
                            text: 'Please select a file smaller than 5MB.'
                        });
                        input.value = '';
                        return;
                    }

                    container.addClass('active');
                    container.find('.upload-text p').text(file.name);
                } else {
                    container.removeClass('active');
                    container.find('.upload-text p').text('Click to upload file');
                }
            }

            function handleImageUpload(input, container, previewId, maxSizeMB) {
                if (input.files && input.files[0]) {
                    const file = input.files[0];
                    const maxSize = maxSizeMB * 1024 * 1024; // Convert MB to bytes

                    if (file.size > maxSize) {
                        Swal.fire({
                            icon: 'error',
                            title: 'File Too Large',
                            text: `Please select a file smaller than ${maxSizeMB}MB.`
                        });
                        input.value = '';
                        return;
                    }

                    // Check if it's an image file for preview
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            $(previewId).attr('src', e.target.result).show();
                            container.find('.upload-text').hide();
                            container.addClass('active');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        // For PDF files, just show filename
                        container.addClass('active');
                        container.find('.upload-text p').text(file.name);
                    }
                } else {
                    container.removeClass('active');
                    container.find('.upload-text').show();
                    $(previewId).hide();
                    if (input.id === 'studentPhoto') {
                        container.find('.upload-text p').text('Click to upload student\'s 2x2 photo');
                    } else {
                        container.find('.upload-text p').text('Click to upload parent\'s valid ID');
                    }
                }
            }

            // Age calculation based on date of birth
            $('#dateOfBirth').change(function() {
                const dob = new Date($(this).val());
                const today = new Date();
                const age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
                if (age < 0 || isNaN(age)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Date of Birth',
                        text: 'Please enter a valid date of birth.'
                    });
                    $(this).val('');
                }
            });
        });

        // Function to show next grade enrollment modal/form
        function showNextGradeEnrollment() {
            <?php if ($can_enroll_next_grade && $current_level_info): ?>
                const currentLevelId = <?php echo $current_level_info['current_level_id']; ?>;
                const nextLevelId = currentLevelId + 1;
                const studentType = '<?php echo $current_level_info['type']; ?>';

                // Check if there's a next level (max is Grade 6 = level_id 8)
                if (nextLevelId > 8) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Maximum Grade Level Reached',
                        text: 'You have completed the highest grade level available (Grade 6).',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                Swal.fire({
                    title: 'Enroll for Next Grade Level',
                    html: `
                        <div class="text-start">
                            <p class="mb-3">You are currently enrolled in <strong><?php echo htmlspecialchars($current_level_info['current_level']); ?></strong></p>
                            <p class="mb-3">Would you like to proceed with enrollment for the next grade level?</p>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Your personal and parent information will be carried over. You only need to confirm your enrollment.
                            </div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Enroll Me',
                    cancelButtonText: 'Not Now',
                    confirmButtonColor: '#02541b',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        enrollNextGrade(nextLevelId, studentType);
                    }
                });
            <?php endif; ?>
        }

        function enrollNextGrade(nextLevelId, studentType) {
            // Show loading
            Swal.fire({
                title: 'Processing Enrollment',
                html: 'Please wait while we process your enrollment...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request to enroll for next grade
            $.ajax({
                url: '../backend/enroll_next_grade.php',
                type: 'POST',
                data: {
                    next_level_id: nextLevelId,
                    student_type: studentType
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Enrollment Successful',
                            text: response.message,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#02541b'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Enrollment Failed',
                            text: response.message || 'An error occurred during enrollment.'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Could not connect to the server. Please try again later.'
                    });
                }
            });
        }
    </script>
</body>

</html>
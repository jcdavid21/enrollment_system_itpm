<!DOCTYPE html>
<?php
session_start();
require_once "../backend/config.php";

if($_SESSION["role"] !== "Admin"){
    header("Location: ../components/logout.php");
    exit;
}

// Fetch dashboard statistics

// Total students count
$total_students_query = "SELECT COUNT(*) AS total FROM tbl_account WHERE enrollment_status = 'Enrolled'";
$total_students_result = $conn->query($total_students_query);
$total_students = $total_students_result->fetch_assoc()['total'];

// Enrollment status breakdown
$enrollment_status_query = "SELECT enrollment_status, COUNT(*) as count FROM tbl_account WHERE role = 'Student' GROUP BY enrollment_status";
$enrollment_status_result = $conn->query($enrollment_status_query);
$enrollment_status = [];
while ($row = $enrollment_status_result->fetch_assoc()) {
    $enrollment_status[] = $row;
}

// Total revenue
$revenue_query = "SELECT SUM(amount) as total_revenue FROM tbl_payments";
$revenue_result = $conn->query($revenue_query);
$total_revenue = $revenue_result->fetch_assoc()['total_revenue'] ?? 0;

// Pending enrollments
$pending_enrollments_query = "SELECT COUNT(*) as pending FROM tbl_enrollments WHERE status = 'Pending'";
$pending_enrollments_result = $conn->query($pending_enrollments_query);
$pending_enrollments = $pending_enrollments_result->fetch_assoc()['pending'];

$grade_distribution_query = "SELECT 
                                f.fee_id,
                                f.level,
                                COALESCE(enrolled_students.student_count, 0) AS student_count
                            FROM tbl_fees f
                            LEFT JOIN (
                                SELECT 
                                    COALESCE(nos.level_id, st.level_id) as level_id,
                                    COUNT(DISTINCT pd.personal_id) as student_count
                                FROM tbl_personal_details pd
                                INNER JOIN tbl_account a ON a.acc_id = pd.acc_id 
                                AND a.role = 'Student' 
                                AND a.enrollment_status = 'Enrolled'
                                LEFT JOIN tbl_new_old_students nos ON pd.personal_id = nos.personal_id
                                LEFT JOIN tbl_student_transferee st ON pd.personal_id = st.personal_id
                                WHERE COALESCE(nos.level_id, st.level_id) IS NOT NULL
                                GROUP BY COALESCE(nos.level_id, st.level_id)
                            ) enrolled_students ON f.fee_id = enrolled_students.level_id
                            ORDER BY f.fee_id;";
$grade_distribution_result = $conn->query($grade_distribution_query);
$grade_distribution = [];
while ($row = $grade_distribution_result->fetch_assoc()) {
    $grade_distribution[] = $row;
}

$grade_levels_query = "SELECT * FROM tbl_fees ORDER BY fee_id";
$grade_levels_result = $conn->query($grade_levels_query);
$grade_levels = [];
while ($row = $grade_levels_result->fetch_assoc()) {
    $grade_levels[] = $row;
}

// Dropout trend by year
$dropout_trend_query = "SELECT 
                            years.year,
                            COALESCE(dropout_stats.dropout_count, 0) as dropout_count
                        FROM (
                            SELECT 2020 as year UNION ALL SELECT 2021 UNION ALL 
                            SELECT 2022 UNION ALL SELECT 2023 UNION ALL 
                            SELECT 2024 UNION ALL SELECT 2025
                        ) years
                        LEFT JOIN (
                            SELECT 
                                YEAR(dtr.date_recorded) as year,
                                COUNT(*) as dropout_count
                            FROM tbl_dropout_transfer_reasons dtr
                            WHERE dtr.reason_type = 'Dropped Out'
                                AND YEAR(dtr.date_recorded) >= 2020
                            GROUP BY YEAR(dtr.date_recorded)
                        ) dropout_stats ON years.year = dropout_stats.year
                        GROUP BY years.year
                        ORDER BY years.year ASC;";
$dropout_trend_result = $conn->query($dropout_trend_query);
$dropout_trend = [];
while ($row = $dropout_trend_result->fetch_assoc()) {
    $dropout_trend[] = $row;
}

// Recent account registrations (recent enrollment form fill-ups)
// Recent account registrations with detailed information
$recent_registrations_query = "SELECT 
                                a.acc_id,
                                a.username,
                                a.email,
                                a.enrollment_status,
                                a.date_registered,
                                CONCAT(COALESCE(pd.first_name, ''), ' ', COALESCE(pd.middle_name, ''), ' ', COALESCE(pd.last_name, '')) as full_name,
                                pd.first_name,
                                pd.middle_name,
                                pd.last_name,
                                pd.date_of_birth,
                                pd.gender,
                                pd.address,
                                parent.parent_full_name,
                                parent.contact_num,
                                parent.relationship,
                                parent.fb_account,
                                f.level as grade_level,
                                nos.student_image,
                                nos.parents_valid_id,
                                st.prev_school,
                                st.prev_address_school,
                                st.prev_id_school_file,
                                st.prev_school_card
                            FROM tbl_account a
                            LEFT JOIN tbl_personal_details pd ON a.acc_id = pd.acc_id
                            LEFT JOIN tbl_parents_details parent ON pd.personal_id = parent.child_id
                            LEFT JOIN tbl_new_old_students nos ON pd.personal_id = nos.personal_id
                            LEFT JOIN tbl_student_transferee st ON pd.personal_id = st.personal_id
                            LEFT JOIN tbl_fees f ON (nos.level_id = f.fee_id OR st.level_id = f.fee_id)
                            WHERE a.role = 'Student'
                            ORDER BY a.date_registered DESC
                            LIMIT 15";
$recent_registrations_result = $conn->query($recent_registrations_query);
$recent_registrations = [];
while ($row = $recent_registrations_result->fetch_assoc()) {
    $recent_registrations[] = $row;
}

// Payment methods distribution
$payment_methods_query = "SELECT method, COUNT(*) as count, SUM(amount) as total_amount FROM tbl_payments GROUP BY method";
$payment_methods_result = $conn->query($payment_methods_query);
$payment_methods = [];
while ($row = $payment_methods_result->fetch_assoc()) {
    $payment_methods[] = $row;
}

// Requirements status overview
$requirements_status_query = "SELECT 
                                    r.requirement_name,
                                    COUNT(sr.std_rq_id) as total_submissions,
                                    SUM(CASE WHEN sr.requirement_status = 'Accepted' THEN 1 ELSE 0 END) as accepted,
                                    SUM(CASE WHEN sr.requirement_status = 'Pending' THEN 1 ELSE 0 END) as pending,
                                    SUM(CASE WHEN sr.requirement_status = 'Verifying' THEN 1 ELSE 0 END) as verifying
                                FROM tbl_requirements r
                                LEFT JOIN tbl_student_requirements sr ON r.requirement_id = sr.requirement_id
                                GROUP BY r.requirement_id, r.requirement_name";
$requirements_status_result = $conn->query($requirements_status_query);
$requirements_status = [];
while ($row = $requirements_status_result->fetch_assoc()) {
    $requirements_status[] = $row;
}

// Monthly enrollment trend (last 6 months)
// Monthly enrollment trend (last 12 months with all months included)
$monthly_trend_query = "SELECT 
                            months.month,
                            COALESCE(registrations.count, 0) as registrations
                        FROM (
                            SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL n.num MONTH), '%Y-%m') as month
                            FROM (SELECT 0 as num UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 
                                  UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11) n
                        ) months
                        LEFT JOIN (
                            SELECT DATE_FORMAT(date_registered, '%Y-%m') as month, COUNT(*) as count
                            FROM tbl_account 
                            WHERE role = 'Student' AND date_registered >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                            GROUP BY DATE_FORMAT(date_registered, '%Y-%m')
                        ) registrations ON months.month = registrations.month
                        ORDER BY months.month";
$monthly_trend_result = $conn->query($monthly_trend_query);
$monthly_trend = [];
while ($row = $monthly_trend_result->fetch_assoc()) {
    $monthly_trend[] = $row;
}

// Parent contact information summary
$parent_contacts_query = "SELECT 
                                relationship,
                                COUNT(*) as count
                            FROM tbl_parents_details
                            GROUP BY relationship";
$parent_contacts_result = $conn->query($parent_contacts_query);
$parent_contacts = [];
while ($row = $parent_contacts_result->fetch_assoc()) {
    $parent_contacts[] = $row;
}

// Total new vs transferee students
$student_types_query = "SELECT 'New Students' as type, COUNT(*) as count 
FROM tbl_new_old_students tn
LEFT JOIN tbl_personal_details pd ON tn.personal_id = pd.personal_id
LEFT JOIN tbl_account a ON pd.acc_id = a.acc_id
WHERE a.enrollment_status = 'Enrolled'

UNION ALL

SELECT 'Transferee Students' as type, COUNT(*) as count 
FROM tbl_student_transferee ts
LEFT JOIN tbl_personal_details pd ON ts.personal_id = pd.personal_id
LEFT JOIN tbl_account a ON pd.acc_id = a.acc_id
WHERE a.enrollment_status = 'Enrolled'";
$student_types_result = $conn->query($student_types_query);
$student_types = [];
while ($row = $student_types_result->fetch_assoc()) {
    $student_types[] = $row;
}
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css"
        rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz6YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="../styles/general.css">
    <link rel="stylesheet" href="../styles/sidebar.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            padding: 2rem;
        }

        .dashboard-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border-left: 4px solid #3498db;
        }

        .dashboard-header h1 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .dashboard-header p {
            color: #64748b;
            margin: 0;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 4px solid transparent;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card.primary {
            border-left-color: #3498db;
        }

        .stat-card.success {
            border-left-color: #27ae60;
        }

        .stat-card.warning {
            border-left-color: #f39c12;
        }

        .stat-card.danger {
            border-left-color: #e74c3c;
        }

        .stat-card.info {
            border-left-color: #17a2b8;
        }

        .stat-card.purple {
            border-left-color: #6f42c1;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-card.primary .stat-icon {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .stat-card.success .stat-icon {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }

        .stat-card.warning .stat-icon {
            background: rgba(243, 156, 18, 0.1);
            color: #f39c12;
        }

        .stat-card.danger .stat-icon {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .stat-card.info .stat-icon {
            background: rgba(23, 162, 184, 0.1);
            color: #17a2b8;
        }

        .stat-card.purple .stat-icon {
            background: rgba(111, 66, 193, 0.1);
            color: #6f42c1;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #64748b;
            font-weight: 500;
        }

        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .chart-card h5 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .chart-card h5 i {
            margin-right: 0.5rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }

        .chart-container canvas {
            max-height: 300px !important;
        }

        /* Tables */
        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .table-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 1.5rem;
        }

        .table-header h5 {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .table-header h5 i {
            margin-right: 0.5rem;
        }

        .table-responsive {
            border-radius: 0 0 12px 12px;
        }

        .table {
            margin: 0;
        }

        .table th {
            background: #f8fafc;
            color: #2c3e50;
            font-weight: 600;
            border: none;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            border-color: #e2e8f0;
            vertical-align: middle;
        }

        .badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.5rem 0.75rem;
        }

        .progress {
            height: 8px;
            background: #e2e8f0;
        }

        .progress-bar {
            border-radius: 4px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .mini-chart {
            height: 200px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }
        }

        .filter-card {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .filter-card label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .modal-img {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }

        .label-date {
            font-weight: 500;
            color: #41668cff;
            margin-bottom: 0.5rem;
            display: block;
            font-size: 12px;
        }
    </style>
    <title>Admin Dashboard</title>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h1>
            <p>Welcome back! Here's an overview of your enrollment system for School Year 2025-2026.</p>
        </div>


        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-label">Total Enrolled</div>
            </div>

            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-peso-sign"></i>
                </div>
                <div class="stat-number">₱<?php echo number_format($total_revenue, 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>

            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo $pending_enrollments; ?></div>
                <div class="stat-label">Pending Enrollments</div>
            </div>

            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-number">
                    <?php
                    $new_students = 0;
                    foreach ($student_types as $type) {
                        if ($type['type'] == 'New Students') {
                            $new_students = $type['count'];
                            break;
                        }
                    }
                    echo $new_students;
                    ?>
                </div>
                <div class="stat-label">New Students</div>
            </div>

            <div class="stat-card purple">
                <div class="stat-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-number">
                    <?php
                    $transferees = 0;
                    foreach ($student_types as $type) {
                        if ($type['type'] == 'Transferee Students') {
                            $transferees = $type['count'];
                            break;
                        }
                    }
                    echo $transferees;
                    ?>
                </div>
                <div class="stat-label">Transferee Students</div>
            </div>

            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-number">2025-2026</div>
                <div class="stat-label">Current School Year</div>
            </div>
        </div>


        <!-- Charts Section -->
        <div class="charts-grid">
            <!-- Enrollment Status Chart -->
            <div class="chart-card">
                <h5><i class="fas fa-chart-pie"></i> Enrollment Status Distribution</h5>
                <select class="form-select" id="enrollmentStatusFilter" class="" style="width: 200px; margin-bottom: 1rem;">
                    <option value="all">All Statuses</option>
                    <option value="Enrolled">Enrolled Only</option>
                    <option value="Pending">Pending Only</option>
                    <option value="Not Enrolled">Not Enrolled Only</option>
                </select>
                <div class="chart-container">
                    <canvas id="enrollmentStatusChart"></canvas>
                </div>
            </div>

            <!-- Grade Distribution Chart -->
            <div class="chart-card">
                <h5><i class="fas fa-chart-bar"></i> Students by Grade Level</h5>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <label for="gradeDistributionFilter" class="label-date">Select Grade Level:</label>
                        <select class="form-select" id="gradeDistributionFilter" class="" style="width: 200px; margin-bottom: 1rem;">
                            <option value="all">All Grades</option>
                            <option value="kinder">Kinder Only</option>
                            <option value="elementary">Elementary Only</option>
                        </select>
                    </div>
                </div>

                <div class="chart-container">
                    <canvas id="gradeDistributionChart"></canvas>
                </div>
            </div>
        </div>


        <!-- Monthly Registration Trend -->
        <div class="charts-grid">
            <div class="chart-card mb-4">
                <h5><i class="fas fa-chart-line"></i> Monthly Registration Trend</h5>
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex gap-3">
                        <!-- Month Filter -->
                        <div>
                            <label for="monthlyTrendMonthFilter" class="label-date">Select Month:</label>
                            <select class="form-select" id="monthlyTrendMonthFilter" style="width: 150px;">
                                <option value="all">All Months</option>
                                <option value="1">January</option>
                                <option value="2">February</option>
                                <option value="3">March</option>
                                <option value="4">April</option>
                                <option value="5">May</option>
                                <option value="6">June</option>
                                <option value="7">July</option>
                                <option value="8">August</option>
                                <option value="9">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        <!-- Year Filter -->
                        <div>
                            <label for="monthlyTrendYearFilter" class="label-date">Select Year:</label>
                            <select class="form-select" id="monthlyTrendYearFilter" style="width: 120px;">
                                <option value="all">All Years</option>
                                <?php
                                $currentYear = date('Y');
                                for ($year = $currentYear; $year >= $currentYear - 3; $year--) {
                                    echo "<option value='$year'>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="monthlyTrendChart"></canvas>
                </div>
            </div>
            <!-- Dropout Trend Chart -->
            <div class="chart-card">
                <h5><i class="fas fa-chart-line text-danger"></i> Student Dropouts by Year</h5>
                <?php
                if (count($dropout_trend) == 0) {
                    echo "<div class='text-center text-muted' style='padding: 2rem;'>
                    <i class='fas fa-info-circle fa-2x mb-3'></i>
                    <div>No dropout data available.</div>
                    <div>Please ensure that dropout records exist in the system.</div></div>";
                } else {
                ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <label for="dropoutTrendFilter" class="label-date">Select Period:</label>
                            <select class="form-select" id="dropoutTrendFilter" style="width: 200px;">
                                <option value="5">Last 5 Years</option>
                                <option value="3">Last 3 Years</option>
                                <option value="1">Current Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="dropoutTrendChart"></canvas>
                    </div>
                <?php } ?>
            </div>
        </div>



        <!-- Recent Form Submissions -->
        <div class="table-card">
            <div class="table-header">
                <h5><i class="fas fa-user-plus"></i> Recent Enrollment Form Submissions</h5>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_registrations as $registration): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3">
                                            <?php
                                            $name = trim($registration['full_name']);
                                            if (empty($name) || $name == ' ') {
                                                echo strtoupper(substr($registration['username'], 0, 2));
                                            } else {
                                                $names = explode(' ', $name);
                                                if (count($names) >= 2) {
                                                    echo strtoupper(substr($names[0], 0, 1) . substr($names[1], 0, 1));
                                                } else {
                                                    echo strtoupper(substr($names[0], 0, 2));
                                                }
                                            }
                                            ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold">
                                                <?php echo !empty(trim($registration['full_name'])) && trim($registration['full_name']) != ' '
                                                    ? htmlspecialchars($registration['full_name'])
                                                    : htmlspecialchars($registration['username']); ?>
                                            </div>
                                            <small class="text-muted">ID: <?php echo $registration['acc_id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($registration['username']); ?></td>
                                <td><?php echo htmlspecialchars($registration['email']); ?></td>
                                <td><?php echo date('M d, Y g:i A', strtotime($registration['date_registered'])); ?></td>
                                <td>
                                    <?php
                                    $status = $registration['enrollment_status'];
                                    $badge_class = '';
                                    switch ($status) {
                                        case 'Enrolled':
                                            $badge_class = 'bg-success';
                                            break;
                                        case 'Pending':
                                            $badge_class = 'bg-warning';
                                            break;
                                        case 'Not Enrolled':
                                            $badge_class = 'bg-secondary';
                                            break;
                                        case 'Dropped Out':
                                            $badge_class = 'bg-danger';
                                            break;
                                        case 'Newly Registered':
                                            $badge_class = 'bg-info';
                                            break;
                                        default:
                                            $badge_class = 'bg-primary';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewStudentDetails(<?php echo $registration['acc_id']; ?>)" data-bs-toggle="modal" data-bs-target="#studentModal">
                                        <i class="fas fa-eye"></i> View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Requirements Status Overview -->
        <div class="table-card">
            <div class="table-header">
                <h5><i class="fas fa-file-alt"></i> Requirements Status Overview</h5>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Requirement</th>
                            <th>Total Submissions</th>
                            <th>Accepted</th>
                            <th>Pending Review</th>
                            <th>Under Verification</th>
                            <th>Completion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requirements_status as $requirement): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($requirement['requirement_name']); ?></td>
                                <td><span class="badge bg-primary"><?php echo $requirement['total_submissions']; ?></span></td>
                                <td><span class="badge bg-success"><?php echo $requirement['accepted']; ?></span></td>
                                <td><span class="badge bg-warning"><?php echo $requirement['pending']; ?></span></td>
                                <td><span class="badge bg-info"><?php echo $requirement['verifying']; ?></span></td>
                                <td>
                                    <?php
                                    $completion_rate = $requirement['total_submissions'] > 0
                                        ? round(($requirement['accepted'] / $requirement['total_submissions']) * 100, 1)
                                        : 0;
                                    ?>
                                    <div class="progress">
                                        <div class="progress-bar <?php echo $completion_rate > 80 ? 'bg-success' : ($completion_rate > 50 ? 'bg-warning' : 'bg-danger'); ?>"
                                            style="width: <?php echo $completion_rate; ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $completion_rate; ?>%</small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Student Details Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalLabel">Student Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Full Image Modal -->
    <div class="modal fade" id="fullImageModal" tabindex="-1" aria-labelledby="fullImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fullImageModalLabel">Full Image View</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="fullImage" src="" class="img-fluid" alt="Full Image" style="max-height: 80vh;">
                </div>
            </div>
        </div>
    </div>

    <script>
        // Store original data for filtering
        let originalEnrollmentData = <?php echo json_encode($enrollment_status); ?>;
        let originalGradeData = <?php echo json_encode($grade_distribution); ?>;
        let originalMonthlyData = <?php echo json_encode($monthly_trend); ?>;
        let studentsData = <?php echo json_encode($recent_registrations); ?>;
        let gradeLevels = <?php echo json_encode($grade_levels); ?>;
        let originalDropoutData = <?php echo json_encode($dropout_trend); ?>;
        console.log(originalDropoutData);
        let dropoutChart;


        // Chart instances
        let enrollmentChart, gradeChart, monthlyChart;

        // Initialize charts
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        // Enrollment Status Chart
        function createEnrollmentChart(data) {
            const ctx = document.getElementById('enrollmentStatusChart').getContext('2d');
            const chartContainer = document.getElementById('enrollmentStatusChart').parentElement;

            if (enrollmentChart) enrollmentChart.destroy();

            // Check if data is empty or null
            if (!data || data.length === 0) {
                // Hide canvas and show no data message
                ctx.canvas.style.display = 'none';

                // Create or update no data message
                let noDataMsg = chartContainer.querySelector('.no-data-message');
                if (!noDataMsg) {
                    noDataMsg = document.createElement('div');
                    noDataMsg.className = 'no-data-message';
                    noDataMsg.style.cssText = `
                text-align: center;
                color: #666;
                font-size: 16px;
                padding: 40px;
                background: #f8f9fa;
                border-radius: 8px;
                margin: 20px 0;
            `;
                    chartContainer.appendChild(noDataMsg);
                }
                noDataMsg.textContent = 'No enrollment data available';
                noDataMsg.style.display = 'block';
                return;
            }

            // Show canvas and hide no data message if it exists
            ctx.canvas.style.display = 'block';
            const noDataMsg = chartContainer.querySelector('.no-data-message');
            if (noDataMsg) {
                noDataMsg.style.display = 'none';
            }

            const labels = data.map(item => item.enrollment_status);
            const counts = data.map(item => item.count);

            enrollmentChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: counts,
                        backgroundColor: ['#3498db', '#27ae60', '#e74c3c', '#f39c12', '#9b59b6', '#17a2b8'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });
        }

        // Grade Distribution Chart
        function createGradeChart(data) {
            const ctx = document.getElementById('gradeDistributionChart').getContext('2d');
            if (gradeChart) gradeChart.destroy();

            const labels = data.map(item => item.level);
            const counts = data.map(item => item.student_count);

            gradeChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Number of Students',
                        data: counts,
                        backgroundColor: 'rgba(52, 152, 219, 0.8)',
                        borderColor: '#3498db',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Monthly Trend Chart
        function createMonthlyChart(data) {
            const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
            if (monthlyChart) monthlyChart.destroy();

            const labels = data.map(item => {
                const date = new Date(item.month + '-01');
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    year: 'numeric'
                });
            });
            const counts = data.map(item => parseInt(item.registrations));

            monthlyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'New Registrations',
                        data: counts,
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderColor: '#3498db',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Dropout Trend Chart
        function createDropoutChart(data) {
            const ctx = document.getElementById('dropoutTrendChart').getContext('2d');
            if (dropoutChart) dropoutChart.destroy();

            const labels = data.map(item => item.year);
            const counts = data.map(item => parseInt(item.dropout_count));

            dropoutChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Dropouts',
                        data: counts,
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        borderColor: '#e74c3c',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#e74c3c',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            color: '#e74c3c',
                            font: {
                                weight: 'bold',
                                size: 12
                            },
                            formatter: function(value) {
                                return value;
                            }
                        }
                    }
                },
            });
        }

        // Initialize charts
        createEnrollmentChart(originalEnrollmentData);
        createGradeChart(originalGradeData);
        createMonthlyChart(originalMonthlyData);
        createDropoutChart(originalDropoutData);

        // Filter functions
        document.getElementById('enrollmentStatusFilter').addEventListener('change', function() {
            const filter = this.value;
            let filteredData = originalEnrollmentData;

            if (filter !== 'all') {
                filteredData = originalEnrollmentData.filter(item => item.enrollment_status === filter);
            }

            createEnrollmentChart(filteredData);
        });

        document.getElementById('dropoutTrendFilter').addEventListener('change', function() {
            const years = parseInt(this.value);
            let filteredData = originalDropoutData;

            if (years !== 5) {
                const currentYear = new Date().getFullYear();
                const cutoffYear = currentYear - years + 1;
                filteredData = originalDropoutData.filter(item => parseInt(item.year) >= cutoffYear);
            }

            createDropoutChart(filteredData);
        });

        document.getElementById('gradeDistributionFilter').addEventListener('change', function() {
            const filter = this.value;
            let filteredData = originalGradeData;

            if (filter === 'kinder') {
                filteredData = originalGradeData.filter(item => item.level.includes('Kinder'));
            } else if (filter === 'elementary') {
                filteredData = originalGradeData.filter(item => item.level.includes('Grade'));
            }

            createGradeChart(filteredData);
        });


        // Monthly trend filters
        document.getElementById('monthlyTrendMonthFilter').addEventListener('change', function() {
            const month = this.value;
            let filteredData = originalMonthlyData;

            if (month !== 'all') {
                filteredData = originalMonthlyData.filter(item => {
                    const itemMonth = new Date(item.month + '-01').getMonth() + 1;
                    return itemMonth.toString() === month;
                });
            }

            createMonthlyChart(filteredData);
        });

        document.getElementById('monthlyTrendYearFilter').addEventListener('change', function() {
            const year = this.value;
            let filteredData = originalMonthlyData;

            if (year !== 'all') {
                filteredData = originalMonthlyData.filter(item => {
                    const itemYear = new Date(item.month + '-01').getFullYear();
                    return itemYear.toString() === year;
                });
            }

            createMonthlyChart(filteredData);
        });

        // Student details modal function
        function viewStudentDetails(accId) {
            const student = studentsData.find(s => s.acc_id == accId);
            if (!student) return;

            const isTransferee = student.prev_school ? true : false;

            document.getElementById('modalContent').innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">Personal Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Name:</strong></td><td>${student.first_name || ''} ${student.middle_name || ''} ${student.last_name || ''}</td></tr>
                            <tr><td><strong>Username:</strong></td><td>${student.username}</td></tr>
                            <tr><td><strong>Email:</strong></td><td>${student.email}</td></tr>
                            <tr><td><strong>Date of Birth:</strong></td><td>${student.date_of_birth || 'N/A'}</td></tr>
                            <tr><td><strong>Gender:</strong></td><td>${student.gender || 'N/A'}</td></tr>
                            <tr><td><strong>Address:</strong></td><td>${student.address || 'N/A'}</td></tr>
                            <tr><td><strong>Grade Level:</strong></td><td>${student.grade_level || 'N/A'}</td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="badge ${getStatusBadgeClass(student.enrollment_status)}">${student.enrollment_status}</span></td></tr>
                            <tr><td><strong>Registered:</strong></td><td>${new Date(student.date_registered).toLocaleDateString()}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">Parent Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Parent Name:</strong></td><td>${student.parent_full_name || 'N/A'}</td></tr>
                            <tr><td><strong>Contact:</strong></td><td>${student.contact_num || 'N/A'}</td></tr>
                            <tr><td><strong>Relationship:</strong></td><td>${student.relationship || 'N/A'}</td></tr>
                            <tr><td><strong>Facebook:</strong></td><td>${student.fb_account !== 'No provided link' ? `<a href="${student.fb_account}" class="text-primary" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook me-1 "></i>View Link</a>` : 'N/A'}</td></tr>
                        </table>
                        
                        ${isTransferee ? `
                            <h6 class="text-primary mt-3">Previous School</h6>
                            <table class="table table-sm">
                                <tr><td><strong>School:</strong></td><td>${student.prev_school}</td></tr>
                                <tr><td><strong>Address:</strong></td><td>${student.prev_address_school}</td></tr>
                            </table>
                        ` : ''}

                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="text-primary">Uploaded Documents</h6>
                                <div class="row">
                                    ${student.student_image ? `
                                        <div class="col-md-6 mb-2">
                                            <p class="mb-1"><strong>Student Photo:</strong></p>
                                            <img src="../assets/${isTransferee ? 'transferee' : 'new_student'}/${student.student_image}" class="modal-img" alt="Student Photo">
                                            <br><button class="btn btn-sm btn-outline-primary mt-1" onclick="viewFullImage('../assets/${isTransferee ? 'transferee' : 'new_student'}/${student.student_image}', 'Student Photo')"><i class="fas fa-expand me-1"></i>View Full</button>
                                        </div>
                                    ` : ''}
                                    
                                    ${student.parents_valid_id ? `
                                        <div class="col-md-6 mb-2">
                                            <p class="mb-1"><strong>Parent's ID:</strong></p>
                                            <img src="../assets/${isTransferee ? 'transferee' : 'new_student'}/${student.parents_valid_id}" class="modal-img" alt="Parent ID">
                                            <br><button class="btn btn-sm btn-outline-primary mt-1" onclick="viewFullImage('../assets/${isTransferee ? 'transferee' : 'new_student'}/${student.parents_valid_id}', 'Parent ID')"><i class="fas fa-expand me-1"></i>View Full</button>
                                        </div>
                                    ` : ''}
                                    
                                    ${student.prev_id_school_file ? `
                                        <div class="col-md-6 mb-2">
                                            <p class="mb-1"><strong>Previous School ID:</strong></p>
                                            <img src="../assets/transferee/${student.prev_id_school_file}" class="modal-img" alt="School ID">
                                            <br><button class="btn btn-sm btn-outline-primary mt-1" onclick="viewFullImage('../assets/transferee/${student.prev_id_school_file}', 'Previous School ID')"><i class="fas fa-expand me-1"></i>View Full</button>
                                        </div>
                                    ` : ''}
                                    
                                    ${student.prev_school_card ? `
                                        <div class="col-md-6 mb-2">
                                            <p class="mb-1"><strong>School Card:</strong></p>
                                            <img src="../assets/transferee/${student.prev_school_card}" class="modal-img" alt="School Card">
                                            <br><button class="btn btn-sm btn-outline-primary mt-1" onclick="viewFullImage('../assets/transferee/${student.prev_school_card}', 'School Card')"><i class="fas fa-expand me-1"></i>View Full</button>
                                        </div>
                                    ` : ''}
                                </div>
                                
                                ${!student.student_image && !student.parents_valid_id && !student.prev_id_school_file && !student.prev_school_card ? 
                                    '<p class="text-muted">No documents uploaded yet.</p>' : ''}
                            </div>
                        </div>
                    </div>
                </div>
                
            `;
        }

        // Function to view full image
        function viewFullImage(imageSrc, imageTitle) {
            document.getElementById('fullImage').src = imageSrc;
            document.getElementById('fullImageModalLabel').textContent = imageTitle;
            const fullImageModal = new bootstrap.Modal(document.getElementById('fullImageModal'));
            fullImageModal.show();
        }


        function getStatusBadgeClass(enrollment_status) {
            switch (enrollment_status) {
                case 'Enrolled':
                    return 'bg-success';
                case 'Pending':
                    return 'bg-warning';
                case 'Not Enrolled':
                    return 'bg-secondary';
                case 'Dropped Out':
                    return 'bg-danger';
                case 'Newly Registered':
                    return 'bg-info';
                default:
                    return 'bg-primary';
            }
        }
    </script>
</body>

</html>
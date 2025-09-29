<!DOCTYPE html>
<?php
session_start();
require_once "../backend/config.php";

// Get grade levels for filter
$grade_levels_query = "SELECT * FROM tbl_fees ORDER BY fee_id";
$grade_levels_result = $conn->query($grade_levels_query);
$grade_levels = [];
while ($row = $grade_levels_result->fetch_assoc()) {
    $grade_levels[] = $row;
}

// Get available years
$years_query = "SELECT DISTINCT YEAR(date_registered) as year FROM tbl_account WHERE role = 'Student' ORDER BY year DESC";
$years_result = $conn->query($years_query);
$available_years = [];
while ($row = $years_result->fetch_assoc()) {
    $available_years[] = $row['year'];
}

// Get sections data
$sections_query = "SELECT s.*, f.level FROM tbl_sections s JOIN tbl_fees f ON s.level_id = f.fee_id ORDER BY s.level_id, s.sec_id";
$sections_result = $conn->query($sections_query);
$sections = [];
while ($row = $sections_result->fetch_assoc()) {
    $sections[] = $row;
}

?>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
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

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border-left: 4px solid #3498db;
        }

        .page-header h1 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #64748b;
            margin: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-left: 4px solid transparent;
            text-align: center;
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

        .stat-card.info {
            border-left-color: #17a2b8;
        }

        .stat-card.purple {
            border-left-color: #6f42c1;
        }

        .stat-card.danger {
            border-left-color: #e74c3c;
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
            font-size: 0.9rem;
        }

        .filter-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .filter-card h6 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .filter-card h6 i {
            margin-right: 0.5rem;
        }

        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .modal-img {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        .info-item {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 0.75rem;
        }

        .info-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .document-card {
            transition: all 0.2s ease;
        }

        .document-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .modal-xl {
            max-width: 1200px;
        }

        .avatar-large {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .bg-light {
            background-color: #f8f9fa !important;
        }

        .border-start {
            border-left: 4px solid;
        }

        .border-primary {
            border-color: #0d6efd !important;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .pagination-container {
            padding: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .balance-alert {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .balance-alert i {
            color: #f39c12;
        }

        .enrollment-actions {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .section-info {
            background: #e3f2fd;
            padding: 0.75rem;
            border-radius: 6px;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .year-end-card {
            background: linear-gradient(135deg, #fff5f5, #fed7d7);
            border: 1px solid #fed7d7;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .year-end-card h6 {
            color: #c53030;
            margin-bottom: 1rem;
        }
    </style>
    <title>Enrollment Management</title>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-graduation-cap me-2"></i>Enrollment Management</h1>
            <p>Manage student enrollments, update status, and assign sections.</p>
        </div>

        <!-- School Year End Management -->
        <div class="year-end-card">
            <h6><i class="fas fa-calendar-times me-2"></i>School Year Management</h6>
            <div class="row align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Set School Year End Date</label>
                    <input type="date" class="form-control" id="schoolYearEndDate" value="">
                </div>
                <div class="col-md-6">
                    <button class="btn btn-danger" id="endSchoolYearBtn">
                        <i class="fas fa-calendar-times me-1"></i>End School Year
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid" id="statsContainer">
            <div class="stat-card primary">
                <div class="stat-number" id="totalStudents">0</div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number" id="enrolledCount">0</div>
                <div class="stat-label">Enrolled</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number" id="pendingCount">0</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card info">
                <div class="stat-number" id="newStudentsCount">0</div>
                <div class="stat-label">New Students</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-number" id="transfereeCount">0</div>
                <div class="stat-label">Transferees</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-number" id="withBalanceCount">0</div>
                <div class="stat-label">With Balance</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <h6><i class="fas fa-filter"></i>Filter Students</h6>
            <div class="row g-3" id="filterForm">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status">
                        <option value="all" selected>All Status</option>
                        <option value="Enrolled">Enrolled</option>
                        <option value="Pending">Pending</option>
                        <option value="Not Enrolled">Not Enrolled</option>
                        <option value="Dropped Out">Dropped Out</option>
                        <option value="Newly Registered">Newly Registered</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Grade Level</label>
                    <select class="form-select" id="grade">
                        <option value="all">All Grades</option>
                        <?php foreach ($grade_levels as $level): ?>
                            <option value="<?php echo $level['fee_id']; ?>">
                                <?php echo $level['level']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Registration Year</label>
                    <select class="form-select" id="year">
                        <option value="all">All Years</option>
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $year == date('Y') ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Balance Status</label>
                    <select class="form-select" id="balanceFilter">
                        <option value="all">All</option>
                        <option value="with_balance">With Balance</option>
                        <option value="no_balance">No Balance</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" placeholder="Name, username, or email...">
                        <button class="btn btn-primary" type="button" id="searchBtn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button class="btn btn-outline-secondary w-100" id="resetBtn">
                            <i class="fas fa-refresh"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Table -->
        <div class="table-card position-relative">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div class="table-header">
                <h5 id="tableTitle"><i class="fas fa-table"></i>Enrollment Records (0 students)</h5>
            </div>

            <div id="studentTableContainer">
                <!-- Table content will be loaded here -->
            </div>

            <!-- Pagination -->
            <div class="pagination-container" id="paginationContainer">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>

    <!-- View Student Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1" aria-labelledby="studentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalLabel">Student Enrollment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Enrollment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="updateStatusContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Full Image Modal -->
    <div class="modal fade" id="fullImageModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fullImageModalLabel">Full Image View</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="fullImage" src="" class="img-fluid" alt="Full Image" style="max-height: 80vh;">
                </div>
            </div>
        </div>
    </div>

    <script>
        class EnrollmentManager {
            constructor() {
                this.current_page = 1;
                this.records_per_page = 10;
                this.students = [];
                this.current_student = null;
                this.total_records = 0;
                this.gradeLevels = <?php echo json_encode($grade_levels); ?>;
                this.sections = <?php echo json_encode($sections); ?>;
                this.init();
            }

            async init() {
                await this.fetchStudents();
                this.initEventListeners();
            }

            initEventListeners() {
                // Filter change events
                ['status', 'grade', 'year', 'balanceFilter'].forEach(id => {
                    document.getElementById(id).addEventListener('change', () => {
                        this.current_page = 1;
                        this.fetchStudents();
                    });
                });

                // Search functionality
                document.getElementById('searchBtn').addEventListener('click', () => {
                    this.current_page = 1;
                    this.fetchStudents();
                });

                document.getElementById('search').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.current_page = 1;
                        this.fetchStudents();
                    }
                });

                // Reset button
                document.getElementById('resetBtn').addEventListener('click', () => {
                    this.resetFilters();
                });

                // School Year End button
                document.getElementById('endSchoolYearBtn').addEventListener('click', () => {
                    this.endSchoolYear();
                });
            }

            resetFilters() {
                document.getElementById('status').value = 'all';
                document.getElementById('grade').value = 'all';
                document.getElementById('year').value = 'all';
                document.getElementById('balanceFilter').value = 'all';
                document.getElementById('search').value = '';
                this.current_page = 1;
                this.fetchStudents();
            }

            showLoading(show = true) {
                const overlay = document.getElementById('loadingOverlay');
                overlay.style.display = show ? 'flex' : 'none';
            }

            async fetchStudents() {
                try {
                    this.showLoading(true);

                    const params = new URLSearchParams({
                        page: this.current_page,
                        limit: this.records_per_page,
                        status: document.getElementById('status')?.value || 'all',
                        grade: document.getElementById('grade')?.value || 'all',
                        year: document.getElementById('year')?.value || 'all',
                        balance_filter: document.getElementById('balanceFilter')?.value || 'all',
                        search: document.getElementById('search')?.value || ''
                    });

                    console.log('Fetching students with params:', params);

                    const response = await fetch(`./student_lists/get_students.php?${params.toString()}`);
                    const mockData = await response.json();

                    this.students = mockData.students || [];
                    this.total_records = mockData.pagination.total_records || 0;

                    this.renderStudentList();
                    this.renderPagination(mockData.pagination);
                    this.updateStats(mockData.stats);
                } catch (error) {
                    console.error('Error fetching students:', error);
                    this.renderError();
                } finally {
                    this.showLoading(false);
                }
            }

            renderStudentList() {
                const container = document.getElementById('studentTableContainer');
                const title = document.getElementById('tableTitle');

                title.innerHTML = `<i class="fas fa-table"></i>Enrollment Records (${this.total_records} students)`;

                if (this.students.length === 0) {
                    container.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-graduation-cap"></i>
                    <h4>No students found</h4>
                    <p>No students match your current filter criteria.</p>
                </div>
            `;
                    return;
                }

                let tableHTML = `
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Contact Info</th>
                            <th>Grade Level</th>
                            <th>Status</th>
                            <th>Balance</th>
                            <th>Section</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

                this.students.forEach(student => {
                    tableHTML += this.renderStudentRow(student);
                });

                tableHTML += `
                    </tbody>
                </table>
            </div>
        `;

                container.innerHTML = tableHTML;
            }

            renderStudentRow(student) {
                const statusBadgeClass = this.getStatusBadgeClass(student.enrollment_status);
                const balanceBadge = student.total_balance > 0 ? 'bg-warning' : 'bg-success';
                const balanceText = student.total_balance > 0 ? `₱${parseFloat(student.total_balance).toLocaleString()}` : 'No Balance';

                return `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar me-3">
                            ${this.getStudentInitials(student)}
                        </div>
                        <div>
                            <div class="fw-bold">
                                ${this.getDisplayName(student)}
                            </div>
                            <small class="text-muted">ID: ${student.acc_id}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div>${student.email}</div>
                    <small class="text-muted">@${student.username}</small>
                </td>
                <td>
                    <span class="badge bg-info">${student.grade_level || 'Not Set'}</span>
                </td>
                <td>
                    <span class="badge ${statusBadgeClass}">${student.enrollment_status}</span>
                </td>
                <td>
                    <span class="badge ${balanceBadge}">${balanceText}</span>
                </td>
                <td>
                    <div class="small">${student.current_section || 'Not Assigned'}</div>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary"
                            onclick="enrollmentManager.viewStudent(${student.acc_id})"
                            data-bs-toggle="modal"
                            data-bs-target="#studentModal"
                            title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-success"
                            onclick="updateStatus(${student.acc_id})"
                            data-bs-toggle="modal"
                            data-bs-target="#updateStatusModal"
                            title="Update Status">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
            }

            renderPagination(pagination) {
                const container = document.getElementById('paginationContainer');

                if (pagination.total_pages <= 1) {
                    container.innerHTML = '';
                    return;
                }

                let paginationHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                    Showing ${((pagination.current_page - 1) * pagination.per_page) + 1} to 
                    ${Math.min(pagination.current_page * pagination.per_page, pagination.total_records)} of 
                    ${pagination.total_records} entries
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
        `;

                // Previous button
                paginationHTML += `
            <li class="page-item ${!pagination.has_prev ? 'disabled' : ''}">
                <button class="page-link" onclick="enrollmentManager.goToPage(${pagination.current_page - 1})" ${!pagination.has_prev ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i>
                </button>
            </li>
        `;

                // Page numbers
                const startPage = Math.max(1, pagination.current_page - 2);
                const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

                if (startPage > 1) {
                    paginationHTML += `
                <li class="page-item">
                    <button class="page-link" onclick="enrollmentManager.goToPage(1)">1</button>
                </li>
            `;
                    if (startPage > 2) {
                        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    paginationHTML += `
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <button class="page-link" onclick="enrollmentManager.goToPage(${i})">${i}</button>
                </li>
            `;
                }

                if (endPage < pagination.total_pages) {
                    if (endPage < pagination.total_pages - 1) {
                        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    paginationHTML += `
                <li class="page-item">
                    <button class="page-link" onclick="enrollmentManager.goToPage(${pagination.total_pages})">${pagination.total_pages}</button>
                </li>
            `;
                }

                // Next button
                paginationHTML += `
            <li class="page-item ${!pagination.has_next ? 'disabled' : ''}">
                <button class="page-link" onclick="enrollmentManager.goToPage(${pagination.current_page + 1})" ${!pagination.has_next ? 'disabled' : ''}>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </li>
        `;

                paginationHTML += `
                    </ul>
                </nav>
            </div>
        `;

                container.innerHTML = paginationHTML;
            }

            goToPage(page) {
                if (page >= 1 && page <= Math.ceil(this.total_records / this.records_per_page)) {
                    this.current_page = page;
                    this.fetchStudents();
                }
            }

            updateStats(stats) {
                document.getElementById('totalStudents').textContent = stats.total_students;
                document.getElementById('enrolledCount').textContent = stats.enrolled_count;
                document.getElementById('pendingCount').textContent = stats.pending_count;
                document.getElementById('newStudentsCount').textContent = stats.new_students_count;
                document.getElementById('transfereeCount').textContent = stats.transferee_count;
                document.getElementById('withBalanceCount').textContent = stats.with_balance_count;
            }

            renderError() {
                const container = document.getElementById('studentTableContainer');
                container.innerHTML = `
            <div class="no-results">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                <h4>Error Loading Students</h4>
                <p>There was an error loading the enrollment data. Please try again.</p>
                <button class="btn btn-primary" onclick="enrollmentManager.fetchStudents()">
                    <i class="fas fa-refresh me-1"></i>Retry
                </button>
            </div>
        `;
            }

            viewStudent(accId) {
                const student = this.students.find(s => s.acc_id == accId);
                if (!student) return;

                const isTransferee = student.student_type === 'Transferee';

                // Calculate balance from the updated field names
                const totalFees = parseFloat(student.calculated_total_fee?.replace(/,/g, '') || 0);
                const totalPaid = parseFloat(student.total_paid?.replace(/,/g, '') || 0);
                const remainingBalance = parseFloat(student.remaining_balance?.replace(/,/g, '') || 0);

                document.getElementById('modalContent').innerHTML = `
                    <div class="container-fluid p-0">
                        <!-- Balance Alert -->
                        ${remainingBalance > 0 ? `
                            <div class="balance-alert mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Balance Alert:</strong>
                                    <span class="ms-2">Student has an outstanding balance of ₱${remainingBalance.toLocaleString()}</span>
                                </div>
                            </div>
                        ` : ''}

                        <!-- Header Section -->
                        <div class="bg-light p-3 rounded mb-4 border-start border-primary border-4">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="avatar-large bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: 600;">
                                        ${this.getStudentInitials(student)}
                                    </div>
                                </div>
                                <div class="col">
                                    <h4 class="mb-1 text-dark">${this.getDisplayName(student)}</h4>
                                    <p class="mb-1 text-muted">Student ID: ${student.acc_id}</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="badge ${this.getStatusBadgeClass(student.enrollment_status)} px-3 py-2">${student.enrollment_status}</span>
                                        <span class="badge ${student.student_type === 'New Student' ? 'bg-success' : 'bg-info'} px-3 py-2">${student.student_type}</span>
                                        ${student.current_grade_level ? `<span class="badge bg-secondary px-3 py-2">${student.current_grade_level}</span>` : ''}
                                        ${student.payment_status ? `<span class="badge ${student.payment_status === 'Fully Paid' ? 'bg-success' : student.payment_status === 'Partially Paid' ? 'bg-warning' : 'bg-danger'} px-3 py-2">${student.payment_status}</span>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <!-- Personal Information -->
                            <div class="col-md-6">
                                <div class="bg-light p-4 rounded h-100">
                                    <h5 class="text-dark mb-3 pb-2 border-bottom border-secondary">
                                        <i class="fas fa-user text-primary me-2"></i>Personal Information
                                    </h5>
                                    <div class="info-grid">
                                        <div class="info-item mb-3">
                                            <label class="form-label text-muted mb-1">Full Name</label>
                                            <div class="text-dark fw-medium">${student.first_name || ''} ${student.middle_name || ''} ${student.last_name || ''}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="info-item mb-3">
                                                    <label class="form-label text-muted mb-1">Username</label>
                                                    <div class="text-dark fw-medium">@${student.username}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="info-item mb-3">
                                                    <label class="form-label text-muted mb-1">Email</label>
                                                    <div class="text-dark fw-medium">${student.email}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="info-item mb-3">
                                                    <label class="form-label text-muted mb-1">Date of Birth</label>
                                                    <div class="text-dark fw-medium">${student.date_of_birth || 'Not provided'}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="info-item mb-3">
                                                    <label class="form-label text-muted mb-1">Gender</label>
                                                    <div class="text-dark fw-medium">${student.gender || 'Not specified'}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="info-item mb-3">
                                            <label class="form-label text-muted mb-1">Address</label>
                                            <div class="text-dark fw-medium">${student.address || 'Not provided'}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="info-item mb-3">
                                                    <label class="form-label text-muted mb-1">Registered</label>
                                                    <div class="text-dark fw-medium">${new Date(student.date_registered).toLocaleDateString()}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="info-item mb-3">
                                                    <label class="form-label text-muted mb-1">Enrolled</label>
                                                    <div class="text-dark fw-medium">${student.date_enrolled ? new Date(student.date_enrolled).toLocaleDateString() : 'Not enrolled'}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="info-item mb-3">
                                                    <label class="form-label text-muted mb-1">School Year</label>
                                                    <div class="text-dark fw-medium">${student.school_year || 'Not assigned'}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="info-item mb-3">
                                                    <label class="form-label text-muted mb-1">Enrollment Status</label>
                                                    <div class="text-dark fw-medium">${student.enrollment_status_detail || 'Not enrolled'}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Enrollment & Financial Information -->
                            <div class="col-md-6">
                                <div class="bg-light p-4 rounded h-100">
                                    <h5 class="text-dark mb-3 pb-2 border-bottom border-secondary">
                                        <i class="fas fa-dollar-sign text-success me-2"></i>Financial Information
                                    </h5>
                                    <div class="info-grid">
                                        <!-- Fee Breakdown -->
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <div class="info-item mb-2">
                                                    <label class="form-label text-muted mb-1 small">Registration Fee</label>
                                                    <div class="text-dark fw-medium">₱${parseFloat(student.registration_fee || 0).toLocaleString()}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="info-item mb-2">
                                                    <label class="form-label text-muted mb-1 small">Miscellaneous Fee</label>
                                                    <div class="text-dark fw-medium">₱${parseFloat(student.miscellaneous_fee || 0).toLocaleString()}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <div class="info-item mb-2">
                                                    <label class="form-label text-muted mb-1 small">Books Fee</label>
                                                    <div class="text-dark fw-medium">₱${parseFloat(student.books_fee || 0).toLocaleString()}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="info-item mb-2">
                                                    <label class="form-label text-muted mb-1 small">Tuition Fee</label>
                                                    <div class="text-dark fw-medium">₱${parseFloat(student.tuition_fee || 0).toLocaleString()}</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                        <!-- Totals -->
                                        <div class="info-item mb-3">
                                            <label class="form-label text-muted mb-1">Total Fees</label>
                                            <div class="text-dark fw-medium fs-5">₱${totalFees.toLocaleString()}</div>
                                        </div>
                                        <div class="info-item mb-3">
                                            <label class="form-label text-muted mb-1">Total Paid</label>
                                            <div class="text-success fw-medium fs-5">₱${totalPaid.toLocaleString()}</div>
                                        </div>
                                        <div class="info-item mb-3">
                                            <label class="form-label text-muted mb-1">Outstanding Balance</label>
                                            <div class="fw-medium fs-5 ${remainingBalance > 0 ? 'text-warning' : 'text-success'}">
                                                ₱${remainingBalance.toLocaleString()}
                                            </div>
                                        </div>
                                        
                                        ${student.monthly_fee ? `
                                            <div class="alert alert-info">
                                                <small><i class="fas fa-info-circle me-1"></i>Monthly Fee: ₱${parseFloat(student.monthly_fee).toLocaleString()}</small>
                                            </div>
                                        ` : ''}
                                    </div>

                                    <hr>

                                    <h6 class="text-dark mb-3">
                                        <i class="fas fa-users text-success me-2"></i>Parent/Guardian Information
                                    </h6>
                                    <div class="info-grid">
                                        <div class="info-item mb-3">
                                            <label class="form-label text-muted mb-1">Parent/Guardian Name</label>
                                            <div class="text-dark fw-medium">${student.parent_full_name || 'Not provided'}</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="info-item mb-3">
                                                    <label class="form-label text-muted mb-1">Contact Number</label>
                                                    <div class="text-dark fw-medium">${student.contact_num || 'Not provided'}</div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="info-item mb-3">
                                                    <label class="form-label text-muted mb-1">Relationship</label>
                                                    <div class="text-dark fw-medium">${student.relationship || 'Not specified'}</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="info-item mb-3">
                                            <label class="form-label text-muted mb-1">Facebook Account</label>
                                            <div class="text-dark fw-medium">
                                                ${student.fb_account && student.fb_account !== 'No provided link' ? 
                                                    `<a href="${student.fb_account}" class="text-primary text-decoration-none" target="_blank">
                                                        <i class="fab fa-facebook me-1"></i>View Profile
                                                    </a>` : 'Not provided'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            ${isTransferee ? `
                                <!-- Previous School Information -->
                                <div class="col-12">
                                    <div class="bg-light p-4 rounded">
                                        <h5 class="text-dark mb-3 pb-2 border-bottom border-secondary">
                                            <i class="fas fa-school text-warning me-2"></i>Previous School Information
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="info-item mb-3">
                                                    <label class="form-label text-muted mb-1">Previous School</label>
                                                    <div class="text-dark fw-medium">${student.prev_school || 'Not provided'}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-item mb-3">
                                                    <label class="form-label text-muted mb-1">School Address</label>
                                                    <div class="text-dark fw-medium">${student.prev_address_school || 'Not provided'}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ` : ''}

                            <!-- Uploaded Documents -->
                            <div class="col-12">
                                <div class="bg-light p-4 rounded">
                                    <h5 class="text-dark mb-3 pb-2 border-bottom border-secondary">
                                        <i class="fas fa-file-alt text-info me-2"></i>Uploaded Documents
                                    </h5>
                                    <div class="row g-3">
                                        ${student.new_student_image ? `
                                            <div class="col-md-3">
                                                <div class="document-card bg-white p-3 rounded border text-center">
                                                    <div class="mb-2">
                                                        <i class="fas fa-camera text-primary fs-4"></i>
                                                    </div>
                                                    <p class="mb-2 small fw-medium text-dark">Student Photo</p>
                                                    <img src="../assets/new_student/${student.new_student_image}" class="img-thumbnail mb-2" style="width: 80px; height: 80px; object-fit: cover;" alt="Student Photo">
                                                    <div>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewFullImage('../assets/new_student/${student.new_student_image}', 'Student Photo')">
                                                            <i class="fas fa-expand me-1"></i>View
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        ` : ''}
                                        
                                        ${student.new_parents_id ? `
                                            <div class="col-md-3">
                                                <div class="document-card bg-white p-3 rounded border text-center">
                                                    <div class="mb-2">
                                                        <i class="fas fa-id-card text-success fs-4"></i>
                                                    </div>
                                                    <p class="mb-2 small fw-medium text-dark">Parent's ID</p>
                                                    <img src="../assets/new_student/${student.new_parents_id}" class="img-thumbnail mb-2" style="width: 80px; height: 80px; object-fit: cover;" alt="Parent ID">
                                                    <div>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewFullImage('../assets/new_student/${student.new_parents_id}', 'Parent ID')">
                                                            <i class="fas fa-expand me-1"></i>View
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        ` : ''}
                                        
                                        ${student.prev_id_school_file ? `
                                            <div class="col-md-3">
                                                <div class="document-card bg-white p-3 rounded border text-center">
                                                    <div class="mb-2">
                                                        <i class="fas fa-school text-warning fs-4"></i>
                                                    </div>
                                                    <p class="mb-2 small fw-medium text-dark">School ID</p>
                                                    <img src="../assets/transferee/${student.prev_id_school_file}" class="img-thumbnail mb-2" style="width: 80px; height: 80px; object-fit: cover;" alt="School ID">
                                                    <div>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewFullImage('../assets/transferee/${student.prev_id_school_file}', 'Previous School ID')">
                                                            <i class="fas fa-expand me-1"></i>View
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        ` : ''}
                                        
                                        ${student.prev_school_card ? `
                                            <div class="col-md-3">
                                                <div class="document-card bg-white p-3 rounded border text-center">
                                                    <div class="mb-2">
                                                        <i class="fas fa-id-badge text-info fs-4"></i>
                                                    </div>
                                                    <p class="mb-2 small fw-medium text-dark">School Card</p>
                                                    <img src="../assets/transferee/${student.prev_school_card}" class="img-thumbnail mb-2" style="width: 80px; height: 80px; object-fit: cover;" alt="School Card">
                                                    <div>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewFullImage('../assets/transferee/${student.prev_school_card}', 'School Card')">
                                                            <i class="fas fa-expand me-1"></i>View
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        ` : ''}
                                        
                                        ${!student.new_student_image && !student.new_parents_id && !student.prev_id_school_file && !student.prev_school_card ? 
                                            `<div class="col-12">
                                                <div class="text-center py-4">
                                                    <i class="fas fa-file-alt text-muted fs-1 mb-2"></i>
                                                    <p class="text-muted mb-0">No documents uploaded yet</p>
                                                </div>
                                            </div>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }

            updateStatus(accId) {
                const student = this.students.find(s => s.acc_id == accId);
                if (!student) return;

                this.current_student = student;

                document.getElementById('updateStatusContent').innerHTML = `
            <form id="updateStatusForm">
                <input type="hidden" name="acc_id" value="${student.acc_id}">
                
                ${student.total_balance > 0 ? `
                    <div class="balance-alert mb-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <strong>Balance Alert:</strong>
                                <div class="mt-1">Student has an outstanding balance of ₱${parseFloat(student.total_balance).toLocaleString()}</div>
                                <small class="text-muted">Student can still be enrolled despite having a balance.</small>
                            </div>
                        </div>
                    </div>
                ` : ''}

                <div class="mb-3">
                    <h6 class="fw-bold">${this.getDisplayName(student)}</h6>
                    <small class="text-muted">Current Status: <span class="badge ${this.getStatusBadgeClass(student.enrollment_status)}">${student.enrollment_status}</span></small>
                </div>

                <div class="mb-3">
                    <small class="text-muted">Current Grade: <span class="badge ${this.getStatusBadgeClass(student.current_grade_level)}">${student.current_grade_level}</span></small>
                </div>

                <div class="mb-3">
                    <label class="form-label">New Enrollment Status</label>
                    <select class="form-select" name="enrollment_status" id="newStatus" required>
                        <option value="">Select Status</option>
                        <option value="Pending" ${student.enrollment_status === 'Pending' ? 'selected' : ''}>Pending</option>
                        <option value="Enrolled" ${student.enrollment_status === 'Enrolled' ? 'selected' : ''}>Enrolled</option>
                        <option value="Not Enrolled" ${student.enrollment_status === 'Not Enrolled' ? 'selected' : ''}>Not Enrolled</option>
                        <option value="Dropped Out" ${student.enrollment_status === 'Dropped Out' ? 'selected' : ''}>Dropped Out</option>
                    </select>
                </div>

                <div class="mb-3" id="gradeSelection" style="display: none;">
                    <label class="form-label">Grade Level</label>
                    <select class="form-select" name="level_id" id="gradeLevel">
                        <option value="">Select Grade Level</option>
                        ${this.gradeLevels.map(level => 
                            `<option value="${level.fee_id}" ${student.current_level == level.fee_id ? 'selected' : ''}>${level.level}</option>`
                        ).join('')}
                    </select>
                </div>

                <div id="sectionInfo" style="display: none;">
                    <div class="section-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Section Assignment:</strong>
                                <div class="mt-2" id="sectionDetails">
                                    Section will be automatically assigned based on availability.
                                </div>
                            </div>
                        </div>

                        <div class="enrollment-actions">
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Update Status
                                </button>
                            </div>
                        </div>
                    </form>
                `;

                this.initializeStatusForm();
            }

            initializeStatusForm() {
                const form = document.getElementById('updateStatusForm');
                const statusSelect = document.getElementById('newStatus');
                const gradeSelection = document.getElementById('gradeSelection');
                const sectionInfo = document.getElementById('sectionInfo');
                const gradeLevel = document.getElementById('gradeLevel');

                statusSelect.addEventListener('change', (e) => {
                    if (e.target.value === 'Enrolled') {
                        gradeSelection.style.display = 'block';
                        sectionInfo.style.display = 'block';
                    } else {
                        gradeSelection.style.display = 'none';
                        sectionInfo.style.display = 'none';
                    }
                });

                gradeLevel.addEventListener('change', (e) => {
                    if (e.target.value && statusSelect.value === 'Enrolled') {
                        this.showAvailableSections(e.target.value);
                    }
                });

                statusSelect.addEventListener('change', (e) => {
                    if (e.target.value === 'Enrolled' && gradeLevel.value) {
                        this.showAvailableSections(gradeLevel.value);
                    } else {
                        document.getElementById('sectionDetails').innerHTML = 'Section will be automatically assigned based on availability.';
                    }
                });

                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.processStatusUpdate(new FormData(form));
                });

                // Trigger change if already enrolled
                if (statusSelect.value === 'Enrolled') {
                    statusSelect.dispatchEvent(new Event('change'));
                    if (gradeLevel.value) {
                        gradeLevel.dispatchEvent(new Event('change'));
                    }
                }
            }

            showAvailableSections(levelId) {
                const availableSections = this.sections.filter(s => s.level_id == levelId);
                const sectionDetails = document.getElementById('sectionDetails');

                if (availableSections.length > 0) {
                    let sectionsHTML = '<div class="mt-2">';

                    // Fetch current enrollment count for each section
                    this.fetchSectionCapacities(levelId).then(sectionCounts => {
                        availableSections.forEach(section => {
                            const currentCount = sectionCounts[section.sec_id] || 0;
                            const isAvailable = currentCount < section.sec_capacity;

                            sectionsHTML += `
                    <div class="d-flex justify-content-between align-items-center py-1">
                        <span>${section.sec_name}</span>
                        <span class="badge ${isAvailable ? 'bg-success' : 'bg-warning'}">
                            ${currentCount}/${section.sec_capacity}
                        </span>
                    </div>
                `;
                        });
                        sectionsHTML += '</div>';

                        sectionDetails.innerHTML = `
                <div>System will assign to the first available section:</div>
                ${sectionsHTML}
            `;
                    });
                } else {
                    sectionDetails.innerHTML = '<div class="text-warning">No sections available for this grade level.</div>';
                }
            }




            // New method to fetch actual section capacities
            async fetchSectionCapacities(levelId) {
                try {
                    const response = await fetch(`./student_lists/get_section_capacities.php?level_id=${levelId}`);
                    const data = await response.json();

                    if (!response.ok || !data.success) {
                        throw new Error(data.error || 'Failed to fetch section capacities');
                    }

                    return data.section_counts || {};
                } catch (error) {
                    console.error('Error fetching section capacities:', error);
                    return {};
                }
            }

            async processStatusUpdate(formData) {
                try {
                    const statusData = {
                        acc_id: parseInt(formData.get('acc_id')),
                        enrollment_status: formData.get('enrollment_status'),
                        level_id: formData.get('level_id') ? parseInt(formData.get('level_id')) : null
                    };

                    const areYouSure = await Swal.fire({
                        icon: 'question',
                        title: 'Confirm Status Update',
                        html: `Are you sure you want to change the enrollment status of <strong>${this.getDisplayName(this.current_student)}</strong> to <span class="badge ${this.getStatusBadgeClass(statusData.enrollment_status)}">${statusData.enrollment_status}</span>?`,
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Update',
                        cancelButtonText: 'Cancel'
                    });

                    if (!areYouSure.isConfirmed) {
                        return;
                    }

                    // Show loading state
                    const submitBtn = document.querySelector('#updateStatusForm button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';

                    const response = await fetch('./student_lists/update_enrollment_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(statusData)
                    });

                    const result = await response.json();

                    if (!response.ok || !result.success) {
                        throw new Error(result.error || 'Failed to update status');
                    }

                    console.log(result);

                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Status Updated',
                        text: result.message || 'Student enrollment status has been updated successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    }).then((result) => {
                        if (result) {
                            window.location.reload();
                        }
                    })

                } catch (error) {
                    console.error('Error updating status:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'An error occurred while updating the status.'
                    });
                } finally {
                    // Reset button state
                    const submitBtn = document.querySelector('#updateStatusForm button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Update Status';
                    }
                }
            }

            // Helper methods continued from the previous code
            getStudentInitials(student) {
                const name = (student.full_name || '').trim();
                if (!name) return '?';
                const parts = name.split(' ');
                if (parts.length >= 2) {
                    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
                }
                return parts[0].charAt(0).toUpperCase();
            }

            getDisplayName(student) {
                if (student.full_name) {
                    return student.full_name.trim();
                }
                const parts = [];
                if (student.first_name) parts.push(student.first_name.trim());
                if (student.middle_name) parts.push(student.middle_name.trim());
                if (student.last_name) parts.push(student.last_name.trim());
                return parts.length > 0 ? parts.join(' ') : 'Unknown';
            }

            getStatusBadgeClass(status) {
                const classes = {
                    'Enrolled': 'bg-success',
                    'Pending': 'bg-warning',
                    'Not Enrolled': 'bg-secondary',
                    'Dropped Out': 'bg-danger',
                    'Newly Registered': 'bg-info'
                };
                return classes[status] || 'bg-secondary';
            }



            async endSchoolYear() {
                const endDate = document.getElementById('schoolYearEndDate').value;

                if (!endDate) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Date Required',
                        text: 'Please select the school year end date.'
                    });
                    return;
                }

                const result = await Swal.fire({
                    icon: 'warning',
                    title: 'End School Year',
                    text: `This will update ALL enrolled students to "Not Enrolled" status as of ${new Date(endDate).toLocaleDateString()}. This action cannot be undone.`,
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, End School Year',
                    cancelButtonText: 'Cancel'
                });

                if (result.isConfirmed) {
                    try {
                        // Show loading state
                        const endBtn = document.getElementById('endSchoolYearBtn');
                        const originalText = endBtn.innerHTML;
                        endBtn.disabled = true;
                        endBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processing...';

                        const response = await fetch('./student_lists/end_school_year.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                end_date: endDate
                            })
                        });

                        const result = await response.json();

                        if (!response.ok || !result.success) {
                            throw new Error(result.error || 'Failed to end school year');
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'School Year Ended',
                            text: 'All enrolled students have been updated to "Not Enrolled" status.',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        this.fetchStudents();

                    } catch (error) {
                        console.error('Error ending school year:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'An error occurred while ending the school year.'
                        });
                    } finally {
                        // Reset button state
                        const endBtn = document.getElementById('endSchoolYearBtn');
                        if (endBtn) {
                            endBtn.disabled = false;
                            endBtn.innerHTML = originalText;
                        }
                    }
                }
            }
        }

        function updateStatus(accId) {
            enrollmentManager.updateStatus(accId);
        }

        function viewFullImage(src, title) {
            Swal.fire({
                title: title,
                imageUrl: src,
                imageAlt: title,
                showCloseButton: true,
                showConfirmButton: false,
                width: 'auto'
            });
        }

        let enrollmentManager = null;
        document.addEventListener('DOMContentLoaded', () => {
            enrollmentManager = new EnrollmentManager();
        });
    </script>
</body>
<!DOCTYPE html>
<?php
session_start();
require_once "../backend/config.php";

if($_SESSION["role"] !== "Admin"){
    header("Location: ../components/logout.php");
    exit;
}

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

        .readOnly {
            pointer-events: none;
            background-color: #e9ecef;
        }
    </style>
    <title>Student Management</title>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-users me-2"></i>Student Management</h1>
            <p>Manage and view all student records in the enrollment system.</p>
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
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <h6><i class="fas fa-filter"></i>Filter Students</h6>
            <div class="row g-3" id="filterForm">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status">
                        <option value="all">All Status</option>
                        <option value="Enrolled" selected>Enrolled</option>
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
                    <label class="form-label">Student Type</label>
                    <select class="form-select" id="type">
                        <option value="all">All Types</option>
                        <option value="new">New Students</option>
                        <option value="transferee">Transferees</option>
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
                <h5 id="tableTitle"><i class="fas fa-table"></i>Student Records (0 students)</h5>
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
                    <h5 class="modal-title" id="studentModalLabel">Student Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Student Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editModalContent">
                    <!-- Edit form will be loaded here -->
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
        class StudentManager {
            constructor() {
                this.current_page = 1;
                this.records_per_page = 10;
                this.students = [];
                this.current_student = null;
                this.total_records = 0;
                this.gradeLevels = <?php echo json_encode($grade_levels); ?>;
                this.init();
            }

            async init(){
                await this.fetchStudents();
                this.initEventListeners();
            }

            initEventListeners() {
                // Filter change events
                ['status', 'grade', 'year', 'type'].forEach(id => {
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
            }

            resetFilters() {
                document.getElementById('status').value = 'all';
                document.getElementById('grade').value = 'all';
                document.getElementById('year').value = 'all';
                document.getElementById('type').value = 'all';
                document.getElementById('search').value = '';
                this.current_page = 1;
                this.fetchStudents();
            }

            showLoading(show = true) {
                const overlay = document.getElementById('loadingOverlay');
                overlay.style.display = show ? 'flex' : 'none';
            }

            async fetchStudents(){
                try{
                    this.showLoading(true);
                    
                    const params = new URLSearchParams({
                        page: this.current_page,
                        limit: this.records_per_page,
                        status: document.getElementById('status')?.value || 'all',
                        grade: document.getElementById('grade')?.value || 'all',
                        year: document.getElementById('year')?.value || 'all',
                        student_type: document.getElementById('type')?.value || 'all',
                        search: document.getElementById('search')?.value || ''
                    });

                    const response = await fetch(`./student_lists/get_students.php?${params}`);
                    const data = await response.json();

                    if (data.success) {
                        this.students = data.students || [];
                        this.total_records = data.pagination.total_records || 0;
                        
                        this.renderStudentList();
                        this.renderPagination(data.pagination);
                        this.updateStats(data.stats);
                    } else {
                        throw new Error('Failed to fetch students');
                    }
                } catch(error) {
                    console.error('Error fetching students:', error);
                    this.renderError();
                } finally {
                    this.showLoading(false);
                }
            }

            renderStudentList() {
                const container = document.getElementById('studentTableContainer');
                const title = document.getElementById('tableTitle');
                
                title.innerHTML = `<i class="fas fa-table"></i>Student Records (${this.total_records} students)`;

                if (this.students.length === 0) {
                    container.innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-users"></i>
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
                                    <th>Type</th>
                                    <th>Registered</th>
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
                const studentTypeBadge = student.student_type === 'New Student' ? 'bg-success' : 'bg-primary';
                
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
                            <span class="badge bg-info">${student.current_grade_level || 'Not Set'}</span>
                        </td>
                        <td>
                            <span class="badge ${statusBadgeClass}">${student.enrollment_status}</span>
                        </td>
                        <td>
                            <span class="badge ${studentTypeBadge}">
                                ${student.student_type}
                            </span>
                        </td>
                        <td>
                            <div>${new Date(student.date_registered).toLocaleDateString('en-US', {month: 'short', day: '2-digit', year: 'numeric'})}</div>
                            <small class="text-muted">${new Date(student.date_registered).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true})}</small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary"
                                    onclick="studentManager.viewStudent(${student.acc_id})"
                                    data-bs-toggle="modal"
                                    data-bs-target="#studentModal"
                                    title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success"
                                    onclick="studentManager.editStudent(${student.acc_id})"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal"
                                    title="Edit Student">
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
                        <button class="page-link" onclick="studentManager.goToPage(${pagination.current_page - 1})" ${!pagination.has_prev ? 'disabled' : ''}>
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
                            <button class="page-link" onclick="studentManager.goToPage(1)">1</button>
                        </li>
                    `;
                    if (startPage > 2) {
                        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    paginationHTML += `
                        <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                            <button class="page-link" onclick="studentManager.goToPage(${i})">${i}</button>
                        </li>
                    `;
                }

                if (endPage < pagination.total_pages) {
                    if (endPage < pagination.total_pages - 1) {
                        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    paginationHTML += `
                        <li class="page-item">
                            <button class="page-link" onclick="studentManager.goToPage(${pagination.total_pages})">${pagination.total_pages}</button>
                        </li>
                    `;
                }

                // Next button
                paginationHTML += `
                    <li class="page-item ${!pagination.has_next ? 'disabled' : ''}">
                        <button class="page-link" onclick="studentManager.goToPage(${pagination.current_page + 1})" ${!pagination.has_next ? 'disabled' : ''}>
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
            }

            renderError() {
                const container = document.getElementById('studentTableContainer');
                container.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        <h4>Error Loading Students</h4>
                        <p>There was an error loading the student data. Please try again.</p>
                        <button class="btn btn-primary" onclick="studentManager.fetchStudents()">
                            <i class="fas fa-refresh me-1"></i>Retry
                        </button>
                    </div>
                `;
            }

            // Helper methods
            getStudentInitials(student) {
                const name = (student.full_name || '').trim();
                if (!name || name === '  ') {
                    return (student.username || '').substring(0, 2).toUpperCase();
                } else {
                    const names = name.split(' ').filter(n => n.length > 0);
                    if (names.length >= 2) {
                        return (names[0].substring(0, 1) + names[1].substring(0, 1)).toUpperCase();
                    } else {
                        return names[0].substring(0, 2).toUpperCase();
                    }
                }
            }

            getDisplayName(student) {
                const displayName = (student.full_name || '').trim();
                if (!displayName || displayName === '  ') {
                    return student.username;
                } else {
                    return displayName;
                }
            }

            getStatusBadgeClass(status) {
                const statusClasses = {
                    'Enrolled': 'bg-success',
                    'Pending': 'bg-warning',
                    'Not Enrolled': 'bg-secondary',
                    'Dropped Out': 'bg-danger',
                    'Newly Registered': 'bg-info'
                };
                return statusClasses[status] || 'bg-primary';
            }

            viewStudent(accId) {
                const student = this.students.find(s => s.acc_id == accId);
                if (!student) return;

                const isTransferee = student.student_type === 'Transferee';

                document.getElementById('modalContent').innerHTML = `
                    <div class="container-fluid p-0">
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
                                        ${student.grade_level ? `<span class="badge bg-secondary px-3 py-2">${student.grade_level}</span>` : ''}
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
                                    </div>
                                </div>
                            </div>

                            <!-- Parent/Guardian Information -->
                            <div class="col-md-6">
                                <div class="bg-light p-4 rounded h-100">
                                    <h5 class="text-dark mb-3 pb-2 border-bottom border-secondary">
                                        <i class="fas fa-users text-success me-2"></i>Parent/Guardian Information
                                    </h5>
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

            editStudent(accId) {
                const student = this.students.find(s => s.acc_id == accId);
                if (!student) return;

                const isTransferee = student.student_type === 'Transferee';
                console.log(student);

                document.getElementById('editModalContent').innerHTML = `
                    <form id="editStudentForm">
                        <input type="hidden" name="acc_id" value="${student.acc_id}">
                        <input type="hidden" name="personal_id" value="${student.personal_id}">
                        
                        <div class="row">
                            <div class="col-12 mb-4">
                                <h6 class="text-primary">Account Information</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" value="${student.username}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="${student.email}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Enrollment Status</label>
                                <select class="form-select readOnly" name="enrollment_status" readonly>
                                    <option value="Newly Registered" ${student.enrollment_status === 'Newly Registered' ? 'selected' : ''}>Newly Registered</option>
                                    <option value="Pending" ${student.enrollment_status === 'Pending' ? 'selected' : ''}>Pending</option>
                                    <option value="Enrolled" ${student.enrollment_status === 'Enrolled' ? 'selected' : ''}>Enrolled</option>
                                    <option value="Not Enrolled" ${student.enrollment_status === 'Not Enrolled' ? 'selected' : ''}>Not Enrolled</option>
                                    <option value="Dropped Out" ${student.enrollment_status === 'Dropped Out' ? 'selected' : ''}>Dropped Out</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Grade Level</label>
                                <select class="form-select" name="level_id" readonly>
                                    <option value="">Select Grade Level</option>
                                    ${this.gradeLevels.map(level => 
                                        `<option value="${level.fee_id}" ${student.current_level_id == level.fee_id ? 'selected' : ''}>${level.level}</option>`
                                    ).join('')}
                                </select>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-12 mb-3">
                                <h6 class="text-primary">Personal Information</h6>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" value="${student.first_name || ''}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" name="middle_name" value="${student.middle_name || ''}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" value="${student.last_name || ''}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="date_of_birth" value="${student.date_of_birth || ''}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select class="form-select" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male" ${student.gender === 'Male' ? 'selected' : ''}>Male</option>
                                    <option value="Female" ${student.gender === 'Female' ? 'selected' : ''}>Female</option>
                                    <option value="Other" ${student.gender === 'Other' ? 'selected' : ''}>Other</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2">${student.address || ''}</textarea>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-12 mb-3">
                                <h6 class="text-primary">Parent/Guardian Information</h6>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Parent/Guardian Name</label>
                                <input type="text" class="form-control" name="parent_full_name" value="${student.parent_full_name || ''}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" class="form-control" name="contact_num" value="${student.contact_num || ''}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Relationship</label>
                                <select class="form-select" name="relationship">
                                    <option value="">Select Relationship</option>
                                    <option value="Mother" ${student.relationship === 'Mother' ? 'selected' : ''}>Mother</option>
                                    <option value="Father" ${student.relationship === 'Father' ? 'selected' : ''}>Father</option>
                                    <option value="Guardian" ${student.relationship === 'Guardian' ? 'selected' : ''}>Guardian</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Facebook Account</label>
                                <input type="url" class="form-control" name="fb_account" value="${student.fb_account !== 'No provided link' ? student.fb_account || '' : ''}">
                            </div>
                        </div>
                        
                        ${isTransferee ? `
                            <hr>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <h6 class="text-primary">Previous School Information</h6>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Previous School</label>
                                    <input type="text" class="form-control" name="prev_school" value="${student.prev_school || ''}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">School Address</label>
                                    <input type="text" class="form-control" name="prev_address_school" value="${student.prev_address_school || ''}">
                                </div>
                            </div>
                        ` : ''}
                        <hr>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Save Changes
                            </button>
                        </div>
                    </form>
                `;
                this.initializeEditForm();
            }
            initializeEditForm() {
                const form = document.getElementById('editStudentForm');
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const formData = new FormData(form);
                    fetch('../backend/admin/update_student.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Student information updated successfully.',
                                timer: 2000,
                                showConfirmButton: false
                            }).then((result)=>{
                                if(result){
                                    location.reload();
                                }
                            })
                            this.fetchStudents();
                        } else {
                            alert('Error updating student: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the student.');
                    });
                });
            }
        }

        function viewFullImage(imageSrc, imageTitle) {
            document.getElementById('fullImage').src = imageSrc;
            document.getElementById('fullImageModalLabel').textContent = imageTitle;
            const fullImageModal = new bootstrap.Modal(document.getElementById('fullImageModal'));
            fullImageModal.show();
        }

        const studentManager = new StudentManager();
        studentManager.fetchStudents();
    </script>
</body>
</html>




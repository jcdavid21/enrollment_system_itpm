<!DOCTYPE html>
<?php
session_start();
require_once "../backend/config.php";

if($_SESSION["role"] !== "Admin"){
    header("Location: ../components/logout.php");
    exit;
}

// Get requirements for management
$requirements_query = "SELECT * FROM tbl_requirements ORDER BY requirement_id";
$requirements_result = $conn->query($requirements_query);
$requirements = [];
while ($row = $requirements_result->fetch_assoc()) {
    $requirements[] = $row;
}

// Get available years for filtering
$years_query = "SELECT DISTINCT YEAR(date_registered) as year FROM tbl_account WHERE role = 'Student' ORDER BY year DESC";
$years_result = $conn->query($years_query);
$available_years = [];
while ($row = $years_result->fetch_assoc()) {
    $available_years[] = $row['year'];
}

// Get grade levels from tbl_fees
$grade_levels_query = "SELECT * FROM tbl_fees ORDER BY fee_id";
$grade_levels_result = $conn->query($grade_levels_query);
$grade_levels = [];
while ($row = $grade_levels_result->fetch_assoc()) {
    $grade_levels[] = $row;
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
            border-left: 4px solid #9b59b6;
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

        .stat-card.completed {
            border-left-color: #27ae60;
        }

        .stat-card.pending {
            border-left-color: #f39c12;
        }

        .stat-card.incomplete {
            border-left-color: #e74c3c;
        }

        .stat-card.total {
            border-left-color: #3498db;
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

        .section-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .section-card h6 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-card h6 i {
            margin-right: 0.5rem;
        }

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

        .form-control:focus,
        .form-select:focus {
            border-color: #9b59b6;
            box-shadow: 0 0 0 0.2rem rgba(155, 89, 182, 0.25);
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: #64748b;
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

        .requirement-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #9b59b6;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: between;
            align-items: center; 
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-accept {
            background: white;
            border: 1px solid #27ae60;
            color: green;
        }

        .btn-accept:hover {
            background: linear-gradient(135deg, #229954, #27ae60);
            border: 1px solid #27ae60;
            color: white;
        }

        .btn-decline {
            background: white;
            border: 1px solid #c0392b;
            color: red;
        }

        .btn-decline:hover {
            background: linear-gradient(135deg, #c0392b, #a93226);
            border: 1px solid #c0392b;
            color: white;
        }

        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
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
    </style>
    <title>File Requirements Management</title>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-file-alt me-2"></i>File Requirements Management</h1>
            <p>Manage file requirements, track student submissions, and set deadlines.</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid" id="statsContainer">
            <div class="stat-card total">
                <div class="stat-number" id="totalStudents">0</div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card completed">
                <div class="stat-number" id="completedCount">0</div>
                <div class="stat-label">Complete Requirements</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number" id="pendingCount">0</div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card incomplete">
                <div class="stat-number" id="incompleteCount">0</div>
                <div class="stat-label">Incomplete Requirements</div>
            </div>
        </div>

        <!-- Requirements Management Section -->
        <div class="section-card">
            <h6>
                <span><i class="fas fa-list-check"></i>Manage Requirements</span>
                <button class="btn btn-primary btn-sm" onclick="requirementManager.showAddRequirementModal()">
                    <i class="fas fa-plus me-1"></i>Add Requirement
                </button>
            </h6>
            <div id="requirementsList">
                <!-- Requirements will be loaded here -->
            </div>
        </div>

        <!-- Deadline Management Section -->
        <div class="section-card">
            <h6><i class="fas fa-calendar-alt"></i>Announcements</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Deadline Date</label>
                    <input type="date" class="form-control" id="deadlineDate">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Message (optional)</label>
                    <input type="text" class="form-control" id="deadlineMessage" 
                           placeholder="Custom message for students...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100" onclick="requirementManager.setDeadline()">
                        <i class="fas fa-paper-plane me-1"></i>Set & Notify
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="section-card">
            <h6><i class="fas fa-filter"></i>Filter Students</h6>
            <div class="row g-3" id="filterForm">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status">
                        <option value="all">All Status</option>
                        <option value="complete">Complete</option>
                        <option value="incomplete" selected>Incomplete</option>
                        <option value="pending">Pending Review</option>
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
                    <label class="form-label">Grade Level</label>
                    <select class="form-select" id="gradeLevel">
                        <option value="all">All Grades</option>
                        <?php foreach ($grade_levels as $grade): ?>
                            <option value="<?php echo $grade['fee_id']; ?>">
                                <?php echo $grade['level']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" placeholder="Student name or username...">
                        <button class="btn btn-primary" type="button" id="searchBtn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button class="btn btn-outline-secondary w-100" id="resetBtn">
                            <i class="fas fa-refresh"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="table-card position-relative">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            
            <div class="table-header">
                <h5 id="tableTitle"><i class="fas fa-users"></i>Students Requirements Status (0 students)</h5>
            </div>

            <div id="studentsTableContainer">
                <!-- Table content will be loaded here -->
            </div>

            <!-- Pagination -->
            <div class="pagination-container" id="paginationContainer">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Add/Edit Requirement Modal -->
    <div class="modal fade" id="requirementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="requirementModalTitle">Add Requirement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="requirementForm">
                        <input type="hidden" id="requirementId">
                        <div class="mb-3">
                            <label class="form-label">Requirement Name</label>
                            <input type="text" class="form-control" id="requirementName" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="requirementManager.saveRequirement()">
                        Save Requirement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Student Requirements Modal -->
    <div class="modal fade" id="studentModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalTitle">Student Requirements</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="studentModalContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        class RequirementManager {
            constructor() {
                this.current_page = 1;
                this.records_per_page = 10;
                this.students = [];
                this.requirements = [];
                this.total_records = 0;
                this.init();
            }

            async init() {
                await this.loadRequirements();
                await this.fetchStudents();
                this.initEventListeners();
            }

            initEventListeners() {
                // Filter change events
                ['status', 'year', 'gradeLevel'].forEach(id => {
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
                document.getElementById('status').value = 'incomplete';
                document.getElementById('year').value = 'all';
                document.getElementById('gradeLevel').value = 'all';
                document.getElementById('search').value = '';
                this.current_page = 1;
                this.fetchStudents();
            }

            showLoading(show = true) {
                const overlay = document.getElementById('loadingOverlay');
                overlay.style.display = show ? 'flex' : 'none';
            }

            async loadRequirements() {
                try {
                    const response = await fetch('./requirements/get_requirements.php');
                    const data = await response.json();
                    
                    if (data.success) {
                        this.requirements = data.requirements;
                        this.renderRequirements();
                    }
                } catch (error) {
                    console.error('Error loading requirements:', error);
                }
            }

            renderRequirements() {
                const container = document.getElementById('requirementsList');
                
                if (this.requirements.length === 0) {
                    container.innerHTML = `
                        <div class="text-muted text-center py-3">
                            No requirements found. Add your first requirement.
                        </div>
                    `;
                    return;
                }

                let html = '';
                this.requirements.forEach(req => {
                    html += `
                        <div class="requirement-item">
                            <div>
                                <strong>${req.requirement_name}</strong>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" 
                                        onclick="requirementManager.editRequirement(${req.requirement_id}, '${req.requirement_name}')"
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger" 
                                        onclick="requirementManager.deleteRequirement(${req.requirement_id})"
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = html;
            }

            showAddRequirementModal() {
                document.getElementById('requirementModalTitle').textContent = 'Add Requirement';
                document.getElementById('requirementId').value = '';
                document.getElementById('requirementName').value = '';
                new bootstrap.Modal(document.getElementById('requirementModal')).show();
            }

            editRequirement(id, name) {
                document.getElementById('requirementModalTitle').textContent = 'Edit Requirement';
                document.getElementById('requirementId').value = id;
                document.getElementById('requirementName').value = name;
                new bootstrap.Modal(document.getElementById('requirementModal')).show();
            }

            async saveRequirement() {
                const id = document.getElementById('requirementId').value;
                const name = document.getElementById('requirementName').value.trim();

                if (!name) {
                    Swal.fire('Error', 'Please enter a requirement name', 'error');
                    return;
                }

                try {
                    const response = await fetch('./requirements/save_requirements.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            requirement_id: id,
                            requirement_name: name
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('Success', 'Requirement saved successfully', 'success');
                        bootstrap.Modal.getInstance(document.getElementById('requirementModal')).hide();
                        await this.loadRequirements();
                    } else {
                        throw new Error(data.message || 'Failed to save requirement');
                    }
                } catch (error) {
                    console.error('Error saving requirement:', error);
                    Swal.fire('Error', 'Failed to save requirement', 'error');
                }
            }

            async deleteRequirement(id) {
                const result = await Swal.fire({
                    title: 'Delete Requirement?',
                    text: 'This will remove the requirement for all students. This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74c3c',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete'
                });

                if (result.isConfirmed) {
                    try {
                        const response = await fetch('./requirements/delete_requirement.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ requirement_id: id })
                        });

                        const data = await response.json();

                        if (data.success) {
                            Swal.fire('Deleted', 'Requirement deleted successfully', 'success');
                            await this.loadRequirements();
                            await this.fetchStudents();
                        } else {
                            throw new Error(data.message || 'Failed to delete requirement');
                        }
                    } catch (error) {
                        console.error('Error deleting requirement:', error);
                        Swal.fire('Error', 'Failed to delete requirement', 'error');
                    }
                }
            }

            async setDeadline() {
                const date = document.getElementById('deadlineDate').value;
                const message = document.getElementById('deadlineMessage').value;

                if (!date) {
                    Swal.fire('Error', 'Please select a deadline date', 'error');
                    return;
                }

                const result = await Swal.fire({
                    title: 'Set Requirements Deadline?',
                    text: 'This will send email notifications to all students with incomplete requirements.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#f39c12',
                    confirmButtonText: 'Set Deadline & Send Emails'
                });

                if (result.isConfirmed) {
                    try {
                        Swal.fire({
                            title: 'Sending emails...',
                            text: 'Please wait while we notify students.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        const response = await fetch('./requirements/set_deadline.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                deadline_date: date,
                                custom_message: message
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            Swal.fire({
                                title: 'Deadline Set!',
                                text: `Deadline set for ${new Date(date).toLocaleDateString()}. ${data.emails_sent} email notifications sent.`,
                                icon: 'success'
                            });
                            
                            document.getElementById('deadlineDate').value = '';
                            document.getElementById('deadlineMessage').value = '';
                        } else {
                            throw new Error(data.message || 'Failed to set deadline');
                        }
                    } catch (error) {
                        console.error('Error setting deadline:', error);
                        Swal.fire('Error', 'Failed to set deadline and send notifications', 'error');
                    }
                }
            }

            async fetchStudents() {
                try {
                    this.showLoading(true);
                    
                    const params = new URLSearchParams({
                        page: this.current_page,
                        limit: this.records_per_page,
                        status: document.getElementById('status')?.value || 'incomplete',
                        year: document.getElementById('year')?.value || 'all',
                        grade_level: document.getElementById('gradeLevel')?.value || 'all',
                        search: document.getElementById('search')?.value || ''
                    });

                    const response = await fetch(`./requirements/get_students.php?${params}`);
                    const data = await response.json();

                    if (data.success) {
                        this.students = data.students || [];
                        this.total_records = data.pagination.total_records || 0;
                        
                        this.renderStudentsList();
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

            renderStudentsList() {
                const container = document.getElementById('studentsTableContainer');
                const title = document.getElementById('tableTitle');
                
                title.innerHTML = `<i class="fas fa-users"></i>Students Requirements Status (${this.total_records} students)`;

                if (this.students.length === 0) {
                    container.innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-file-alt"></i>
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
                                    <th>Grade & Section</th>
                                    <th>Requirements Progress</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
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
                const completedReqs = parseInt(student.completed_requirements);
                const totalReqs = parseInt(student.total_requirements);
                const progress = totalReqs > 0 ? Math.round((completedReqs / totalReqs) * 100) : 0;
                
                let statusBadge = '';
                let statusClass = '';
                
                if (completedReqs === totalReqs && totalReqs > 0) {
                    statusBadge = '<span class="badge bg-success">Complete</span>';
                    statusClass = '';
                } else if (student.pending_requirements > 0) {
                    statusBadge = '<span class="badge bg-warning">Pending Review</span>';
                    statusClass = '';
                } else {
                    statusBadge = '<span class="badge bg-danger">Incomplete</span>';
                    statusClass = '';
                }

                return `
                    <tr class="${statusClass}">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar me-3">
                                    ${this.getStudentInitials(student)}
                                </div>
                                <div>
                                    <div class="fw-bold">${student.full_name}</div>
                                    <small class="text-muted">@${student.username}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-medium">${student.level || 'Not assigned'}</div>
                            <small class="text-muted">${student.section || 'No section'}</small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="progress me-2 flex-grow-1" style="height: 8px;">
                                    <div class="progress-bar ${progress === 100 ? 'bg-success' : progress >= 50 ? 'bg-warning' : 'bg-danger'}" 
                                         role="progressbar" style="width: ${progress}%"></div>
                                </div>
                                <small class="fw-bold">${completedReqs}/${totalReqs}</small>
                            </div>
                            <small class="text-muted">${progress}% complete</small>
                        </td>
                        <td>${statusBadge}</td>
                        <td>
                            <div>${student.last_updated ? new Date(student.last_updated).toLocaleDateString('en-US', {month: 'short', day: '2-digit', year: 'numeric'}) : 'Never'}</div>
                            <small class="text-muted">${student.last_updated ? new Date(student.last_updated).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true}) : ''}</small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary"
                                    onclick="requirementManager.viewStudentRequirements(${student.acc_id})"
                                    data-bs-toggle="modal"
                                    data-bs-target="#studentModal"
                                    title="View Requirements">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-info"
                                    onclick="requirementManager.sendReminder(${student.acc_id})"
                                    title="Send Reminder">
                                    <i class="fas fa-paper-plane"></i>
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
                        <button class="page-link" onclick="requirementManager.goToPage(${pagination.current_page - 1})" ${!pagination.has_prev ? 'disabled' : ''}>
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
                            <button class="page-link" onclick="requirementManager.goToPage(1)">1</button>
                        </li>
                    `;
                    if (startPage > 2) {
                        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    paginationHTML += `
                        <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                            <button class="page-link" onclick="requirementManager.goToPage(${i})">${i}</button>
                        </li>
                    `;
                }

                if (endPage < pagination.total_pages) {
                    if (endPage < pagination.total_pages - 1) {
                        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    paginationHTML += `
                        <li class="page-item">
                            <button class="page-link" onclick="requirementManager.goToPage(${pagination.total_pages})">${pagination.total_pages}</button>
                        </li>
                    `;
                }

                // Next button
                paginationHTML += `
                    <li class="page-item ${!pagination.has_next ? 'disabled' : ''}">
                        <button class="page-link" onclick="requirementManager.goToPage(${pagination.current_page + 1})" ${!pagination.has_next ? 'disabled' : ''}>
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
                document.getElementById('completedCount').textContent = stats.completed_count;
                document.getElementById('pendingCount').textContent = stats.pending_count;
                document.getElementById('incompleteCount').textContent = stats.incomplete_count;
            }

            renderError() {
                const container = document.getElementById('studentsTableContainer');
                container.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        <h4>Error Loading Students</h4>
                        <p>There was an error loading the student data. Please try again.</p>
                        <button class="btn btn-primary" onclick="requirementManager.fetchStudents()">
                            <i class="fas fa-refresh me-1"></i>Retry
                        </button>
                    </div>
                `;
            }

            async viewStudentRequirements(accId) {
                try {
                    const response = await fetch(`./requirements/get_student_requirements.php?acc_id=${accId}`);
                    const data = await response.json();

                    if (data.success) {
                        this.renderStudentModal(data.student, data.requirements);
                    } else {
                        throw new Error(data.message || 'Failed to load student requirements');
                    }
                } catch (error) {
                    console.error('Error loading student requirements:', error);
                    Swal.fire('Error', 'Failed to load student requirements', 'error');
                }
            }

            renderStudentModal(student, requirements) {
                document.getElementById('studentModalTitle').textContent = `${student.full_name} - Requirements Status`;

                const completedCount = requirements.filter(r => r.status === 'Accepted').length;
                const totalCount = requirements.length;
                const progress = totalCount > 0 ? Math.round((completedCount / totalCount) * 100) : 0;

                let modalHTML = `
                    <div class="container-fluid p-0">
                        <!-- Student Info Header -->
                        <div class="bg-light p-3 rounded mb-4 border-start border-primary border-4">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="avatar-large bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: 600;">
                                        ${this.getStudentInitials(student)}
                                    </div>
                                </div>
                                <div class="col">
                                    <h4 class="mb-1">${student.full_name}</h4>
                                    <p class="text-muted mb-2">
                                        <span class="me-3"><i class="fas fa-user me-1"></i>@${student.username}</span>
                                        <span class="me-3"><i class="fas fa-envelope me-1"></i>${student.email}</span>
                                        <span><i class="fas fa-graduation-cap me-1"></i>${student.level} - ${student.section}</span>
                                    </p>
                                    <div class="d-flex align-items-center">
                                        <div class="progress me-3" style="width: 200px; height: 10px;">
                                            <div class="progress-bar ${progress === 100 ? 'bg-success' : progress >= 50 ? 'bg-warning' : 'bg-danger'}" 
                                                 role="progressbar" style="width: ${progress}%"></div>
                                        </div>
                                        <span class="fw-bold">${completedCount}/${totalCount} Complete (${progress}%)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Requirements List -->
                        <div class="row">
                `;

                requirements.forEach((req, index) => {
                    let statusBadge = '';
                    let statusClass = '';
                    let actionButtons = '';

                    switch (req.status) {
                        case 'Accepted':
                            statusBadge = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Accepted</span>';
                            statusClass = 'border-success';
                            break;
                        case 'Pending':
                            statusBadge = '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Pending Review</span>';
                            statusClass = 'border-warning';
                            actionButtons = `
                                <div>
                                    <div class="btn-group btn-group-sm mt-2">
                                        <button class="btn btn-accept" onclick="requirementManager.updateRequirementStatus(${student.acc_id}, ${req.requirement_id}, 'Accepted')">
                                            <i class="fas fa-check me-1"></i>Accept
                                        </button>
                                    </div>
                                </div>
                            `;
                            break;
                        case 'Declined':
                            statusBadge = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Declined</span>';
                            statusClass = 'border-danger';
                            break;
                        case 'Verifying':
                            statusBadge = '<span class="badge bg-info"><i class="fas fa-search me-1"></i>Verifying</span>';
                            statusClass = 'border-info';
                            actionButtons = `
                                <div class="btn-group btn-group-sm mt-2">
                                    <button class="btn btn-accept" onclick="requirementManager.updateRequirementStatus(${student.acc_id}, ${req.requirement_id}, 'Accepted')">
                                        <i class="fas fa-check me-1"></i>Accept
                                    </button>
                                    <button class="btn btn-decline" onclick="requirementManager.updateRequirementStatus(${student.acc_id}, ${req.requirement_id}, 'Declined')">
                                        <i class="fas fa-times me-1"></i>Decline
                                    </button>
                                </div>
                            `;
                            break;
                        default:
                            statusBadge = '<span class="badge bg-secondary"><i class="fas fa-minus me-1"></i>Not Submitted</span>';
                            statusClass = 'border-secondary';
                    }

                    modalHTML += `
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 ${statusClass}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">${req.requirement_name}</h6>
                                        ${statusBadge}
                                    </div>
                                    ${req.submitted_at ? `
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            Submitted: ${new Date(req.submitted_at).toLocaleDateString('en-US', {
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric',
                                                hour: 'numeric',
                                                minute: '2-digit',
                                                hour12: true
                                            })}
                                        </small>
                                    ` : `
                                        <small class="text-muted"><i class="fas fa-minus me-1"></i>Not submitted</small>
                                    `}
                                    ${actionButtons}
                                </div>
                            </div>
                        </div>
                    `;
                });

                modalHTML += `
                        </div>
                    </div>
                `;

                document.getElementById('studentModalContent').innerHTML = modalHTML;
            }

            async updateRequirementStatus(accId, reqId, status) {
                try {
                    const confirmResult = await Swal.fire({
                        title: `Mark as ${status}?`,
                        text: `Are you sure you want to mark this requirement as ${status.toLowerCase()}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: status === 'Accepted' ? '#28a745' : '#dc3545',
                        confirmButtonText: `Yes, ${status}`
                    }); 

                    if (!confirmResult.isConfirmed) {
                        return;
                    }

                    const response = await fetch('./requirements/update_requirement_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            acc_id: accId,
                            requirement_id: reqId,
                            status: status
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            title: 'Status Updated!',
                            text: `Requirement has been ${status.toLowerCase()}.`,
                            icon: 'success',
                            timer: 1500
                        });

                        // Refresh the modal and main table
                        await this.viewStudentRequirements(accId);
                        await this.fetchStudents();
                    } else {
                        throw new Error(data.message || 'Failed to update status');
                    }
                } catch (error) {
                    console.error('Error updating requirement status:', error);
                    Swal.fire('Error', 'Failed to update requirement status', 'error');
                }
            }

            async sendReminder(accId) {
                const result = await Swal.fire({
                    title: 'Send Reminder?',
                    text: 'This will send an email reminder to the student about their incomplete requirements.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3498db',
                    confirmButtonText: 'Send Reminder'
                });

                if (result.isConfirmed) {
                    try {
                        Swal.fire({
                            title: 'Sending reminder...',
                            text: 'Please wait.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        const response = await fetch('./requirements/send_reminder.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ acc_id: accId })
                        });

                        const data = await response.json();

                        if (data.success) {
                            Swal.fire({
                                title: 'Reminder Sent!',
                                text: 'Email reminder has been sent to the student.',
                                icon: 'success'
                            });
                        } else {
                            throw new Error(data.message || 'Failed to send reminder');
                        }
                    } catch (error) {
                        console.error('Error sending reminder:', error);
                        Swal.fire('Error', 'Failed to send reminder email', 'error');
                    }
                }
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
        }

        // Initialize the requirement manager
        let requirementManager;
        document.addEventListener('DOMContentLoaded', function() {
            requirementManager = new RequirementManager();
        });
    </script>
</body>
</html>
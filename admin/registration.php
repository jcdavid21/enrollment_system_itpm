<!DOCTYPE html>
<?php
session_start();
require_once "../backend/config.php";

if ($_SESSION["role"] !== "Admin") {
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
            border-left: 4px solid #e67e22;
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

        .stat-card.pending {
            border-left-color: #f39c12;
        }

        .stat-card.accepted {
            border-left-color: #27ae60;
        }

        .stat-card.declined {
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
            background: linear-gradient(135deg, #e67e22, #d35400);
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
            border-color: #e67e22;
            box-shadow: 0 0 0 0.2rem rgba(230, 126, 34, 0.25);
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: #64748b;
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

        .border-warning {
            border-color: #f39c12 !important;
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

        .parent-id-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            cursor: pointer;
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
    </style>
    <title>Registration Management</title>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-user-plus me-2"></i>Registration Management</h1>
            <p>Review and manage newly registered student applications.</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid" id="statsContainer">
            <div class="stat-card total">
                <div class="stat-number" id="totalRegistrations">0</div>
                <div class="stat-label">Total Registrations</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number" id="pendingCount">0</div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card accepted">
                <div class="stat-number" id="acceptedCount">0</div>
                <div class="stat-label">Accepted</div>
            </div>
            <div class="stat-card declined">
                <div class="stat-number" id="declinedCount">0</div>
                <div class="stat-label">Declined</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <h6><i class="fas fa-filter"></i>Filter Registrations</h6>
            <div class="row g-3" id="filterForm">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="status">
                        <option value="all">All Status</option>
                        <option value="1" selected>Pending Review</option>
                        <option value="2">Accepted</option>
                        <option value="0">Declined</option>
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
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" placeholder="Name, username, or email...">
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

        <!-- Registration Table -->
        <div class="table-card position-relative">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner-border text-warning" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div class="table-header">
                <h5 id="tableTitle"><i class="fas fa-clipboard-list"></i>Registration Applications (0 applications)</h5>
            </div>

            <div id="registrationTableContainer">
                <!-- Table content will be loaded here -->
            </div>

            <!-- Pagination -->
            <div class="pagination-container" id="paginationContainer">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>

    <!-- View Registration Modal -->
    <div class="modal fade" id="registrationModal" tabindex="-1" aria-labelledby="registrationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registrationModalLabel">Registration Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer" id="modalFooter">
                    <!-- Action buttons will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Full Image Modal -->
    <div class="modal fade" id="fullImageModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fullImageModalLabel">Parent ID Verification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="fullImage" src="" class="img-fluid" alt="Parent ID" style="max-height: 80vh;">
                </div>
            </div>
        </div>
    </div>

    <script>
        class RegistrationManager {
            constructor() {
                this.current_page = 1;
                this.records_per_page = 10;
                this.registrations = [];
                this.current_registration = null;
                this.total_records = 0;
                this.init();
            }

            async init() {
                await this.fetchRegistrations();
                this.initEventListeners();
            }

            initEventListeners() {
                // Filter change events
                ['status', 'year', 'type'].forEach(id => {
                    document.getElementById(id).addEventListener('change', () => {
                        this.current_page = 1;
                        this.fetchRegistrations();
                    });
                });

                // Search functionality
                document.getElementById('searchBtn').addEventListener('click', () => {
                    this.current_page = 1;
                    this.fetchRegistrations();
                });

                document.getElementById('search').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.current_page = 1;
                        this.fetchRegistrations();
                    }
                });

                // Reset button
                document.getElementById('resetBtn').addEventListener('click', () => {
                    this.resetFilters();
                });
            }

            resetFilters() {
                document.getElementById('status').value = '1';
                document.getElementById('year').value = 'all';
                document.getElementById('type').value = 'all';
                document.getElementById('search').value = '';
                this.current_page = 1;
                this.fetchRegistrations();
            }

            showLoading(show = true) {
                const overlay = document.getElementById('loadingOverlay');
                overlay.style.display = show ? 'flex' : 'none';
            }

            async fetchRegistrations() {
                try {
                    this.showLoading(true);

                    const params = new URLSearchParams({
                        page: this.current_page,
                        limit: this.records_per_page,
                        status: document.getElementById('status')?.value || '1',
                        year: document.getElementById('year')?.value || 'all',
                        student_type: document.getElementById('type')?.value || 'all',
                        search: document.getElementById('search')?.value || ''
                    });

                    const response = await fetch(`./registration_lists/get_registrations.php?${params}`);
                    const data = await response.json();

                    if (data.success) {
                        this.registrations = data.registrations || [];
                        this.total_records = data.pagination.total_records || 0;

                        this.renderRegistrationList();
                        this.renderPagination(data.pagination);
                        this.updateStats(data.stats);
                    } else {
                        throw new Error('Failed to fetch registrations');
                    }
                } catch (error) {
                    console.error('Error fetching registrations:', error);
                    this.renderError();
                } finally {
                    this.showLoading(false);
                }
            }

            renderRegistrationList() {
                const container = document.getElementById('registrationTableContainer');
                const title = document.getElementById('tableTitle');

                title.innerHTML = `<i class="fas fa-clipboard-list"></i>Registration Applications (${this.total_records} applications)`;

                if (this.registrations.length === 0) {
                    container.innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-user-plus"></i>
                            <h4>No registrations found</h4>
                            <p>No registrations match your current filter criteria.</p>
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
                                    <th>Parent/Guardian</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                this.registrations.forEach(registration => {
                    tableHTML += this.renderRegistrationRow(registration);
                });

                tableHTML += `
                            </tbody>
                        </table>
                    </div>
                `;

                container.innerHTML = tableHTML;
            }

            renderRegistrationRow(registration) {
                const statusBadgeClass = this.getStatusBadgeClass(registration.reg_acc_status);
                const statusText = this.getStatusText(registration.reg_acc_status);
                const studentTypeBadge = registration.student_type === 'New Student' ? 'bg-success' : 'bg-primary';

                return `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar me-3">
                                    ${this.getStudentInitials(registration)}
                                </div>
                                <div>
                                    <div class="fw-bold">
                                        ${this.getDisplayName(registration)}
                                    </div>
                                    <small class="text-muted">ID: ${registration.acc_id}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>${registration.email}</div>
                            <small class="text-muted">@${registration.username}</small>
                        </td>
                        <td>
                            <div class="fw-medium">${registration.parent_full_name || 'Not provided'}</div>
                            <small class="text-muted">${registration.contact_num || 'No contact'}</small>
                        </td>
                        <td>
                            <span class="badge ${statusBadgeClass}">${statusText}</span>
                        </td>
                        <td>
                            <span class="badge ${studentTypeBadge}">
                                ${registration.student_type}
                            </span>
                        </td>
                        <td>
                            <div>${new Date(registration.date_registered).toLocaleDateString('en-US', {month: 'short', day: '2-digit', year: 'numeric'})}</div>
                            <small class="text-muted">${new Date(registration.date_registered).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true})}</small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary"
                                    onclick="registrationManager.viewRegistration(${registration.acc_id})"
                                    data-bs-toggle="modal"
                                    data-bs-target="#registrationModal"
                                    title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                ${registration.reg_acc_status == 1 ? `
                                    <button class="btn btn-accept"
                                        onclick="registrationManager.acceptRegistration(${registration.acc_id})"
                                        title="Accept Registration">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-decline"
                                        onclick="registrationManager.declineRegistration(${registration.acc_id})"
                                        title="Decline Registration">
                                        <i class="fas fa-times"></i>
                                    </button>
                                ` : ''}
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
                        <button class="page-link" onclick="registrationManager.goToPage(${pagination.current_page - 1})" ${!pagination.has_prev ? 'disabled' : ''}>
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
                            <button class="page-link" onclick="registrationManager.goToPage(1)">1</button>
                        </li>
                    `;
                    if (startPage > 2) {
                        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    paginationHTML += `
                        <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                            <button class="page-link" onclick="registrationManager.goToPage(${i})">${i}</button>
                        </li>
                    `;
                }

                if (endPage < pagination.total_pages) {
                    if (endPage < pagination.total_pages - 1) {
                        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    paginationHTML += `
                        <li class="page-item">
                            <button class="page-link" onclick="registrationManager.goToPage(${pagination.total_pages})">${pagination.total_pages}</button>
                        </li>
                    `;
                }

                // Next button
                paginationHTML += `
                    <li class="page-item ${!pagination.has_next ? 'disabled' : ''}">
                        <button class="page-link" onclick="registrationManager.goToPage(${pagination.current_page + 1})" ${!pagination.has_next ? 'disabled' : ''}>
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
                    this.fetchRegistrations();
                }
            }

            updateStats(stats) {
                document.getElementById('totalRegistrations').textContent = stats.total_registrations;
                document.getElementById('pendingCount').textContent = stats.pending_count;
                document.getElementById('acceptedCount').textContent = stats.accepted_count;
                document.getElementById('declinedCount').textContent = stats.declined_count;
            }

            renderError() {
                const container = document.getElementById('registrationTableContainer');
                container.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        <h4>Error Loading Registrations</h4>
                        <p>There was an error loading the registration data. Please try again.</p>
                        <button class="btn btn-primary" onclick="registrationManager.fetchRegistrations()">
                            <i class="fas fa-refresh me-1"></i>Retry
                        </button>
                    </div>
                `;
            }

            // Helper methods
            getStudentInitials(registration) {
                const name = (registration.full_name || '').trim();
                if (!name || name === '  ') {
                    return (registration.username || '').substring(0, 2).toUpperCase();
                } else {
                    const names = name.split(' ').filter(n => n.length > 0);
                    if (names.length >= 2) {
                        return (names[0].substring(0, 1) + names[1].substring(0, 1)).toUpperCase();
                    } else {
                        return names[0].substring(0, 2).toUpperCase();
                    }
                }
            }

            getDisplayName(registration) {
                const displayName = (registration.full_name || '').trim();
                if (!displayName || displayName === '  ') {
                    return registration.username;
                } else {
                    return displayName;
                }
            }

            getStatusBadgeClass(status) {
                const statusClasses = {
                    '1': 'bg-warning',
                    '2': 'bg-success',
                    '0': 'bg-danger'
                };
                return statusClasses[status] || 'bg-secondary';
            }

            getStatusText(status) {
                const statusTexts = {
                    '1': 'Pending Review',
                    '2': 'Accepted',
                    '0': 'Declined'
                };
                return statusTexts[status] || 'Unknown';
            }

            viewRegistration(accId) {
                const registration = this.registrations.find(r => r.acc_id == accId);
                if (!registration) return;

                const isTransferee = registration.student_type === 'Transferee';

                document.getElementById('modalContent').innerHTML = `
                    <div class="container-fluid p-0">
                        <!-- Header Section -->
                        <div class="bg-light p-3 rounded mb-4 border-start border-warning border-4">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="avatar-large bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: 600;">
                                        ${this.getStudentInitials(registration)}
                                    </div>
                </div>
                <div class="col">
                    <h4 class="mb-1">${this.getDisplayName(registration)}</h4>
                    <p class="text-muted mb-2">Account ID: ${registration.acc_id}</p>
                    <div class="d-flex gap-2">
                        <span class="badge ${this.getStatusBadgeClass(registration.reg_acc_status)} fs-6">
                            ${this.getStatusText(registration.reg_acc_status)}
                        </span>
                        <span class="badge ${registration.student_type === 'New Student' ? 'bg-success' : 'bg-primary'} fs-6">
                            ${registration.student_type}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Personal Information -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="info-item mb-3">
                            <strong>Full Name:</strong>
                            <div class="mt-1">${this.getDisplayName(registration)}</div>
                        </div>
                        <div class="info-item mb-3">
                            <strong>Date of Birth:</strong>
                            <div class="mt-1">${registration.date_of_birth ? new Date(registration.date_of_birth).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric'}) : 'Not provided'}</div>
                        </div>
                        <div class="info-item mb-3">
                            <strong>Gender:</strong>
                            <div class="mt-1">${registration.gender || 'Not provided'}</div>
                        </div>
                        <div class="info-item">
                            <strong>Address:</strong>
                            <div class="mt-1">${registration.address || 'Not provided'}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-user-circle me-2"></i>Account Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="info-item mb-3">
                            <strong>Username:</strong>
                            <div class="mt-1">@${registration.username}</div>
                        </div>
                        <div class="info-item mb-3">
                            <strong>Email Address:</strong>
                            <div class="mt-1">${registration.email}</div>
                        </div>
                        <div class="info-item mb-3">
                            <strong>Registration Date:</strong>
                            <div class="mt-1">${new Date(registration.date_registered).toLocaleDateString('en-US', {year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true})}</div>
                        </div>
                        <div class="info-item">
                            <strong>Student Type:</strong>
                            <div class="mt-1">${registration.student_type}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parent/Guardian Information -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Parent/Guardian Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <strong>Parent/Guardian Name:</strong>
                                            <div class="mt-1">${registration.parent_full_name || 'Not provided'}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <strong>Relationship:</strong>
                                            <div class="mt-1">${registration.relationship || 'Not provided'}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <strong>Contact Number:</strong>
                                            <div class="mt-1">${registration.contact_num || 'Not provided'}</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="info-item">
                                            <strong>Facebook Account:</strong>
                                            <div class="mt-1">
                                                ${registration.fb_account && registration.fb_account !== 'No provided link' ? 
                                                    `<a href="${registration.fb_account}" target="_blank" class="text-primary">${registration.fb_account}</a>` : 
                                                    'Not provided'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <strong class="d-block mb-2">Parent ID Verification:</strong>
                                    ${registration.parent_temp_id && registration.parent_temp_id !== 'No image uploaded yet' ? `
                                        <div class="document-card">
                                            <img src="../assets/enrollment_img/${registration.parent_temp_id}" 
                                                 class="parent-id-preview img-thumbnail" 
                                                 alt="Parent ID"
                                                 onclick="registrationManager.showFullImage('../assets/enrollment_img/${registration.parent_temp_id}')">
                                            <div class="mt-2">
                                                <small class="text-muted">Click to view full size</small>
                                            </div>
                                        </div>
                                    ` : `
                                        <div class="border rounded p-3 text-muted">
                                            <i class="fas fa-image fa-2x mb-2"></i>
                                            <div>No ID uploaded</div>
                                        </div>
                                    `}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
                `;

                // Modal footer with action buttons
                const footerHTML = registration.reg_acc_status == 1 ? `
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-decline me-2" onclick="registrationManager.declineRegistration(${registration.acc_id})">
                        <i class="fas fa-times me-1"></i>Decline Registration
                    </button>
                    <button type="button" class="btn btn-accept" onclick="registrationManager.acceptRegistration(${registration.acc_id})">
                        <i class="fas fa-check me-1"></i>Accept Registration
                    </button>
                ` : `
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                `;

                document.getElementById('modalFooter').innerHTML = footerHTML;
            }

            showFullImage(imageSrc) {
                document.getElementById('fullImage').src = imageSrc;
                new bootstrap.Modal(document.getElementById('fullImageModal')).show();
            }

            async acceptRegistration(accId) {
                const result = await Swal.fire({
                    title: 'Accept Registration?',
                    text: 'Are you sure you want to accept this student registration?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#27ae60',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-check me-1"></i>Yes, Accept',
                    cancelButtonText: 'Cancel'
                });

                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Processing...',
                        html: 'Accepting registration and sending notification email...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    try {
                        const response = await fetch('./registration_lists/update_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                acc_id: accId,
                                status: 2
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            Swal.close();
                            await Swal.fire({
                                title: 'Registration Accepted!',
                                text: 'The student registration has been successfully accepted.',
                                icon: 'success',
                                confirmButtonColor: '#27ae60'
                            }).then((result) => {
                                if (result) {
                                    window.location.reload();
                                }
                            })
                        } else {
                            throw new Error(data.message || 'Failed to accept registration');
                        }
                    } catch (error) {
                        console.error('Error accepting registration:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: 'There was an error accepting the registration. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#e74c3c'
                        });
                    }
                }
            }

            async declineRegistration(accId) {
                const result = await Swal.fire({
                    title: 'Decline Registration?',
                    text: 'Are you sure you want to decline this student registration?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74c3c',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="fas fa-times me-1"></i>Yes, Decline',
                    cancelButtonText: 'Cancel'
                });

                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Processing...',
                        html: 'Declining registration and sending notification email...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    try {
                        const response = await fetch('./registration_lists/update_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                acc_id: accId,
                                status: 0
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            Swal.close();
                            await Swal.fire({
                                title: 'Registration Declined!',
                                text: 'The student registration has been declined and a notification email has been sent.',
                                icon: 'success',
                                confirmButtonColor: '#e74c3c'
                            }).then((result) => {
                                if (result) {
                                    window.location.reload();
                                }
                            });
                        } else {
                            throw new Error(data.message || 'Failed to decline registration');
                        }
                    } catch (error) {
                        console.error('Error declining registration:', error);
                        Swal.close();
                        Swal.fire({
                            title: 'Error!',
                            text: error.message || 'There was an error declining the registration. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#e74c3c'
                        });
                    }
                }
            }
        }

        // Initialize the registration manager
        let registrationManager;
        document.addEventListener('DOMContentLoaded', function() {
            registrationManager = new RegistrationManager();
        });
    </script>
</body>

</html>
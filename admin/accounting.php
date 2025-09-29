<!DOCTYPE html>
<?php
session_start();
require_once "../backend/config.php";

if($_SESSION["role"] !== "Admin"){
    header("Location: ../components/logout.php");
    exit;
}

// Get available years from payments
$years_query = "SELECT DISTINCT YEAR(payment_date) as year FROM tbl_payments ORDER BY year DESC";
$years_result = $conn->query($years_query);
$available_years = [];
while ($row = $years_result->fetch_assoc()) {
    $available_years[] = $row['year'];
}

// Get enrollment data for dropdowns
$enrollments_query = "SELECT e.enrollment_id, CONCAT(pd.first_name, ' ', pd.last_name) as student_name, f.level, e.school_year 
                      FROM tbl_enrollments e 
                      JOIN tbl_personal_details pd ON e.student_id = pd.personal_id 
                      LEFT JOIN tbl_new_old_students ns ON pd.personal_id = ns.personal_id
                      LEFT JOIN tbl_student_transferee st ON pd.personal_id = st.personal_id
                        JOIN tbl_fees f ON e.current_level_id = f.fee_id
                        WHERE (ns.personal_id IS NOT NULL OR st.personal_id IS NOT NULL) AND e.status = 'Pending'
                        AND (SELECT a.enrollment_status FROM tbl_account a WHERE a.acc_id = pd.acc_id) = 'Enrolled'
                      ORDER BY student_name";
$enrollments_result = $conn->query($enrollments_query);
$enrollments = [];
while ($row = $enrollments_result->fetch_assoc()) {
    $enrollments[] = $row;
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

        .chart-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .chart-header h5 {
            color: #2c3e50;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .chart-header h5 i {
            margin-right: 0.5rem;
        }

        .chart-filters {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
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
            flex-wrap: wrap;
            gap: 1rem;
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

        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .chart-header {
                flex-direction: column;
                align-items: stretch;
            }

            .chart-filters {
                justify-content: center;
            }
        }
    </style>
    <title>Accounting Management</title>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-chart-line me-2"></i>Accounting Management</h1>
            <p>Track payments, manage financial records, and view income analytics.</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid" id="statsContainer">
            <div class="stat-card primary">
                <div class="stat-number" id="totalRevenue">₱0</div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number" id="monthlyRevenue">₱0</div>
                <div class="stat-label">This Month</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number" id="totalPayments">0</div>
                <div class="stat-label">Total Payments</div>
            </div>
            <div class="stat-card info">
                <div class="stat-number" id="avgPayment">₱0</div>
                <div class="stat-label">Avg Payment</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-number" id="activeEnrollments">0</div>
                <div class="stat-label">Active Enrollments</div>
            </div>
        </div>

        <!-- Income Chart -->
        <div class="chart-card position-relative">
            <div class="loading-overlay" id="chartLoadingOverlay">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div class="chart-header">
                <h5><i class="fas fa-chart-bar"></i>Income Analysis</h5>
                <div class="chart-filters">
                    <select class="form-select form-select-sm" id="chartType" style="width: auto;">
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                    <select class="form-select form-select-sm" id="chartYear" style="width: auto;">
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $year == date('Y') ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="height: 400px;">
                <canvas id="incomeChart"></canvas>
            </div>
        </div>

        <!-- Payment History Filters -->
        <div class="filter-card">
            <h6><i class="fas fa-filter"></i>Filter Payment History</h6>
            <div class="row g-3" id="filterForm">
                <div class="col-md-2">
                    <label class="form-label">Payment Method</label>
                    <select class="form-select" id="methodFilter">
                        <option value="all">All Methods</option>
                        <option value="Cash">Cash</option>
                        <option value="Bank">Bank</option>
                        <option value="GCash">GCash</option>
                        <option value="Online">Online</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Year</label>
                    <select class="form-select" id="yearFilter">
                        <option value="all">All Years</option>
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $year == date('Y') ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Amount Range</label>
                    <select class="form-select" id="amountFilter">
                        <option value="all">All Amounts</option>
                        <option value="0-1000">₱0 - ₱1,000</option>
                        <option value="1000-5000">₱1,000 - ₱5,000</option>
                        <option value="5000-15000">₱5,000 - ₱15,000</option>
                        <option value="15000+">₱15,000+</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" placeholder="Student name or remarks...">
                        <button class="btn btn-primary" type="button" id="searchBtn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button class="btn btn-outline-secondary w-100" id="resetBtn">
                            <i class="fas fa-refresh"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History Table -->
        <div class="table-card position-relative">
            <div class="loading-overlay" id="loadingOverlay">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div class="table-header">
                <h5 id="tableTitle"><i class="fas fa-table"></i>Payment History (0 payments)</h5>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                    <i class="fas fa-plus me-1"></i>Add Payment
                </button>
            </div>

            <div id="paymentTableContainer">
                <!-- Table content will be loaded here -->
            </div>

            <!-- Pagination -->
            <div class="pagination-container" id="paginationContainer">
                <!-- Pagination will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel">Add New Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addPaymentForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Student Enrollment</label>
                                <select class="form-select" name="enrollment_id" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($enrollments as $enrollment): ?>
                                        <option value="<?php echo $enrollment['enrollment_id']; ?>">
                                            <?php echo $enrollment['student_name']; ?> - <?php echo $enrollment['level']; ?> (<?php echo $enrollment['school_year']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" class="form-control" name="amount" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-select" name="method" required>
                                    <option value="">Select Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank">Bank</option>
                                    <option value="GCash">GCash</option>
                                    <option value="Online">Online</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Payment Date</label>
                                <input type="datetime-local" class="form-control" name="payment_date" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Fee Type</label>
                                <select class="form-select" name="fee_type" required>
                                    <option value="">Select Fee Type</option>
                                    <option value="Registration">Registration</option>
                                    <option value="Miscellaneous">Miscellaneous</option>
                                    <option value="Books">Books</option>
                                    <option value="Tuition">Tuition</option>
                                    <option value="Monthly">Monthly</option>
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Remarks</label>
                                <textarea class="form-control" name="remarks" rows="3" placeholder="Optional remarks..."></textarea>
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Save Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Payment Modal -->
    <div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPaymentModalLabel">Edit Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editPaymentContent">
                    <!-- Edit form will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        class AccountingManager {
            constructor() {
                this.current_page = 1;
                this.records_per_page = 10;
                this.payments = [];
                this.total_records = 0;
                this.chart = null;
                this.enrollments = <?php echo json_encode($enrollments); ?>;
                this.init();
            }

            async init() {
                await Promise.all([
                    this.fetchPayments(),
                    this.fetchStats(),
                    this.initChart()
                ]);
                this.initEventListeners();
                this.setDefaultDateTime();
            }

            initEventListeners() {
                // Filter change events
                ['methodFilter', 'yearFilter', 'amountFilter'].forEach(id => {
                    document.getElementById(id).addEventListener('change', () => {
                        this.current_page = 1;
                        this.fetchPayments();
                    });
                });

                // Search functionality
                document.getElementById('searchBtn').addEventListener('click', () => {
                    this.current_page = 1;
                    this.fetchPayments();
                });

                document.getElementById('search').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.current_page = 1;
                        this.fetchPayments();
                    }
                });

                // Reset button
                document.getElementById('resetBtn').addEventListener('click', () => {
                    this.resetFilters();
                });

                // Chart filters
                document.getElementById('chartType').addEventListener('change', () => {
                    this.updateChart();
                });

                document.getElementById('chartYear').addEventListener('change', () => {
                    this.updateChart();
                });

                // Add payment form
                document.getElementById('addPaymentForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.savePayment();
                });
            }

            setDefaultDateTime() {
                const now = new Date();
                // Format for datetime-local input (YYYY-MM-DDTHH:MM)
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');

                const datetime = `${year}-${month}-${day}T${hours}:${minutes}`;
                document.querySelector('input[name="payment_date"]').value = datetime;
            }

            formatDateForInput(dateString) {
                const date = new Date(dateString);
                // Ensure we're working with local time
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');

                return `${year}-${month}-${day}T${hours}:${minutes}`;
            }

            showLoading(show = true, overlay = 'loadingOverlay') {
                const overlayElement = document.getElementById(overlay);
                overlayElement.style.display = show ? 'flex' : 'none';
            }

            async fetchPayments() {
                try {
                    this.showLoading(true);

                    const params = new URLSearchParams({
                        page: this.current_page,
                        limit: this.records_per_page,
                        method: document.getElementById('methodFilter')?.value || 'all',
                        year: document.getElementById('yearFilter')?.value || 'all',
                        amount_range: document.getElementById('amountFilter')?.value || 'all',
                        search: document.getElementById('search')?.value || ''
                    });

                    const response = await fetch(`./accounting/get_payments.php?${params}`);
                    const data = await response.json();

                    if (data.success) {
                        this.payments = data.payments || [];
                        this.total_records = data.pagination.total_records || 0;

                        this.renderPaymentList();
                        this.renderPagination(data.pagination);
                    } else {
                        throw new Error('Failed to fetch payments');
                    }
                } catch (error) {
                    console.error('Error fetching payments:', error);
                    this.renderError();
                } finally {
                    this.showLoading(false);
                }
            }

            async fetchStats() {
                try {
                    const response = await fetch('./accounting/get_stats.php');
                    const data = await response.json();

                    if (data.success) {
                        this.updateStatsDisplay(data.stats);
                    }
                } catch (error) {
                    console.error('Error fetching stats:', error);
                }
            }

            updateStatsDisplay(stats) {
                document.getElementById('totalRevenue').textContent = `₱${this.formatNumber(stats.total_revenue)}`;
                document.getElementById('monthlyRevenue').textContent = `₱${this.formatNumber(stats.monthly_revenue)}`;
                document.getElementById('totalPayments').textContent = this.formatNumber(stats.total_payments);
                document.getElementById('avgPayment').textContent = `₱${this.formatNumber(stats.avg_payment)}`;
                document.getElementById('activeEnrollments').textContent = this.formatNumber(stats.active_enrollments);
            }

            renderPaymentList() {
                const container = document.getElementById('paymentTableContainer');
                const title = document.getElementById('tableTitle');

                title.innerHTML = `<i class="fas fa-table"></i>Payment History (${this.total_records} payments)`;

                if (this.payments.length === 0) {
                    container.innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-receipt"></i>
                            <h4>No payments found</h4>
                            <p>No payments match your current filter criteria.</p>
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
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Fee Type</th>
                                    <th>Date</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                this.payments.forEach(payment => {
                    tableHTML += this.renderPaymentRow(payment);
                });

                tableHTML += `
                            </tbody>
                        </table>
                    </div>
                `;

                container.innerHTML = tableHTML;
            }

            renderPaymentRow(payment) {
                const methodBadgeClass = this.getMethodBadgeClass(payment.method);
                const feeTypeBadgeClass = this.getFeeTypeBadgeClass(payment.fee_type);
                console.log(payment);
                console.log(feeTypeBadgeClass);
                return `
                    <tr>
                        <td>
                            <div>
                                <div class="fw-bold">${payment.student_name}</div>
                                <small class="text-muted">${payment.level} - ${payment.school_year}</small>
                            </div>
                        </td>
                        <td>
                            <span class="fw-bold text-success">₱${this.formatNumber(payment.amount)}</span>
                        </td>
                        <td>
                            <span class="badge ${methodBadgeClass}">${payment.method}</span>
                        </td>
                        <td>
                            <span class="badge ${feeTypeBadgeClass}">${payment.fee_type}</span>
                        </td>
                        <td>
                            <div>${this.formatDate(payment.payment_date)}</div>
                            <small class="text-muted">${this.formatTime(payment.payment_date)}</small>
                        </td>
                        <td>
                            <span class="text-muted">${payment.remarks || 'No remarks'}</span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-success"
                                    onclick="accountingManager.editPayment(${payment.payment_id})"
                                    title="Edit Payment">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="accountingManager.deletePayment(${payment.payment_id})"
                                    title="Delete Payment">
                                    <i class="fas fa-trash"></i>
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
                        <button class="page-link" onclick="accountingManager.goToPage(${pagination.current_page - 1})" ${!pagination.has_prev ? 'disabled' : ''}>
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
                            <button class="page-link" onclick="accountingManager.goToPage(1)">1</button>
                        </li>
                    `;
                    if (startPage > 2) {
                        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                for (let i = startPage; i <= endPage; i++) {
                    paginationHTML += `
                        <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                            <button class="page-link" onclick="accountingManager.goToPage(${i})">${i}</button>
                        </li>
                    `;
                }

                if (endPage < pagination.total_pages) {
                    if (endPage < pagination.total_pages - 1) {
                        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                    paginationHTML += `
                        <li class="page-item">
                            <button class="page-link" onclick="accountingManager.goToPage(${pagination.total_pages})">${pagination.total_pages}</button>
                        </li>
                    `;
                }

                // Next button
                paginationHTML += `
                    <li class="page-item ${!pagination.has_next ? 'disabled' : ''}">
                        <button class="page-link" onclick="accountingManager.goToPage(${pagination.current_page + 1})" ${!pagination.has_next ? 'disabled' : ''}>
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
                    this.fetchPayments();
                }
            }

            resetFilters() {
                document.getElementById('methodFilter').value = 'all';
                document.getElementById('yearFilter').value = 'all';
                document.getElementById('amountFilter').value = 'all';
                document.getElementById('search').value = '';
                this.current_page = 1;
                this.fetchPayments();
            }

            renderError() {
                const container = document.getElementById('paymentTableContainer');
                container.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        <h4>Error Loading Payments</h4>
                        <p>There was an error loading the payment data. Please try again.</p>
                        <button class="btn btn-primary" onclick="accountingManager.fetchPayments()">
                            Retry
                        </button>
                    </div>
                `;
            }

            async initChart() {
                const ctx = document.getElementById('incomeChart').getContext('2d');
                this.chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Income',
                            data: [],
                            backgroundColor: 'rgba(52, 152, 219, 0.8)',
                            borderColor: 'rgba(52, 152, 219, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '₱' + value.toLocaleString();
                                    }
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Income: ₱' + context.parsed.y.toLocaleString();
                                    }
                                }
                            },
                            legend: {
                                display: false
                            }
                        }
                    }
                });

                await this.updateChart();
            }

            async updateChart() {
                try {
                    this.showLoading(true, 'chartLoadingOverlay');

                    const chartType = document.getElementById('chartType').value;
                    const year = document.getElementById('chartYear').value;

                    const response = await fetch(`./accounting/get_chart_data.php?type=${chartType}&year=${year}`);
                    const data = await response.json();

                    if (data.success) {
                        this.chart.data.labels = data.labels;
                        this.chart.data.datasets[0].data = data.data;
                        this.chart.data.datasets[0].label = chartType === 'monthly' ? 'Monthly Income' : 'Yearly Income';
                        this.chart.update();
                    }
                } catch (error) {
                    console.error('Error updating chart:', error);
                } finally {
                    this.showLoading(false, 'chartLoadingOverlay');
                }
            }

            async savePayment() {
                try {
                    const form = document.getElementById('addPaymentForm');
                    const formData = new FormData(form);

                    const response = await fetch('./accounting/add_payment.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Payment added successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        const modal = bootstrap.Modal.getInstance(document.getElementById('addPaymentModal'));
                        modal.hide();
                        form.reset();
                        this.setDefaultDateTime();

                        await Promise.all([
                            this.fetchPayments(),
                            this.fetchStats(),
                            this.updateChart()
                        ]);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to add payment.'
                        });
                    }
                } catch (error) {
                    console.error('Error adding payment:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while adding the payment.'
                    });
                }
            }

            async editPayment(paymentId) {
                try {
                    const response = await fetch(`./accounting/get_payment.php?id=${paymentId}`);
                    const data = await response.json();

                    if (data.success) {
                        const payment = data.payment;
                        const paymentDate = new Date(payment.payment_date);
                        const formattedDate = this.formatDateForInput(payment.payment_date);

                        document.getElementById('editPaymentContent').innerHTML = `
                            <form id="editPaymentForm">
                                <input type="hidden" name="payment_id" value="${payment.payment_id}">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Student Enrollment</label>
                                        <select class="form-select" name="enrollment_id" required>
                                            ${this.enrollments.map(enrollment => 
                                                `<option value="${enrollment.enrollment_id}" ${enrollment.enrollment_id == payment.enrollment_id ? 'selected' : ''}>
                                                    ${enrollment.student_name} - ${enrollment.level} (${enrollment.school_year})
                                                </option>`
                                            ).join('')}
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Amount</label>
                                        <input type="number" class="form-control" name="amount" step="0.01" min="0" value="${payment.amount}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Method</label>
                                        <select class="form-select" name="method" required>
                                            <option value="Cash" ${payment.method === 'Cash' ? 'selected' : ''}>Cash</option>
                                            <option value="Bank" ${payment.method === 'Bank' ? 'selected' : ''}>Bank</option>
                                            <option value="GCash" ${payment.method === 'GCash' ? 'selected' : ''}>GCash</option>
                                            <option value="Online" ${payment.method === 'Online' ? 'selected' : ''}>Online</option>
                                            <option value="Other" ${payment.method === 'Other' ? 'selected' : ''}>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Payment Date</label>
                                        <input type="datetime-local" class="form-control" name="payment_date" value="${formattedDate}" required>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Fee Type</label>
                                        <select class="form-select" name="fee_type" required>
                                            <option value="Registration" ${payment.fee_type === 'Registration' ? 'selected' : ''}>Registration</option>
                                            <option value="Miscellaneous" ${payment.fee_type === 'Miscellaneous' ? 'selected' : ''}>Miscellaneous</option>
                                            <option value="Books" ${payment.fee_type === 'Books' ? 'selected' : ''}>Books</option>
                                            <option value="Tuition" ${payment.fee_type === 'Tuition' ? 'selected' : ''}>Tuition</option>
                                            <option value="Monthly" ${payment.fee_type === 'Monthly' ? 'selected' : ''}>Monthly</option>
                                        </select>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Remarks</label>
                                        <textarea class="form-control" name="remarks" rows="3" placeholder="Optional remarks...">${payment.remarks || ''}</textarea>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i>Update Payment
                                    </button>
                                </div>
                            </form>
                        `;

                        // Initialize edit form handler
                        document.getElementById('editPaymentForm').addEventListener('submit', (e) => {
                            e.preventDefault();
                            this.updatePayment();
                        });

                        const modal = new bootstrap.Modal(document.getElementById('editPaymentModal'));
                        modal.show();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load payment data.'
                        });
                    }
                } catch (error) {
                    console.error('Error loading payment:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while loading the payment.'
                    });
                }
            }

            async updatePayment() {
                try {
                    const form = document.getElementById('editPaymentForm');
                    const formData = new FormData(form);

                    const response = await fetch('./accounting/update_payment.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Payment updated successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        const modal = bootstrap.Modal.getInstance(document.getElementById('editPaymentModal'));
                        modal.hide();

                        await Promise.all([
                            this.fetchPayments(),
                            this.fetchStats(),
                            this.updateChart()
                        ]);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to update payment.'
                        });
                    }
                } catch (error) {
                    console.error('Error updating payment:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while updating the payment.'
                    });
                }
            }

            async deletePayment(paymentId) {
                const result = await Swal.fire({
                    icon: 'warning',
                    title: 'Confirm Deletion',
                    text: 'Are you sure you want to delete this payment? This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    confirmButtonColor: '#dc3545',
                    cancelButtonText: 'Cancel'
                });

                if (result.isConfirmed) {
                    try {
                        const response = await fetch('./accounting/delete_payment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                payment_id: paymentId
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: 'Payment deleted successfully.',
                                timer: 2000,
                                showConfirmButton: false
                            });

                            await Promise.all([
                                this.fetchPayments(),
                                this.fetchStats(),
                                this.updateChart()
                            ]);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to delete payment.'
                            });
                        }
                    } catch (error) {
                        console.error('Error deleting payment:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while deleting the payment.'
                        });
                    }
                }
            }

            // Helper methods
            formatNumber(num) {
                return parseFloat(num || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
            }

            formatTime(dateString) {
                const date = new Date(dateString);
                return date.toLocaleTimeString('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }

            getMethodBadgeClass(method) {
                const methodClasses = {
                    'Cash': 'bg-success',
                    'Bank': 'bg-primary',
                    'GCash': 'bg-warning',
                    'Online': 'bg-info',
                    'Other': 'bg-secondary'
                };
                return methodClasses[method] || 'bg-secondary';
            }

            getFeeTypeBadgeClass(feeType) {
                const feeTypeClasses = {
                    'Registration': 'bg-primary',
                    'Miscellaneous': 'bg-warning',
                    'Books': 'bg-info',
                    'Tuition': 'bg-success',
                    'Monthly': 'bg-info'
                };
                return feeTypeClasses[feeType] || 'bg-secondary';
            }
        }

        // Initialize the accounting manager
        const accountingManager = new AccountingManager();
    </script>
</body>

</html>
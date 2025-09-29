<!DOCTYPE html>
<?php
session_start();
require_once "../backend/config.php";

if($_SESSION["role"] !== "Admin"){
    header("Location: ../components/logout.php");
    exit;
}

// Get grade levels for filters and dropdowns
$grade_levels_query = "SELECT * FROM tbl_fees ORDER BY fee_id";
$grade_levels_result = $conn->query($grade_levels_query);
$grade_levels = [];
while ($row = $grade_levels_result->fetch_assoc()) {
    $grade_levels[] = $row;
}

// Get all sections with their details
$sections_query = "
    SELECT s.*, f.level as grade_level,
           COUNT(CASE WHEN ns.section_id = s.sec_id THEN 1 END) + 
           COUNT(CASE WHEN st.section_id = s.sec_id THEN 1 END) as enrolled_count
    FROM tbl_sections s
    LEFT JOIN tbl_fees f ON s.level_id = f.fee_id
    LEFT JOIN tbl_new_old_students ns ON s.sec_id = ns.section_id
    LEFT JOIN tbl_personal_details pd1 ON ns.personal_id = pd1.personal_id
    LEFT JOIN tbl_account a1 ON pd1.acc_id = a1.acc_id AND a1.enrollment_status = 'Enrolled'
    LEFT JOIN tbl_student_transferee st ON s.sec_id = st.section_id
    LEFT JOIN tbl_personal_details pd2 ON st.personal_id = pd2.personal_id
    LEFT JOIN tbl_account a2 ON pd2.acc_id = a2.acc_id AND a2.enrollment_status = 'Enrolled'
    GROUP BY s.sec_id
    ORDER BY s.level_id, s.sec_name
";
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

        .section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .section-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            background: linear-gradient(135deg, #198b49ff, #27ae60);
            color: white;
            padding: 1.5rem;
            position: relative;
        }

        .section-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .section-header .capacity-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            backdrop-filter: blur(10px);
        }

        .section-body {
            padding: 1.5rem;
        }

        .section-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .grade-badge {
            background: #3498db;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .capacity-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .capacity-bar {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin: 0.5rem 0;
        }

        .capacity-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }

        .capacity-fill.low {
            background: #27ae60;
        }

        .capacity-fill.medium {
            background: #f39c12;
        }

        .capacity-fill.high {
            background: #e74c3c;
        }

        .section-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            margin-top: 1rem;
        }

        .btn-group-sm .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
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
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.8rem;
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

            .section-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
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

        .section-students {
            max-height: 200px;
            overflow-y: auto;
        }

        .student-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .student-item:last-child {
            border-bottom: none;
        }

        .student-item .avatar {
            width: 30px;
            height: 30px;
            margin-right: 0.75rem;
            font-size: 0.7rem;
        }

        .student-info {
            flex: 1;
            min-width: 0;
        }

        .student-name {
            font-weight: 500;
            font-size: 0.9rem;
            color: #2c3e50;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .student-id {
            font-size: 0.75rem;
            color: #64748b;
        }

        .empty-section {
            text-align: center;
            padding: 1.5rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .empty-section i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #cbd5e1;
        }
    </style>
    <title>Section Management</title>
</head>

<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1><i class="fas fa-chalkboard me-2"></i>Section Management</h1>
                    <p>Manage sections, capacity, and student assignments across all grade levels.</p>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid" id="statsContainer">
            <div class="stat-card primary">
                <div class="stat-number" id="totalSections">0</div>
                <div class="stat-label">Total Sections</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number" id="totalStudents">0</div>
                <div class="stat-label">Enrolled Students</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number" id="avgCapacity">0%</div>
                <div class="stat-label">Average Capacity</div>
            </div>
            <div class="stat-card info">
                <div class="stat-number" id="availableSlots">0</div>
                <div class="stat-label">Available Slots</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-card">
            <h6><i class="fas fa-filter"></i>Filter Sections</h6>
            <div class="row g-3" id="filterForm">
                <div class="col-md-3">
                    <label class="form-label">Grade Level</label>
                    <select class="form-select" id="gradeFilter">
                        <option value="all">All Grade Levels</option>
                        <?php foreach ($grade_levels as $level): ?>
                            <option value="<?php echo $level['fee_id']; ?>">
                                <?php echo $level['level']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Capacity Status</label>
                    <select class="form-select" id="capacityFilter">
                        <option value="all">All Capacities</option>
                        <option value="available">Available Slots</option>
                        <option value="near_full">Near Full (80%+)</option>
                        <option value="full">Full Capacity</option>
                        <option value="over_capacity">Over Capacity</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchSection" placeholder="Section name or adviser...">
                        <button class="btn btn-primary" type="button" id="searchBtn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>

                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                        <i class="fas fa-plus me-1"></i>Add New Section
                    </button>
                </div>
            </div>
        </div>


        <!-- Sections Grid -->
        <div id="sectionsContainer">
            <!-- Sections will be loaded here -->
        </div>
    </div>

    <!-- Add Section Modal -->
    <div class="modal fade" id="addSectionModal" tabindex="-1" aria-labelledby="addSectionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSectionModalLabel">Add New Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addSectionForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Section Name</label>
                            <input type="text" class="form-control" name="sec_name" required>
                            <div class="form-text">e.g., "Grade 1 - Mabini" or "Kinder 1 - Rose"</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Grade Level</label>
                            <select class="form-select" name="level_id" required>
                                <option value="">Select Grade Level</option>
                                <?php foreach ($grade_levels as $level): ?>
                                    <option value="<?php echo $level['fee_id']; ?>">
                                        <?php echo $level['level']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section Capacity</label>
                            <input type="number" class="form-control" name="sec_capacity" min="1" max="40" required
                            oninput="this.value = Math.max(1, Math.min(40, this.value))">
                            <div class="form-text">Maximum number of students for this section</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Section Adviser (Optional)</label>
                            <input type="text" class="form-control" name="sec_adviser">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Add Section
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Section Modal -->
    <div class="modal fade" id="editSectionModal" tabindex="-1" aria-labelledby="editSectionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSectionModalLabel">Edit Section</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editSectionForm">
                    <input type="hidden" name="sec_id" id="editSecId">
                    <div class="modal-body" id="editSectionContent">
                        <!-- Content will be loaded here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Students Modal -->
    <div class="modal fade" id="viewStudentsModal" tabindex="-1" aria-labelledby="viewStudentsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewStudentsModalLabel">Section Students</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewStudentsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Transfer Student Modal -->
    <div class="modal fade" id="transferStudentModal" tabindex="-1" aria-labelledby="transferStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="transferStudentModalLabel">Transfer Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="transferStudentForm">
                    <div class="modal-body" id="transferStudentContent">
                        <!-- Content will be loaded here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-exchange-alt me-1"></i>Transfer Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        class SectionManager {
            constructor() {
                this.sections = [];
                this.gradeLevels = <?php echo json_encode($grade_levels); ?>;
                this.init();
            }

            async init() {
                await this.fetchSections();
                this.initEventListeners();
            }

            initEventListeners() {
                // Filter change events
                ['gradeFilter', 'capacityFilter'].forEach(id => {
                    document.getElementById(id).addEventListener('change', () => {
                        this.renderSections();
                    });
                });

                // Search functionality
                document.getElementById('searchBtn').addEventListener('click', () => {
                    this.renderSections();
                });

                document.getElementById('searchSection').addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        this.renderSections();
                    }
                });


                // Add section form
                document.getElementById('addSectionForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.addSection(new FormData(e.target));
                });

                // Edit section form
                document.getElementById('editSectionForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.updateSection(new FormData(e.target));
                });

                // Transfer student form
                document.getElementById('transferStudentForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.transferStudent(new FormData(e.target));
                });
            }

            async fetchSections() {
                try {
                    const response = await fetch('./sections/get_sections.php');
                    const data = await response.json();

                    if (data.success) {
                        this.sections = data.sections;
                        this.renderSections();
                        this.updateStats(data.stats);
                    } else {
                        throw new Error('Failed to fetch sections');
                    }
                } catch (error) {
                    console.error('Error fetching sections:', error);
                    this.renderError();
                }
            }

            renderSections() {
                const container = document.getElementById('sectionsContainer');
                let filteredSections = this.filterSections();

                if (filteredSections.length === 0) {
                    container.innerHTML = `
                        <div class="no-results">
                            <i class="fas fa-chalkboard"></i>
                            <h4>No sections found</h4>
                            <p>No sections match your current filter criteria.</p>
                        </div>
                    `;
                    return;
                }

                // Group sections by grade level
                const groupedSections = {};
                filteredSections.forEach(section => {
                    const gradeLevel = section.grade_level;
                    if (!groupedSections[gradeLevel]) {
                        groupedSections[gradeLevel] = [];
                    }
                    groupedSections[gradeLevel].push(section);
                });

                let html = '';
                Object.keys(groupedSections).forEach(gradeLevel => {
                    html += `
                        <div class="mb-4">
                            <h4 class="text-primary mb-3 border-bottom pb-2">
                                <i class="fas fa-graduation-cap me-2"></i>${gradeLevel}
                            </h4>
                            <div class="section-grid">
                                ${groupedSections[gradeLevel].map(section => this.renderSectionCard(section)).join('')}
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = html;
            }

            renderSectionCard(section) {
                const enrolledCount = parseInt(section.enrolled_count) || 0;
                const capacity = parseInt(section.sec_capacity);
                const capacityPercentage = capacity > 0 ? Math.round((enrolledCount / capacity) * 100) : 0;

                let capacityClass = 'low';
                if (capacityPercentage >= 100) capacityClass = 'high';
                else if (capacityPercentage >= 80) capacityClass = 'medium';

                const availableSlots = Math.max(0, capacity - enrolledCount);

                return `
                    <div class="section-card">
                        <div class="section-header">
                            <h5>${section.sec_name}</h5>
                            <div class="capacity-badge">
                                ${enrolledCount}/${capacity}
                            </div>
                        </div>
                        <div class="section-body">
                            <div class="section-info">
                                <span class="grade-badge">${section.grade_level}</span>
                                <div class="capacity-info">
                                    <i class="fas fa-users"></i>
                                    <span>${enrolledCount} students</span>
                                </div>
                            </div>
                            
                            <div class="capacity-bar">
                                <div class="capacity-fill ${capacityClass}" style="width: ${Math.min(capacityPercentage, 100)}%"></div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Capacity: ${capacityPercentage}%</small>
                                <small class="text-success">Available: ${availableSlots}</small>
                            </div>
                            
                            ${section.sec_adviser ? `
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-user-tie me-1"></i>
                                        Adviser: ${section.sec_adviser}
                                    </small>
                                </div>
                            ` : ''}
                            
                            <div class="section-actions">
                                <button class="btn btn-sm btn-outline-primary" onclick="sectionManager.viewStudents(${section.sec_id})" data-bs-toggle="modal" data-bs-target="#viewStudentsModal">
                                    <i class="fas fa-users"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success" onclick="sectionManager.editSection(${section.sec_id})" data-bs-toggle="modal" data-bs-target="#editSectionModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="sectionManager.deleteSection(${section.sec_id}, '${section.sec_name}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }

            filterSections() {
                const gradeFilter = document.getElementById('gradeFilter').value;
                const capacityFilter = document.getElementById('capacityFilter').value;
                const searchTerm = document.getElementById('searchSection').value.toLowerCase();

                return this.sections.filter(section => {
                    // Grade level filter
                    if (gradeFilter !== 'all' && section.level_id != gradeFilter) {
                        return false;
                    }

                    // Capacity filter
                    if (capacityFilter !== 'all') {
                        const enrolledCount = parseInt(section.enrolled_count) || 0;
                        const capacity = parseInt(section.sec_capacity);
                        const percentage = capacity > 0 ? (enrolledCount / capacity) * 100 : 0;

                        switch (capacityFilter) {
                            case 'available':
                                if (percentage >= 100) return false;
                                break;
                            case 'near_full':
                                if (percentage < 80) return false;
                                break;
                            case 'full':
                                if (percentage < 100) return false;
                                break;
                            case 'over_capacity':
                                if (percentage <= 100) return false;
                                break;
                        }
                    }

                    // Search filter
                    if (searchTerm) {
                        const sectionName = section.sec_name.toLowerCase();
                        const adviser = (section.sec_adviser || '').toLowerCase();
                        if (!sectionName.includes(searchTerm) && !adviser.includes(searchTerm)) {
                            return false;
                        }
                    }

                    return true;
                });
            }

            resetFilters() {
                document.getElementById('gradeFilter').value = 'all';
                document.getElementById('capacityFilter').value = 'all';
                document.getElementById('searchSection').value = '';
                this.renderSections();
            }

            updateStats(stats) {
                document.getElementById('totalSections').textContent = stats.total_sections;
                document.getElementById('totalStudents').textContent = stats.total_students;
                document.getElementById('avgCapacity').textContent = stats.avg_capacity + '%';
                document.getElementById('availableSlots').textContent = stats.available_slots;
            }

            async addSection(formData) {
                try {
                    const response = await fetch('./sections/add_section.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Section added successfully!',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(async (result)=>{
                            if(result){
                                bootstrap.Modal.getInstance(document.getElementById('addSectionModal')).hide();
                                document.getElementById('addSectionForm').reset();
                                await this.fetchSections();
                            }
                        })
                        
                    } else {
                        throw new Error(data.message || 'Failed to add section');
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message
                    });
                }
            }

            async editSection(sectionId) {
                const section = this.sections.find(s => s.sec_id == sectionId);
                if (!section) return;

                document.getElementById('editSecId').value = section.sec_id;
                document.getElementById('editSectionContent').innerHTML = `
                    <div class="mb-3">
                        <label class="form-label">Section Name</label>
                        <input type="text" class="form-control" name="sec_name" value="${section.sec_name}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grade Level</label>
                        <select class="form-select" name="level_id" required>
                            ${this.gradeLevels.map(level => 
                                `<option value="${level.fee_id}" ${section.level_id == level.fee_id ? 'selected' : ''}>${level.level}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Section Capacity</label>
                        <input type="number" class="form-control" name="sec_capacity" value="${section.sec_capacity}" min="1" max="50" required>
                        <div class="form-text">Current enrolled: ${section.enrolled_count} students</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Section Adviser</label>
                        <input type="text" class="form-control" name="sec_adviser" value="${section.sec_adviser || ''}">
                    </div>
                `;
            }

            async updateSection(formData) {
                try {
                    const response = await fetch('./sections/update_section.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Section updated successfully!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        bootstrap.Modal.getInstance(document.getElementById('editSectionModal')).hide();
                        await this.fetchSections();
                    } else {
                        throw new Error(data.message || 'Failed to update section');
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message
                    });
                }
            }

            async deleteSection(sectionId, sectionName) {
                const result = await Swal.fire({
                    title: 'Delete Section',
                    text: `Are you sure you want to delete "${sectionName}"? This action cannot be undone.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                });

                if (result.isConfirmed) {
                    try {
                        const response = await fetch('./sections/delete_section.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                sec_id: sectionId
                            })
                        });
                        const data = await response.json();

                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Section has been deleted.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            await this.fetchSections();
                        } else {
                            throw new Error(data.message || 'Failed to delete section');
                        }
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message
                        });
                    }
                }
            }

            formatEnrollmentDate(dateString) {
                if (!dateString) return 'N/A';

                const date = new Date(dateString);

                // Check if date is valid
                if (isNaN(date.getTime())) return 'N/A';

                const options = {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                };

                return date.toLocaleDateString('en-US', options);
            }

            async viewStudents(sectionId) {
                try {
                    const response = await fetch(`./sections/get_section_students.php?sec_id=${sectionId}`);
                    const data = await response.json();

                    if (data.success) {
                        const section = data.section;
                        const students = data.students;

                        document.getElementById('viewStudentsModalLabel').textContent = `${section.sec_name} - Students`;

                        let studentsHtml = '';
                        if (students.length === 0) {
                            studentsHtml = `
                                <div class="empty-section">
                                    <i class="fas fa-users"></i>
                                    <p>No students enrolled in this section yet.</p>
                                </div>
                            `;
                        } else {
                            studentsHtml = `
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Student ID</th>
                                                <th>Type</th>
                                                <th>Enrolled Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${students.map(student => `
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar me-3">
                                                                ${this.getStudentInitials(student)}
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold">${this.getDisplayName(student)}</div>
                                                                <small class="text-muted">${student.email}</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info">${student.acc_id}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge ${student.student_type === 'New Student' ? 'bg-success' : 'bg-primary'}">
                                                            ${student.student_type}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        ${this.formatEnrollmentDate(student.date_enrolled)}
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-info" onclick="sectionManager.showTransferModal(${student.acc_id}, ${sectionId})" data-bs-toggle="modal" data-bs-target="#transferStudentModal">
                                                            <i class="fas fa-exchange-alt me-1"></i>Transfer
                                                        </button>
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            `;
                        }

                        document.getElementById('viewStudentsContent').innerHTML = `
                            <div class="mb-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="bg-light p-3 rounded">
                                            <h6 class="mb-2">Section Information</h6>
                                            <p class="mb-1"><strong>Name:</strong> ${section.sec_name}</p>
                                            <p class="mb-1"><strong>Grade Level:</strong> ${section.grade_level}</p>
                                            <p class="mb-1"><strong>Capacity:</strong> ${students.length}/${section.sec_capacity}</p>
                                            ${section.sec_adviser ? `<p class="mb-0"><strong>Adviser:</strong> ${section.sec_adviser}</p>` : ''}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="bg-light p-3 rounded">
                                            <h6 class="mb-2">Quick Stats</h6>
                                            <p class="mb-1"><strong>Enrolled Students:</strong> ${students.length}</p>
                                            <p class="mb-1"><strong>Available Slots:</strong> ${Math.max(0, section.sec_capacity - students.length)}</p>
                                            <p class="mb-0"><strong>Capacity Usage:</strong> ${Math.round((students.length / section.sec_capacity) * 100)}%</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            ${studentsHtml}
                        `;
                    } else {
                        throw new Error(data.message || 'Failed to load students');
                    }
                } catch (error) {
                    document.getElementById('viewStudentsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading students: ${error.message}
                        </div>
                    `;
                }
            }

            async showTransferModal(studentId, currentSectionId) {
                try {
                    const response = await fetch(`./sections/get_transfer_options.php?student_id=${studentId}&current_section=${currentSectionId}`);
                    const data = await response.json();

                    if (data.success) {
                        const student = data.student;
                        const availableSections = data.available_sections;

                        document.getElementById('transferStudentContent').innerHTML = `
                            <input type="hidden" name="student_id" value="${studentId}">
                            <input type="hidden" name="current_section_id" value="${currentSectionId}">
                            <input type="hidden" name="student_type" value="${student.student_type}">

                            <div class="mb-4">
                                <div class="bg-light p-3 rounded">
                                    <h6 class="mb-2">Student Information</h6>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3">
                                            ${this.getStudentInitials(student)}
                                        </div>
                                        <div>
                                            <div class="fw-bold">${this.getDisplayName(student)}</div>
                                            <small class="text-muted">ID: ${student.acc_id} | Grade: ${student.grade_level}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Transfer to Section</label>
                                <select class="form-select" name="new_section_id" required>
                                    <option value="">Select new section</option>
                                    ${availableSections.map(section => `
                                        <option value="${section.sec_id}">
                                            ${section.sec_name} (${section.enrolled_count}/${section.sec_capacity} students)
                                        </option>
                                    `).join('')}
                                </select>
                                <div class="form-text">Only sections in the same grade level are shown</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Reason for Transfer (Optional)</label>
                                <textarea class="form-control" name="transfer_reason" rows="3" placeholder="Enter reason for transfer..."></textarea>
                            </div>
                        `;
                    } else {
                        throw new Error(data.message || 'Failed to load transfer options');
                    }
                } catch (error) {
                    document.getElementById('transferStudentContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading transfer options: ${error.message}
                        </div>
                    `;
                }
            }

            async transferStudent(formData) {
                try {
                    const response = await fetch('./sections/transfer_student.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Student transferred successfully!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        bootstrap.Modal.getInstance(document.getElementById('transferStudentModal')).hide();
                        bootstrap.Modal.getInstance(document.getElementById('viewStudentsModal')).hide();
                        await this.fetchSections();
                    } else {
                        throw new Error(data.message || 'Failed to transfer student');
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message
                    });
                }
            }

            renderError() {
                const container = document.getElementById('sectionsContainer');
                container.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-exclamation-triangle text-warning"></i>
                        <h4>Error Loading Sections</h4>
                        <p>There was an error loading the sections data. Please try again.</p>
                        <button class="btn btn-primary" onclick="sectionManager.fetchSections()">
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
        }

        // Initialize the section manager
        const sectionManager = new SectionManager();

        // Function to view full image (if needed for future features)
        function viewFullImage(imagePath, imageTitle) {
            document.getElementById('fullImageModalLabel').textContent = imageTitle;
            document.getElementById('fullImage').src = imagePath;
        }
    </script>
</body>

</html>
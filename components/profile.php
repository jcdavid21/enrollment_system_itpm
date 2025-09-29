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
$success_message = "";
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name']);
        $last_name = trim($_POST['last_name']);
        $date_of_birth = $_POST['date_of_birth'];
        $gender = $_POST['gender'];
        $address = trim($_POST['address']);
        $email = trim($_POST['email']);

        // Update personal details
        $update_personal = "UPDATE tbl_personal_details SET 
                    first_name = ?, 
                    middle_name = ?, 
                    last_name = ?, 
                    date_of_birth = ?, 
                    gender = ?, 
                    address = ? 
                    WHERE acc_id = ?";

        $stmt = $conn->prepare($update_personal);
        $stmt->bind_param("ssssssi", $first_name, $middle_name, $last_name, $date_of_birth, $gender, $address, $user_id);
        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Error updating profile.";
        }
    }

    if (isset($_POST['update_parent'])) {
        $parent_full_name = trim($_POST['parent_full_name']);
        $contact_num = trim($_POST['contact_num']);
        $relationship = $_POST['relationship'];

        // Get student personal_id
        $get_personal_id = "SELECT personal_id FROM tbl_personal_details WHERE acc_id = ?";
        $stmt = $conn->prepare($get_personal_id);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student_data = $result->fetch_assoc();
        $personal_id = $student_data['personal_id'];

        // Check if parent record exists
        $check_parent = "SELECT parent_id FROM tbl_parents_details WHERE child_id = ?";
        $stmt = $conn->prepare($check_parent);
        $stmt->bind_param("i", $personal_id);
        $stmt->execute();
        $parent_result = $stmt->get_result();

        if ($parent_result->num_rows > 0) {
            // Update existing parent record
            $update_parent = "UPDATE tbl_parents_details SET 
                        parent_full_name = ?, 
                        contact_num = ?, 
                        relationship = ? 
                        WHERE child_id = ?";
            $stmt = $conn->prepare($update_parent);
            $stmt->bind_param("sssi", $parent_full_name, $contact_num, $relationship, $personal_id);
        } else {
            // Insert new parent record
            $insert_parent = "INSERT INTO tbl_parents_details (child_id, parent_full_name, contact_num, relationship) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_parent);
            $stmt->bind_param("isss", $personal_id, $parent_full_name, $contact_num, $relationship);
        }

        if ($stmt->execute()) {
            $success_message = "Parent information updated successfully!";
        } else {
            $error_message = "Error updating parent information.";
        }
    }
}

// Get current student data
$student_query = "SELECT 
            pd.*, 
            acc.username, 
            acc.email, 
            acc.enrollment_status
        FROM tbl_personal_details pd 
        LEFT JOIN tbl_account acc ON pd.acc_id = acc.acc_id 
        WHERE pd.acc_id = ?";

$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student_data = $student_result->fetch_assoc();

// Get parent details
$parent_query = "SELECT * FROM tbl_parents_details WHERE child_id = ?";
$stmt = $conn->prepare($parent_query);
$stmt->bind_param("i", $student_data['personal_id']);
$stmt->execute();
$parent_result = $stmt->get_result();
$parent_data = $parent_result->fetch_assoc();
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
    <title>Fonthills Christian School - My Profile</title>
    <style>
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 2rem;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(2, 84, 27, 0.3);
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .profile-content {
            position: relative;
            z-index: 2;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card-header-custom {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
        }

        .card-body-custom {
            padding: 2rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(2, 84, 27, 0.1);
            background: white;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(2, 84, 27, 0.3);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(2, 84, 27, 0.4);
        }

        .btn-secondary-custom {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-secondary-custom:hover {
            transform: translateY(-2px);
            color: white;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .alert-custom {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success-custom {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border: 1px solid #28a745;
        }

        .alert-danger-custom {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border: 1px solid #dc3545;
        }

        .info-display {
            background: rgba(52, 152, 219, 0.05);
            border-left: 4px solid var(--accent-color);
            padding: 1rem 1.5rem;
            border-radius: 0 12px 12px 0;
            margin-bottom: 1.5rem;
        }

        .readonly-field {
            background: #f8f9fa !important;
            color: #6c757d;
            cursor: not-allowed;
        }

        .section-divider {
            height: 2px;
            background: linear-gradient(90deg, var(--primary-color) 0%, transparent 100%);
            border: none;
            margin: 2rem 0;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section:last-child {
            margin-bottom: 0;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .card-body-custom {
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php include_once "./sidebar.php"; ?>

    <div class="main-content">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-content">
                <div class="d-flex align-items-center">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="ms-3">
                        <h1 class="mb-1">My Profile</h1>
                        <p class="mb-0 opacity-75">Manage your personal information and account settings</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success-custom alert-custom">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger-custom alert-custom">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Personal Information Card -->
        <div class="profile-card">
            <div class="card-header-custom">
                <h4 class="mb-0 fw-bold">
                    <i class="fas fa-user-edit me-2 text-primary"></i>
                    Personal Information
                </h4>
            </div>
            <div class="card-body-custom">
                <div class="info-display">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-2 text-info"></i>
                        <strong>Student ID: <?php echo htmlspecialchars($student_data['personal_id']); ?></strong>
                        <span class="ms-3">|</span>
                        <strong class="ms-3">Username: <?php echo htmlspecialchars($student_data['username']); ?></strong>
                    </div>
                </div>

                <form method="POST" id="profileForm">
                    <div class="form-section">
                        <div class="form-row">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">
                                    <i class="fas fa-user me-1"></i>First Name
                                </label>
                                <input type="text" class="form-control" id="first_name" name="first_name"
                                    value="<?php echo htmlspecialchars($student_data['first_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="middle_name" class="form-label">
                                    <i class="fas fa-user me-1"></i>Middle Name
                                </label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name"
                                    value="<?php echo htmlspecialchars($student_data['middle_name']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">
                                    <i class="fas fa-user me-1"></i>Last Name
                                </label>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                    value="<?php echo htmlspecialchars($student_data['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Date of Birth
                                </label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                    value="<?php echo $student_data['date_of_birth']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="gender" class="form-label">
                                    <i class="fas fa-venus-mars me-1"></i>Gender
                                </label>
                                <select class="form-control" id="gender" name="gender" required>
                                    <option value="Male" <?php echo $student_data['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $student_data['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo $student_data['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email Address
                                </label>
                                <input type="email" class="form-control readonly-field" id="email" name="email"
                                    value="<?php echo htmlspecialchars($student_data['email']); ?>" required readonly>
                                <div class="form-text" style="font-size: 12px;">To change your email, please contact the administrator.</div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group-full mb-3">
                                <label for="address" class="form-label">
                                    <i class="fas fa-map-marker-alt me-1"></i>Address
                                </label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($student_data['address']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" name="update_profile" onclick="updateProfile()" class="btn btn-primary-custom text-white fw-medium">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                        <button type="button" class="btn btn-secondary-custom text-white fw-medium" onclick="resetForm('profileForm')">
                            <i class="fas fa-undo me-2"></i>Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Parent/Guardian Information Card -->
        <div class="profile-card">
            <div class="card-header-custom">
                <h4 class="mb-0 fw-bold">
                    <i class="fas fa-users me-2 text-success"></i>
                    Parent/Guardian Information
                </h4>
            </div>
            <div class="card-body-custom">
                <form method="POST" id="parentForm">
                    <div class="form-section">
                        <div class="form-row">
                            <div class="mb-3">
                                <label for="parent_full_name" class="form-label">
                                    <i class="fas fa-user-tie me-1"></i>Full Name
                                </label>
                                <input type="text" class="form-control" id="parent_full_name" name="parent_full_name"
                                    value="<?php echo $parent_data ? htmlspecialchars($parent_data['parent_full_name']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="contact_num" class="form-label">
                                    <i class="fas fa-phone me-1"></i>Contact Number
                                </label>
                                <input type="text" class="form-control" id="contact_num" name="contact_num"
                                    value="<?php echo $parent_data ? htmlspecialchars($parent_data['contact_num']) : ''; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="relationship" class="form-label">
                                    <i class="fas fa-heart me-1"></i>Relationship
                                </label>
                                <select class="form-control" id="relationship" name="relationship" required>
                                    <option value="">Select Relationship</option>
                                    <option value="Mother" <?php echo ($parent_data && $parent_data['relationship'] == 'Mother') ? 'selected' : ''; ?>>Mother</option>
                                    <option value="Father" <?php echo ($parent_data && $parent_data['relationship'] == 'Father') ? 'selected' : ''; ?>>Father</option>
                                    <option value="Guardian" <?php echo ($parent_data && $parent_data['relationship'] == 'Guardian') ? 'selected' : ''; ?>>Guardian</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" name="update_parent" class="btn btn-primary-custom fw-medium text-white">
                            <i class="fas fa-save me-2"></i>Update Parent Info
                        </button>
                        <button type="button" class="btn btn-secondary-custom fw-medium text-white" onclick="resetForm('parentForm')">
                            <i class="fas fa-undo me-2"></i>Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Account Information (Read-only) -->
        <div class="profile-card">
            <div class="card-header-custom">
                <h4 class="mb-0 fw-bold">
                    <i class="fas fa-shield-alt me-2 text-warning"></i>
                    Account Information
                </h4>
            </div>
            <div class="card-body-custom">
                <div class="form-row">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-user-circle me-1"></i>Username
                        </label>
                        <input type="text" class="form-control readonly-field"
                            value="<?php echo htmlspecialchars($student_data['username']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-id-badge me-1"></i>Student ID
                        </label>
                        <input type="text" class="form-control readonly-field"
                            value="<?php echo htmlspecialchars($student_data['personal_id']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-graduation-cap me-1"></i>Enrollment Status
                        </label>
                        <input type="text" class="form-control readonly-field"
                            value="<?php echo htmlspecialchars($student_data['enrollment_status']); ?>" readonly>
                    </div>
                </div>
                <div class="info-display">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-2 text-info"></i>
                        <small>These fields are read-only and can only be modified by the administrator. Contact the school office if you need to make changes to this information.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function resetForm(formId) {
            document.getElementById(formId).reset();
        }

        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const address = document.getElementById('address').value.trim();
            const gender = document.getElementById('gender').value;
            const birthDate = document.getElementById('date_of_birth').value;

            if (!firstName || !lastName || !email || !address || !gender || !birthDate) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all required fields.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }


            const curren_date = new Date();
            const selected_date = new Date(birthDate);
            if (selected_date >= curren_date) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date of Birth',
                    text: 'Date of Birth cannot be in the future.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }

            //age must be at least 3 years old
            const ageDiff = curren_date - selected_date;
            const ageDate = new Date(ageDiff);
            const age = Math.abs(ageDate.getUTCFullYear() - 1970);
            if (age < 3) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Age',
                    text: 'Student must be at least 3 years old.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Email',
                    text: 'Please enter a valid email address.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }
        });

        document.getElementById('parentForm').addEventListener('submit', function(e) {
            const parentName = document.getElementById('parent_full_name').value.trim();
            const contactNum = document.getElementById('contact_num').value.trim();
            const relationship = document.getElementById('relationship').value;


            if (!parentName || !contactNum || !relationship) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all parent/guardian information fields.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }

            // Phone number validation (basic)
            const phoneRegex = /^[0-9+\-\s()]+$/;
            if (!phoneRegex.test(contactNum)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Contact Number',
                    text: 'Please enter a valid contact number.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }
        });

        // Success message handling
        <?php if ($success_message): ?>
            setTimeout(function() {
                document.querySelector('.alert-success-custom').style.display = 'none';
            }, 5000);
        <?php endif; ?>

        // Error message handling
        <?php if ($error_message): ?>
            setTimeout(function() {
                document.querySelector('.alert-danger-custom').style.display = 'none';
            }, 5000);
        <?php endif; ?>
    </script>
</body>

</html>
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
    <title>Foothills Christian School - My Profile</title>
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

        .profile-avatar-large {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            border: 4px solid rgba(255, 255, 255, 0.3);
            position: relative;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .profile-edit-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--accent-color);
            border: 3px solid white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
        }

        .profile-edit-btn:hover {
            background: #2980b9;
            transform: scale(1.1);
        }

        .profile-picture-preview {
            width: 200px;
            height: 200px;
            margin: 0 auto;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
        }

        .preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .preview-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #dee2e6;
        }

        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 20px 20px 0 0;
        }

        .modal-title {
            font-weight: 700;
            color: var(--primary-color);
        }


        .profile-avatar-large .camera-icon {
            font-size: 1rem !important;
        }

        .id-preview-container {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 1.5rem;
        }

        .id-preview-box {
            text-align: center;
        }

        .id-preview-image {
            max-width: 500px;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .id-upload-placeholder {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        #parentIdModalImage {
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <?php include_once "./sidebar.php"; ?>

    <div class="main-content">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-content">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div class="d-flex align-items-center">
                        <div class="profile-avatar-large position-relative">
                            <?php if ($student_data['profile_picture']): ?>
                                <img src="../assets/profiles/<?php echo htmlspecialchars($student_data['profile_picture']); ?>"
                                    alt="Profile Picture" id="headerProfileImage" class="profile-image">
                            <?php else: ?>
                                <i class="fas fa-user" id="headerProfileIcon"></i>
                            <?php endif; ?>
                            <button class="profile-edit-btn" onclick="openProfilePictureModal()" title="Change Profile Picture">
                                <i class="fas fa-camera camera-icon"></i>
                            </button>
                        </div>
                        <div class="ms-4">
                            <h1 class="mb-1">My Profile</h1>
                            <p class="mb-0 opacity-75">Manage your personal information and account settings</p>
                            <button class="btn btn-primary mt-2 btn-changepass"
                                id="changePassBtn" data-bs-toggle="modal" data-bs-target="#changePassModal">
                                Change Password
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="changePassModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-key me-2"></i>Change Password
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="changePassForm">
                            <div class="mb-3 position-relative">
                                <label for="current_password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Current Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('current_password')">
                                        <i class="fas fa-eye" id="current_password_icon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>New Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('new_password')">
                                        <i class="fas fa-eye" id="new_password_icon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Confirm New Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('confirm_password')">
                                        <i class="fas fa-eye" id="confirm_password_icon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary-custom text-white fw-medium" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </button>
                                <button type="submit" class="btn btn-primary-custom text-white fw-medium">
                                    <i class="fas fa-save me-2"></i>Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Picture Modal -->
        <div class="modal fade" id="profilePictureModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-image me-2"></i>Profile Picture
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="profile-picture-preview mb-4">
                            <?php if ($student_data['profile_picture']): ?>
                                <img src="../assets/profiles/<?php echo htmlspecialchars($student_data['profile_picture']); ?>"
                                    alt="Profile Picture" id="previewImage" class="preview-image">
                            <?php else: ?>
                                <div class="preview-placeholder" id="previewPlaceholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <input type="file" id="profilePictureInput" accept="image/jpeg,image/jpg,image/png,image/gif" style="display: none;">

                        <!-- Initial Buttons (shown by default) -->
                        <div id="initialButtons" class="d-flex gap-2 justify-content-center flex-wrap">
                            <button class="btn btn-primary-custom" style="color: white;" onclick="document.getElementById('profilePictureInput').click()">
                                <i class="fas fa-upload me-2"></i>Choose New Picture
                            </button>
                            <?php if ($student_data['profile_picture']): ?>
                                <button class="btn btn-danger" onclick="deleteProfilePicture()">
                                    <i class="fas fa-trash me-2"></i>Remove Picture
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Preview Buttons (hidden by default, shown after selecting image) -->
                        <div id="previewButtons" class="d-none">
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Preview your new profile picture above</strong>
                            </div>
                            <div class="d-flex gap-2 justify-content-center flex-wrap">
                                <button class="btn btn-success" onclick="confirmUpload()">
                                    <i class="fas fa-check me-2"></i>Confirm & Upload
                                </button>
                                <button class="btn btn-secondary" onclick="cancelUpload()">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </button>
                            </div>
                        </div>

                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Accepted formats: JPG, JPEG, PNG, GIF (Max 5MB)
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                        <button type="button" onclick="updateProfile()" class="btn btn-primary-custom text-white fw-medium">
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
                                    maxlength="11" minlength="11"
                                    pattern="[0-9]*"
                                    inputmode="numeric"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                                    value="<?php echo $parent_data ? htmlspecialchars($parent_data['contact_num']) : ''; ?>"
                                    required>

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

                        <!-- Parent Valid ID Section -->
                        <div class="form-row">
                            <div class="form-group-full mb-3">
                                <label class="form-label">
                                    <i class="fas fa-id-card me-1"></i>Parent/Guardian Valid ID
                                </label>
                                <div class="id-preview-container">
                                    <?php
                                    $id_image_path = null;
                                    $id_image_source = null;

                                    // Check if student is in tbl_new_old_students
                                    $check_new_old = "SELECT parents_valid_id FROM tbl_new_old_students WHERE personal_id = ?";
                                    $stmt = $conn->prepare($check_new_old);
                                    $stmt->bind_param("i", $student_data['personal_id']);
                                    $stmt->execute();
                                    $new_old_result = $stmt->get_result();

                                    if ($new_old_result->num_rows > 0) {
                                        $new_old_data = $new_old_result->fetch_assoc();
                                        if ($new_old_data['parents_valid_id']) {
                                            $id_image_path = "../assets/new_students/" . $new_old_data['parents_valid_id'];
                                            $id_image_source = "new_students";
                                        }
                                    }

                                    // If not found, check parent_temp_id
                                    if (!$id_image_path && $parent_data && $parent_data['parent_temp_id'] && $parent_data['parent_temp_id'] != 'No image uploaded yet') {
                                        $id_image_path = "../assets/enrollment_img/" . $parent_data['parent_temp_id'];
                                        $id_image_source = "parent_temp";
                                    }
                                    ?>

                                    <?php if ($id_image_path): ?>
                                        <div class="id-preview-box mb-3">
                                            <img src="../assets/enrollment_img/<?php echo $parent_data["parent_temp_id"] ?>"
                                                alt="Parent Valid ID"
                                                class="id-preview-image"
                                                id="parentIdPreview">
                                            <div class="mt-2 d-flex gap-2">
                                                <button type="button" class="btn btn-sm btn-info" onclick="viewParentId()">
                                                    <i class="fas fa-eye me-1"></i>View Full Size
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('parentIdInput').click()">
                                                    <i class="fas fa-edit me-1"></i>Change ID
                                                </button>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="id-upload-placeholder mb-3" id="idUploadPlaceholder">
                                            <i class="fas fa-id-card fa-3x mb-2 text-muted"></i>
                                            <p class="mb-2">No ID uploaded yet</p>
                                            <button type="button" class="btn btn-primary-custom" onclick="document.getElementById('parentIdInput').click()">
                                                <i class="fas fa-upload me-1"></i>Upload Valid ID
                                            </button>
                                        </div>
                                    <?php endif; ?>

                                    <input type="file" id="parentIdInput" accept="image/jpeg,image/jpg,image/png,image/gif" style="display: none;">

                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Accepted formats: JPG, JPEG, PNG, GIF (Max 5MB)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="button" onclick="updateParent()" class="btn btn-primary-custom fw-medium text-white">
                            <i class="fas fa-save me-2"></i>Update Parent Info
                        </button>
                        <button type="button" class="btn btn-secondary-custom fw-medium text-white" onclick="resetForm('parentForm')">
                            <i class="fas fa-undo me-2"></i>Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Parent ID View Modal -->
        <div class="modal fade" id="parentIdModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-id-card me-2"></i>Parent/Guardian Valid ID
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="" alt="Parent Valid ID" id="parentIdModalImage" style="max-width: 100%; height: auto;">
                    </div>
                </div>
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

        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + '_icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function updateProfile() {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const address = document.getElementById('address').value.trim();
            const gender = document.getElementById('gender').value;
            const birthDate = document.getElementById('date_of_birth').value;

            // Validation
            if (!firstName || !lastName || !email || !address || !gender || !birthDate) {
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
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date of Birth',
                    text: 'Date of Birth cannot be in the future.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }

            const ageDiff = curren_date - selected_date;
            const ageDate = new Date(ageDiff);
            const age = Math.abs(ageDate.getUTCFullYear() - 1970);
            if (age < 3) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Age',
                    text: 'Student must be at least 3 years old.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Email',
                    text: 'Please enter a valid email address.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }

            // Confirmation dialog
            Swal.fire({
                title: 'Confirm Update',
                text: 'Are you sure you want to update your profile information?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#02541b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, update it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Updating...',
                        text: 'Please wait while we update your profile.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // AJAX request
                    const formData = new FormData(document.getElementById('profileForm'));
                    formData.append('action', 'update_profile');

                    $.ajax({
                        url: '../backend/update_profile.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: response.message,
                                    confirmButtonColor: '#02541b'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message,
                                    confirmButtonColor: '#02541b'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'An error occurred while updating your profile. Please try again.',
                                confirmButtonColor: '#02541b'
                            });
                        }
                    });
                }
            });
        }


        $('#changePassForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'change_password');

            if (!formData.get('current_password') || !formData.get('new_password') || !formData.get('confirm_password')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all password fields.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }


            if (formData.get('new_password') !== formData.get('confirm_password')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'New Password and Confirm New Password do not match.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }

            if (formData.get('new_password').length < 8) {
                Swal.fire({
                    icon: 'error',
                    title: 'Weak Password',
                    text: 'New Password must be at least 8 characters long.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }

            $.ajax({
                url: '../backend/update_password.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    Swal.fire({
                        title: 'Changing Password...',
                        text: 'Please wait while we change your password.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            confirmButtonColor: '#02541b'
                        }).then((result) => {
                            if(result){
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message,
                            confirmButtonColor: '#02541b'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while changing your password. Please try again.',
                        confirmButtonColor: '#02541b'
                    });
                }
            })
        });

        function updateParent() {
            const parentName = document.getElementById('parent_full_name').value.trim();
            const contactNum = document.getElementById('contact_num').value.trim();
            const relationship = document.getElementById('relationship').value;

            // Validation
            if (!parentName || !contactNum || !relationship) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'Please fill in all parent/guardian information fields.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }

            const phoneRegex = /^[0-9+\-\s()]+$/;
            if (!phoneRegex.test(contactNum)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Contact Number',
                    text: 'Please enter a valid contact number.',
                    confirmButtonColor: '#02541b'
                });
                return false;
            }

            // Confirmation dialog
            Swal.fire({
                title: 'Confirm Update',
                text: 'Are you sure you want to update parent/guardian information?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#02541b',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, update it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Updating...',
                        text: 'Please wait while we update parent information.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // AJAX request
                    const formData = new FormData(document.getElementById('parentForm'));
                    formData.append('action', 'update_parent');

                    $.ajax({
                        url: '../backend/update_profile.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: response.message,
                                    confirmButtonColor: '#02541b'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message,
                                    confirmButtonColor: '#02541b'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'An error occurred while updating parent information. Please try again.',
                                confirmButtonColor: '#02541b'
                            });
                        }
                    });
                }
            });
        }


        // Profile Picture Functions
        // Parent ID Functions
        let selectedParentId = null;
        let originalParentIdSrc = '<?php echo $id_image_path ?? ''; ?>';
        let originalParentIdHtml = null;

        document.getElementById('parentIdInput').addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (!file) return;

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File Type',
                    text: 'Please upload a JPG, JPEG, PNG, or GIF image.',
                    confirmButtonColor: '#02541b'
                });
                e.target.value = '';
                return;
            }

            // Validate file size (5MB)
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Too Large',
                    text: 'Maximum file size is 5MB.',
                    confirmButtonColor: '#02541b'
                });
                e.target.value = '';
                return;
            }

            // Store selected file
            selectedParentId = file;

            // Show preview
            const reader = new FileReader();
            reader.onload = function(event) {
                const previewImage = document.getElementById('parentIdPreview');
                const placeholder = document.getElementById('idUploadPlaceholder');

                // Store original HTML for cancel
                if (!originalParentIdHtml) {
                    if (previewImage) {
                        originalParentIdHtml = previewImage.parentElement.innerHTML;
                    } else if (placeholder) {
                        originalParentIdHtml = placeholder.innerHTML;
                    }
                }

                // Create preview with update/cancel buttons
                const previewHtml = `
            <div class="id-preview-box mb-3">
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Preview your new ID image</strong>
                </div>
                <img src="${event.target.result}" alt="Parent Valid ID" class="id-preview-image" id="parentIdPreview">
                <div class="mt-3 d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-success" onclick="confirmUploadParentId()">
                        <i class="fas fa-check me-1"></i>Update ID
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="cancelUploadParentId()">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                </div>
            </div>
        `;

                if (previewImage) {
                    previewImage.parentElement.innerHTML = previewHtml;
                } else if (placeholder) {
                    placeholder.innerHTML = previewHtml;
                }
            };

            reader.readAsDataURL(file);
        });

        function confirmUploadParentId() {
            if (!selectedParentId) {
                Swal.fire({
                    icon: 'error',
                    title: 'No File Selected',
                    text: 'Please select an ID image first.',
                    confirmButtonColor: '#02541b'
                });
                return;
            }

            Swal.fire({
                title: 'Uploading...',
                text: 'Please wait while we upload the ID.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('parent_id', selectedParentId);

            $.ajax({
                url: '../backend/upload_parent_id.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            confirmButtonColor: '#02541b'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message,
                            confirmButtonColor: '#02541b'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while uploading the ID.',
                        confirmButtonColor: '#02541b'
                    });
                }
            });
        }

        function cancelUploadParentId() {
            // Reset file input
            document.getElementById('parentIdInput').value = '';
            selectedParentId = null;

            // Restore original content
            const previewImage = document.getElementById('parentIdPreview');
            const placeholder = document.getElementById('idUploadPlaceholder');

            if (originalParentIdHtml) {
                if (previewImage && previewImage.parentElement) {
                    previewImage.parentElement.innerHTML = originalParentIdHtml;
                } else if (placeholder) {
                    placeholder.outerHTML = '<div class="id-upload-placeholder mb-3" id="idUploadPlaceholder">' + originalParentIdHtml + '</div>';
                }
            } else {
                // Fallback: reload page
                location.reload();
            }

            originalParentIdHtml = null;
        }

        function viewParentId() {
            const previewImage = document.getElementById('parentIdPreview');
            if (previewImage) {
                document.getElementById('parentIdModalImage').src = previewImage.src;
                const modal = new bootstrap.Modal(document.getElementById('parentIdModal'));
                modal.show();
            }
        }
    </script>
</body>

</html>
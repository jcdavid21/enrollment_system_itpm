<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/login.css">
    <link rel="stylesheet" href="../styles/general.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <title>Fonthills Christian School - Login</title>
    <style>
        .page-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .form-container {
            max-height: 680px;
            overflow-y: auto;
        }

        .terms-modal .modal-body {
            max-height: 60vh;
            overflow-y: auto;
        }

        .btn-primary-custom {
            background-color: #0c7c51;
            border-color: #0c7c51;
        }

        .btn-primary-custom:hover {
            background-color: #0a6642;
            border-color: #0a6642;
        }

        .text-primary-custom {
            color: #0c7c51 !important;
        }

        .text-primary-custom:hover {
            color: #0a6642 !important;
        }

        .hidden {
            display: none !important;
        }

        .form-switch .form-check-input:checked {
            background-color: #0c7c51;
            border-color: #0c7c51;
        }

        .alert {
            margin-bottom: 1rem;
        }

        .password-strength {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .strength-weak {
            color: #dc3545;
        }

        .strength-medium {
            color: #ffc107;
        }

        .strength-strong {
            color: #28a745;
        }

        input,
        textarea,
        select {
            border: 1px solid #afafafff !important;
        }

        .file-upload-container {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-container:hover {
            border-color: var(--primary-color);
            background: rgba(2, 84, 27, 0.05);
        }

        .file-upload-container.active {
            border-color: var(--success-color);
            background: rgba(39, 174, 96, 0.05);
        }

        .image-preview {
            max-width: 100%;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            display: none;
            background: #f8f9fa;
            text-align: center;
            transition: all 0.3s ease;
        }

        .image-preview.show {
            display: block;
            border-color: #0c7c51;
            background: rgba(12, 124, 81, 0.05);
        }

        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            object-fit: contain;
            background: white;
            padding: 5px;
        }

        .file-info {
            margin-top: 10px;
            padding: 8px;
            background: rgba(12, 124, 81, 0.1);
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .file-name {
            font-weight: 600;
            color: #0c7c51;
            margin-bottom: 2px;
        }

        .file-size {
            color: #6c757d;
            font-size: 0.8rem;
        }

        .remove-file {
            margin-top: 8px;
        }

        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-remove:hover {
            background: #c82333;
        }

        .verification-modal .modal-body {
            text-align: center;
            padding: 2rem;
        }

        .verification-code-input {
            font-size: 2rem;
            text-align: center;
            letter-spacing: 1rem;
            width: 300px;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <div class="page-container">
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col col-xl-11">
                    <div class="card shadow-lg" style="border-radius: 1rem;">
                        <div class="row g-0">
                            <div class="col-md-3 col-lg-4 d-none d-md-block">
                                <img src="../assets/logo.png" alt="School Logo" class="img-fluid object-fit-cover" style="border-radius: 1rem 0 0 1rem; height: 100%;" />
                            </div>
                            <div class="col-md-6 col-lg-8 d-flex align-items-center">
                                <div class="card-body p-4 p-lg-5 text-black form-container">

                                    <form action="" id="loginFormElement">
                                        <div id="loginForm">
                                            <div class="d-flex align-items-center mb-3 pb-1">
                                                <i class="fas fa-cubes fa-2x me-3" style="color: #0c7c51;"></i>
                                                <span class="h4 fw-semibold mb-0">Fonthills Christian School Q.C.</span>
                                            </div>

                                            <h5 class="fw-normal mb-3 pb-3" style="letter-spacing: 1px;">Sign into your account</h5>

                                            <div class="form-outline mb-4">
                                                <label class="form-label" for="loginEmail">Email address / Username</label>
                                                <input type="text" id="loginEmail"
                                                    name="loginEmail" class="form-control form-control-lg" required />
                                            </div>

                                            <div class="form-outline mb-4">
                                                <label class="form-label" for="loginPassword">Password</label>
                                                <div class="input-group">
                                                    <input type="password" id="loginPassword" name="loginPassword" class="form-control form-control-lg" required />
                                                    <button class="btn btn-outline-secondary" type="button" id="toggleLoginPassword">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="pt-1 mb-4">
                                                <button class="btn btn-primary-custom btn-lg w-100 text-white fw-bold" type="submit">Login</button>
                                            </div>

                                            <a href="#" class="small text-muted" onclick="showForgotPassword()">Forgot password?</a>
                                            <p class="mb-3 pb-lg-2" style="color: #393f81;">Do you want to enroll?
                                                <a href="#" onclick="showRegisterForm()" class="text-primary-custom">Enroll now</a>
                                            </p>
                                            <a href="#" class="small text-muted" data-bs-toggle="modal" data-bs-target="#termsModal">Terms of use</a> |
                                            <a href="#" class="small text-muted">Privacy policy</a>
                                        </div>
                                    </form>

                                    <!-- Register Form -->
                                    <form action="" id="registerFormElement">
                                        <div id="registerForm" class="hidden">
                                            <div class="d-flex align-items-center mb-3 pb-1">
                                                <i class="fas fa-user-plus fa-2x me-3" style="color: #0c7c51;"></i>
                                                <span class="h4 fw-semibold mb-0">Create Account</span>
                                            </div>

                                            <h5 class="fw-normal mb-3 pb-3" style="letter-spacing: 1px;">Student Registration</h5>

                                            <!-- Personal Details -->
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="firstName">First Name</label>
                                                    <input type="text" id="firstName" class="form-control" name="firstName" required />
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="lastName">Last Name</label>
                                                    <input type="text" id="lastName" class="form-control" name="lastName" required />
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="middleName">Middle Name</label>
                                                <input type="text" id="middleName" class="form-control" name="middleName" />
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="dateOfBirth">Date of Birth</label>
                                                    <input type="date" id="dateOfBirth" class="form-control" name="dateOfBirth" required />
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="gender">Gender</label>
                                                    <select id="gender" class="form-select" name="gender" required>
                                                        <option value="">Select Gender</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                        <option value="Other">Other</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Parent/Guardian Details -->
                                            <hr class="my-4">
                                            <h6 class="fw-semibold mb-3">
                                                <i class="fas fa-user-shield me-2" style="color: #0c7c51;"></i>
                                                Parent/Guardian Information
                                            </h6>

                                            <div class="mb-3">
                                                <label class="form-label" for="parentName">Parent/Guardian Full Name</label>
                                                <input type="text" id="parentName" class="form-control" name="parentName" required />
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="parentContact">Contact Number</label>
                                                    <input type="tel" id="parentContact"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                                                        class="form-control" name="parentContact" required />
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="relationship">Relationship</label>
                                                    <select id="relationship" class="form-select" name="relationship" required>
                                                        <option value="">Select Relationship</option>
                                                        <option value="Mother">Mother</option>
                                                        <option value="Father">Father</option>
                                                        <option value="Guardian">Guardian</option>
                                                    </select>
                                                </div>
                                            </div>


                                            <div class="mb-3">
                                                <label class="form-label" for="address">Address</label>
                                                <textarea id="address" class="form-control" rows="2" name="address" required></textarea>
                                            </div>

                                            <!-- Account Details -->
                                            <div class="mb-3">
                                                <label class="form-label" for="username">Username</label>
                                                <input type="text" id="username" class="form-control" name="username" required />
                                                <div class="form-text">Username must be unique and contain only letters and numbers</div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="email">Email Address</label>
                                                    <input type="email" id="email" class="form-control" name="email" required />
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="facebook">Facebook link</label>
                                                    <input type="url" id="facebook" class="form-control" name="facebook" required />
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="registerPassword">Password</label>
                                                <div class="input-group">
                                                    <input type="password" id="registerPassword" class="form-control" name="registerPassword" required />
                                                    <button class="btn btn-outline-secondary" type="button" id="toggleRegisterPassword">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                                <div id="passwordStrength" class="password-strength"></div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="confirmPassword">Confirm Password</label>
                                                <input type="password" id="confirmPassword" class="form-control" name="confirmPassword" required />
                                                <div id="passwordMatch" class="form-text"></div>
                                            </div>
                                            <div class="mb-5">
                                                <div class="row col-md-12">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label text-muted fw-semibold">Valid ID *</label>
                                                        <div class="file-upload-container" onclick="document.getElementById('prevIdSchoolFile').click()">
                                                            <i class="fas fa-cloud-upload-alt fs-3 mb-2 text-muted"></i>
                                                            <p class="mb-1 text-muted">Click to upload Valid ID</p>
                                                            <small class="text-muted">PDF, JPG, PNG (Max 5MB)</small>
                                                            <input type="file" id="prevIdSchoolFile" name="prev_id_school_file" class="d-none" accept="image/*" onchange="previewImage(this)">
                                                        </div>
                                                    </div>
                                                    <div id="imagePreview" class="image-preview col-md-6">
                                                        <img id="previewImg" src="" alt="ID Preview" class="preview-image">
                                                        <div class="file-info">
                                                            <div id="fileName" class="file-name"></div>
                                                            <div id="fileSize" class="file-size"></div>
                                                            <div class="remove-file">
                                                                <button type="button" class="btn-remove" onclick="removeFile()">Remove File</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Terms Agreement -->
                                            <div class="form-check mb-4">
                                                <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                                <label class="form-check-label" for="agreeTerms">
                                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="text-primary-custom">Terms and Conditions</a>
                                                </label>
                                            </div>

                                            <div class="pt-1 mb-4">
                                                <button class="btn btn-primary-custom btn-lg w-100 fw-bold text-white" type="button" onclick="handleRegister()">Register</button>
                                            </div>

                                            <p class="mb-3 text-center">
                                                Already have an account?
                                                <a href="#" onclick="showLoginForm()" class="text-primary-custom">Login here</a>
                                            </p>
                                        </div>
                                    </form>

                                    <!-- Forgot Password Form -->
                                    <div id="forgotPasswordForm" class="hidden">
                                        <div class="d-flex align-items-center mb-3 pb-1">
                                            <i class="fas fa-key fa-2x me-3" style="color: #0c7c51;"></i>
                                            <span class="h4 fw-semibold mb-0">Reset Password</span>
                                        </div>

                                        <h5 class="fw-normal mb-3 pb-3" style="letter-spacing: 1px;">Enter your email to reset password</h5>

                                        <div class="mb-4">
                                            <label class="form-label" for="resetEmail">Email Address</label>
                                            <input type="email" id="resetEmail" class="form-control form-control-lg" required />
                                            <div class="form-text">We'll send you a link to reset your password</div>
                                        </div>

                                        <div class="pt-1 mb-4">
                                            <button class="btn btn-primary-custom btn-lg w-100" type="button" onclick="handleForgotPassword()">Send Reset Link</button>
                                        </div>

                                        <p class="text-center">
                                            Remember your password?
                                            <a href="#" onclick="showLoginForm()" class="text-primary-custom">Back to Login</a>
                                        </p>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade terms-modal" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #0c7c51; color: white;">
                    <h5 class="modal-title" id="termsModalLabel">
                        <i class="fas fa-file-contract me-2"></i>Terms and Conditions
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="terms-content">
                        <h6 class="fw-bold text-primary-custom">1. ACCEPTANCE OF TERMS</h6>
                        <p>By registering and enrolling at Fonthills Christian School Q.C., you acknowledge that you have read, understood, and agree to be bound by these Terms and Conditions.</p>

                        <h6 class="fw-bold text-primary-custom">2. ENROLLMENT POLICIES</h6>
                        <p><strong>2.1 Eligibility:</strong> Students must meet age requirements and academic prerequisites for their intended grade level.</p>
                        <p><strong>2.2 Application Process:</strong> All required documents must be submitted completely and accurately. Incomplete applications will not be processed.</p>
                        <p><strong>2.3 Admission Decision:</strong> The school reserves the right to accept or decline any application based on academic, behavioral, and space availability criteria.</p>

                        <h6 class="fw-bold text-primary-custom">3. FINANCIAL OBLIGATIONS</h6>
                        <p><strong>3.1 Fee Structure:</strong> All fees are as published in the school's official fee structure and are subject to change with proper notice.</p>
                        <p><strong>3.2 Payment Terms:</strong> Fees must be paid according to the established schedule. Late payments may incur additional charges.</p>
                        <p><strong>3.3 Refund Policy:</strong> Refunds are governed by the school's official refund policy and are processed according to the terms therein.</p>

                        <h6 class="fw-bold text-primary-custom">4. ACADEMIC REQUIREMENTS</h6>
                        <p><strong>4.1 Attendance:</strong> Regular attendance is mandatory. Excessive absences may result in academic penalties or dismissal.</p>
                        <p><strong>4.2 Academic Performance:</strong> Students must maintain satisfactory academic progress as defined by school standards.</p>
                        <p><strong>4.3 Curriculum:</strong> The school reserves the right to modify curriculum and academic requirements as deemed necessary.</p>

                        <h6 class="fw-bold text-primary-custom">5. STUDENT CONDUCT</h6>
                        <p><strong>5.1 Code of Conduct:</strong> All students must adhere to the school's Student Code of Conduct and disciplinary policies.</p>
                        <p><strong>5.2 Disciplinary Actions:</strong> The school reserves the right to impose disciplinary measures, including suspension or expulsion, for violations of school policies.</p>
                        <p><strong>5.3 Christian Values:</strong> As a Christian institution, students are expected to respect and uphold Christian values and principles.</p>

                        <h6 class="fw-bold text-primary-custom">6. PARENT/GUARDIAN RESPONSIBILITIES</h6>
                        <p><strong>6.1 Cooperation:</strong> Parents/guardians must cooperate with school personnel and support educational objectives.</p>
                        <p><strong>6.2 Communication:</strong> Maintain open and respectful communication with school staff and administration.</p>
                        <p><strong>6.3 Student Support:</strong> Provide necessary support for their child's educational and personal development.</p>

                        <h6 class="fw-bold text-primary-custom">7. HEALTH AND SAFETY</h6>
                        <p><strong>7.1 Medical Information:</strong> Accurate medical information must be provided and updated as necessary.</p>
                        <p><strong>7.2 Emergency Contacts:</strong> Current emergency contact information must be maintained with the school.</p>
                        <p><strong>7.3 Safety Protocols:</strong> All safety protocols and emergency procedures must be followed.</p>

                        <h6 class="fw-bold text-primary-custom">8. PRIVACY AND DATA PROTECTION</h6>
                        <p><strong>8.1 Information Use:</strong> Student information will be used solely for educational and administrative purposes.</p>
                        <p><strong>8.2 Confidentiality:</strong> The school maintains confidentiality of student records in accordance with applicable laws.</p>
                        <p><strong>8.3 Photo/Video Release:</strong> By enrollment, consent is given for use of student photos/videos in school-related materials unless specifically opted out.</p>

                        <h6 class="fw-bold text-primary-custom">9. TECHNOLOGY USE</h6>
                        <p><strong>9.1 Acceptable Use:</strong> Technology resources must be used responsibly and in accordance with the school's Acceptable Use Policy.</p>
                        <p><strong>9.2 Digital Citizenship:</strong> Students must demonstrate appropriate digital citizenship and online behavior.</p>

                        <h6 class="fw-bold text-primary-custom">10. TRANSPORTATION</h6>
                        <p><strong>10.1 School Transport:</strong> Use of school transportation services is governed by separate transportation policies.</p>
                        <p><strong>10.2 Pick-up/Drop-off:</strong> Parents/guardians are responsible for timely and safe pick-up and drop-off procedures.</p>

                        <h6 class="fw-bold text-primary-custom">11. LIMITATION OF LIABILITY</h6>
                        <p>The school's liability is limited to the extent permitted by law. The school is not responsible for personal property loss or damage unless due to proven negligence.</p>

                        <h6 class="fw-bold text-primary-custom">12. AMENDMENTS</h6>
                        <p>These terms may be amended by the school administration with appropriate notice to affected parties.</p>

                        <h6 class="fw-bold text-primary-custom">13. GOVERNING LAW</h6>
                        <p>These terms are governed by the laws of the Republic of the Philippines and the jurisdiction of appropriate courts.</p>

                        <div class="alert alert-info mt-4">
                            <strong>Last Updated:</strong> September 12, 2025<br>
                            <strong>Effective Date:</strong> School Year 2025-2026
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary-custom" onclick="acceptTerms()">I Accept These Terms</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Verification Modal -->
    <div class="modal fade verification-modal" id="verificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background-color:#0c7c51;color:white;">
                    <h5 class="modal-title"><i class="fas fa-envelope me-2"></i>Email Verification</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Enter the 6-digit verification code sent to your email:</p>
                    <input type="text" id="verificationCode" maxlength="6" class="form-control verification-code-input mb-3">
                    <div id="countdownTimer" class="text-muted small"></div>
                    <button class="btn btn-primary-custom w-100 fw-bold text-white mt-3" onclick="verifyCode()">Verify</button>
                </div>
            </div>
        </div>
    </div>


    <script>
        // Form switching functions
        function showLoginForm() {
            document.getElementById('loginForm').classList.remove('hidden');
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById('forgotPasswordForm').classList.add('hidden');
        }

        function showRegisterForm() {
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerForm').classList.remove('hidden');
            document.getElementById('forgotPasswordForm').classList.add('hidden');
        }

        function showForgotPassword() {
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById('forgotPasswordForm').classList.remove('hidden');
        }

        // Password visibility toggle
        document.getElementById('toggleLoginPassword').addEventListener('click', function() {
            togglePasswordVisibility('loginPassword', this);
        });

        document.getElementById("registerFormElement").addEventListener("input", function(event) {
            // Skip file inputs when saving to localStorage
            if (event.target.type !== 'file') {
                localStorage.setItem(event.target.name, event.target.value);
            }
        });

        window.addEventListener("load", function() {
            const form_elements = document.querySelectorAll("#registerFormElement input:not([type='file']), #registerFormElement textarea, #registerFormElement select");
            form_elements.forEach(function(element) {
                const saved_value = localStorage.getItem(element.name);
                if (saved_value) {
                    element.value = saved_value;
                }
            });
        });

        document.getElementById('toggleRegisterPassword').addEventListener('click', function() {
            togglePasswordVisibility('registerPassword', this);
        });

        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');

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

        // Password strength checker
        document.getElementById('registerPassword').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('passwordStrength');
            let strength = 0;
            let feedback = '';

            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            switch (strength) {
                case 0:
                case 1:
                case 2:
                    feedback = '<span class="strength-weak">Weak password</span>';
                    break;
                case 3:
                case 4:
                    feedback = '<span class="strength-medium">Medium password</span>';
                    break;
                case 5:
                    feedback = '<span class="strength-strong">Strong password</span>';
                    break;
            }

            strengthDiv.innerHTML = feedback;
        }

        // Password confirmation checker
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = this.value;
            const matchDiv = document.getElementById('passwordMatch');

            if (confirmPassword === '') {
                matchDiv.innerHTML = '';
                return;
            }

            if (password === confirmPassword) {
                matchDiv.innerHTML = '<span class="text-success">Passwords match</span>';
            } else {
                matchDiv.innerHTML = '<span class="text-danger">Passwords do not match</span>';
            }
        });

        // Terms modal functions
        function acceptTerms() {
            document.getElementById('agreeTerms').checked = true;
            bootstrap.Modal.getInstance(document.getElementById('termsModal')).hide();

            Swal.fire({
                icon: 'success',
                title: 'Terms Accepted',
                text: 'Thank you for accepting our terms and conditions.',
                timer: 1500,
                showConfirmButton: false
            });
        }

        // Global variables
        let currentUserEmail = '';
        let countdownTimer = null;

        function previewImage(input) {
            const file = input.files[0];
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');

            if (file) {
                // Validate file size (5MB = 5 * 1024 * 1024 bytes)
                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Please select a file smaller than 5MB.',
                    });
                    input.value = '';
                    preview.classList.remove('show');
                    return;
                }

                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                if (!allowedTypes.includes(file.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid File Type',
                        text: 'Please select a JPG, PNG, or PDF file.',
                    });
                    input.value = '';
                    preview.classList.remove('show');
                    return;
                }

                // Format file size
                const formatFileSize = (bytes) => {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                };

                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);

                if (file.type === 'application/pdf') {
                    previewImg.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMjQgMjQiIGZpbGw9IiNkYzM1NDUiPjxwYXRoIGQ9Ik0yMCA2djE0SDR2LTE0aDZ2LTJINnY0aC0yVjJoMTZWNmgtMlY0aDJWMGgtMTZ2MjJoMTZ6bS02IDE0di00aDJWMTRoLTJWMTJoLTJ2MmgtMnYyaDJWMThoMlY2aDJ6Ii8+PC9zdmc+';
                    previewImg.alt = 'PDF File';
                } else {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        previewImg.alt = 'ID Preview';
                    };
                    reader.readAsDataURL(file);
                }

                preview.classList.add('show');
            } else {
                preview.classList.remove('show');
            }
        }

        // Add this new function for removing files
        function removeFile() {
            const input = document.getElementById('prevIdSchoolFile');
            const preview = document.getElementById('imagePreview');

            input.value = '';
            preview.classList.remove('show');

            Swal.fire({
                icon: 'info',
                title: 'File Removed',
                text: 'The uploaded file has been removed.',
                timer: 1500,
                showConfirmButton: false
            });
        }

        // Validation before sending verification email
        function validateFormBeforeVerification() {
            const requiredFields = [
                'firstName', 'lastName', 'dateOfBirth', 'gender',
                'parentName', 'parentContact', 'relationship', 'address',
                'username', 'email', 'registerPassword', 'confirmPassword'
            ];

            // Check required fields
            for (let field of requiredFields) {
                const element = document.getElementById(field);
                if (!element.value.trim()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Information',
                        text: `Please fill in the ${field.replace(/([A-Z])/g, ' $1').toLowerCase()}.`,
                    });
                    element.focus();
                    return false;
                }
            }

            // Check file upload
            const fileInput = document.getElementById('prevIdSchoolFile');
            if (!fileInput.files[0]) {
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Document',
                    text: 'Please upload a valid ID document.',
                });
                return false;
            }

            // Check password match
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            if (password !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'Passwords do not match.',
                });
                return false;
            }

            // Check terms agreement
            if (!document.getElementById('agreeTerms').checked) {
                Swal.fire({
                    icon: 'error',
                    title: 'Terms Agreement Required',
                    text: 'Please accept the terms and conditions.',
                });
                return false;
            }

            return true;
        }

        // Send verification email
        function sendVerificationEmail() {
            if (!validateFormBeforeVerification()) {
                return false;
            }

            const form = document.getElementById("registerFormElement");
            const formData = new FormData(form);

            currentUserEmail = formData.get('email');

            Swal.fire({
                title: 'Sending Verification Email...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "../backend/send_verification.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        $('#verificationModal').modal('show');
                        startCountdown(900); // 15 minutes
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to Send Email',
                            text: response.message,
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error);
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Email Send Failed',
                        text: 'Unable to send verification email. Please try again.',
                    });
                }
            });
        }

        // Verify code
        function verifyCode() {
            const code = document.getElementById('verificationCode').value;

            if (code.length !== 6) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Code',
                    text: 'Please enter the 6-digit verification code.',
                });
                return;
            }

            Swal.fire({
                title: 'Verifying Code...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "../backend/verify_code.php",
                type: "POST",
                data: {
                    email: currentUserEmail,
                    code: code
                },
                dataType: "json",
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        $('#verificationModal').modal('hide');
                        clearInterval(countdownTimer);

                        Swal.fire({
                            icon: 'success',
                            title: 'Email Verified!',
                            text: 'Your email has been verified successfully. Completing registration...',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            completeRegistration();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Verification Failed',
                            text: response.message,
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error);
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Verification Failed',
                        text: 'Unable to verify code. Please try again.',
                    });
                }
            });
        }

        // Complete registration after verification
        function completeRegistration() {
            Swal.fire({
                title: 'Completing Registration...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "../backend/complete_registration.php",
                type: "POST",
                data: {
                    email: currentUserEmail
                },
                dataType: "json",
                success: function(response) {
                    Swal.close();
                    console.log("Registration response:", response.message);
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Registration Complete!',
                            text: response.message,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            localStorage.clear();
                            window.location.href = "index.php";
                        });
                    } else {
                        console.error("Registration error:", response.message);
                        Swal.fire({
                            icon: 'error',
                            title: 'Registration Failed',
                            text: response.message,
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error:", status, error);
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Registration Failed',
                        text: 'Unable to complete registration. Please contact support.',
                    });
                }
            });
        }

        // Resend verification code
        function resendCode() {
            const form = document.getElementById("registerFormElement");
            const formData = new FormData(form);

            Swal.fire({
                title: 'Resending Code...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "../backend/send_verification.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        document.getElementById('verificationCode').value = '';
                        startCountdown(900);
                        Swal.fire({
                            icon: 'success',
                            title: 'Code Resent',
                            text: 'A new verification code has been sent to your email.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to Resend',
                            text: response.message,
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Resend Failed',
                        text: 'Unable to resend verification code.',
                    });
                }
            });
        }

        // Countdown timer
        function startCountdown(seconds) {
            const countdownDiv = document.getElementById('countdownTimer');

            // Check if the element exists
            if (!countdownDiv) {
                console.error('Countdown timer element not found');
                return;
            }

            countdownDiv.style.display = 'block';
            countdownDiv.innerHTML = ''; // Clear any existing content

            countdownTimer = setInterval(() => {
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = seconds % 60;

                if (countdownDiv) {
                    countdownDiv.innerHTML = `
                <div class="text-center">
                    <small class="text-muted">Code expires in ${minutes}:${remainingSeconds.toString().padStart(2, '0')}</small>
                    <br>
                    <button class="btn btn-link btn-sm text-primary-custom mt-2" onclick="resendCode()" ${seconds > 0 ? 'disabled' : ''}>
                        ${seconds > 0 ? 'Resend available after expiry' : 'Resend Code'}
                    </button>
                </div>
            `;
                }

                if (seconds <= 0) {
                    clearInterval(countdownTimer);
                    if (countdownDiv) {
                        countdownDiv.innerHTML = `
                    <div class="text-center">
                        <small class="text-danger">Code expired</small>
                        <br>
                        <button class="btn btn-link btn-sm text-primary-custom mt-2" onclick="resendCode()">
                            Resend Code
                        </button>
                    </div>
                `;
                    }
                }
                seconds--;
            }, 1000);
        }
    </script>
    <script src="../js/enroll.js"></script>
</body>

</html>
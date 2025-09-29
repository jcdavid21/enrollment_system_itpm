<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Add these styles to your existing CSS */
        .mobile-navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .dropdown-header-mobile {
            color: rgba(255, 255, 255, 0.7) !important;
            font-weight: 600;
            font-size: 0.8rem;
            margin-top: 1rem;
        }
        
        .navbar-nav-mobile .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 0.2rem 0;
            transition: all 0.3s ease;
        }
        
        .navbar-nav-mobile .nav-link:hover,
        .navbar-nav-mobile .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white !important;
        }
        
        .navbar-nav-mobile .nav-icon {
            margin-right: 0.75rem;
            width: 20px;
        }
    </style>
</head>

<body>
    <!-- Desktop Sidebar -->
    <div class="desktop-sidebar d-none d-md-block">
        <div class="sidebar-header">
            <img src="../assets/logo.png" class="object-fit-cover rounded-circle mx-auto d-block mt-3 mb-2"
            style="height: 120px; width: 120px;" alt="">
        </div>

        <ul class="nav-menu">
            <!-- Dashboard -->
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link-custom <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <!-- Student Details -->
            <div class="nav-section">Student Details</div>
            <li class="nav-item">
                <a href="./profile.php" class="nav-link-custom <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
                    <i class="fas fa-user nav-icon"></i>
                    <span class="nav-text">Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="./files_requirement.php" class="nav-link-custom <?php echo basename($_SERVER['PHP_SELF']) == 'files_requirement.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-file nav-icon"></i>
                    <span class="nav-text">File Requirements</span>
                </a>
            </li>
            
            <!-- Enrollment Section -->
            <div class="nav-section">Enrollment</div>
            <li class="nav-item">
                <a href="./enrollment_form.php" class="nav-link-custom <?php echo basename($_SERVER['PHP_SELF']) == 'enrollment_form.php' ? 'active' : '' ?>">
                    <i class="fas fa-book nav-icon"></i>
                    <span class="nav-text">Enrollment Form</span>
                </a>
            </li>
            
            <!-- Others -->
            <div class="nav-section">Others</div>
            <li class="nav-item">
                <a href="./account.php" class="nav-link-custom <?php echo basename($_SERVER['PHP_SELF']) == 'account.php' ? 'active' : '' ?>">
                    <i class="fas fa-money-bill nav-icon"></i>
                    <span class="nav-text">Accounting</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="./faqs.php" class="nav-link-custom <?php echo basename($_SERVER['PHP_SELF']) == 'faqs.php' ? 'active' : '' ?>">
                    <i class="fas fa-question-circle nav-icon"></i>
                    <span class="nav-text">FAQs</span>
                </a>
            </li>
        </ul>

        <div class="nav-section">Settings</div>
        <div class="user-info">
            <div class="user-profile" onclick="logout()" style="cursor: pointer;">
                <div class="user-details">
                    <div class="user-name">Log out</div>
                </div>
                <i class="fas fa-sign-out-alt logout-btn"></i>
            </div>
        </div>
    </div>

    <!-- Mobile Navbar -->
    <nav class="navbar navbar-expand-md navbar-dark fixed-top mobile-navbar d-md-none">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="../assets/logo.png" class="object-fit-cover rounded-circle" 
                     style="height: 40px; width: 40px;" alt="Logo">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNavbar" 
                    aria-controls="mobileNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mobileNavbar">
                <ul class="navbar-nav navbar-nav-mobile w-100 mt-3">

                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="fas fa-tachometer-alt nav-icon"></i>Dashboard
                        </a>
                    </li>

                    <!-- Student Details -->
                    <li>
                        <h6 class="dropdown-header dropdown-header-mobile">Student Details</h6>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>" href="./profile.php">
                            <i class="fas fa-user nav-icon"></i>Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'files_requirement.php' ? 'active' : '' ?>" href="./files_requirement.php">
                            <i class="fa-solid fa-file nav-icon"></i>File Requirements
                        </a>
                    </li>

                    <!-- Enrollment -->
                    <li>
                        <h6 class="dropdown-header dropdown-header-mobile">Enrollment</h6>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'enrollment_form.php' ? 'active' : '' ?>" href="./enrollment_form.php">
                            <i class="fas fa-book nav-icon"></i>Enrollment Form
                        </a>
                    </li>

                    <!-- Others -->
                    <li>
                        <h6 class="dropdown-header dropdown-header-mobile">Others</h6>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'account.php' ? 'active' : '' ?>" href="./account.php">
                            <i class="fas fa-money-bill nav-icon"></i>Accounting
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'faqs.php' ? 'active' : '' ?>" href="./faqs.php">
                            <i class="fas fa-question-circle nav-icon"></i>FAQs
                        </a>
                    </li>

                    <!-- Logout -->
                    <li>
                        <h6 class="dropdown-header dropdown-header-mobile">Settings</h6>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="#" onclick="logout()">
                            <i class="fas fa-sign-out-alt nav-icon"></i>Logout
                        </a>
                    </li>

                </ul>
            </div>
        </div>
    </nav>

    <!-- Fixed Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Handle window resize
            $(window).on('resize', function() {
                if ($(window).width() > 768) {
                    $('.navbar-collapse').collapse('hide');
                }
            });

            // Initialize tooltips (only if Bootstrap is loaded)
            if (typeof bootstrap !== 'undefined') {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
        });

        function logout(){
            Swal.fire({
                title: "Signing out...",
                timer: 2000,
                didOpen: () => {
                    Swal.showLoading();
                    // Simulate a delay for demonstration purposes
                    setTimeout(() => {
                        localStorage.clear(); // Clear local storage
                        window.location.href = "./logout.php"; // Redirect to logout.php
                    }, 1500);
                }
            })
        }

        function showSuccessMessage(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                timer: 3000,
                timerProgressBar: true
            });
        }

        function showErrorMessage(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: message
            });
        }
    </script>
</body>

</html>
<!DOCTYPE html>
<html lang="en">
<?php
session_start();
require_once "../backend/config.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "Student") {
    header("Location: ./logout.php");
    exit();
}
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
    <title>Fonthills Christian School - FAQs</title>
    <style>
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(2, 84, 27, 0.3);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .faq-container {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .faq-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .category-btn {
            padding: 1rem;
            border: 2px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }

        .category-btn:hover, .category-btn.active {
            background: var(--primary-color);
            color: white;
        }

        .category-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .faq-section {
            margin-bottom: 2rem;
        }

        .section-title {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .accordion-item {
            border: none;
            margin-bottom: 1rem;
            border-radius: 12px !important;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }

        .accordion-button {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: none;
            font-weight: 600;
            color: var(--primary-color);
            padding: 1.2rem;
            border-radius: 12px !important;
        }

        .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .accordion-button:focus {
            box-shadow: none;
            border: none;
        }

        .accordion-body {
            background: white;
            padding: 1.5rem;
            color: #555;
            line-height: 1.6;
        }

        .highlight-box {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border-left: 4px solid var(--warning-color);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .contact-section {
            background-color: #117139ff;
            color: white;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            margin-top: 2rem;
        }

        .contact-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 10px;
            margin: 0.5rem;
            display: inline-block;
            min-width: 200px;
        }

        .search-box {
            position: relative;
            margin-bottom: 2rem;
        }

        .search-input {
            width: 100%;
            padding: 1rem 3rem 1rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 50px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(2, 84, 27, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .no-results {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .faq-categories {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>
    <?php include_once "./sidebar.php"; ?>

    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="position-relative z-2">
                <h1 class="mb-2"><i class="fas fa-question-circle me-3"></i>Frequently Asked Questions</h1>
                <p class="mb-0 opacity-75">Find answers to common questions about enrollment, payments, and school policies</p>
            </div>
        </div>

        <!-- FAQ Container -->
        <div class="faq-container">
            <!-- Search Box -->
            <div class="search-box">
                <input type="text" class="search-input" id="faqSearch" placeholder="Search for questions...">
                <i class="fas fa-search search-icon"></i>
            </div>

            <!-- Category Navigation -->
            <div class="faq-categories">
                <a href="#" class="category-btn active" data-category="all">
                    <i class="fas fa-th-list category-icon"></i>
                    All Questions
                </a>
                <a href="#" class="category-btn" data-category="enrollment">
                    <i class="fas fa-graduation-cap category-icon"></i>
                    Enrollment
                </a>
                <a href="#" class="category-btn" data-category="payments">
                    <i class="fas fa-credit-card category-icon"></i>
                    Payments
                </a>
                <a href="#" class="category-btn" data-category="requirements">
                    <i class="fas fa-file-alt category-icon"></i>
                    Requirements
                </a>
                <a href="#" class="category-btn" data-category="academic">
                    <i class="fas fa-book category-icon"></i>
                    Academic
                </a>
            </div>

            <!-- Enrollment FAQs -->
            <div class="faq-section" data-category="enrollment">
                <h3 class="section-title"><i class="fas fa-graduation-cap me-2"></i>Enrollment Process</h3>
                <div class="accordion" id="enrollmentAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#enroll1">
                                How do I enroll my child at Fonthills Christian School?
                            </button>
                        </h2>
                        <div id="enroll1" class="accordion-collapse collapse" data-bs-parent="#enrollmentAccordion">
                            <div class="accordion-body">
                                To enroll your child, follow these steps:
                                <ol>
                                    <li>Create an account on our enrollment portal</li>
                                    <li>Complete the student's personal information</li>
                                    <li>Submit all required documents</li>
                                    <li>Pay the initial fees (registration and miscellaneous fees)</li>
                                    <li>Wait for approval from the school administration</li>
                                </ol>
                                <div class="highlight-box">
                                    <strong>Important:</strong> Newly registered students must bring their original PSA/Birth Certificate to the school office for physical verification.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#enroll2">
                                What are the enrollment deadlines for Academic Year 2025-2026?
                            </button>
                        </h2>
                        <div id="enroll2" class="accordion-collapse collapse" data-bs-parent="#enrollmentAccordion">
                            <div class="accordion-body">
                                <ul>
                                    <li><strong>Early Enrollment:</strong> January 15 - March 31, 2025</li>
                                    <li><strong>Regular Enrollment:</strong> April 1 - May 31, 2025</li>
                                    <li><strong>Late Enrollment:</strong> June 1 - June 15, 2025 (with late fees)</li>
                                </ul>
                                <p>We recommend enrolling during the early enrollment period to secure your slot and avoid late fees.</p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#enroll3">
                                Can transferee students enroll mid-year?
                            </button>
                        </h2>
                        <div id="enroll3" class="accordion-collapse collapse" data-bs-parent="#enrollmentAccordion">
                            <div class="accordion-body">
                                Yes, transferee students can enroll mid-year subject to slot availability. Additional requirements include:
                                <ul>
                                    <li>Transfer credentials (Form 137-A)</li>
                                    <li>Certificate of Good Moral Character from previous school</li>
                                    <li>Report card from previous school</li>
                                </ul>
                                <p>Tuition fees will be pro-rated based on the remaining months in the school year.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment FAQs -->
            <div class="faq-section" data-category="payments">
                <h3 class="section-title"><i class="fas fa-credit-card me-2"></i>Payments & Fees</h3>
                <div class="accordion" id="paymentsAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pay1">
                                What are the total fees for each grade level?
                            </button>
                        </h2>
                        <div id="pay1" class="accordion-collapse collapse" data-bs-parent="#paymentsAccordion">
                            <div class="accordion-body">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Grade Level</th>
                                            <th>Total Annual Fee</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>Kinder 1 & 2</td><td>₱18,000</td></tr>
                                        <tr><td>Grade 1-6</td><td>₱20,000</td></tr>
                                    </tbody>
                                </table>
                                <p><small>Fees include: Registration (₱2,500), Miscellaneous (₱2,500), Books (varies), and Tuition (₱10,000)</small></p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pay2">
                                What payment methods are accepted?
                            </button>
                        </h2>
                        <div id="pay2" class="accordion-collapse collapse" data-bs-parent="#paymentsAccordion">
                            <div class="accordion-body">
                                We accept the following payment methods:
                                <ul>
                                    <li><strong>Cash:</strong> Pay directly at the school cashier</li>
                                    <li><strong>Bank Transfer:</strong> Direct deposit to our school account</li>
                                    <li><strong>GCash:</strong> Mobile payment via GCash</li>
                                    <li><strong>Online Banking:</strong> Through your bank's online portal</li>
                                </ul>
                                <div class="highlight-box">
                                    <strong>Note:</strong> Always keep your official receipts as proof of payment.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pay3">
                                Can I pay in installments?
                            </button>
                        </h2>
                        <div id="pay3" class="accordion-collapse collapse" data-bs-parent="#paymentsAccordion">
                            <div class="accordion-body">
                                Yes! We offer flexible payment options:
                                <ul>
                                    <li><strong>Full Payment:</strong> Pay the entire amount upon enrollment with 5% discount</li>
                                    <li><strong>Monthly Installments:</strong> ₱1,000 per month for 10 months (June-March)</li>
                                    <li><strong>Quarterly:</strong> Split into 4 equal payments</li>
                                </ul>
                                <p><strong>Required upon enrollment:</strong> Registration and Miscellaneous fees must be paid in full.</p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#pay4">
                                What happens if I miss a payment deadline?
                            </button>
                        </h2>
                        <div id="pay4" class="accordion-collapse collapse" data-bs-parent="#paymentsAccordion">
                            <div class="accordion-body">
                                Late payment policies:
                                <ul>
                                    <li><strong>Grace Period:</strong> 5 days after due date without penalty</li>
                                    <li><strong>Late Fee:</strong> ₱100 after grace period</li>
                                    <li><strong>Suspension:</strong> Student may be suspended from classes after 1 month of non-payment</li>
                                    <li><strong>Non-Readmission:</strong> Outstanding balances must be settled before next school year</li>
                                </ul>
                                <p>Contact the finance office immediately if you're experiencing payment difficulties to discuss arrangements.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requirements FAQs -->
            <div class="faq-section" data-category="requirements">
                <h3 class="section-title"><i class="fas fa-file-alt me-2"></i>Requirements & Documents</h3>
                <div class="accordion" id="requirementsAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#req1">
                                What documents are required for enrollment?
                            </button>
                        </h2>
                        <div id="req1" class="accordion-collapse collapse" data-bs-parent="#requirementsAccordion">
                            <div class="accordion-body">
                                Required documents for all students:
                                <ul>
                                    <li><strong>PSA/Birth Certificate</strong> (Original and photocopy)</li>
                                    <li><strong>Report Card</strong> from previous school</li>
                                    <li><strong>Good Moral Certificate</strong></li>
                                    <li><strong>2x2 ID Pictures</strong> (4 copies)</li>
                                    <li><strong>Form 137</strong> (for transferees)</li>
                                </ul>
                                <div class="highlight-box">
                                    <strong>For New Students:</strong> Original PSA/Birth Certificate must be presented physically at the school office for verification.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#req2">
                                How long does document verification take?
                            </button>
                        </h2>
                        <div id="req2" class="accordion-collapse collapse" data-bs-parent="#requirementsAccordion">
                            <div class="accordion-body">
                                Document verification timeline:
                                <ul>
                                    <li><strong>Complete Requirements:</strong> 3-5 business days</li>
                                    <li><strong>Incomplete Requirements:</strong> Processing will be delayed until all documents are submitted</li>
                                    <li><strong>Peak Season:</strong> During enrollment season, processing may take up to 7 business days</li>
                                </ul>
                                <p>You can check your requirements status in the student portal under "Requirements Status".</p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#req3">
                                What if I lost some of my documents?
                            </button>
                        </h2>
                        <div id="req3" class="accordion-collapse collapse" data-bs-parent="#requirementsAccordion">
                            <div class="accordion-body">
                                If you've lost required documents:
                                <ul>
                                    <li><strong>PSA/Birth Certificate:</strong> Request from PSA online or visit their office</li>
                                    <li><strong>Report Card:</strong> Request certified true copy from previous school</li>
                                    <li><strong>Good Moral:</strong> Request from previous school's guidance office</li>
                                    <li><strong>Form 137:</strong> Request from previous school's registrar</li>
                                </ul>
                                <p>Contact our registrar's office for assistance in obtaining replacement documents.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic FAQs -->
            <div class="faq-section" data-category="academic">
                <h3 class="section-title"><i class="fas fa-book me-2"></i>Academic Information</h3>
                <div class="accordion" id="academicAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acad1">
                                What is the school calendar for Academic Year 2025-2026?
                            </button>
                        </h2>
                        <div id="acad1" class="accordion-collapse collapse" data-bs-parent="#academicAccordion">
                            <div class="accordion-body">
                                Academic Calendar highlights:
                                <ul>
                                    <li><strong>Classes Start:</strong> August 26, 2025</li>
                                    <li><strong>First Quarter:</strong> August 26 - October 25, 2025</li>
                                    <li><strong>Second Quarter:</strong> October 28 - January 17, 2026</li>
                                    <li><strong>Third Quarter:</strong> January 20 - March 27, 2026</li>
                                    <li><strong>Fourth Quarter:</strong> March 30 - June 12, 2026</li>
                                    <li><strong>Graduation:</strong> March 28, 2026</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acad2">
                                What are the school hours?
                            </button>
                        </h2>
                        <div id="acad2" class="accordion-collapse collapse" data-bs-parent="#academicAccordion">
                            <div class="accordion-body">
                                Regular school hours:
                                <ul>
                                    <li><strong>Kinder 1-2:</strong> 8:00 AM - 11:00 AM</li>
                                    <li><strong>Grade 1-3:</strong> 7:30 AM - 12:00 PM</li>
                                    <li><strong>Grade 4-6:</strong> 7:30 AM - 3:30 PM</li>
                                </ul>
                                <p><strong>Extended Care:</strong> Available until 5:00 PM for working parents (additional fee applies).</p>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#acad3">
                                Does the school provide textbooks and learning materials?
                            </button>
                        </h2>
                        <div id="acad3" class="accordion-collapse collapse" data-bs-parent="#academicAccordion">
                            <div class="accordion-body">
                                Yes, the books fee covers:
                                <ul>
                                    <li>All required textbooks for the grade level</li>
                                    <li>Workbooks and activity sheets</li>
                                    <li>Basic school supplies (notebooks, pencils, etc.)</li>
                                    <li>Art and project materials</li>
                                </ul>
                                <p><strong>Note:</strong> Some specialized supplies for special projects may require additional purchases.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Results Message -->
            <div id="noResults" class="no-results" style="display: none;">
                <i class="fas fa-search fa-3x mb-3"></i>
                <h5>No results found</h5>
                <p>Try adjusting your search terms or browse by category.</p>
            </div>
        </div>

        <!-- Contact Section -->
        <div class="contact-section">
            <h4 class="mb-3">Still have questions?</h4>
            <p class="mb-4">Our staff is here to help you with any additional questions or concerns.</p>
            <div class="row">
                <div class="col-md-4">
                    <div class="contact-item">
                        <i class="fas fa-phone fa-2x mb-2"></i>
                        <h6>Call Us</h6>
                        <p class="mb-0">(02) 123-4567</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-item">
                        <i class="fas fa-envelope fa-2x mb-2"></i>
                        <h6>Email Us</h6>
                        <p class="mb-0">info@fonthillschristian.edu.ph</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-item">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h6>Office Hours</h6>
                        <p class="mb-0">Mon-Fri: 8AM-5PM</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // FAQ Search functionality
        document.getElementById('faqSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const accordionItems = document.querySelectorAll('.accordion-item');
            const sections = document.querySelectorAll('.faq-section');
            let hasResults = false;

            if (searchTerm === '') {
                // Show all sections and items
                sections.forEach(section => section.style.display = 'block');
                accordionItems.forEach(item => item.style.display = 'block');
                document.getElementById('noResults').style.display = 'none';
                return;
            }

            sections.forEach(section => {
                let sectionHasResults = false;
                const items = section.querySelectorAll('.accordion-item');
                
                items.forEach(item => {
                    const button = item.querySelector('.accordion-button');
                    const body = item.querySelector('.accordion-body');
                    const text = (button.textContent + ' ' + body.textContent).toLowerCase();
                    
                    if (text.includes(searchTerm)) {
                        item.style.display = 'block';
                        sectionHasResults = true;
                        hasResults = true;
                    } else {
                        item.style.display = 'none';
                    }
                });

                section.style.display = sectionHasResults ? 'block' : 'none';
            });

            document.getElementById('noResults').style.display = hasResults ? 'none' : 'block';
        });

        // Category filtering
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Update active button
                document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const category = this.dataset.category;
                const sections = document.querySelectorAll('.faq-section');
                
                if (category === 'all') {
                    sections.forEach(section => section.style.display = 'block');
                } else {
                    sections.forEach(section => {
                        if (section.dataset.category === category) {
                            section.style.display = 'block';
                        } else {
                            section.style.display = 'none';
                        }
                    });
                }
                
                // Clear search when filtering by category
                document.getElementById('faqSearch').value = '';
                document.getElementById('noResults').style.display = 'none';
            });
        });
    </script>
</body>
</html>
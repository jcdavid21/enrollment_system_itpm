function handleRegister() {
    // Use the existing validation and email sending workflow
    sendVerificationEmail();
}

$('form#loginFormElement').on('submit', function (event) {
    event.preventDefault();
    const form = document.getElementById("loginFormElement");
    const formData = new FormData(form);
    const loginData = Object.fromEntries(formData.entries());

    try {
        console.log("Sending login data:", loginData);

        Swal.fire({
            title: 'Processing Login...',
            allowOutsideClick: false,
            timer: 2000, // 2 seconds timeout
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: "../backend/login.php",
            type: "POST",
            data: loginData,
            dataType: "json",
            success: function (response) {
                Swal.close();
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Successful',
                        text: response.message,
                        showConfirmButton: false,
                    }).then((result) => {
                        if(result){
                            if (response.role === 'Admin') {
                                window.location.href = "../admin/index.php";
                            } else if (response.role === 'Student') {
                                window.location.href = "dashboard.php";
                            }
                        }
                    });
                }
                else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: response.message || 'An unexpected error occurred. Please try again later.',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX error:", status, error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: 'An unexpected error occurred. Please try again later.',
                    confirmButtonText: 'OK'
                });
            }
        });
    } catch (error) {
        console.error("Error during login:", error);
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: 'An unexpected error occurred. Please try again later.',
            confirmButtonText: 'OK'
        });
    }
});
<?php
// Sertakan file koneksi database
require_once 'config.php';

// Inisialisasi variabel untuk pesan error/sukses
$namalengkap_err = $email_err = $username_err = $password_err = $confirm_password_err = "";
$namalengkap = $email = $username = $password = "";
$success_message = "";

// Proses data formulir saat formulir dikirimkan
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Validasi nama lengkap
    if(empty(trim($_POST["namalengkap"]))){
        $namalengkap_err = "Mohon masukkan nama lengkap.";
    } else {
        $namalengkap = trim($_POST["namalengkap"]);
    }

    // Validasi email
    if(empty(trim($_POST["email"]))){
        $email_err = "Mohon masukkan email.";
    } else {
        // Siapkan pernyataan SELECT
        $sql = "SELECT idUser FROM users WHERE email = ?";

        if($stmt = mysqli_prepare($conn, $sql)){
            // Ikat variabel ke pernyataan yang disiapkan sebagai parameter
            mysqli_stmt_bind_param($stmt, "s", $param_email);

            // Set parameter
            $param_email = trim($_POST["email"]);

            // Coba jalankan pernyataan yang disiapkan
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);

                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "Email ini sudah terdaftar.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Oops! Ada yang salah. Silakan coba lagi nanti.";
            }

            // Tutup pernyataan
            mysqli_stmt_close($stmt);
        }
    }

    // Validasi username
    if(empty(trim($_POST["username"]))){
        $username_err = "Mohon masukkan username.";
    } else {
        // Siapkan pernyataan SELECT
        $sql = "SELECT idUser FROM users WHERE username = ?";

        if($stmt = mysqli_prepare($conn, $sql)){
            // Ikat variabel ke pernyataan yang disiapkan sebagai parameter
            mysqli_stmt_bind_param($stmt, "s", $param_username);

            // Set parameter
            $param_username = trim($_POST["username"]);

            // Coba jalankan pernyataan yang disiapkan
            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);

                if(mysqli_stmt_num_rows($stmt) == 1){
                    $username_err = "Username ini sudah digunakan.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Ada yang salah. Silakan coba lagi nanti.";
            }

            // Tutup pernyataan
            mysqli_stmt_close($stmt);
        }
    }

    // Validasi password
    if(empty(trim($_POST["password"]))){
        $password_err = "Mohon masukkan password.";
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password harus memiliki setidaknya 6 karakter.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validasi konfirmasi password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Mohon konfirmasi password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password tidak cocok.";
        }
    }

    // Periksa error input sebelum memasukkan data ke database
    if(empty($namalengkap_err) && empty($email_err) && empty($username_err) && empty($password_err) && empty($confirm_password_err)){

        // Siapkan pernyataan INSERT
        $sql = "INSERT INTO users (namalengkap, email, username, password) VALUES (?, ?, ?, ?)";

        if($stmt = mysqli_prepare($conn, $sql)){
            // Ikat variabel ke pernyataan yang disiapkan sebagai parameter
            mysqli_stmt_bind_param($stmt, "ssss", $param_namalengkap, $param_email, $param_username, $param_password);

            // Set parameter
            $param_namalengkap = $namalengkap;
            $param_email = $email;
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Buat hash password

            // Coba jalankan pernyataan yang disiapkan
            if(mysqli_stmt_execute($stmt)){
                $success_message = "Registrasi berhasil! Anda sekarang bisa <a href='login.php' class='alert-link'>login</a>.";
                // Kosongkan field formulir setelah sukses registrasi
                $namalengkap = $email = $username = $password = "";
            } else {
                echo "Ada yang salah. Silakan coba lagi nanti.";
            }

            // Tutup pernyataan
            mysqli_stmt_close($stmt);
        }
    }

    // Tutup koneksi
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome untuk ikon mata -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0 rounded-4 p-4 form-card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4 fw-bold text-white">Registrasi Akun</h2>
                        <?php
                        if(!empty($success_message)){
                            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $success_message . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        }
                        ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="namalengkap" class="form-label text-white">Nama Lengkap</label>
                                <input type="text" name="namalengkap" class="form-control rounded-pill <?php echo (!empty($namalengkap_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $namalengkap; ?>" required>
                                <div class="invalid-feedback"><?php echo $namalengkap_err; ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label text-white">Email</label>
                                <input type="email" name="email" class="form-control rounded-pill <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>" required>
                                <div class="invalid-feedback"><?php echo $email_err; ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label text-white">Username</label>
                                <input type="text" name="username" class="form-control rounded-pill <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" required>
                                <div class="invalid-feedback"><?php echo $username_err; ?></div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label text-white">Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control rounded-pill-left <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>" required>
                                    <button type="button" class="btn btn-outline-secondary rounded-pill-right toggle-password text-white" data-target="password">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback <?php echo (!empty($password_err)) ? 'd-block' : ''; ?>"><?php echo $password_err; ?></div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label text-white">Konfirmasi Password</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control rounded-pill-left <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" required>
                                    <button type="button" class="btn btn-outline-secondary rounded-pill-right toggle-password text-white" data-target="confirm_password">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback <?php echo (!empty($confirm_password_err)) ? 'd-block' : ''; ?>"><?php echo $confirm_password_err; ?></div>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold gradient-button">Daftar</button>
                            </div>
                            <p class="text-center mt-3 text-white">Sudah punya akun? <a href="login.php" class="text-white fw-bold">Login di sini</a>.</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');

                // Toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle the eye icon
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>

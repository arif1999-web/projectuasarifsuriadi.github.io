<?php
// Mulai sesi PHP
session_start();

// Periksa jika pengguna sudah login, arahkan ke halaman dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}

// Sertakan file koneksi database
require_once 'config.php';

// Inisialisasi variabel
$username_email = $password = "";
$username_email_err = $password_err = $login_err = "";

// Proses data formulir saat formulir dikirimkan
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Validasi username atau email
    if(empty(trim($_POST["username_email"]))){
        $username_email_err = "Mohon masukkan username atau email.";
    } else {
        $username_email = trim($_POST["username_email"]);
    }

    // Validasi password
    if(empty(trim($_POST["password"]))){
        $password_err = "Mohon masukkan password Anda.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validasi kredensial
    if(empty($username_email_err) && empty($password_err)){
        // Siapkan pernyataan SELECT
        // Coba cari berdasarkan username atau email
        $sql = "SELECT idUser, namalengkap, email, username, password FROM users WHERE username = ? OR email = ?";

        if($stmt = mysqli_prepare($conn, $sql)){
            // Ikat variabel ke pernyataan yang disiapkan sebagai parameter
            mysqli_stmt_bind_param($stmt, "ss", $param_username_email, $param_username_email); // Bind dua kali untuk OR

            // Set parameter
            $param_username_email = $username_email;

            // Coba jalankan pernyataan yang disiapkan
            if(mysqli_stmt_execute($stmt)){
                // Simpan hasil
                mysqli_stmt_store_result($stmt);

                // Periksa jika username/email ada, lalu verifikasi password
                if(mysqli_stmt_num_rows($stmt) == 1){
                    // Ikat variabel hasil
                    mysqli_stmt_bind_result($stmt, $id, $namalengkap, $email, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password benar, mulai sesi baru
                            session_start();

                            // Simpan data di variabel sesi
                            $_SESSION["loggedin"] = true;
                            $_SESSION["idUser"] = $id;
                            $_SESSION["namalengkap"] = $namalengkap;
                            $_SESSION["email"] = $email;
                            $_SESSION["username"] = $username;

                            // Arahkan pengguna ke halaman dashboard
                            header("location: dashboard.php");
                        } else {
                            // Password tidak valid
                            $login_err = "Username/Email atau password salah.";
                        }
                    }
                } else {
                    // Username/Email tidak ada
                    $login_err = "Username/Email atau password salah.";
                }
            } else {
                echo "Oops! Ada yang salah. Silakan coba lagi nanti.";
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
    <title>Login Akun</title>
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
                        <h2 class="card-title text-center mb-4 fw-bold text-white">Login Akun</h2>
                        <?php
                        if(!empty($login_err)){
                            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $login_err . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        }
                        ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label for="username_email" class="form-label text-white">Username atau Email</label>
                                <input type="text" name="username_email" class="form-control rounded-pill <?php echo (!empty($username_email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username_email; ?>" required>
                                <div class="invalid-feedback"><?php echo $username_email_err; ?></div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label text-white">Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="loginPassword" class="form-control rounded-pill-left <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                                    <button type="button" class="btn btn-outline-secondary rounded-pill-right toggle-password text-white" data-target="loginPassword">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback <?php echo (!empty($password_err)) ? 'd-block' : ''; ?>"><?php echo $password_err; ?></div>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold gradient-button">Login</button>
                            </div>
                            <p class="text-center mt-3 text-white">Belum punya akun? <a href="register.php" class="text-white fw-bold">Daftar di sini</a>.</p>
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

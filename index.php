<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang!</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome untuk ikon (jika diperlukan di masa depan) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    <style>
        /* Gaya tambahan untuk halaman selamat datang */
        .welcome-card {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px !important;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 3rem;
            text-align: center;
        }
        .welcome-card h1 {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        .welcome-card p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            color: rgba(255, 255, 255, 0.8);
        }
        .welcome-card .btn {
            margin: 0 10px;
            min-width: 150px;
        }

        /* Gaya untuk Header (disalin dari dashboard.php, disesuaikan untuk index.php) */
        .main-header {
            background: linear-gradient(to right, #6A5ACD, #483D8B); /* Gradasi ungu yang lebih gelap untuk header */
            padding: 15px 30px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            position: fixed; /* Header tetap di atas saat scroll */
            top: 0;
            left: 0;
            z-index: 1000;
        }

        .header-title {
            color: #fff;
            margin: 0;
            font-size: 1.8rem;
            font-weight: bold;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px; /* Jarak antara tombol */
        }

        .header-controls .btn-header {
            padding: 8px 15px;
            font-size: 0.9rem;
            border-radius: 50px;
            border: none;
            transition: all 0.3s ease;
            color: #fff; /* Pastikan teks tombol putih */
            text-decoration: none; /* Hapus underline default link */
        }

        .header-controls .btn-login-top {
            background: linear-gradient(to right, #9370DB, #8A2BE2); /* Gradasi ungu untuk login */
        }
        .header-controls .btn-login-top:hover {
            background: linear-gradient(to right, #8A2BE2, #9370DB);
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        .header-controls .btn-register-top {
            background: linear-gradient(to right, #20B2AA, #008B8B); /* Gradasi teal untuk register */
        }
        .header-controls .btn-register-top:hover {
            background: linear-gradient(to right, #008B8B, #20B2AA);
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        /* Tambahan padding ke body agar konten tidak tertutup header fixed */
        body {
            padding-top: 80px; /* Sesuaikan dengan tinggi header */
            display: flex; /* Menggunakan flexbox untuk footer sticky */
            flex-direction: column; /* Susun konten dan footer secara vertikal */
            min-height: 100vh; /* Pastikan body setidaknya setinggi viewport */
        }

        /* Gaya untuk Footer */
        .simple-footer {
            margin-top: auto; /* Mendorong footer ke bagian bawah */
            background-color: rgba(0, 0, 0, 0.5); /* Latar belakang semi-transparan */
            color: rgba(255, 255, 255, 0.7);
            padding: 15px 0;
            text-align: center;
            width: 100%;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body class="d-flex flex-column align-items-center min-vh-100">
    <!-- Header Utama -->
    <header class="main-header">
        <h2 class="header-title">UAS Project Arif Suriadi</h2>
        <div class="header-controls">
            <a href="login.php" class="btn btn-header fw-bold btn-login-top">Login</a>
            <a href="register.php" class="btn btn-header fw-bold btn-register-top">Registrasi</a>
        </div>
    </header>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="welcome-card shadow-lg">
                    <h1>Selamat Datang!</h1>
                    <p>Silakan masuk atau daftar untuk melanjutkan.</p>
                    <div class="d-flex justify-content-center flex-wrap">
                        <a href="login.php" class="btn btn-primary btn-lg rounded-pill fw-bold gradient-button m-2">Login</a>
                        <a href="register.php" class="btn btn-secondary btn-lg rounded-pill fw-bold gradient-button m-2">Registrasi</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Footer -->
    <footer class="simple-footer">
        <p>&copy; <?php echo date("Y"); ?> Project UAS Arif Suriadi</p>
    </footer>
</body>
</html>

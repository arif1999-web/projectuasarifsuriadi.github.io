<?php
// Mulai sesi PHP
session_start();

// Periksa apakah pengguna belum login, jika ya, arahkan kembali ke halaman login
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Sertakan file koneksi database
require_once 'config.php';

// Inisialisasi variabel untuk pesan SweetAlert
$alert_type = ""; // 'success' or 'error'
$alert_message = "";

$user_id = $_SESSION["idUser"];
$current_profile_pic = "assets/default_profile.png"; // Default profile picture if none exists

// Direktori untuk menyimpan foto profil
$upload_dir = "uploads/profile_pics/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Buat direktori jika belum ada
}

// --- Fungsionalitas READ (Ambil Foto Profil Saat Ini) ---
$sql_get_photo = "SELECT foto FROM tbl_foto WHERE idUser = ?";
if ($stmt_get_photo = mysqli_prepare($conn, $sql_get_photo)) {
    mysqli_stmt_bind_param($stmt_get_photo, "i", $param_user_id);
    $param_user_id = $user_id;
    if (mysqli_stmt_execute($stmt_get_photo)) {
        mysqli_stmt_store_result($stmt_get_photo);
        if (mysqli_stmt_num_rows($stmt_get_photo) == 1) {
            mysqli_stmt_bind_result($stmt_get_photo, $foto_path_db);
            mysqli_stmt_fetch($stmt_get_photo);
            if (!empty($foto_path_db) && file_exists($foto_path_db)) {
                $current_profile_pic = $foto_path_db;
            }
        }
    } else {
        $alert_type = "error";
        $alert_message = "Error saat mengambil foto profil: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt_get_photo);
}

// --- Fungsionalitas UPLOAD FOTO ---
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_photo'])){
    if(isset($_FILES["profile_pic"]) && $_FILES["profile_pic"]["error"] == 0){
        $allowed_types = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
        $filename = $_FILES["profile_pic"]["name"];
        $filetype = $_FILES["profile_pic"]["type"];
        $filesize = $_FILES["profile_pic"]["size"];

        // Verifikasi ekstensi file
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(!array_key_exists($ext, $allowed_types)){
            $alert_type = "error";
            $alert_message = "Error: Format file tidak diizinkan. Hanya JPG, JPEG, GIF, PNG yang diizinkan.";
        }

        // Verifikasi ukuran file - maksimal 5MB
        $max_size = 5 * 1024 * 1024; // 5 MB
        if($filesize > $max_size){
            $alert_type = "error";
            $alert_message = "Error: Ukuran file terlalu besar. Maksimal 5MB.";
        }

        // Verifikasi tipe MIME file
        if(!in_array($filetype, $allowed_types)){
            $alert_type = "error";
            $alert_message = "Error: Tipe file tidak valid.";
        }

        // Jika tidak ada error validasi
        if(empty($alert_message)){
            // Generate nama file unik
            $new_filename = uniqid('profile_') . '.' . $ext;
            $destination_path = $upload_dir . $new_filename;

            // Pindahkan file ke direktori upload
            if(move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $destination_path)){
                // Hapus foto lama jika ada dan bukan default
                if ($current_profile_pic != "assets/default_profile.png" && file_exists($current_profile_pic)) {
                    unlink($current_profile_pic); // Hapus file fisik
                }

                // Cek apakah sudah ada entri di tbl_foto untuk user ini
                $sql_check_exists = "SELECT idFoto FROM tbl_foto WHERE idUser = ?";
                if ($stmt_check_exists = mysqli_prepare($conn, $sql_check_exists)) {
                    mysqli_stmt_bind_param($stmt_check_exists, "i", $param_user_id);
                    $param_user_id = $user_id;
                    if (mysqli_stmt_execute($stmt_check_exists)) {
                        mysqli_stmt_store_result($stmt_check_exists);
                        if (mysqli_stmt_num_rows($stmt_check_exists) == 1) {
                            // Update entri yang sudah ada
                            $sql_update_photo = "UPDATE tbl_foto SET foto = ? WHERE idUser = ?";
                            if ($stmt_update_photo = mysqli_prepare($conn, $sql_update_photo)) {
                                mysqli_stmt_bind_param($stmt_update_photo, "si", $param_foto_path, $param_user_id);
                                $param_foto_path = $destination_path;
                                $param_user_id = $user_id;
                                if (mysqli_stmt_execute($stmt_update_photo)) {
                                    $alert_type = "success";
                                    $alert_message = "Foto profil berhasil diperbarui.";
                                    $current_profile_pic = $destination_path; // Update path foto saat ini
                                } else {
                                    $alert_type = "error";
                                    $alert_message = "Error database saat memperbarui foto: " . mysqli_error($conn);
                                }
                                mysqli_stmt_close($stmt_update_photo);
                            }
                        } else {
                            // Insert entri baru
                            $sql_insert_photo = "INSERT INTO tbl_foto (idUser, foto) VALUES (?, ?)";
                            if ($stmt_insert_photo = mysqli_prepare($conn, $sql_insert_photo)) {
                                mysqli_stmt_bind_param($stmt_insert_photo, "is", $param_user_id, $param_foto_path);
                                $param_user_id = $user_id;
                                $param_foto_path = $destination_path;
                                if (mysqli_stmt_execute($stmt_insert_photo)) {
                                    $alert_type = "success";
                                    $alert_message = "Foto profil berhasil diunggah.";
                                    $current_profile_pic = $destination_path; // Update path foto saat ini
                                } else {
                                    $alert_type = "error";
                                    $alert_message = "Error database saat mengunggah foto: " . mysqli_error($conn);
                                }
                                mysqli_stmt_close($stmt_insert_photo);
                            }
                        }
                    } else {
                        $alert_type = "error";
                        $alert_message = "Error database saat memeriksa foto: " . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt_check_exists);
                }
            } else {
                $alert_type = "error";
                $alert_message = "Error: Gagal memindahkan file yang diunggah.";
            }
        }
    } else {
        $alert_type = "error";
        $alert_message = "Error: Tidak ada file yang diunggah atau terjadi kesalahan upload.";
    }
}

// Tutup koneksi database
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-pic-container {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 20px auto;
            border: 3px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }
        .profile-pic-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .file-upload-label {
            display: block;
            background-color: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            padding: 10px 15px;
            border-radius: 50px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .file-upload-label:hover {
            background-color: rgba(255, 255, 255, 0.25);
        }
        .file-upload-label i {
            margin-right: 8px;
        }
        #profile_pic_input {
            display: none; /* Sembunyikan input file asli */
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0 rounded-4 p-4 form-card text-center">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4 fw-bold text-white">Edit Profil</h2>

                        <div class="profile-pic-container mb-3">
                            <img src="<?php echo htmlspecialchars($current_profile_pic); ?>" alt="Foto Profil">
                        </div>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label for="profile_pic_input" class="file-upload-label">
                                    <i class="fas fa-upload"></i> Pilih Foto Baru
                                </label>
                                <input type="file" name="profile_pic" id="profile_pic_input" accept="image/jpeg, image/png, image/gif">
                                <small class="form-text text-white-50 mt-2 d-block">Maks. 5MB (JPG, PNG, GIF)</small>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" name="upload_photo" class="btn btn-primary btn-lg rounded-pill fw-bold gradient-button">Unggah Foto</button>
                                <a href="dashboard.php" class="btn btn-secondary btn-lg rounded-pill fw-bold gradient-button-red">Kembali ke Dashboard</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        // Tampilkan SweetAlert berdasarkan pesan dari PHP
        <?php if (!empty($alert_message)): ?>
            Swal.fire({
                icon: '<?php echo $alert_type; ?>',
                title: '<?php echo ($alert_type == "success") ? "Berhasil!" : "Error!"; ?>',
                text: '<?php echo $alert_message; ?>',
                confirmButtonText: 'OK',
                customClass: {
                    popup: 'swal2-popup-custom',
                    title: 'swal2-title-custom',
                    content: 'swal2-content-custom',
                    confirmButton: 'swal2-confirm-button-custom'
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>

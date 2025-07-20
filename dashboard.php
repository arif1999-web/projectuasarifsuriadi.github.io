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
$profile_pic_path = "assets/default_profile.png"; // Path default jika tidak ada foto

// Ambil nama lengkap dari sesi untuk ditampilkan di header
$logged_in_display_name = htmlspecialchars($_SESSION["namalengkap"]);

// --- Ambil Foto Profil Pengguna ---
$sql_get_profile_pic = "SELECT foto FROM tbl_foto WHERE idUser = ?";
if ($stmt_get_profile_pic = mysqli_prepare($conn, $sql_get_profile_pic)) {
    mysqli_stmt_bind_param($stmt_get_profile_pic, "i", $param_user_id);
    $param_user_id = $user_id;
    if (mysqli_stmt_execute($stmt_get_profile_pic)) {
        mysqli_stmt_store_result($stmt_get_profile_pic);
        if (mysqli_stmt_num_rows($stmt_get_profile_pic) == 1) {
            mysqli_stmt_bind_result($stmt_get_profile_pic, $foto_path_db);
            mysqli_stmt_fetch($stmt_get_profile_pic);
            if (!empty($foto_path_db) && file_exists($foto_path_db)) {
                $profile_pic_path = $foto_path_db;
            }
        }
    }
    mysqli_stmt_close($stmt_get_profile_pic);
}


// --- Fungsionalitas CREATE (Tambah Mahasiswa) ---
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_mahasiswa'])){
    $npm = trim($_POST['npm']);
    $nama = trim($_POST['nama']);
    $prodi = trim($_POST['prodi']);
    $email = trim($_POST['email']);
    $alamat = trim($_POST['alamat']);

    // Validasi sederhana untuk data mahasiswa
    if(empty($npm) || empty($nama) || empty($prodi)){
        $alert_type = "error";
        $alert_message = "NPM, Nama, dan Prodi tidak boleh kosong.";
    } else {
        // Cek apakah NPM sudah ada di tabel mahasiswa
        $sql_check_npm_mhs = "SELECT idMhs FROM mahasiswa WHERE npm = ?";
        if($stmt_check_npm_mhs = mysqli_prepare($conn, $sql_check_npm_mhs)){
            mysqli_stmt_bind_param($stmt_check_npm_mhs, "s", $param_npm_mhs);
            $param_npm_mhs = $npm;
            if(mysqli_stmt_execute($stmt_check_npm_mhs)){
                mysqli_stmt_store_result($stmt_check_npm_mhs);
                if(mysqli_stmt_num_rows($stmt_check_npm_mhs) > 0){
                    $alert_type = "error";
                    $alert_message = "NPM ini sudah terdaftar di data mahasiswa.";
                } else {
                    // Cek apakah NPM sudah ada sebagai username di tabel users
                    $sql_check_npm_user = "SELECT idUser FROM users WHERE username = ?";
                    if($stmt_check_npm_user = mysqli_prepare($conn, $sql_check_npm_user)){
                        mysqli_stmt_bind_param($stmt_check_npm_user, "s", $param_npm_user);
                        $param_npm_user = $npm;
                        if(mysqli_stmt_execute($stmt_check_npm_user)){
                            mysqli_stmt_store_result($stmt_check_npm_user);
                            if(mysqli_stmt_num_rows($stmt_check_npm_user) > 0){
                                $alert_type = "error";
                                $alert_message = "NPM ini sudah digunakan sebagai username di akun pengguna.";
                            } else {
                                // Jika email mahasiswa kosong, gunakan placeholder email
                                $user_email = !empty($email) ? $email : $npm . "@example.com";

                                // Cek apakah email sudah ada di tabel users
                                $sql_check_email_user = "SELECT idUser FROM users WHERE email = ?";
                                if($stmt_check_email_user = mysqli_prepare($conn, $sql_check_email_user)){
                                    mysqli_stmt_bind_param($stmt_check_email_user, "s", $param_email_user);
                                    $param_email_user = $user_email;
                                    if(mysqli_stmt_execute($stmt_check_email_user)){
                                        mysqli_stmt_store_result($stmt_check_email_user);
                                        if(mysqli_stmt_num_rows($stmt_check_email_user) > 0){
                                            $alert_type = "error";
                                            $alert_message = "Email ini sudah terdaftar di akun pengguna. Mohon gunakan email lain atau kosongkan untuk email placeholder.";
                                        } else {
                                            // --- Mulai Transaksi ---
                                            mysqli_autocommit($conn, FALSE); // Matikan autocommit

                                            $insert_success_mhs = false;
                                            $insert_success_user = false;
                                            $last_inserted_mhs_id = NULL;

                                            // 1. Siapkan pernyataan INSERT untuk tabel mahasiswa
                                            $sql_insert_mhs = "INSERT INTO mahasiswa (npm, nama, prodi, email, alamat) VALUES (?, ?, ?, ?, ?)";
                                            if($stmt_insert_mhs = mysqli_prepare($conn, $sql_insert_mhs)){
                                                mysqli_stmt_bind_param($stmt_insert_mhs, "sssss", $param_npm_mhs_insert, $param_nama_mhs_insert, $param_prodi_mhs_insert, $param_email_mhs_insert, $param_alamat_mhs_insert);
                                                $param_npm_mhs_insert = $npm;
                                                $param_nama_mhs_insert = $nama;
                                                $param_prodi_mhs_insert = $prodi;
                                                $param_email_mhs_insert = !empty($email) ? $email : NULL; // Set NULL jika kosong
                                                $param_alamat_mhs_insert = !empty($alamat) ? $alamat : NULL; // Set NULL jika kosong

                                                if(mysqli_stmt_execute($stmt_insert_mhs)){
                                                    $insert_success_mhs = true;
                                                    $last_inserted_mhs_id = mysqli_insert_id($conn); // Ambil ID mahasiswa yang baru saja di-insert
                                                } else {
                                                    $alert_type = "error";
                                                    $alert_message = "Error: Tidak dapat menambahkan data mahasiswa. " . mysqli_error($conn);
                                                }
                                                mysqli_stmt_close($stmt_insert_mhs);
                                            } else {
                                                $alert_type = "error";
                                                $alert_message = "Error preparing statement for mahasiswa: " . mysqli_error($conn);
                                            }

                                            // 2. Jika insert mahasiswa berhasil, lanjutkan dengan insert user
                                            if ($insert_success_mhs && $last_inserted_mhs_id !== NULL) {
                                                // Generate username dan password dari NPM
                                                $user_username = $npm;
                                                $user_password_hashed = password_hash($npm, PASSWORD_DEFAULT); // Password default adalah NPM (dihash)
                                                $user_namalengkap = $nama;
                                                // Email sudah ditentukan di atas ($user_email)

                                                // Siapkan pernyataan INSERT untuk tabel users, termasuk idMhs
                                                $sql_insert_user = "INSERT INTO users (namalengkap, email, username, password, idMhs) VALUES (?, ?, ?, ?, ?)";
                                                if($stmt_insert_user = mysqli_prepare($conn, $sql_insert_user)){
                                                    mysqli_stmt_bind_param($stmt_insert_user, "ssssi", $param_user_namalengkap, $param_user_email, $param_user_username, $param_user_password, $param_user_idMhs);
                                                    $param_user_namalengkap = $user_namalengkap;
                                                    $param_user_email = $user_email;
                                                    $param_user_username = $user_username;
                                                    $param_user_password = $user_password_hashed;
                                                    $param_user_idMhs = $last_inserted_mhs_id; // Masukkan idMhs yang baru didapatkan

                                                    if(mysqli_stmt_execute($stmt_insert_user)){
                                                        $insert_success_user = true;
                                                    } else {
                                                        $alert_type = "error";
                                                        $alert_message = "Error: Tidak dapat membuat akun pengguna. " . mysqli_error($conn);
                                                    }
                                                    mysqli_stmt_close($stmt_insert_user);
                                                } else {
                                                    $alert_type = "error";
                                                    $alert_message = "Error preparing statement for user: " . mysqli_error($conn);
                                                }
                                            }

                                            // --- Commit atau Rollback Transaksi ---
                                            if ($insert_success_mhs && $insert_success_user) {
                                                mysqli_commit($conn); // Commit kedua operasi
                                                $alert_type = "success";
                                                $alert_message = "Data mahasiswa dan akun pengguna berhasil ditambahkan.";
                                            } else {
                                                mysqli_rollback($conn); // Rollback jika ada yang gagal
                                                if (empty($alert_message)) { // Jika belum ada pesan error spesifik
                                                    $alert_type = "error";
                                                    $alert_message = "Gagal menambahkan data mahasiswa dan/atau akun pengguna.";
                                                }
                                            }
                                            mysqli_autocommit($conn, TRUE); // Aktifkan kembali autocommit
                                        }
                                    } else {
                                        $alert_type = "error";
                                        $alert_message = "Error saat memeriksa email di users: " . mysqli_error($conn);
                                    }
                                    mysqli_stmt_close($stmt_check_email_user);
                                }
                            }
                        } else {
                            $alert_type = "error";
                            $alert_message = "Error saat memeriksa username di users: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt_check_npm_user);
                    }
                }
            } else {
                $alert_type = "error";
                $alert_message = "Error saat memeriksa NPM di mahasiswa: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt_check_npm_mhs);
        }
    }
}

// --- Fungsionalitas UPDATE (Edit Mahasiswa) ---
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_mahasiswa'])){
    $idMhs = $_POST['idMhs_edit'];
    $npm = trim($_POST['npm_edit']);
    $nama = trim($_POST['nama_edit']);
    $prodi = trim($_POST['prodi_edit']);
    $email = trim($_POST['email_edit']);
    $alamat = trim($_POST['alamat_edit']);

    // Validasi sederhana
    if(empty($npm) || empty($nama) || empty($prodi)){
        $alert_type = "error";
        $alert_message = "NPM, Nama, dan Prodi tidak boleh kosong.";
    } else {
        // Cek apakah NPM sudah ada untuk mahasiswa lain
        $sql_check_npm_update = "SELECT idMhs FROM mahasiswa WHERE npm = ? AND idMhs != ?";
        if($stmt_check_npm_update = mysqli_prepare($conn, $sql_check_npm_update)){
            mysqli_stmt_bind_param($stmt_check_npm_update, "si", $param_npm, $param_idMhs);
            $param_npm = $npm;
            $param_idMhs = $idMhs;
            if(mysqli_stmt_execute($stmt_check_npm_update)){
                mysqli_stmt_store_result($stmt_check_npm_update);
                if(mysqli_stmt_num_rows($stmt_check_npm_update) > 0){
                    $alert_type = "error";
                    $alert_message = "NPM ini sudah digunakan oleh mahasiswa lain.";
                } else {
                    // Ambil NPM lama dan email lama untuk update tabel users
                    $old_npm = '';
                    $old_email = '';
                    $sql_get_old_data = "SELECT npm, email FROM mahasiswa WHERE idMhs = ?";
                    if ($stmt_get_old_data = mysqli_prepare($conn, $sql_get_old_data)) {
                        mysqli_stmt_bind_param($stmt_get_old_data, "i", $idMhs);
                        if (mysqli_stmt_execute($stmt_get_old_data)) {
                            mysqli_stmt_bind_result($stmt_get_old_data, $old_npm, $old_email);
                            mysqli_stmt_fetch($stmt_get_old_data);
                        }
                        mysqli_stmt_close($stmt_get_old_data);
                    }

                    // Jika email mahasiswa kosong, gunakan placeholder email
                    $user_email = !empty($email) ? $email : $npm . "@example.com";

                    // Cek apakah email baru sudah ada di tabel users untuk user lain
                    $sql_check_email_update_user = "SELECT idUser FROM users WHERE email = ? AND (idMhs IS NULL OR idMhs != ?)";
                    if ($stmt_check_email_update_user = mysqli_prepare($conn, $sql_check_email_update_user)) {
                        mysqli_stmt_bind_param($stmt_check_email_update_user, "si", $param_new_email, $param_current_idMhs_user);
                        $param_new_email = $user_email;
                        $param_current_idMhs_user = $idMhs; // idMhs dari mahasiswa yang sedang diedit

                        if(mysqli_stmt_execute($stmt_check_email_update_user)){
                            mysqli_stmt_store_result($stmt_check_email_update_user);
                            if(mysqli_stmt_num_rows($stmt_check_email_update_user) > 0){
                                $alert_type = "error";
                                $alert_message = "Email yang dimasukkan sudah digunakan oleh akun pengguna lain.";
                            } else {
                                // --- Mulai Transaksi untuk Update ---
                                mysqli_autocommit($conn, FALSE);

                                $update_success_mhs = false;
                                $update_success_user = true; // Asumsikan true jika tidak ada perubahan user yang diperlukan

                                // Siapkan pernyataan UPDATE untuk tabel mahasiswa
                                $sql_update_mhs = "UPDATE mahasiswa SET npm = ?, nama = ?, prodi = ?, email = ?, alamat = ? WHERE idMhs = ?";
                                if($stmt_update_mhs = mysqli_prepare($conn, $sql_update_mhs)){
                                    mysqli_stmt_bind_param($stmt_update_mhs, "sssssi", $param_npm_update, $param_nama_update, $param_prodi_update, $param_email_update, $param_alamat_update, $param_idMhs_update);
                                    $param_npm_update = $npm;
                                    $param_nama_update = $nama;
                                    $param_prodi_update = $prodi;
                                    $param_email_update = !empty($email) ? $email : NULL;
                                    $param_alamat_update = !empty($alamat) ? $alamat : NULL;
                                    $param_idMhs_update = $idMhs;

                                    if(mysqli_stmt_execute($stmt_update_mhs)){
                                        $update_success_mhs = true;
                                    } else {
                                        $alert_type = "error";
                                        $alert_message = "Error: Tidak dapat memperbarui data mahasiswa. " . mysqli_error($conn);
                                    }
                                    mysqli_stmt_close($stmt_update_mhs);
                                } else {
                                    $alert_type = "error";
                                    $alert_message = "Error preparing statement for mahasiswa update: " . mysqli_error($conn);
                                }

                                // Jika update mahasiswa berhasil, coba update user juga
                                if ($update_success_mhs) {
                                    // Update akun pengguna jika NPM atau nama/email berubah
                                    // Perlu juga cek jika old_npm tidak kosong, karena mungkin ada mahasiswa tanpa user
                                    if (!empty($old_npm)) { // Hanya update user jika ada user yang terkait dengan NPM lama
                                        $user_username = $npm;
                                        $user_password_hashed = password_hash($npm, PASSWORD_DEFAULT); // Update password juga dengan NPM baru (dihash)
                                        $user_namalengkap = $nama;
                                        // Email sudah ditentukan di atas ($user_email)

                                        $sql_update_user = "UPDATE users SET namalengkap = ?, email = ?, username = ?, password = ? WHERE idMhs = ?"; // WHERE idMhs
                                        if($stmt_update_user = mysqli_prepare($conn, $sql_update_user)){
                                            // Menggunakan call_user_func_array untuk mengatasi ArgumentCountError
                                            $bind_params_user_update = [];
                                            $bind_params_user_update[] = "ssssi"; // Type definition string
                                            $bind_params_user_update[] = &$param_user_namalengkap;
                                            $bind_params_user_update[] = &$param_user_email;
                                            $bind_params_user_update[] = &$param_user_username;
                                            $bind_params_user_update[] = &$param_user_password;
                                            $bind_params_user_update[] = &$param_idMhs_user_update;

                                            $param_user_namalengkap = $user_namalengkap;
                                            $param_user_email = $user_email;
                                            $param_user_username = $user_username;
                                            $param_user_password = $user_password_hashed;
                                            $param_idMhs_user_update = $idMhs; // Gunakan idMhs dari mahasiswa yang sedang diedit

                                            // Line 278 (sebelumnya) - diganti dengan call_user_func_array
                                            if(call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt_update_user], $bind_params_user_update))){
                                                if(mysqli_stmt_execute($stmt_update_user)){
                                                    $update_success_user = true;
                                                } else {
                                                    $alert_type = "error";
                                                    $alert_message = "Error: Tidak dapat memperbarui akun pengguna terkait. " . mysqli_error($conn);
                                                    $update_success_user = false;
                                                }
                                            } else {
                                                $alert_type = "error";
                                                $alert_message = "Error binding parameters for user update: " . mysqli_error($conn);
                                                $update_success_user = false;
                                            }
                                            mysqli_stmt_close($stmt_update_user);
                                        } else {
                                            $alert_type = "error";
                                            $alert_message = "Error preparing statement for user update: " . mysqli_error($conn);
                                            $update_success_user = false;
                                        }
                                    }
                                }

                                // --- Commit atau Rollback Transaksi ---
                                if ($update_success_mhs && $update_success_user) {
                                    mysqli_commit($conn);
                                    $alert_type = "success";
                                    $alert_message = "Data mahasiswa dan akun pengguna berhasil diperbarui.";
                                } else {
                                    mysqli_rollback($conn);
                                    if (empty($alert_message)) {
                                        $alert_type = "error";
                                        $alert_message = "Gagal memperbarui data mahasiswa dan/atau akun pengguna.";
                                    }
                                }
                                mysqli_autocommit($conn, TRUE);
                            }
                        } else {
                            $alert_type = "error";
                            $alert_message = "Error saat memeriksa email baru di users: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt_check_email_update_user);
                    }
                }
            } else {
                $alert_type = "error";
                $alert_message = "Error saat memeriksa NPM (update): " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt_check_npm_update);
        }
    }
}

// --- Fungsionalitas DELETE (Hapus Mahasiswa) ---
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_mahasiswa'])){
    $idMhs_delete = $_POST['idMhs_delete'];

    // --- Mulai Transaksi untuk Delete ---
    mysqli_autocommit($conn, FALSE);

    $delete_success_mhs = false;

    // 1. Siapkan pernyataan DELETE untuk tabel mahasiswa
    // Penghapusan user terkait akan otomatis ditangani oleh ON DELETE CASCADE
    $sql_delete_mhs = "DELETE FROM mahasiswa WHERE idMhs = ?";
    if($stmt_delete_mhs = mysqli_prepare($conn, $sql_delete_mhs)){
        mysqli_stmt_bind_param($stmt_delete_mhs, "i", $param_idMhs_delete_mhs);
        $param_idMhs_delete_mhs = $idMhs_delete;

        if(mysqli_stmt_execute($stmt_delete_mhs)){
            $delete_success_mhs = true;
        } else {
            $alert_type = "error";
            $alert_message = "Error: Tidak dapat menghapus data mahasiswa. " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt_delete_mhs);
    } else {
        $alert_type = "error";
        $alert_message = "Error preparing statement for mahasiswa delete: " . mysqli_error($conn);
    }

    // --- Commit atau Rollback Transaksi ---
    if ($delete_success_mhs) { // Hanya perlu cek keberhasilan delete mahasiswa
        mysqli_commit($conn);
        $alert_type = "success";
        $alert_message = "Data mahasiswa dan akun pengguna terkait berhasil dihapus.";
    } else {
        mysqli_rollback($conn);
        if (empty($alert_message)) {
            $alert_type = "error";
            $alert_message = "Gagal menghapus data mahasiswa dan/atau akun pengguna terkait.";
        }
    }
    mysqli_autocommit($conn, TRUE);
}

// --- Fungsionalitas READ (Ambil Data Mahasiswa) ---
$mahasiswa_data = [];
$sql_select = "SELECT idMhs, npm, nama, prodi, email, alamat FROM mahasiswa ORDER BY npm ASC";
if($result = mysqli_query($conn, $sql_select)){
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            $mahasiswa_data[] = $row;
        }
        mysqli_free_result($result);
    }
} else {
    $alert_type = "error"; // Jika ada error saat mengambil data awal
    $alert_message = "Error: Tidak dapat mengambil data mahasiswa. " . mysqli_error($conn);
}

// Tutup koneksi database setelah semua operasi selesai
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome untuk ikon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    <style>
        /* Tambahan CSS untuk tabel dan modal */
        .table-responsive {
            margin-top: 20px;
        }
        .table-dark-custom {
            background-color: rgba(0, 0, 0, 0.3); /* Sedikit transparan */
            color: #fff;
            border-radius: 10px;
            overflow: hidden; /* Memastikan border-radius bekerja pada header */
        }
        .table-dark-custom th, .table-dark-custom td {
            border-color: rgba(255, 255, 255, 0.2);
            vertical-align: middle;
        }
        .table-dark-custom thead th {
            background-color: rgba(0, 0, 0, 0.5);
            color: #fff;
        }
        .action-buttons .btn {
            margin-right: 5px;
        }
        .modal-content {
            background: linear-gradient(to right, #8A2BE2, #4B0082); /* Gradasi ungu untuk modal */
            color: #fff;
            border-radius: 15px;
            border: none;
        }
        .modal-header, .modal-footer {
            border-color: rgba(255, 255, 255, 0.2);
        }
        .modal-title {
            color: #fff;
        }
        .btn-close {
            filter: invert(1); /* Membuat ikon close putih */
        }
        .form-control-modal {
            background-color: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
        }
        .form-control-modal:focus {
            background-color: rgba(255, 255, 255, 0.25);
            border-color: #BA55D3;
            box-shadow: 0 0 0 0.25rem rgba(186, 85, 211, 0.25);
            color: #fff;
        }
        .form-control-modal::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        /* Gaya untuk Header */
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
            gap: 15px; /* Jarak antara ikon profil dan tombol logout */
        }

        .profile-username {
            color: #fff;
            font-weight: 600;
            margin-right: 10px; /* Jarak antara username dan ikon foto */
        }

        .profile-icon-container {
            width: 45px; /* Ukuran ikon di header */
            height: 45px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
            transition: transform 0.2s ease-in-out, border-color 0.2s ease-in-out;
            flex-shrink: 0; /* Mencegah ikon menyusut */
        }
        .profile-icon-container a {
            display: block; /* Penting untuk mengisi container */
            width: 100%;
            height: 100%;
        }
        .profile-icon-container a:hover {
            transform: scale(1.1);
            border-color: #BA55D3;
        }
        .profile-icon-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .header-controls .btn-logout-top {
            padding: 8px 15px;
            font-size: 0.9rem;
            border-radius: 50px;
            background: linear-gradient(to right, #DC3545, #8B0000);
            border: none;
            transition: all 0.3s ease;
            color: #fff; /* Pastikan teks tombol putih */
            text-decoration: none; /* Hapus underline default link */
        }
        .header-controls .btn-logout-top:hover {
            background: linear-gradient(to right, #8B0000, #DC3545);
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

        /* Gaya untuk dropdown di modal */
        .modal-content .form-select {
            background-color: rgba(255, 255, 255, 0.15); /* Latar belakang semi-transparan */
            border: 1px solid rgba(255, 255, 255, 0.3); /* Border transparan */
            color: #fff; /* Warna teks putih */
            padding: 0.75rem 1.25rem;
            border-radius: 50px; /* Sudut membulat */
            -webkit-appearance: none; /* Hapus gaya default browser untuk dropdown */
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e"); /* Ikon panah putih */
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
        }

        .modal-content .form-select:focus {
            background-color: rgba(255, 255, 255, 0.25);
            border-color: #BA55D3;
            box-shadow: 0 0 0 0.25rem rgba(186, 85, 211, 0.25);
            color: #fff;
        }

        .modal-content .form-select option {
            background-color: #4B0082; /* Latar belakang opsi dropdown */
            color: #fff; /* Warna teks opsi dropdown */
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
        <h2 class="header-title">Project UAS Arif Suriadi</h2>
        <div class="header-controls">
            <span class="profile-username"><?php echo $logged_in_display_name; ?></span>
            <div class="profile-icon-container">
                <a href="editprofile.php">
                    <img src="<?php echo htmlspecialchars($profile_pic_path); ?>" alt="Foto Profil">
                </a>
            </div>
            <a href="#" id="logoutButton" class="btn btn-danger fw-bold btn-logout-top">Logout</a>
        </div>
    </header>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-lg border-0 rounded-4 p-5 form-card text-center">
                    <div class="card-body">
                        <h1 class="card-title mb-4 fw-bold text-white">Dashboard Mahasiswa</h1>
                        <p class="lead text-white-50">Selamat datang, <?php echo htmlspecialchars($_SESSION["namalengkap"]); ?>! Kelola data mahasiswa Anda di sini.</p>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="text-white mb-0">Data Mahasiswa</h3>
                            <button type="button" class="btn btn-success rounded-pill fw-bold gradient-button" data-bs-toggle="modal" data-bs-target="#addMahasiswaModal">
                                <i class="fas fa-plus me-2"></i> Tambah Mahasiswa
                            </button>
                        </div>

                        <!-- Pesan SweetAlert akan muncul di sini via JavaScript -->

                        <div class="table-responsive">
                            <table class="table table-dark-custom table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>NPM</th>
                                        <th>Nama</th>
                                        <th>Prodi</th>
                                        <th>Email</th>
                                        <th>Alamat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($mahasiswa_data)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-white-50">Belum ada data mahasiswa.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php $counter = 1; ?>
                                        <?php foreach($mahasiswa_data as $mahasiswa): ?>
                                            <tr>
                                                <td><?php echo $counter++; ?></td>
                                                <td><?php echo htmlspecialchars($mahasiswa['npm']); ?></td>
                                                <td><?php echo htmlspecialchars($mahasiswa['nama']); ?></td>
                                                <td><?php echo htmlspecialchars($mahasiswa['prodi']); ?></td>
                                                <td><?php echo htmlspecialchars($mahasiswa['email']); ?></td>
                                                <td><?php echo htmlspecialchars($mahasiswa['alamat']); ?></td>
                                                <td class="action-buttons">
                                                    <button type="button" class="btn btn-sm btn-info rounded-pill"
                                                        data-bs-toggle="modal" data-bs-target="#editMahasiswaModal"
                                                        data-idmhs="<?php echo $mahasiswa['idMhs']; ?>"
                                                        data-npm="<?php echo $mahasiswa['npm']; ?>"
                                                        data-nama="<?php echo $mahasiswa['nama']; ?>"
                                                        data-prodi="<?php echo $mahasiswa['prodi']; ?>"
                                                        data-email="<?php echo $mahasiswa['email']; ?>"
                                                        data-alamat="<?php echo $mahasiswa['alamat']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger rounded-pill"
                                                        data-bs-toggle="modal" data-bs-target="#deleteMahasiswaModal"
                                                        data-idmhs="<?php echo $mahasiswa['idMhs']; ?>"
                                                        data-nama="<?php echo $mahasiswa['nama']; ?>">
                                                        <i class="fas fa-trash-alt"></i> Hapus
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Mahasiswa -->
    <div class="modal fade" id="addMahasiswaModal" tabindex="-1" aria-labelledby="addMahasiswaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMahasiswaModalLabel">Tambah Mahasiswa Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="npm" class="form-label">NPM</label>
                            <input type="text" class="form-control form-control-modal rounded-pill" id="npm" name="npm" required>
                        </div>
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control form-control-modal rounded-pill" id="nama" name="nama" required>
                        </div>
                        <div class="mb-3">
                            <label for="prodi" class="form-label">Program Studi</label>
                            <select class="form-select form-control-modal rounded-pill" id="prodi" name="prodi" required>
                                <option value="">Pilih Program Studi</option>
                                <option value="Pendidikan Informatika">Pendidikan Informatika</option>
                                <option value="Pendidikan Biologi">Pendidikan Biologi</option>
                                <option value="Pendidikan Matematika">Pendidikan Matematika</option>
                                <option value="Pendidikan Fisika">Pendidikan Fisika</option>
                                <option value="Pendidikan IPA">Pendidikan IPA</option>
                                <option value="Statistika">Statistika</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control form-control-modal rounded-pill" id="email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat</label>
                            <textarea class="form-control form-control-modal rounded-4" id="alamat" name="alamat" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_mahasiswa" class="btn btn-primary rounded-pill gradient-button">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Mahasiswa -->
    <div class="modal fade" id="editMahasiswaModal" tabindex="-1" aria-labelledby="editMahasiswaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMahasiswaModalLabel">Edit Data Mahasiswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="idMhs_edit" id="idMhs_edit">
                        <div class="mb-3">
                            <label for="npm_edit" class="form-label">NPM</label>
                            <input type="text" class="form-control form-control-modal rounded-pill" id="npm_edit" name="npm_edit" required>
                        </div>
                        <div class="mb-3">
                            <label for="nama_edit" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control form-control-modal rounded-pill" id="nama_edit" name="nama_edit" required>
                        </div>
                        <div class="mb-3">
                            <label for="prodi_edit" class="form-label">Program Studi</label>
                            <select class="form-select form-control-modal rounded-pill" id="prodi_edit" name="prodi_edit" required>
                                <option value="">Pilih Program Studi</option>
                                <option value="Pendidikan Informatika">Pendidikan Informatika</option>
                                <option value="Pendidikan Biologi">Pendidikan Biologi</option>
                                <option value="Pendidikan Matematika">Pendidikan Matematika</option>
                                <option value="Pendidikan Fisika">Pendidikan Fisika</option>
                                <option value="Pendidikan IPA">Pendidikan IPA</option>
                                <option value="Statistika">Statistika</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="email_edit" class="form-label">Email</label>
                            <input type="email" class="form-control form-control-modal rounded-pill" id="email_edit" name="email_edit">
                        </div>
                        <div class="mb-3">
                            <label for="alamat_edit" class="form-label">Alamat</label>
                            <textarea class="form-control form-control-modal rounded-4" id="alamat_edit" name="alamat_edit" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_mahasiswa" class="btn btn-primary rounded-pill gradient-button">Perbarui</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Hapus Mahasiswa -->
    <div class="modal fade" id="deleteMahasiswaModal" tabindex="-1" aria-labelledby="deleteMahasiswaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteMahasiswaModalLabel">Konfirmasi Hapus Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="idMhs_delete" id="idMhs_delete">
                        <p class="text-white">Apakah Anda yakin ingin menghapus data mahasiswa <strong id="deleteMahasiswaNama"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="delete_mahasiswa" class="btn btn-danger rounded-pill gradient-button-red">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="simple-footer">
        <p>&copy; <?php echo date("Y"); ?> Project UAS Arif Suriadi</p>
    </footer>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
        // Script untuk mengisi data ke modal edit
        var editMahasiswaModal = document.getElementById('editMahasiswaModal');
        editMahasiswaModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Tombol yang memicu modal
            var idMhs = button.getAttribute('data-idmhs');
            var npm = button.getAttribute('data-npm');
            var nama = button.getAttribute('data-nama');
            var prodi = button.getAttribute('data-prodi');
            var email = button.getAttribute('data-email');
            var alamat = button.getAttribute('data-alamat');

            var modalIdMhs = editMahasiswaModal.querySelector('#idMhs_edit');
            var modalNpm = editMahasiswaModal.querySelector('#npm_edit');
            var modalNama = editMahasiswaModal.querySelector('#nama_edit');
            var modalProdi = editMahasiswaModal.querySelector('#prodi_edit');
            var modalEmail = editMahasiswaModal.querySelector('#email_edit');
            var modalAlamat = editMahasiswaModal.querySelector('#alamat_edit');

            modalIdMhs.value = idMhs;
            modalNpm.value = npm;
            modalNama.value = nama;
            // Set nilai dropdown prodi
            modalProdi.value = prodi; // Ini akan memilih opsi yang sesuai
            modalEmail.value = email;
            modalAlamat.value = alamat;
        });

        // Script untuk mengisi data ke modal hapus
        var deleteMahasiswaModal = document.getElementById('deleteMahasiswaModal');
        deleteMahasiswaModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Tombol yang memicu modal
            var idMhs = button.getAttribute('data-idmhs');
            var nama = button.getAttribute('data-nama');

            var modalIdMhs = deleteMahasiswaModal.querySelector('#idMhs_delete');
            var modalNamaDisplay = deleteMahasiswaModal.querySelector('#deleteMahasiswaNama');

            modalIdMhs.value = idMhs;
            modalNamaDisplay.textContent = nama;
        });

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

        // SweetAlert untuk konfirmasi logout
        document.getElementById('logoutButton').addEventListener('click', function(event) {
            event.preventDefault(); // Mencegah aksi default link

            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Anda akan keluar dari sesi ini!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Logout!',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'swal2-popup-custom',
                    title: 'swal2-title-custom',
                    content: 'swal2-content-custom',
                    confirmButton: 'swal2-confirm-button-custom',
                    cancelButton: 'swal2-cancel-button-custom'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php'; // Arahkan ke logout.php jika dikonfirmasi
                }
            });
        });
    </script>
</body>
</html>

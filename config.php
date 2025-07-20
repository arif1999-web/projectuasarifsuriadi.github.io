<?php
// Aktifkan pelaporan error MySQLi untuk debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Konfigurasi koneksi database
define('DB_SERVER', 'localhost'); // Ganti dengan host database Anda jika berbeda
define('DB_USERNAME', 'root');     // Ganti dengan username database Anda
define('DB_PASSWORD', '');         // Ganti dengan password database Anda
define('DB_NAME', 'db_login_registrasi'); // Nama database yang telah Anda buat

// Membuat koneksi ke database MySQL
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Memeriksa koneksi
if($conn === false){
    // Jika koneksi gagal, hentikan skrip dan tampilkan pesan error
    die("ERROR: Tidak dapat terhubung ke database. " . mysqli_connect_error());
} else {
    // Opsional: Tampilkan pesan sukses jika koneksi berhasil (bisa dihapus di produksi)
    // echo "Koneksi database berhasil!";
}
?>

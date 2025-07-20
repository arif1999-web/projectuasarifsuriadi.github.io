<?php
// Mulai sesi PHP
session_start();

// Hapus semua variabel sesi
$_SESSION = array();

// Hancurkan sesi.
session_destroy();

// Arahkan pengguna ke halaman login
header("location: index.php");
exit;
?>

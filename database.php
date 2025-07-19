<?php

define('DB_SERVER', 'localhost'); // Biasanya 'localhost'
define('DB_USERNAME', 'root');    // Username database Anda (default XAMPP/Laragon: 'root')
define('DB_PASSWORD', '');        // Password database Anda (default XAMPP/Laragon: kosong)
define('DB_NAME', 'empowquiz'); // Nama database yang akan Anda buat

// Membuat koneksi ke database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Mengecek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
?>
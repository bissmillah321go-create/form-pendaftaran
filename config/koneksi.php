<?php

$host = "localhost";
$user = "root";
$pass = "";
$db = "ppdb_sekolah";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    error_log("Koneksi database PPDB gagal: " . mysqli_connect_error());
    http_response_code(500);
    exit("Koneksi database gagal. Periksa konfigurasi dan pastikan database sudah diimpor.");
}

if (!mysqli_set_charset($conn, "utf8mb4")) {
    error_log("Gagal mengatur charset database PPDB: " . mysqli_error($conn));
    http_response_code(500);
    exit("Koneksi database gagal disiapkan. Silakan coba kembali nanti.");
}

CREATE DATABASE IF NOT EXISTS ppdb_sekolah
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ppdb_sekolah;

CREATE TABLE IF NOT EXISTS pendaftaran (
    id INT NOT NULL AUTO_INCREMENT,
    nomor_pendaftaran VARCHAR(30) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    nisn VARCHAR(20) NOT NULL,
    jenis_kelamin VARCHAR(20) NOT NULL,
    tempat_lahir VARCHAR(100) NOT NULL,
    tanggal_lahir DATE NOT NULL,
    asal_sekolah VARCHAR(100) NOT NULL,
    alamat TEXT NOT NULL,
    nama_ortu VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20) NOT NULL,
    status ENUM('Menunggu', 'Diterima', 'Ditolak') NOT NULL DEFAULT 'Menunggu',
    tanggal_daftar TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pendaftaran_nomor (nomor_pendaftaran),
    KEY idx_pendaftaran_no_hp (no_hp),
    KEY idx_pendaftaran_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(80) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_username (username)
) ENGINE=InnoDB;

-- Buat akun admin dengan hash password menggunakan password_hash() di PHP.
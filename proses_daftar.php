<?php
session_start();

function redirect_to_form(string $type, string $message): never
{
    $_SESSION["flash"] = ["type" => $type, "message" => $message];
    header("Location: daftar.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: daftar.php");
    exit;
}

$csrfToken = $_POST["csrf_token"] ?? "";
if (!is_string($csrfToken) || empty($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $csrfToken)) {
    redirect_to_form("error", "Sesi formulir tidak valid atau sudah berakhir. Silakan coba kirim kembali.");
}

$fields = ["nama", "nisn", "jenis_kelamin", "no_hp", "tempat_lahir", "tanggal_lahir", "asal_sekolah", "nama_ortu", "alamat"];
$input = [];
foreach ($fields as $field) {
    $value = $_POST[$field] ?? "";
    $input[$field] = is_string($value) ? trim($value) : "";
}
$_SESSION["old_input"] = $input;

$errors = [];
foreach (["nama", "nisn", "jenis_kelamin", "no_hp", "tempat_lahir", "tanggal_lahir", "asal_sekolah", "nama_ortu", "alamat"] as $required) {
    if ($input[$required] === "") {
        $errors[] = "Semua kolom wajib harus diisi.";
        break;
    }
}

if (mb_strlen($input["nama"]) > 100 || mb_strlen($input["tempat_lahir"]) > 100 || mb_strlen($input["asal_sekolah"]) > 100 || mb_strlen($input["nama_ortu"]) > 100 || mb_strlen($input["alamat"]) > 500) {
    $errors[] = "Ada data yang melebihi panjang maksimum.";
}
if (!preg_match('/^[0-9]{10,20}$/', $input["nisn"])) {
    $errors[] = "NISN harus terdiri dari 10 sampai 20 digit angka.";
}
if (!in_array($input["jenis_kelamin"], ["Laki-laki", "Perempuan"], true)) {
    $errors[] = "Pilih jenis kelamin yang tersedia.";
}
if (!preg_match('/^[0-9+\s().-]{8,20}$/', $input["no_hp"])) {
    $errors[] = "Nomor HP harus berisi 8 sampai 20 karakter angka atau tanda nomor telepon yang umum.";
}
$birthDate = DateTimeImmutable::createFromFormat("!Y-m-d", $input["tanggal_lahir"]);
$dateErrors = DateTimeImmutable::getLastErrors();
if (!$birthDate || ($dateErrors !== false && ($dateErrors["warning_count"] > 0 || $dateErrors["error_count"] > 0)) || $birthDate->format("Y-m-d") !== $input["tanggal_lahir"] || $birthDate > new DateTimeImmutable("today")) {
    $errors[] = "Tanggal lahir tidak valid.";
}

if ($errors) {
    redirect_to_form("error", implode(" ", array_unique($errors)));
}

require __DIR__ . "/config/koneksi.php";

$statement = mysqli_prepare(
    $conn,
    "INSERT INTO pendaftaran (nomor_pendaftaran, nama, nisn, jenis_kelamin, no_hp, tempat_lahir, tanggal_lahir, asal_sekolah, alamat, nama_ortu)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
if (!$statement) {
    error_log("Gagal menyiapkan penyimpanan pendaftaran: " . mysqli_error($conn));
    redirect_to_form("error", "Pendaftaran belum dapat disimpan. Silakan coba kembali nanti.");
}

$saved = false;
$number = "";
for ($attempt = 0; $attempt < 3; $attempt++) {
    $number = "PPDB-" . date("Y") . "-" . strtoupper(bin2hex(random_bytes(3)));
    mysqli_stmt_bind_param(
        $statement,
        "ssssssssss",
        $number,
        $input["nama"],
        $input["nisn"],
        $input["jenis_kelamin"],
        $input["no_hp"],
        $input["tempat_lahir"],
        $input["tanggal_lahir"],
        $input["asal_sekolah"],
        $input["alamat"],
        $input["nama_ortu"]
    );
    if (mysqli_stmt_execute($statement)) {
        $saved = true;
        break;
    }
    if (mysqli_stmt_errno($statement) !== 1062) {
        error_log("Gagal menyimpan pendaftaran: " . mysqli_stmt_error($statement));
        break;
    }
}
mysqli_stmt_close($statement);

if (!$saved) {
    redirect_to_form("error", "Pendaftaran belum dapat disimpan. Silakan coba kembali nanti.");
}

unset($_SESSION["old_input"]);
$_SESSION["flash"] = [
    "type" => "success",
    "message" => "Pendaftaran berhasil dikirim. Simpan nomor pendaftaran Anda: " . $number . ". Status awal: Menunggu."
];
header("Location: daftar.php");
exit;
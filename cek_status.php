<?php
session_start();

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$flash = $_SESSION["status_flash"] ?? null;
unset($_SESSION["status_flash"]);
$registration = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $number = strtoupper(trim(is_string($_POST["no_pendaftaran"] ?? null) ? $_POST["no_pendaftaran"] : ""));
    $phone = trim(is_string($_POST["no_hp"] ?? null) ? $_POST["no_hp"] : "");

    if (!is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
        $flash = ["type" => "error", "message" => "Sesi formulir tidak valid atau sudah berakhir. Silakan muat ulang halaman."];
    } elseif ($number === "" || $phone === "") {
        $flash = ["type" => "error", "message" => "Nomor pendaftaran dan nomor HP wajib diisi."];
    } else {
        require __DIR__ . "/config/koneksi.php";
        $statement = mysqli_prepare(
            $conn,
            "SELECT nomor_pendaftaran, nama, asal_sekolah, status, tanggal_daftar
             FROM pendaftaran
             WHERE nomor_pendaftaran = ? AND no_hp = ?
             LIMIT 1"
        );
        if (!$statement) {
            error_log("Gagal menyiapkan pengecekan status: " . mysqli_error($conn));
            http_response_code(500);
            exit("Status pendaftaran belum dapat diperiksa. Silakan coba kembali nanti.");
        }
        mysqli_stmt_bind_param($statement, "ss", $number, $phone);
        if (!mysqli_stmt_execute($statement)) {
            error_log("Gagal memeriksa status pendaftaran: " . mysqli_stmt_error($statement));
            mysqli_stmt_close($statement);
            http_response_code(500);
            exit("Status pendaftaran belum dapat diperiksa. Silakan coba kembali nanti.");
        }
        $result = mysqli_stmt_get_result($statement);
        $registration = mysqli_fetch_assoc($result) ?: null;
        mysqli_stmt_close($statement);

        if (!$registration) {
            $flash = ["type" => "error", "message" => "Data pendaftaran tidak ditemukan. Periksa kembali nomor pendaftaran dan nomor HP."];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Status Pendaftaran - EDU REGISTER</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<nav>
    <div class="logo">EDU<span>REGISTER</span></div>
    <div class="menu">
        <a href="index.php">Beranda</a>
        <a href="daftar.php">Pendaftaran</a>
        <a href="cek_status.php" aria-current="page">Cek Status</a>
    </div>
</nav>

<header class="page-header">
    <h1>Cek Status Pendaftaran</h1>
    <p>Masukkan nomor pendaftaran dan nomor HP yang digunakan saat mengisi formulir.</p>
</header>

<main class="form-wrap">
    <section class="form-card">
        <h2>Lacak Pendaftaran</h2>
        <p class="form-intro">Untuk menjaga privasi, data hanya ditampilkan jika nomor pendaftaran cocok dengan nomor HP terdaftar.</p>

        <?php if ($flash): ?>
            <div class="alert <?= $flash["type"] === "success" ? "alert-success" : "alert-error" ?>" role="alert">
                <?= htmlspecialchars($flash["message"], ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="post" action="cek_status.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
            <div class="form-grid">
                <div class="field">
                    <label for="no_pendaftaran">Nomor pendaftaran *</label>
                    <input id="no_pendaftaran" name="no_pendaftaran" type="text" maxlength="20" placeholder="Contoh: PPDB-2026-A1B2C3" required>
                </div>
                <div class="field">
                    <label for="no_hp">Nomor HP terdaftar *</label>
                    <input id="no_hp" name="no_hp" type="tel" maxlength="20" autocomplete="tel" required>
                </div>
            </div>
            <div class="form-actions">
                <button class="button-primary" type="submit">Cek Status</button>
                <a class="button-link" href="daftar.php">Daftar sekarang</a>
            </div>
        </form>

        <?php if ($registration): ?>
            <section class="status-card" aria-live="polite">
                <h2>Informasi Pendaftaran</h2>
                <div class="status-details">
                    <p>Nomor pendaftaran<br><strong><?= htmlspecialchars((string) $registration["nomor_pendaftaran"], ENT_QUOTES, "UTF-8") ?></strong></p>
                    <p>Nama calon siswa<br><strong><?= htmlspecialchars((string) $registration["nama"], ENT_QUOTES, "UTF-8") ?></strong></p>
                    <p>Asal sekolah<br><strong><?= htmlspecialchars((string) $registration["asal_sekolah"], ENT_QUOTES, "UTF-8") ?></strong></p>
                    <p>Status<br><span class="status-badge"><?= htmlspecialchars((string) $registration["status"], ENT_QUOTES, "UTF-8") ?></span></p>
                    <p>Tanggal pendaftaran<br><strong><?= htmlspecialchars($registration["tanggal_daftar"] ? date("d-m-Y", strtotime($registration["tanggal_daftar"])) : "-", ENT_QUOTES, "UTF-8") ?></strong></p>
                </div>
            </section>
        <?php endif; ?>
    </section>
</main>

<footer><p>© <?= date("Y") ?> EDU REGISTER - Sistem Pendaftaran Siswa Baru</p></footer>
</body>
</html>
<?php
session_start();

if (empty($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$flash = $_SESSION["admin_flash"] ?? null;
unset($_SESSION["admin_flash"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);
    $status = is_string($_POST["status"] ?? null) ? $_POST["status"] : "";
    $allowedStatuses = ["Menunggu", "Diterima", "Ditolak"];

    if (!is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
        $_SESSION["admin_flash"] = ["type" => "error", "message" => "Sesi tidak valid. Silakan coba kembali."];
    } elseif (!$id || !in_array($status, $allowedStatuses, true)) {
        $_SESSION["admin_flash"] = ["type" => "error", "message" => "Data perubahan status tidak valid."];
    } else {
        require __DIR__ . "/../config/koneksi.php";
        $update = mysqli_prepare($conn, "UPDATE pendaftaran SET status = ? WHERE id = ?");
        if (!$update) {
            error_log("Gagal menyiapkan perubahan status pendaftaran: " . mysqli_error($conn));
            http_response_code(500);
            exit("Status pendaftaran belum dapat diperbarui. Silakan coba kembali nanti.");
        }
        mysqli_stmt_bind_param($update, "si", $status, $id);
        if (mysqli_stmt_execute($update)) {
            $_SESSION["admin_flash"] = ["type" => "success", "message" => "Status pendaftaran berhasil diperbarui."];
        } else {
            error_log("Gagal memperbarui status pendaftaran: " . mysqli_stmt_error($update));
            $_SESSION["admin_flash"] = ["type" => "error", "message" => "Status pendaftaran gagal diperbarui."];
        }
        mysqli_stmt_close($update);
    }

    header("Location: dashboard.php");
    exit;
}

require __DIR__ . "/../config/koneksi.php";
$result = mysqli_query(
    $conn,
    "SELECT id, nomor_pendaftaran, nama, nisn, asal_sekolah, status, tanggal_daftar
     FROM pendaftaran
     ORDER BY tanggal_daftar DESC
     LIMIT 100"
);
if (!$result) {
    error_log("Gagal memuat daftar pendaftaran admin: " . mysqli_error($conn));
    http_response_code(500);
    exit("Daftar pendaftaran belum dapat dimuat. Silakan coba kembali nanti.");
}
$registrations = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - EDU REGISTER</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<nav>
    <div class="logo">EDU<span>REGISTER</span></div>
    <div class="menu">
        <a href="../index.php">Beranda</a>
        <form class="nav-form" method="post" action="logout.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
            <button class="button-link" type="submit">Keluar (<?= htmlspecialchars($_SESSION["admin"], ENT_QUOTES, "UTF-8") ?>)</button>
        </form>
    </div>
</nav>

<header class="page-header">
    <h1>Dashboard Admin</h1>
    <p>Kelola status pendaftaran peserta didik baru.</p>
</header>

<main class="form-wrap">
    <section class="form-card">
        <h2>Data Pendaftar</h2>
        <p class="form-intro">Menampilkan maksimal 100 pendaftaran terbaru.</p>
        <?php if ($flash): ?>
            <div class="alert <?= $flash["type"] === "success" ? "alert-success" : "alert-error" ?>" role="alert">
                <?= htmlspecialchars($flash["message"], ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <?php if (!$registrations): ?>
            <p>Belum ada pendaftaran yang masuk.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No. pendaftaran</th>
                            <th>Nama</th>
                            <th>NISN</th>
                            <th>Asal sekolah</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $registration): ?>
                            <?php $registeredAt = $registration["tanggal_daftar"] ? date("d-m-Y", strtotime($registration["tanggal_daftar"])) : "-"; ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $registration["nomor_pendaftaran"], ENT_QUOTES, "UTF-8") ?></td>
                                <td><?= htmlspecialchars((string) $registration["nama"], ENT_QUOTES, "UTF-8") ?></td>
                                <td><?= htmlspecialchars((string) $registration["nisn"], ENT_QUOTES, "UTF-8") ?></td>
                                <td><?= htmlspecialchars((string) $registration["asal_sekolah"], ENT_QUOTES, "UTF-8") ?></td>
                                <td><?= htmlspecialchars($registeredAt, ENT_QUOTES, "UTF-8") ?></td>
                                <td>
                                    <form class="status-form" method="post" action="dashboard.php">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
                                        <input type="hidden" name="id" value="<?= (int) $registration["id"] ?>">
                                        <select name="status" aria-label="Status untuk <?= htmlspecialchars((string) $registration["nama"], ENT_QUOTES, "UTF-8") ?>">
                                            <?php foreach (["Menunggu", "Diterima", "Ditolak"] as $option): ?>
                                                <option value="<?= htmlspecialchars($option, ENT_QUOTES, "UTF-8") ?>" <?= $registration["status"] === $option ? "selected" : "" ?>>
                                                    <?= htmlspecialchars($option, ENT_QUOTES, "UTF-8") ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="button-primary" type="submit">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer><p>© <?= date("Y") ?> EDU REGISTER - Sistem Pendaftaran Siswa Baru</p></footer>
</body>
</html>

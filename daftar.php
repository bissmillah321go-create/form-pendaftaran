<?php
session_start();

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$flash = $_SESSION["flash"] ?? null;
unset($_SESSION["flash"]);
$old = $_SESSION["old_input"] ?? [];
unset($_SESSION["old_input"]);

function old_value(string $key, array $old): string
{
    return htmlspecialchars((string) ($old[$key] ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Siswa Baru - EDU REGISTER</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<nav>
    <div class="logo">EDU<span>REGISTER</span></div>
    <div class="menu">
        <a href="index.php">Beranda</a>
        <a href="daftar.php" aria-current="page">Pendaftaran</a>
        <a href="cek_status.php">Cek Status</a>
    </div>
</nav>

<header class="page-header">
    <h1>Formulir Pendaftaran</h1>
    <p>Lengkapi data calon peserta didik dengan benar. Data bertanda * wajib diisi.</p>
</header>

<main class="form-wrap">
    <section class="form-card">
        <h2>Data Calon Peserta Didik</h2>
        <p class="form-intro">Pastikan nomor HP yang dicantumkan aktif. Nomor ini diperlukan untuk mengecek status pendaftaran.</p>

        <?php if ($flash): ?>
            <div class="alert <?= $flash["type"] === "success" ? "alert-success" : "alert-error" ?>" role="alert">
                <?= htmlspecialchars($flash["message"], ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form action="proses_daftar.php" method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
            <div class="form-grid">
                <div class="field field-full">
                    <label for="nama">Nama lengkap *</label>
                    <input id="nama" name="nama" type="text" maxlength="100" autocomplete="name" value="<?= old_value("nama", $old) ?>" required>
                </div>
                <div class="field">
                    <label for="nisn">NISN *</label>
                    <input id="nisn" name="nisn" type="text" inputmode="numeric" maxlength="20" value="<?= old_value("nisn", $old) ?>" required>
                </div>
                <div class="field">
                    <label for="jenis_kelamin">Jenis kelamin *</label>
                    <select id="jenis_kelamin" name="jenis_kelamin" required>
                        <option value="">Pilih jenis kelamin</option>
                        <option value="Laki-laki" <?= ($old["jenis_kelamin"] ?? "") === "Laki-laki" ? "selected" : "" ?>>Laki-laki</option>
                        <option value="Perempuan" <?= ($old["jenis_kelamin"] ?? "") === "Perempuan" ? "selected" : "" ?>>Perempuan</option>
                    </select>
                </div>
                <div class="field">
                    <label for="no_hp">Nomor HP aktif *</label>
                    <input id="no_hp" name="no_hp" type="tel" maxlength="20" autocomplete="tel" placeholder="Contoh: 081234567890" value="<?= old_value("no_hp", $old) ?>" required>
                </div>
                <div class="field">
                    <label for="tempat_lahir">Tempat lahir *</label>
                    <input id="tempat_lahir" name="tempat_lahir" type="text" maxlength="100" value="<?= old_value("tempat_lahir", $old) ?>" required>
                </div>
                <div class="field">
                    <label for="tanggal_lahir">Tanggal lahir *</label>
                    <input id="tanggal_lahir" name="tanggal_lahir" type="date" max="<?= date("Y-m-d") ?>" value="<?= old_value("tanggal_lahir", $old) ?>" required>
                </div>
                <div class="field">
                    <label for="asal_sekolah">Asal sekolah *</label>
                    <input id="asal_sekolah" name="asal_sekolah" type="text" maxlength="100" value="<?= old_value("asal_sekolah", $old) ?>" required>
                </div>
                <div class="field field-full">
                    <label for="nama_ortu">Nama orang tua/wali *</label>
                    <input id="nama_ortu" name="nama_ortu" type="text" maxlength="100" value="<?= old_value("nama_ortu", $old) ?>" required>
                </div>
                <div class="field field-full">
                    <label for="alamat">Alamat lengkap *</label>
                    <textarea id="alamat" name="alamat" maxlength="500" autocomplete="street-address" required><?= old_value("alamat", $old) ?></textarea>
                </div>
            </div>
            <p class="required-note">* Wajib diisi. Dengan mengirim formulir, Anda menyatakan data yang diberikan sudah benar.</p>
            <div class="form-actions">
                <button class="button-primary" type="submit">Kirim Pendaftaran</button>
                <a class="button-link" href="cek_status.php">Sudah mendaftar? Cek status</a>
            </div>
        </form>
    </section>
</main>

<footer><p>© <?= date("Y") ?> EDU REGISTER - Sistem Pendaftaran Siswa Baru</p></footer>
</body>
</html>
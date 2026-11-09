<?php
session_start();
include '../config/koneksi.php';

$error = "";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $username = is_string($_POST["username"] ?? null) ? trim($_POST["username"]) : "";
    $password = is_string($_POST["password"] ?? null) ? $_POST["password"] : "";

    if (!is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
        $error = "Sesi login tidak valid. Silakan muat ulang halaman.";
    } elseif ($username === "" || $password === "") {
        $error = "Username dan password wajib diisi.";
    } else {
        $query = mysqli_prepare(
            $conn,
            "SELECT id, username, password FROM admin WHERE username = ?"
        );

        if (!$query) {
            error_log("Gagal menyiapkan login admin: " . mysqli_error($conn));
            http_response_code(500);
            exit("Login admin belum dapat diproses. Silakan coba kembali nanti.");
        }

        mysqli_stmt_bind_param($query, "s", $username);
        if (!mysqli_stmt_execute($query)) {
            error_log("Gagal memeriksa login admin: " . mysqli_stmt_error($query));
            mysqli_stmt_close($query);
            http_response_code(500);
            exit("Login admin belum dapat diproses. Silakan coba kembali nanti.");
        }

        $result = mysqli_stmt_get_result($query);
        $admin = mysqli_fetch_assoc($result);
        mysqli_stmt_close($query);

        if ($admin && password_verify($password, $admin["password"])) {
            session_regenerate_id(true);

            $_SESSION["admin_id"] = $admin["id"];
            $_SESSION["admin"] = $admin["username"];
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

            header("Location: dashboard.php");
            exit;
        }
        $error = "Username atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="container">
    <h2>Login Admin</h2>
    <p>Silakan masuk untuk mengelola pendaftaran siswa.</p>

    <?php if ($error): ?>
        <p class="alert alert-error" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8"); ?>
        </p>
    <?php endif; ?>

    <form method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
        <label>Username</label>
        <input type="text" name="username" autocomplete="username" required>

        <label>Password</label>
        <input type="password" name="password" autocomplete="current-password" required>

        <button class="button-primary" type="submit" name="login">
            Masuk
        </button>
    </form>
</div>

</body>
</html>
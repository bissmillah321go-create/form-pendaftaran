<?php
session_start();

$token = $_POST["csrf_token"] ?? "";
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !is_string($token) || empty($_SESSION["csrf_token"]) || !hash_equals($_SESSION["csrf_token"], $token)) {
    http_response_code(400);
    exit("Permintaan logout tidak valid.");
}

$_SESSION = [];
session_destroy();
header("Location: login.php");
exit;

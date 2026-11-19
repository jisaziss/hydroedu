<?php
// ========================================================
// FILE: pages/logout.php
// Keluar Akun & Hapus Sesi
// ========================================================
require_once __DIR__ . '/../config/koneksi.php';

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: " . BASE_URL . "pages/login.php?msg=logout_success");
exit;

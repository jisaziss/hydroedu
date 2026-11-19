<?php
// ========================================================
// FILE: config/koneksi.php
// Konfigurasi Database & Helper Global HydroEdu
// ========================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "hydroedu";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("<div style='font-family: Arial; padding: 20px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin: 20px;'>
            <h3>⚠️ Koneksi Database Gagal!</h3>
            <p>Pastikan MySQL di XAMPP sudah dinyalakan dan database <strong>hydroedu</strong> sudah di-import.</p>
            <p><strong>Error:</strong> " . mysqli_connect_error() . "</p>
         </div>");
}

// Menentukan Base URL Otomatis & Dinamis yang Akurat
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

// Hitung folder aplikasi relatif terhadap DOCUMENT_ROOT
$doc_root = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
$app_root = str_replace('\\', '/', realpath(dirname(__DIR__)));
$relative_path = str_replace($doc_root, '', $app_root);
$relative_path = '/' . trim($relative_path, '/') . '/';
if ($relative_path === '//') {
    $relative_path = '/';
}

$base_url = $protocol . $host . $relative_path;
define('BASE_URL', $base_url);

// ========================================================
// FUNGSI HELPER GLOBAL
// ========================================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getCurrentUser($conn) {
    if (!isLoggedIn()) return null;
    $user_id = (int)$_SESSION['user_id'];
    $query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id LIMIT 1");
    return mysqli_fetch_assoc($query);
}

// Perhitungan Kebutuhan Air (Standar Medis 30-35ml/kg + Faktor Aktivitas)
function hitungTargetAir($berat_badan, $tingkat_aktivitas = 'sedang') {
    $dasar = $berat_badan * 33; // 33ml per kg
    if ($tingkat_aktivitas === 'berat') {
        $dasar += 600;
    } elseif ($tingkat_aktivitas === 'ringan') {
        $dasar -= 200;
    }
    return max(1500, (int)$dasar);
}
?>

<?php
// ========================================================
// FILE: api/scan.php
// Endpoint backend AI Drink Scanner.
// Menerima 1 foto (multipart "image"), meneruskannya ke Gemini API
// (API key hanya ada di server), lalu mengembalikan hasil terstruktur.
// ========================================================
require_once __DIR__ . '/scanner_lib.php';

@set_time_limit(90);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    scanner_json(['ok' => false, 'error' => 'Metode tidak diizinkan.'], 405);
}
scanner_require_login();
scanner_check_csrf();

// --------------------------------------------------------
// Pembatasan pemakaian (melindungi kuota free tier)
// --------------------------------------------------------
$now   = time();
$times = array_values(array_filter($_SESSION['scan_times'] ?? [], function ($t) use ($now) {
    return is_int($t) && ($now - $t) < SCANNER_WINDOW_SEC;
}));
if (!empty($times) && ($now - end($times)) < SCANNER_MIN_INTERVAL_SEC) {
    scanner_json(['ok' => false, 'error' => 'Terlalu cepat. Tunggu beberapa detik sebelum memindai lagi.'], 429);
}
if (count($times) >= SCANNER_MAX_PER_WINDOW) {
    $wait = max(1, SCANNER_WINDOW_SEC - ($now - $times[0]));
    scanner_json(['ok' => false, 'error' => 'Batas pemindaian tercapai. Coba lagi sekitar ' . (int)ceil($wait / 60) . ' menit lagi.'], 429);
}

// --------------------------------------------------------
// Validasi file gambar
// --------------------------------------------------------
if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
    scanner_json(['ok' => false, 'error' => 'Tidak ada gambar yang diterima.'], 400);
}
$f = $_FILES['image'];
if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $too_big = in_array($f['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
    scanner_json(['ok' => false, 'error' => $too_big ? 'Ukuran gambar terlalu besar.' : 'Gambar gagal diunggah. Coba lagi.'], 400);
}
if (!is_uploaded_file($f['tmp_name']) || $f['size'] <= 0 || $f['size'] > SCANNER_MAX_IMAGE_BYTES) {
    scanner_json(['ok' => false, 'error' => 'Ukuran gambar tidak valid (maksimal ' . (SCANNER_MAX_IMAGE_BYTES / 1048576) . ' MB).'], 400);
}

$bytes = file_get_contents($f['tmp_name']);
$info  = $bytes !== false ? @getimagesizefromstring($bytes) : false;
$allowed = ['image/jpeg', 'image/png', 'image/webp'];
if ($info === false || !in_array($info['mime'], $allowed, true) || $info[0] < 32 || $info[1] < 32) {
    scanner_json(['ok' => false, 'error' => 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WebP.'], 415);
}
$mime = $info['mime'];
$b64  = base64_encode($bytes);
unset($bytes);

// Catat pemakaian, lalu lepas kunci sesi supaya halaman lain tetap responsif
$times[] = $now;
$_SESSION['scan_times'] = $times;
session_write_close();

// --------------------------------------------------------
// Panggil Gemini
// --------------------------------------------------------
$res = scanner_call_gemini($b64, $mime);
unset($b64);

if (!$res['ok']) {
    error_log('[HydroEdu Scanner] ' . ($res['code'] ?? '?') . ' - ' . ($res['debug'] ?? ''));
    // Detail teknis hanya untuk admin
    $out = ['ok' => false, 'error' => $res['message'] ?? 'Terjadi kesalahan.', 'code' => $res['code'] ?? 'unknown'];
    if (isAdmin() && !empty($res['debug'])) $out['debug'] = $res['debug'];
    $status = in_array($res['code'] ?? '', ['quota', 'busy', 'timeout'], true) ? 503 : 502;
    scanner_json($out, $status);
}

$hasil = scanner_normalize($res['data']);

// --------------------------------------------------------
// Simpan sementara di sesi agar "Simpan ke Riwayat" memakai hasil
// asli server (bukan data kiriman browser)
// --------------------------------------------------------
$scan_id = bin2hex(random_bytes(8));
session_start();
$store = $_SESSION['scanner_last'] ?? [];
$store[$scan_id] = ['hasil' => $hasil, 'saved_id' => 0, 't' => time()];
if (count($store) > 6) {
    uasort($store, function ($a, $b) { return $a['t'] <=> $b['t']; });
    $store = array_slice($store, -6, null, true);
}
$_SESSION['scanner_last'] = $store;
session_write_close();

scanner_json(['ok' => true, 'scan_id' => $scan_id, 'hasil' => $hasil]);

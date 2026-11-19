<?php
// ========================================================
// FILE: config/gemini.php
// Konfigurasi AI Drink Scanner (Gemini API)
//
// PENTING:
// - File ini HANYA dibaca oleh PHP di server. API key tidak
//   pernah dikirim ke browser.
// - Jangan commit API key asli ke GitHub publik.
// - Cara mengisi key (pilih salah satu):
//     a) Ganti 'ISI_API_KEY_ANDA' di bawah dengan key dari
//        https://aistudio.google.com/apikey
//     b) Atau set environment variable GEMINI_API_KEY
// ========================================================

// Blokir akses langsung lewat URL
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    http_response_code(403);
    exit;
}

if (!defined('GEMINI_API_KEY')) {
    $env_key = getenv('GEMINI_API_KEY');
    define('GEMINI_API_KEY', ($env_key !== false && $env_key !== '') ? $env_key : 'API_KEY_KELOMMPOK');
}

// Daftar model yang dicoba berurutan. Jika model pertama kuotanya habis (429),
// tidak tersedia (404/403), atau error server, scanner otomatis mencoba model berikutnya.
// Nama model Gemini cukup sering berganti: cek daftar terbaru & kuota free tier di
// https://ai.google.dev/gemini-api/docs/models dan Google AI Studio > Rate limits.
if (!defined('GEMINI_MODELS')) {
    define('GEMINI_MODELS', [
        'gemini-3.5-flash-lite',
        'gemini-3.6-flash',
        'gemini-3.1-flash-lite',
    ]);
}

if (!defined('GEMINI_ENDPOINT')) {
    define('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/');
}

// Timeout per permintaan ke Gemini (detik)
if (!defined('GEMINI_TIMEOUT')) {
    define('GEMINI_TIMEOUT', 25);
}

// Opsional (XAMPP/Windows): path ke file cacert.pem bila muncul error
// "SSL certificate problem". Kosongkan jika php.ini sudah mengatur curl.cainfo.
// Contoh: 'C:/xampp/php/extras/ssl/cacert.pem'
if (!defined('GEMINI_CA_BUNDLE')) {
    define('GEMINI_CA_BUNDLE', '');
}

// Batas ukuran gambar yang diterima server (setelah dikompres di browser)
if (!defined('SCANNER_MAX_IMAGE_BYTES')) {
    define('SCANNER_MAX_IMAGE_BYTES', 4 * 1024 * 1024);
}

// Batas pemakaian per sesi login (melindungi kuota free tier)
if (!defined('SCANNER_MIN_INTERVAL_SEC')) {
    define('SCANNER_MIN_INTERVAL_SEC', 3);
}
if (!defined('SCANNER_MAX_PER_WINDOW')) {
    define('SCANNER_MAX_PER_WINDOW', 10);   // maksimal 10 pemindaian ...
}
if (!defined('SCANNER_WINDOW_SEC')) {
    define('SCANNER_WINDOW_SEC', 600);      // ... dalam 10 menit
}

// Batas jumlah hasil yang boleh disimpan per pengguna
if (!defined('SCANNER_MAX_HISTORY')) {
    define('SCANNER_MAX_HISTORY', 200);
}

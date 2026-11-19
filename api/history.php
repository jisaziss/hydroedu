<?php
// ========================================================
// FILE: api/history.php
// Riwayat AI Drink Scanner: list | get | save | delete
// Semua query memakai prepared statement & dibatasi per user_id.
// ========================================================
require_once __DIR__ . '/scanner_lib.php';

scanner_require_login();
scanner_ensure_table($conn);

$user_id = (int)$_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];
$action  = $method === 'POST' ? ($_POST['action'] ?? '') : ($_GET['action'] ?? '');

// --------------------------------------------------------
// LIST (GET): ?action=list&offset=0
// --------------------------------------------------------
if ($method === 'GET' && $action === 'list') {
    $limit  = 12;
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $fetch  = $limit + 1;

    $stmt = $conn->prepare("SELECT id, nama_minuman, kategori, thumbnail, created_at
                            FROM drink_scans WHERE user_id = ?
                            ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('iii', $user_id, $fetch, $offset);
    $stmt->execute();
    $rs = $stmt->get_result();
    $cats = scanner_categories();
    $items = [];
    while ($row = $rs->fetch_assoc()) {
        $items[] = [
            'id'             => (int)$row['id'],
            'nama'           => $row['nama_minuman'],
            'kategori'       => $row['kategori'],
            'kategori_label' => $cats[$row['kategori']] ?? $cats['lainnya'],
            'thumbnail'      => $row['thumbnail'],
            'created_at'     => $row['created_at'],
        ];
    }
    $stmt->close();
    $has_more = count($items) > $limit;
    if ($has_more) array_pop($items);

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM drink_scans WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    scanner_json(['ok' => true, 'items' => $items, 'has_more' => $has_more, 'total' => $total]);
}

// --------------------------------------------------------
// GET (GET): ?action=get&id=123
// --------------------------------------------------------
if ($method === 'GET' && $action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare("SELECT id, hasil_json, thumbnail, created_at FROM drink_scans WHERE id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) scanner_json(['ok' => false, 'error' => 'Data tidak ditemukan.'], 404);

    // Hasil sudah dinormalisasi saat dipindai & disimpan oleh server (bukan dari browser).
    // Frontend tetap menampilkannya via textContent (aman dari XSS).
    $hasil = json_decode($row['hasil_json'], true);
    if (!is_array($hasil)) scanner_json(['ok' => false, 'error' => 'Data riwayat rusak.'], 500);
    scanner_json(['ok' => true, 'item' => [
        'id' => (int)$row['id'], 'hasil' => $hasil, 'thumbnail' => $row['thumbnail'], 'created_at' => $row['created_at'],
    ]]);
}

// --------------------------------------------------------
// SAVE (POST): scan_id + thumbnail
// --------------------------------------------------------
if ($method === 'POST' && $action === 'save') {
    scanner_check_csrf();

    $scan_id = (string)($_POST['scan_id'] ?? '');
    $entry   = $_SESSION['scanner_last'][$scan_id] ?? null;
    if (!$entry || !is_array($entry['hasil'] ?? null)) {
        scanner_json(['ok' => false, 'error' => 'Hasil pemindaian sudah kedaluwarsa. Silakan pindai ulang.'], 410);
    }
    if (($entry['hasil']['status'] ?? '') !== 'minuman') {
        scanner_json(['ok' => false, 'error' => 'Hanya hasil minuman yang dapat disimpan.'], 422);
    }
    if (!empty($entry['saved_id'])) {
        scanner_json(['ok' => true, 'id' => (int)$entry['saved_id'], 'already' => true]);
    }

    // Batas jumlah riwayat
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM drink_scans WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
    if ($total >= SCANNER_MAX_HISTORY) {
        scanner_json(['ok' => false, 'error' => 'Riwayat penuh (' . SCANNER_MAX_HISTORY . ' hasil). Hapus beberapa hasil lama dulu.'], 409);
    }

    // Validasi thumbnail (opsional): harus JPEG kecil
    $thumb = (string)($_POST['thumbnail'] ?? '');
    $thumb_ok = null;
    if ($thumb !== '') {
        if (strlen($thumb) <= 90000 && preg_match('#^data:image/jpeg;base64,([A-Za-z0-9+/=]+)$#', $thumb, $m)) {
            $raw = base64_decode($m[1], true);
            $gi  = $raw !== false ? @getimagesizefromstring($raw) : false;
            if ($gi !== false && $gi['mime'] === 'image/jpeg' && $gi[0] <= 480 && $gi[1] <= 480) {
                $thumb_ok = $thumb;
            }
        }
    }

    $hasil = $entry['hasil'];
    $json  = json_encode($hasil, JSON_UNESCAPED_UNICODE);
    $nama  = scanner_clean_text($hasil['nama'] ?? '', 150);
    $kat   = (string)($hasil['kategori'] ?? 'lainnya');

    $stmt = $conn->prepare("INSERT INTO drink_scans (user_id, nama_minuman, kategori, hasil_json, thumbnail) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('issss', $user_id, $nama, $kat, $json, $thumb_ok);
    if (!$stmt->execute()) {
        error_log('[HydroEdu Scanner] gagal simpan: ' . $stmt->error);
        scanner_json(['ok' => false, 'error' => 'Gagal menyimpan hasil. Coba lagi.'], 500);
    }
    $new_id = (int)$stmt->insert_id;
    $stmt->close();

    $_SESSION['scanner_last'][$scan_id]['saved_id'] = $new_id;
    scanner_json(['ok' => true, 'id' => $new_id]);
}

// --------------------------------------------------------
// DELETE (POST): id
// --------------------------------------------------------
if ($method === 'POST' && $action === 'delete') {
    scanner_check_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM drink_scans WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();
    if (!$deleted) scanner_json(['ok' => false, 'error' => 'Data tidak ditemukan.'], 404);
    scanner_json(['ok' => true]);
}

scanner_json(['ok' => false, 'error' => 'Aksi tidak dikenal.'], 400);

<?php
// ========================================================
// FILE: pages/calculator.php
// Modul 3: Smart Hydration Calculator & Pengaturan Reminder
// ========================================================
require_once __DIR__ . '/../config/koneksi.php';

$page_title = "Smart Hydration Calculator";
$logged_in = isLoggedIn();
$user = $logged_in ? getCurrentUser($conn) : null;

$msg_success = "";
$msg_error = "";

// --------------------------------------------------------
// HANDLER: UPDATE TARGET HARIAN KE DATABASE
// --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_target') {
    if (!$logged_in) {
        header("Location: " . BASE_URL . "pages/login.php");
        exit;
    }
    
    $new_target = (int)$_POST['target_ml'];
    $new_bb = (float)$_POST['berat_badan'];
    $new_akt = mysqli_real_escape_string($conn, $_POST['tingkat_aktivitas']);
    $user_id = (int)$user['id'];

    if ($new_target >= 1000 && $new_target <= 6000) {
        $update = mysqli_query($conn, "UPDATE users SET target_harian_ml = $new_target, berat_badan = $new_bb, tingkat_aktivitas = '$new_akt' WHERE id = $user_id");
        if ($update) {
            $msg_success = "Target air harian Anda berhasil diperbarui menjadi " . number_format($new_target, 0, ',', '.') . " ml!";
            $user = getCurrentUser($conn); // refresh
        } else {
            $msg_error = "Gagal memperbarui target: " . mysqli_error($conn);
        }
    }
}

// --------------------------------------------------------
// HANDLER: UPDATE PENGATURAN REMINDER
// --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_reminder') {
    if (!$logged_in) {
        header("Location: " . BASE_URL . "pages/login.php");
        exit;
    }
    
    $interval = (int)$_POST['interval_menit'];
    $status = isset($_POST['status_aktif']) ? 1 : 0;
    $jam_mulai = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
    $jam_selesai = mysqli_real_escape_string($conn, $_POST['jam_selesai']);
    $user_id = (int)$user['id'];

    $upsert = mysqli_query($conn, "INSERT INTO hydration_reminders (user_id, interval_menit, jam_mulai, jam_selesai, status_aktif) 
                                   VALUES ($user_id, $interval, '$jam_mulai', '$jam_selesai', $status)
                                   ON DUPLICATE KEY UPDATE interval_menit = $interval, jam_mulai = '$jam_mulai', jam_selesai = '$jam_selesai', status_aktif = $status");
    if ($upsert) {
        $msg_success = "Pengaturan alarm pengingat berhasil disimpan!";
    }
}

// Ambil data reminder user
$reminder = null;
if ($logged_in) {
    $q_rem = mysqli_query($conn, "SELECT * FROM hydration_reminders WHERE user_id = " . (int)$user['id'] . " LIMIT 1");
    $reminder = mysqli_fetch_assoc($q_rem);
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <!-- Notifikasi Flash -->
  <?php if (!empty($msg_success)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 small" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> <?= $msg_success ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (!empty($msg_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 small" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $msg_error ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="text-center mb-5">
    <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1 rounded-pill mb-2">
      <i class="bi bi-calculator me-1"></i> Modul 3: Smart Hydration Calculator & Reminder (SDG 3)
    </span>
    <h2 class="fw-bold">Kalkulator Kebutuhan Cairan & Pengingat</h2>
    <p class="text-muted mx-auto" style="max-width: 650px;">
      Gunakan formula klinis presisi untuk menentukan jumlah air ideal tubuh Anda dan jadwalkan alarm minum di peramban Anda.
    </p>
  </div>

  <div class="row g-4 justify-content-center">
    <!-- ============================================== -->
    <!-- BAGIAN 1: KALKULATOR AIR CERDAS                -->
    <!-- ============================================== -->
    <div class="col-lg-7">
      <div class="card card-custom p-4 p-md-5 border-0 h-100">
        <h4 class="fw-bold mb-3"><i class="bi bi-droplet-half text-primary me-2"></i>Kalkulasi Kebutuhan Air</h4>
        <p class="small text-muted mb-4">
          Formula medis dasar: <strong>30-35 ml per kg berat badan</strong>, disesuaikan dengan intensitas beban kerja fisik harian dan iklim sekitar.
        </p>

        <form method="POST" action="" id="calcForm">
          <input type="hidden" name="action" value="save_target">
          <input type="hidden" name="target_ml" id="inputTargetMl" value="2000">

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Berat Badan (kg)</label>
              <div class="input-group">
                <input type="number" name="berat_badan" id="calcWeight" class="form-control" min="30" max="250" value="<?= $user ? $user['berat_badan'] : 60 ?>" required>
                <span class="input-group-text">kg</span>
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold">Intensitas Aktivitas</label>
              <select name="tingkat_aktivitas" id="calcActivity" class="form-select">
                <option value="ringan" <?= ($user && $user['tingkat_aktivitas'] == 'ringan') ? 'selected' : '' ?>>Ringan (Banyak Duduk)</option>
                <option value="sedang" <?= (!$user || $user['tingkat_aktivitas'] == 'sedang') ? 'selected' : '' ?>>Sedang (Jalan/Kuliah Normal)</option>
                <option value="berat" <?= ($user && $user['tingkat_aktivitas'] == 'berat') ? 'selected' : '' ?>>Berat (Olahraga Rutin / Lapangan)</option>
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label small fw-semibold">Kondisi Cuaca / Ruangan</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="cuaca" id="cuacaNormal" value="normal" checked>
                <label class="form-check-label small" for="cuacaNormal">Ruangan Ber-AC / Sejuk</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="cuaca" id="cuacaPanas" value="panas">
                <label class="form-check-label small" for="cuacaPanas">Terik Panas / Lembap (+300 ml)</label>
              </div>
            </div>
          </div>

          <!-- KOTAK HASIL PERHITUNGAN -->
          <div class="p-4 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3 text-center mb-4">
            <div class="text-muted small fw-semibold">Kebutuhan Cairan Ideal Harian Anda:</div>
            <div class="display-5 fw-bold text-primary my-1" id="displayTarget">1.980 ml</div>
            <div class="text-muted small" id="displayGlasses">Setara dengan ~8 gelas standar (250 ml)</div>
          </div>

          <?php if ($logged_in): ?>
            <button type="submit" class="btn btn-hydro w-100 py-2">
              <i class="bi bi-bookmark-check me-1"></i> Terapkan Sebagai Target Harian Saya
            </button>
          <?php else: ?>
            <a href="<?= BASE_URL ?>pages/login.php" class="btn btn-outline-primary w-100 py-2">
              <i class="bi bi-lock me-1"></i> Masuk Akun untuk Menyimpan Target Ini
            </a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- ============================================== -->
    <!-- BAGIAN 2: PENGATURAN REMINDER / ALARM MINUM    -->
    <!-- ============================================== -->
    <div class="col-lg-5">
      <div class="card card-custom p-4 border-0 h-100">
        <h4 class="fw-bold mb-3"><i class="bi bi-bell-fill text-warning me-2"></i>Alarm Pengingat</h4>
        <p class="small text-muted mb-4">
          Sering lupa minum saat asyik mengerjakan tugas atau di depan laptop? Atur interval waktu pengingat otomatis.
        </p>

        <?php if ($logged_in): ?>
          <form method="POST" action="">
            <input type="hidden" name="action" value="save_reminder">

            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" role="switch" id="statusAktif" name="status_aktif" value="1" <?= (!$reminder || $reminder['status_aktif'] == 1) ? 'checked' : '' ?>>
              <label class="form-check-label fw-semibold small" for="statusAktif">Aktifkan Pengingat</label>
            </div>

            <div class="mb-3">
              <label class="form-label small fw-semibold">Ingatkan Saya Setiap</label>
              <select name="interval_menit" class="form-select">
                <option value="30" <?= ($reminder && $reminder['interval_menit'] == 30) ? 'selected' : '' ?>>30 Menit Sekali</option>
                <option value="45" <?= ($reminder && $reminder['interval_menit'] == 45) ? 'selected' : '' ?>>45 Menit Sekali</option>
                <option value="60" <?= (!$reminder || $reminder['interval_menit'] == 60) ? 'selected' : '' ?>>1 Jam Sekali (Rekomendasi)</option>
                <option value="90" <?= ($reminder && $reminder['interval_menit'] == 90) ? 'selected' : '' ?>>1.5 Jam Sekali</option>
                <option value="120" <?= ($reminder && $reminder['interval_menit'] == 120) ? 'selected' : '' ?>>2 Jam Sekali</option>
              </select>
            </div>

            <div class="row g-2 mb-4">
              <div class="col-6">
                <label class="form-label small fw-semibold">Mulai Jam</label>
                <input type="time" name="jam_mulai" class="form-control form-control-sm" value="<?= $reminder ? $reminder['jam_mulai'] : '07:00' ?>">
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold">Hingga Jam</label>
                <input type="time" name="jam_selesai" class="form-control form-control-sm" value="<?= $reminder ? $reminder['jam_selesai'] : '21:00' ?>">
              </div>
            </div>

            <button type="submit" class="btn btn-warning w-100 text-dark fw-bold mb-3">
              <i class="bi bi-save me-1"></i> Simpan Pengaturan
            </button>
          </form>

          <!-- Uji Notifikasi Browser -->
          <div class="p-3 bg-light rounded-3 border">
            <div class="small fw-semibold text-dark mb-1">Uji Notifikasi Web Browser:</div>
            <p class="small text-muted mb-2" style="font-size: 0.8rem;">
              Klik tombol di bawah untuk meminta izin notifikasi di peramban Anda.
            </p>
            <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="testNotification()">
              <i class="bi bi-megaphone me-1"></i> Test Notifikasi Sekarang
            </button>
          </div>

        <?php else: ?>
          <div class="alert alert-light border p-4 text-center rounded-3">
            <i class="bi bi-shield-lock fs-1 text-muted d-block mb-2"></i>
            <h6 class="fw-bold">Fitur Khusus Pengguna</h6>
            <p class="small text-muted mb-3">Silakan masuk atau buat akun untuk menyimpan preferensi alarm pengingat Anda.</p>
            <a href="<?= BASE_URL ?>pages/login.php" class="btn btn-sm btn-primary rounded-pill px-3">Masuk Sekarang</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function hitungTargetLive() {
  const bb = parseFloat(document.getElementById('calcWeight').value) || 60;
  const akt = document.getElementById('calcActivity').value;
  const isPanas = document.getElementById('cuacaPanas').checked;

  let ml = bb * 33;
  if (akt === 'berat') ml += 600;
  else if (akt === 'ringan') ml -= 200;

  if (isPanas) ml += 300;

  ml = Math.max(1500, Math.round(ml));
  const gelas = Math.round(ml / 250);

  document.getElementById('displayTarget').innerText = ml.toLocaleString('id-ID') + ' ml';
  document.getElementById('displayGlasses').innerText = 'Setara dengan ~' + gelas + ' gelas standar (250 ml)';
  document.getElementById('inputTargetMl').value = ml;
}

document.getElementById('calcWeight').addEventListener('input', hitungTargetLive);
document.getElementById('calcActivity').addEventListener('change', hitungTargetLive);
document.getElementById('cuacaNormal').addEventListener('change', hitungTargetLive);
document.getElementById('cuacaPanas').addEventListener('change', hitungTargetLive);

hitungTargetLive();

function testNotification() {
  if (!("Notification" in window)) {
    alert("Browser Anda tidak mendukung notifikasi web desktop.");
    return;
  }
  
  Notification.requestPermission().then(function (permission) {
    if (permission === "granted") {
      new Notification("💧 Waktunya Minum Air! - HydroEdu", {
        body: "Tetap terhidrasi untuk konsentrasi belajar yang prima. Ambil segelas air sekarang!",
        icon: "https://cdn-icons-png.flaticon.com/512/3105/3105807.png"
      });
    } else {
      alert("Izin notifikasi ditolak di browser Anda.");
    }
  });
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

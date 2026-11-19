<?php
// ========================================================
// FILE: dashboard.php
// Dashboard Pengguna - Daily Tracker & Statistik Hidrasi
// ========================================================
require_once __DIR__ . '/config/koneksi.php';

// Wajib Login
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit;
}

$user = getCurrentUser($conn);
$user_id = (int)$user['id'];
$target_harian = (int)$user['target_harian_ml'];

$msg_success = "";
$msg_error = "";

// --------------------------------------------------------
// HANDLER: TAMBAH LOG MINUM (QUICK ADD / CUSTOM)
// --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_water') {
    $jumlah_ml = (int)$_POST['jumlah_ml'];
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori_minuman'] ?? 'air_mineral');
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan'] ?? '');

    if ($jumlah_ml > 0 && $jumlah_ml <= 3000) {
        $insert = mysqli_query($conn, "INSERT INTO hydration_logs (user_id, jumlah_ml, kategori_minuman, catatan, waktu_minum, tanggal) 
                                       VALUES ($user_id, $jumlah_ml, '$kategori', '$catatan', NOW(), CURDATE())");
        if ($insert) {
            $msg_success = "Berhasil mencatat $jumlah_ml ml ke log hidrasi Anda!";

            // Otomatisasi Badge 1: Tegukan Pertama (first_sip)
            mysqli_query($conn, "INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES ($user_id, 1)");

            // Hitung total hari ini untuk cek Badge 2 (Target Hit)
            $cek_tot = mysqli_query($conn, "SELECT SUM(jumlah_ml) AS total FROM hydration_logs WHERE user_id = $user_id AND tanggal = CURDATE()");
            $row_tot = mysqli_fetch_assoc($cek_tot);
            if ($row_tot['total'] >= $target_harian) {
                mysqli_query($conn, "INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES ($user_id, 2)");
            }
        } else {
            $msg_error = "Gagal menyimpan log minum: " . mysqli_error($conn);
        }
    } else {
        $msg_error = "Jumlah ml tidak valid (harus antara 50 - 3000 ml).";
    }
}

// --------------------------------------------------------
// HANDLER: HAPUS LOG MINUM
// --------------------------------------------------------
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM hydration_logs WHERE id = $del_id AND user_id = $user_id AND tanggal = CURDATE()");
    header("Location: " . BASE_URL . "dashboard.php?msg=deleted");
    exit;
}

// --------------------------------------------------------
// DATA HIDRASI HARI INI
// --------------------------------------------------------
$query_today = mysqli_query($conn, "SELECT SUM(jumlah_ml) AS total_minum FROM hydration_logs WHERE user_id = $user_id AND tanggal = CURDATE()");
$data_today = mysqli_fetch_assoc($query_today);
$total_hari_ini = (int)$data_today['total_minum'];
$persentase = $target_harian > 0 ? min(100, round(($total_hari_ini / $target_harian) * 100)) : 0;
$sisa_ml = max(0, $target_harian - $total_hari_ini);

// Ambil riwayat log hari ini
$logs_today = mysqli_query($conn, "SELECT * FROM hydration_logs WHERE user_id = $user_id AND tanggal = CURDATE() ORDER BY waktu_minum DESC");

// Ambil data 7 hari terakhir untuk Chart.js
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($tgl));
    
    $q_chart = mysqli_query($conn, "SELECT SUM(jumlah_ml) AS sum_ml FROM hydration_logs WHERE user_id = $user_id AND tanggal = '$tgl'");
    $r_chart = mysqli_fetch_assoc($q_chart);
    $chart_data[] = (int)($r_chart['sum_ml'] ?? 0);
}

// Ambil badge user
$user_badges_q = mysqli_query($conn, "SELECT b.* FROM user_badges ub JOIN badges b ON ub.badge_id = b.id WHERE ub.user_id = $user_id");
$total_badges = mysqli_num_rows($user_badges_q);

$page_title = "Dashboard Pelacak Hidrasi";
include_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
  <!-- Notifikasi Flash -->
  <?php if (!empty($msg_success)): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 small" role="alert">
      <i class="bi bi-check-circle-fill me-2"></i> <?= $msg_success ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-info alert-dismissible fade show rounded-3 small" role="alert">
      <i class="bi bi-info-circle-fill me-2"></i> Data riwayat minum berhasil dihapus.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- HEADER BAR ATAS -->
  <div class="row align-items-center mb-4">
    <div class="col-md-8">
      <h2 class="fw-bold mb-1">Hai, <?= htmlspecialchars($user['nama']) ?>! 👋</h2>
      <p class="text-muted mb-0">
        Hari ini adalah waktu yang tepat untuk menjaga kesehatan tubuh dan ketajaman konsentrasi Anda.
      </p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
      <span class="badge bg-light text-dark border p-2 px-3 rounded-pill">
        <i class="bi bi-calendar3 text-primary me-1"></i> <?= date('l, d F Y') ?>
      </span>
    </div>
  </div>

  <div class="row g-4">
    <!-- ============================================== -->
    <!-- KOLOM KIRI: MAIN TRACKER & LOG (7 KOLOM)       -->
    <!-- ============================================== -->
    <div class="col-lg-7">
      <!-- KARTU STATUS PROGRESS AIR -->
      <div class="card card-custom p-4 border-0 mb-4">
        <div class="row align-items-center">
          <div class="col-sm-4 text-center mb-3 mb-sm-0">
            <!-- Animasi Botol Air Visual -->
            <div class="water-bottle-container shadow-sm">
              <div class="water-bottle-fill" style="height: <?= $persentase ?>%;">
                <?= $persentase ?>%
              </div>
            </div>
            <span class="small text-muted d-block mt-2">Level Hidrasi</span>
          </div>

          <div class="col-sm-8">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="text-muted small fw-semibold">Progres Harian</span>
              <span class="badge <?= $persentase >= 100 ? 'bg-success' : 'bg-primary' ?> rounded-pill">
                <?= $persentase ?>% Tercapai
              </span>
            </div>

            <div class="display-6 fw-bold text-dark mb-1">
              <?= number_format($total_hari_ini, 0, ',', '.') ?> <span class="fs-5 text-muted fw-normal">/ <?= number_format($target_harian, 0, ',', '.') ?> ml</span>
            </div>

            <!-- Progress Bar -->
            <div class="progress mb-3" style="height: 12px; border-radius: 8px;">
              <div class="progress-bar bg-info progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= $persentase ?>%;"></div>
            </div>

            <?php if ($persentase >= 100): ?>
              <div class="alert alert-success py-2 px-3 small rounded-3 mb-0">
                <i class="bi bi-check2-circle me-1 fw-bold"></i> <strong>Luar Biasa!</strong> Target cairan harian Anda sudah tercukupi dengan baik!
              </div>
            <?php else: ?>
              <p class="small text-muted mb-0">
                Tersisa <strong><?= number_format($sisa_ml, 0, ',', '.') ?> ml</strong> lagi untuk mencapai hidrasi optimal hari ini. Semangat!
              </p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- KARTU TOMBOL CEPAT CATAT MINUM -->
      <div class="card card-custom p-4 border-0 mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle text-primary me-2"></i>Catat Asupan Minum</h5>
        
        <!-- Tombol Preset Cepat -->
        <div class="row g-2 mb-3">
          <div class="col-4">
            <form method="POST" action="">
              <input type="hidden" name="action" value="add_water">
              <input type="hidden" name="jumlah_ml" value="250">
              <input type="hidden" name="kategori_minuman" value="air_mineral">
              <button type="submit" class="btn btn-outline-primary w-100 py-3 rounded-3 text-center">
                <i class="bi bi-cup-hot d-block fs-4 mb-1"></i>
                <div class="fw-bold small">+250 ml</div>
                <div class="text-muted" style="font-size: 0.75rem;">1 Gelas</div>
              </button>
            </form>
          </div>
          <div class="col-4">
            <form method="POST" action="">
              <input type="hidden" name="action" value="add_water">
              <input type="hidden" name="jumlah_ml" value="600">
              <input type="hidden" name="kategori_minuman" value="air_mineral">
              <button type="submit" class="btn btn-outline-primary w-100 py-3 rounded-3 text-center">
                <i class="bi bi-water d-block fs-4 mb-1"></i>
                <div class="fw-bold small">+600 ml</div>
                <div class="text-muted" style="font-size: 0.75rem;">1 Botol Sedang</div>
              </button>
            </form>
          </div>
          <div class="col-4">
            <form method="POST" action="">
              <input type="hidden" name="action" value="add_water">
              <input type="hidden" name="jumlah_ml" value="1000">
              <input type="hidden" name="kategori_minuman" value="air_mineral">
              <button type="submit" class="btn btn-outline-primary w-100 py-3 rounded-3 text-center">
                <i class="bi bi-droplet-fill d-block fs-4 mb-1"></i>
                <div class="fw-bold small">+1.000 ml</div>
                <div class="text-muted" style="font-size: 0.75rem;">1 Tumbler Besar</div>
              </button>
            </form>
          </div>
        </div>

        <!-- Form Input Takaran Kustom -->
        <div class="p-3 bg-light rounded-3 border">
          <form method="POST" action="" class="row g-2 align-items-center">
            <input type="hidden" name="action" value="add_water">
            <div class="col-sm-5">
              <div class="input-group input-group-sm">
                <input type="number" name="jumlah_ml" class="form-control" placeholder="Takaran custom" min="50" max="2500" required>
                <span class="input-group-text">ml</span>
              </div>
            </div>
            <div class="col-sm-4">
              <select name="kategori_minuman" class="form-select form-select-sm">
                <option value="air_mineral">Air Mineral</option>
                <option value="infus_water">Infused Water</option>
                <option value="jus">Jus Segar</option>
                <option value="teh">Teh</option>
              </select>
            </div>
            <div class="col-sm-3">
              <button type="submit" class="btn btn-hydro btn-sm w-100">
                <i class="bi bi-plus-lg me-1"></i> Tambah
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- KARTU LOG RIWAYAT MINUM HARI INI -->
      <div class="card card-custom p-4 border-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Log Minum Hari Ini</h5>
          <span class="badge bg-light text-muted border"><?= mysqli_num_rows($logs_today) ?> Catatan</span>
        </div>

        <?php if ($logs_today && mysqli_num_rows($logs_today) > 0): ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle small mb-0">
              <thead class="table-light">
                <tr>
                  <th>Waktu</th>
                  <th>Jumlah</th>
                  <th>Kategori</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($log = mysqli_fetch_assoc($logs_today)): ?>
                  <tr>
                    <td class="text-muted">
                      <i class="bi bi-clock me-1"></i> <?= date('H:i', strtotime($log['waktu_minum'])) ?> WIB
                    </td>
                    <td class="fw-bold text-primary">
                      +<?= $log['jumlah_ml'] ?> ml
                    </td>
                    <td>
                      <span class="badge bg-light text-secondary border">
                        <?= ucwords(str_replace('_', ' ', $log['kategori_minuman'])) ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <a href="<?= BASE_URL ?>dashboard.php?delete_id=<?= $log['id'] ?>" class="text-danger" onclick="return confirm('Hapus catatan ini?')" title="Hapus Log">
                        <i class="bi bi-trash"></i>
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-4 text-muted small">
            <i class="bi bi-droplet-slash fs-2 d-block text-secondary opacity-50 mb-2"></i>
            Belum ada catatan minum hari ini. Mulai tegukan pertama Anda sekarang!
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ============================================== -->
    <!-- KOLOM KANAN: STATISTIK, GAMIFIKASI & INFO      -->
    <!-- ============================================== -->
    <div class="col-lg-5">
      <!-- KARTU GAMIFIKASI & BADGES -->
      <div class="card card-custom p-4 border-0 mb-4 bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold mb-0"><i class="bi bi-fire text-warning me-2"></i>Streak & Pencapaian</h5>
          <span class="badge bg-white text-primary rounded-pill px-3"><?= $total_badges ?> Lencana</span>
        </div>
        <p class="small text-white-50 mb-3">
          Tingkatkan konsistensi hidrasi dan ikuti kuis edukasi untuk membuka seluruh lencana SDGs HydroEdu.
        </p>

        <div class="d-flex gap-2 overflow-auto pb-2">
          <?php if ($user_badges_q && mysqli_num_rows($user_badges_q) > 0): ?>
            <?php while ($badge = mysqli_fetch_assoc($user_badges_q)): ?>
              <div class="p-2 px-3 bg-white bg-opacity-20 rounded-3 text-center flex-shrink-0" style="min-width: 100px;">
                <i class="bi <?= $badge['icon'] ?> fs-3 text-warning d-block mb-1"></i>
                <div class="small fw-semibold text-truncate" style="max-width: 90px;"><?= htmlspecialchars($badge['nama_badge']) ?></div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="small text-white-50">Belum ada lencana. Capai target atau isi kuis untuk meraih lencana!</div>
          <?php endif; ?>
        </div>

        <div class="text-end mt-2">
          <a href="<?= BASE_URL ?>pages/badges.php" class="text-white small text-decoration-underline">
            Lihat Semua Koleksi Lencana <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      </div>

      <!-- KARTU GRAFIK KONSUMSI 7 HARI TERAKHIR (CHART.JS) -->
      <div class="card card-custom p-4 border-0 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="fw-bold mb-0"><i class="bi bi-bar-chart-line text-primary me-2"></i>Grafik 7 Hari</h5>
          <span class="small text-muted">Target: <?= $target_harian ?> ml</span>
        </div>
        <div style="height: 220px;">
          <canvas id="weeklyHydrationChart"></canvas>
        </div>
      </div>

      <!-- KARTU SHORTCUT EDUKASI & KUIS (SDG 4) -->
      <div class="card card-custom p-4 border-0">
        <h5 class="fw-bold mb-2"><i class="bi bi-mortarboard-fill text-warning me-2"></i>Asah Wawasan Hidrasi</h5>
        <p class="text-muted small mb-3">
          Uji pemahaman Anda mengenai kebutuhan cairan organ tubuh dan raih lencana penghargaan.
        </p>
        <div class="d-grid gap-2">
          <a href="<?= BASE_URL ?>pages/kuis.php" class="btn btn-warning btn-sm text-dark fw-bold py-2 rounded-3">
            <i class="bi bi-patch-question me-1"></i> Buka Kuis Hidrasi Interaktif
          </a>
          <a href="<?= BASE_URL ?>pages/edukasi.php" class="btn btn-light btn-sm text-primary border py-2 rounded-3">
            <i class="bi bi-book me-1"></i> Baca Artikel & Tips Ginjal Sehat
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- INISIALISASI CHART.JS MINGGUAN -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  const ctx = document.getElementById('weeklyHydrationChart').getContext('2d');
  const targetVal = <?= $target_harian ?>;
  
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($chart_labels) ?>,
      datasets: [
        {
          label: 'Konsumsi (ml)',
          data: <?= json_encode($chart_data) ?>,
          backgroundColor: '#0284c7',
          borderRadius: 6
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          suggestedMax: targetVal + 500,
          ticks: {
            stepSize: 500
          }
        }
      }
    }
  });
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

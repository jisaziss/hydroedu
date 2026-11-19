<?php
// ========================================================
// FILE: pages/badges.php
// Modul 6: Galeri Lencana & Gamifikasi Hidrasi
// ========================================================
require_once __DIR__ . '/../config/koneksi.php';

$page_title = "Galeri Lencana & Pencapaian";
$logged_in = isLoggedIn();
$user = $logged_in ? getCurrentUser($conn) : null;
$user_id = $logged_in ? (int)$user['id'] : 0;

// Ambil semua daftar badge dari tabel master
$all_badges = mysqli_query($conn, "SELECT * FROM badges ORDER BY id ASC");

// Ambil badge yang sudah diraih user
$unlocked_ids = [];
$unlock_dates = [];
if ($logged_in) {
    $q_unlocked = mysqli_query($conn, "SELECT badge_id, unlocked_at FROM user_badges WHERE user_id = $user_id");
    while ($row = mysqli_fetch_assoc($q_unlocked)) {
        $unlocked_ids[] = (int)$row['badge_id'];
        $unlock_dates[$row['badge_id']] = $row['unlocked_at'];
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="text-center mb-5">
    <span class="badge bg-warning-subtle text-warning fw-bold px-3 py-1 rounded-pill mb-2">
      <i class="bi bi-trophy me-1"></i> Modul 6: Gamifikasi & Penghargaan Hidrasi
    </span>
    <h2 class="fw-bold">Koleksi Lencana Pencapaian</h2>
    <p class="text-muted mx-auto" style="max-width: 650px;">
      Bangun konsistensi dan perluas literasi hidrasi Anda. Setiap pencapaian memberikan apresiasi atas dedikasi hidup sehat Anda!
    </p>

    <?php if ($logged_in): ?>
      <div class="d-inline-flex align-items-center gap-2 p-2 px-4 bg-white rounded-pill shadow-sm border mt-2">
        <i class="bi bi-award-fill text-warning fs-4"></i>
        <span class="fw-bold text-dark">Lencana Terkumpul: <?= count($unlocked_ids) ?> / <?= mysqli_num_rows($all_badges) ?></span>
      </div>
    <?php else: ?>
      <div class="alert alert-info d-inline-block small py-2 px-4 rounded-pill mt-2">
        <i class="bi bi-info-circle me-1"></i> <a href="<?= BASE_URL ?>pages/login.php" class="fw-bold text-primary">Masuk Akun</a> untuk mulai mengoleksi lencana ini.
      </div>
    <?php endif; ?>
  </div>

  <div class="row g-4">
    <?php mysqli_data_seek($all_badges, 0); ?>
    <?php while ($badge = mysqli_fetch_assoc($all_badges)): ?>
      <?php 
        $bid = (int)$badge['id'];
        $is_unlocked = in_array($bid, $unlocked_ids);
      ?>
      <div class="col-md-6 col-lg-4">
        <div class="card card-custom h-100 p-4 border-0 text-center position-relative <?= $is_unlocked ? 'border-top border-warning border-4' : 'opacity-75' ?>" style="<?= !$is_unlocked ? 'filter: grayscale(80%);' : '' ?>">
          
          <!-- Icon Badge -->
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle mx-auto mb-3 <?= $is_unlocked ? 'bg-warning bg-opacity-20 text-warning' : 'bg-secondary bg-opacity-10 text-secondary' ?>" style="width: 76px; height: 76px;">
            <i class="bi <?= $badge['icon'] ?> display-5"></i>
          </div>

          <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($badge['nama_badge']) ?></h5>
          <p class="small text-muted mb-3 flex-grow-1"><?= htmlspecialchars($badge['deskripsi']) ?></p>

          <div class="p-2 px-3 bg-light rounded-3 border small mb-3">
            <span class="text-secondary fw-semibold">Syarat Membuka:</span><br>
            <span class="text-dark"><?= htmlspecialchars($badge['kriteria']) ?></span>
          </div>

          <div>
            <?php if ($is_unlocked): ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3 rounded-pill">
                <i class="bi bi-check-circle-fill me-1"></i> Terbuka pada <?= date('d M Y', strtotime($unlock_dates[$bid])) ?>
              </span>
            <?php else: ?>
              <span class="badge bg-light text-muted border py-2 px-3 rounded-pill">
                <i class="bi bi-lock-fill me-1"></i> Masih Terkunci
              </span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

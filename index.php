<?php
// ========================================================
// FILE: index.php
// Landing Page / Halaman Beranda Publik HydroEdu
// ========================================================
require_once __DIR__ . '/config/koneksi.php';

$page_title = "Beranda - Hydration & Education";
include_once __DIR__ . '/includes/header.php';

// Ambil 3 artikel edukasi terbaru dari database
$query_artikel = mysqli_query($conn, "SELECT id, judul, ringkasan, kategori, views, created_at FROM articles ORDER BY created_at DESC LIMIT 3");
?>

<!-- HERO SECTION -->
<section class="hero-section text-center">
  <div class="container position-relative">
    <div class="row justify-content-center">
      <div class="col-lg-9">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-white bg-opacity-25 rounded-pill text-white small fw-semibold mb-3">
          <i class="bi bi-stars"></i> Integrasi SDGs 3 (Kesehatan) & SDGs 4 (Pendidikan)
        </div>
        <h1 class="display-4 fw-bold mb-3">
          Bangun Kebiasaan Hidrasi Tepat, Dipandu oleh Edukasi Sains
        </h1>
        <p class="lead mb-4 opacity-90 mx-auto" style="max-width: 720px;">
          HydroEdu membantu Anda menghitung kebutuhan air harian personal, melacak konsumsi cairan secara real-time, dan menguji wawasan kesehatan lewat kuis interaktif.
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-3">
          <?php if (isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-light btn-lg px-4 py-2 fw-bold text-primary shadow-sm rounded-pill">
              <i class="bi bi-speedometer2 me-2"></i> Buka Dashboard Saya
            </a>
          <?php else: ?>
            <a href="<?= BASE_URL ?>pages/register.php" class="btn btn-light btn-lg px-4 py-2 fw-bold text-primary shadow-sm rounded-pill">
              <i class="bi bi-rocket-takeoff me-2"></i> Mulai Sekarang (Gratis)
            </a>
            <a href="<?= BASE_URL ?>pages/login.php" class="btn btn-outline-light btn-lg px-4 py-2 fw-bold rounded-pill">
              <i class="bi bi-box-arrow-in-right me-2"></i> Masuk Akun
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- WIDGET KALKULATOR CEPAT INTERAKTIF -->
<section class="container mt-n5" style="margin-top: -3.5rem;">
  <div class="card card-custom p-4 p-md-5 border-0 shadow">
    <div class="row align-items-center g-4">
      <div class="col-lg-5">
        <span class="badge bg-primary-subtle text-primary mb-2 fw-semibold">Uji Cepat Tanpa Login</span>
        <h3 class="fw-bold mb-2">Berapa Kebutuhan Air Anda?</h3>
        <p class="text-muted small mb-0">
          Setiap orang memiliki takaran air yang berbeda. Hitung estimasi kebutuhan cairan harian Anda berdasarkan berat badan dan intensitas aktivitas.
        </p>
      </div>
      <div class="col-lg-7">
        <div class="row g-3 align-items-end">
          <div class="col-sm-5">
            <label class="form-label small fw-semibold">Berat Badan (kg)</label>
            <div class="input-group">
              <input type="number" id="quickWeight" class="form-control" placeholder="Contoh: 60" value="60" min="20" max="200">
              <span class="input-group-text">kg</span>
            </div>
          </div>
          <div class="col-sm-4">
            <label class="form-label small fw-semibold">Aktivitas</label>
            <select id="quickActivity" class="form-select">
              <option value="ringan">Ringan (Duduk)</option>
              <option value="sedang" selected>Sedang (Aktif)</option>
              <option value="berat">Berat (Olahraga)</option>
            </select>
          </div>
          <div class="col-sm-3">
            <button type="button" class="btn btn-hydro w-100" onclick="calculateQuickWater()">
              Hitung
            </button>
          </div>
        </div>

        <!-- Hasil Perhitungan Instan -->
        <div id="quickResult" class="alert alert-info mt-3 mb-0 d-flex align-items-center justify-content-between p-3 rounded-3">
          <div>
            <span class="small text-muted d-block">Target Air Harian yang Dianjurkan:</span>
            <span class="fs-4 fw-bold text-primary" id="resultMl">1.980 ml</span>
            <span class="text-muted small ms-1" id="resultGlasses">(sekitar 8 gelas)</span>
          </div>
          <a href="<?= BASE_URL ?>pages/register.php" class="btn btn-sm btn-primary rounded-pill">
            Simpan Target Ini
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SECTION 3 PILAR KEUNGGULAN -->
<section class="container py-5 my-3">
  <div class="text-center mb-5">
    <span class="badge bg-info-subtle text-info fw-bold px-3 py-2 rounded-pill mb-2">Mengapa HydroEdu?</span>
    <h2 class="fw-bold">Solusi Hidrasi Komprehensif Berbasis Nilai SDGs</h2>
    <p class="text-muted mx-auto" style="max-width: 600px;">
      Kami merancang sistem yang tidak hanya mencatat data, tetapi juga membentuk kesadaran dan kebiasaan hidup sehat seumur hidup.
    </p>
  </div>

  <div class="row g-4">
    <div class="col-md-4">
      <div class="card card-custom h-100 p-4 border-0">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-3 mb-3" style="width: 54px; height: 54px;">
          <i class="bi bi-droplet-half fs-3"></i>
        </div>
        <h5 class="fw-bold mb-2">Daily Smart Tracker</h5>
        <span class="sdg-badge sdg-3 mb-3"><i class="bi bi-heart-pulse-fill"></i> Mendukung SDG 3</span>
        <p class="text-muted small mb-0">
          Catat konsumsi air dalam hitungan detik dengan tombol cepat 250ml dan 600ml. Pantau progres botol animasi harian Anda dengan mudah.
        </p>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-custom h-100 p-4 border-0">
        <div class="d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning rounded-3 mb-3" style="width: 54px; height: 54px;">
          <i class="bi bi-mortarboard-fill fs-3 text-warning"></i>
        </div>
        <h5 class="fw-bold mb-2">Edukasi & Kuis Interaktif</h5>
        <span class="sdg-badge sdg-4 mb-3"><i class="bi bi-book-half"></i> Mendukung SDG 4</span>
        <p class="text-muted small mb-0">
          Pahami sains di balik metabolisme cairan tubuh lewat artikel terverifikasi dan uji pengetahuan Anda untuk meraih lencana *Cendekia Hidrasi*.
        </p>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card card-custom h-100 p-4 border-0">
        <div class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-3 mb-3" style="width: 54px; height: 54px;">
          <i class="bi bi-trophy-fill fs-3 text-success"></i>
        </div>
        <h5 class="fw-bold mb-2">Gamifikasi & Komunitas</h5>
        <span class="sdg-badge sdg-3 mb-3"><i class="bi bi-fire"></i> Habit Formation</span>
        <p class="text-muted small mb-0">
          Pertahankan streak minum berturut-turut, buka berbagai pencapaian eksklusif, dan bagikan motivasi sehat bersama sesama pengguna di forum.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- SECTION PREVIEW ARTIKEL EDUKASI TERBARU -->
<section class="bg-white py-5 border-top border-bottom">
  <div class="container py-2">
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
      <div>
        <span class="badge bg-warning-subtle text-warning fw-bold px-3 py-1 rounded-pill mb-2">Pusat Literasi Hidrasi</span>
        <h3 class="fw-bold mb-1">Artikel Edukasi Pilihan</h3>
        <p class="text-muted small mb-0">Wawasan medis praktis untuk mendukung performa belajar dan stamina fisik harian Anda.</p>
      </div>
      <a href="<?= BASE_URL ?>pages/edukasi.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 mt-3 mt-md-0">
        Lihat Semua Artikel <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

    <div class="row g-4">
      <?php if ($query_artikel && mysqli_num_rows($query_artikel) > 0): ?>
        <?php while ($art = mysqli_fetch_assoc($query_artikel)): ?>
          <div class="col-lg-4 col-md-6">
            <div class="card card-custom h-100 border-0 p-3">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-info-subtle text-info fw-semibold"><?= htmlspecialchars($art['kategori']) ?></span>
                <span class="text-muted small"><i class="bi bi-eye me-1"></i><?= $art['views'] ?> views</span>
              </div>
              <h5 class="fw-bold text-dark mb-2">
                <?= htmlspecialchars($art['judul']) ?>
              </h5>
              <p class="text-muted small mb-3 flex-grow-1">
                <?= htmlspecialchars(substr($art['ringkasan'], 0, 110)) ?>...
              </p>
              <a href="<?= BASE_URL ?>pages/edukasi.php?id=<?= $art['id'] ?>" class="text-primary fw-semibold small text-decoration-none">
                Baca Selengkapnya <i class="bi bi-chevron-right"></i>
              </a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12 text-center py-4 text-muted">
          Belum ada artikel yang ditambahkan.
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- JAVASCRIPT LOGIC UNTUK QUICK CALCULATOR -->
<script>
function calculateQuickWater() {
  const weight = parseFloat(document.getElementById('quickWeight').value) || 60;
  const activity = document.getElementById('quickActivity').value;
  
  let target = weight * 33; // 33ml per kg
  if (activity === 'berat') {
    target += 600;
  } else if (activity === 'ringan') {
    target -= 200;
  }
  
  target = Math.max(1500, Math.round(target));
  const glasses = Math.round(target / 250);

  document.getElementById('resultMl').innerText = target.toLocaleString('id-ID') + ' ml';
  document.getElementById('resultGlasses').innerText = '(sekitar ' + glasses + ' gelas)';
}
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>

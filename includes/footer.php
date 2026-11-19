<?php
// ========================================================
// FILE: includes/footer.php
// Footer Global & Script Bootstrap
// ========================================================
?>
</main>

<footer class="footer-hydro">
  <div class="container">
    <div class="row g-4 mb-4">
      <!-- Kolom 1: Profil Proyek -->
      <div class="col-lg-4 col-md-6">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-droplet-fill fs-3 text-info"></i>
          <span class="fs-4 fw-bold text-white">HydroEdu</span>
        </div>
        <p class="small text-secondary mb-3">
          <strong>Hydration & Education</strong> adalah platform integratif yang menggabungkan pembiasaan konsumsi air harian berbasis sains dan edukasi kesehatan untuk mendukung pencapaian SDGs.
        </p>
        <div class="d-flex gap-2">
          <span class="sdg-badge sdg-3"><i class="bi bi-heart-pulse-fill"></i> SDG 3: Kesehatan</span>
          <span class="sdg-badge sdg-4"><i class="bi bi-mortarboard-fill"></i> SDG 4: Pendidikan</span>
        </div>
      </div>

      <!-- Kolom 2: Navigasi Cepat -->
      <div class="col-lg-4 col-md-6">
        <h5 class="mb-3">Navigasi Fitur</h5>
        <ul class="list-unstyled small">
          <li class="mb-2"><a href="<?= BASE_URL ?>pages/calculator.php"><i class="bi bi-chevron-right text-info me-1"></i> Smart Hydration Calculator</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>pages/edukasi.php"><i class="bi bi-chevron-right text-info me-1"></i> Ensiklopedia & Artikel Kesehatan</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>pages/kuis.php"><i class="bi bi-chevron-right text-info me-1"></i> Kuis Interaktif & Lencana</a></li>
          <li class="mb-2"><a href="<?= BASE_URL ?>pages/forum.php"><i class="bi bi-chevron-right text-info me-1"></i> HydroCommunity Forum</a></li>
          <li><a href="<?= BASE_URL ?>dashboard.php"><i class="bi bi-chevron-right text-info me-1"></i> Dashboard Pelacak Harian</a></li>
        </ul>
      </div>

      <!-- Kolom 3: Info Akademik & Responsi -->
      <div class="col-lg-4 col-md-12">
        <h5 class="mb-3">Informasi Proyek</h5>
        <p class="small text-secondary mb-2">
          <strong>Responsi Praktikum Pemrograman Web I</strong><br>
          Jurusan Informatika, Fakultas Teknik<br>
          Universitas Jenderal Soedirman (UNSOED) - 2026
        </p>
        <div class="p-3 rounded-3 bg-dark border border-secondary border-opacity-25 mt-3">
          <div class="small text-light"><i class="bi bi-people-fill text-info me-2"></i>Dikerjakan oleh Kelompok 7</div>
          <div class="small text-secondary mt-1">Implementasi 9 Modul Logika Bisnis Terintegrasi</div>
        </div>
      </div>
    </div>

    <hr class="border-secondary opacity-25">

    <div class="d-flex flex-wrap justify-content-between align-items-center py-2 small text-secondary">
      <div>&copy; <?= date('Y') ?> <strong>HydroEdu Team</strong>. All rights reserved.</div>
      <div>Designed for Good Health & Quality Education</div>
    </div>
  </div>
</footer>

<!-- Bootstrap 5.3 Bundle JS (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

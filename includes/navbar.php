<?php
// ========================================================
// FILE: includes/navbar.php
// Navigasi Utama Responsif HydroEdu
// ========================================================
$current_page = basename($_SERVER['PHP_SELF']);
$logged_in = isLoggedIn();
$user = $logged_in ? getCurrentUser($conn) : null;
?>

<nav class="navbar navbar-expand-lg navbar-hydro sticky-top">
  <div class="container">
    <!-- Brand Logo -->
    <a class="navbar-brand navbar-brand-hydro" href="<?= BASE_URL ?>">
      <i class="bi bi-droplet-fill"></i>
      <span>HydroEdu</span>
    </a>

    <!-- Hamburger Toggle Mobile -->
    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#hydroNavbar" aria-controls="hydroNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Nav Items -->
    <div class="collapse navbar-collapse" id="hydroNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>">
            <i class="bi bi-house-door me-1"></i> Beranda
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'edukasi.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/edukasi.php">
            <i class="bi bi-book me-1"></i> Edukasi
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'calculator.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/calculator.php">
            <i class="bi bi-calculator me-1"></i> Kalkulator Air
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'scanner.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/scanner.php">
            <i class="bi bi-camera me-1"></i> AI Scanner
          </a>
        </li>
        <li class="nav-item">
>>>>>>> a41cac0 (kelompok 7 well)
          <a class="nav-link <?= $current_page == 'kuis.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/kuis.php">
            <i class="bi bi-patch-question me-1"></i> Kuis Hidrasi
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $current_page == 'forum.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>pages/forum.php">
            <i class="bi bi-chat-dots me-1"></i> Komunitas
          </a>
        </li>
      </ul>

      <!-- User Action / Profile -->
      <div class="d-flex align-items-center gap-2">
        <?php if ($logged_in && $user): ?>
          <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 me-1">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
          </a>

          <!-- User Dropdown Menu -->
          <div class="dropdown">
            <button class="btn btn-light rounded-pill dropdown-toggle d-flex align-items-center gap-2 border px-3 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle fs-5 text-primary"></i>
              <span class="fw-semibold text-truncate" style="max-width: 120px;"><?= htmlspecialchars($user['nama']) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2">
              <li class="px-3 py-2 border-bottom">
                <div class="small text-muted">Masuk sebagai:</div>
                <div class="fw-bold"><?= htmlspecialchars($user['email']) ?></div>
                <span class="badge <?= $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary' ?> mt-1">
                  <?= strtoupper($user['role']) ?>
                </span>
              </li>
              <li>
                <a class="dropdown-item py-2" href="<?= BASE_URL ?>dashboard.php">
                  <i class="bi bi-droplet me-2 text-primary"></i> Tracker & Catat Minum
                </a>
              </li>
              <li>
                <a class="dropdown-item py-2" href="<?= BASE_URL ?>pages/badges.php">
                  <i class="bi bi-trophy me-2 text-warning"></i> Lencana Saya
                </a>
              </li>
              <?php if ($user['role'] === 'admin'): ?>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a class="dropdown-item py-2 text-danger fw-semibold" href="<?= BASE_URL ?>admin/index.php">
                    <i class="bi bi-shield-lock me-2"></i> Panel Admin (CMS)
                  </a>
                </li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item py-2 text-muted" href="<?= BASE_URL ?>pages/logout.php">
                  <i class="bi bi-box-arrow-right me-2"></i> Keluar
                </a>
              </li>
            </ul>
          </div>

        <?php else: ?>
          <a href="<?= BASE_URL ?>pages/login.php" class="btn btn-hydro-outline btn-sm px-3">
            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
          </a>
          <a href="<?= BASE_URL ?>pages/register.php" class="btn btn-hydro btn-sm px-3">
            Daftar Akun
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

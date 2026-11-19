<?php
// ========================================================
// FILE: pages/register.php
// Halaman Pendaftaran Akun Pengguna Baru
// ========================================================
require_once __DIR__ . '/../config/koneksi.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim(mysqli_real_escape_string($conn, $_POST['nama']));
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];
    $bb = (float)$_POST['berat_badan'];
    $aktivitas = mysqli_real_escape_string($conn, $_POST['tingkat_aktivitas'] ?? 'sedang');
    $gender = mysqli_real_escape_string($conn, $_POST['jenis_kelamin'] ?? 'L');

    if (empty($nama) || empty($email) || empty($password) || $bb <= 0) {
        $error = "Semua field wajib diisi dengan benar!";
    } else {
        // Cek apakah email sudah terdaftar
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Email sudah terdaftar. Silakan gunakan email lain atau masuk.";
        } else {
            // Hitung target air otomatis berdasarkan BB & aktivitas
            $target = hitungTargetAir($bb, $aktivitas);
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            $insert = mysqli_query($conn, "INSERT INTO users (nama, email, password, role, jenis_kelamin, berat_badan, tingkat_aktivitas, target_harian_ml) 
                                           VALUES ('$nama', '$email', '$hashed_password', 'user', '$gender', $bb, '$aktivitas', $target)");

            if ($insert) {
                $new_id = mysqli_insert_id($conn);
                // Buat setting reminder default
                mysqli_query($conn, "INSERT INTO hydration_reminders (user_id, interval_menit, jam_mulai, jam_selesai, status_aktif) 
                                     VALUES ($new_id, 60, '07:00:00', '21:00:00', 1)");

                // Auto login
                $_SESSION['user_id'] = $new_id;
                $_SESSION['nama'] = $nama;
                $_SESSION['role'] = 'user';

                header("Location: " . BASE_URL . "dashboard.php?welcome=1");
                exit;
            } else {
                $error = "Gagal mendaftar: " . mysqli_error($conn);
            }
        }
    }
}

$page_title = "Daftar Akun Baru";
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card card-custom p-4 p-md-5 border-0 shadow">
        <div class="text-center mb-4">
          <div class="d-inline-flex p-3 bg-primary-subtle text-primary rounded-circle mb-2">
            <i class="bi bi-person-plus-fill fs-2"></i>
          </div>
          <h3 class="fw-bold">Buat Akun HydroEdu</h3>
          <p class="text-muted small">Mulai personalisasi target hidrasi dan edukasi Anda</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger small py-2 rounded-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" placeholder="Nama Anda" required value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Alamat Email</label>
            <input type="email" name="email" class="form-control" placeholder="nama@email.com" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Berat Badan</label>
              <div class="input-group">
                <input type="number" name="berat_badan" class="form-control" placeholder="60" min="30" max="250" required value="<?= isset($_POST['berat_badan']) ? htmlspecialchars($_POST['berat_badan']) : '60' ?>">
                <span class="input-group-text">kg</span>
              </div>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Jenis Kelamin</label>
              <select name="jenis_kelamin" class="form-select">
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label small fw-semibold">Tingkat Aktivitas Fisik</label>
            <select name="tingkat_aktivitas" class="form-select">
              <option value="ringan">Ringan (Banyak duduk / minim bergerak)</option>
              <option value="sedang" selected>Sedang (Kuliah / aktivitas normal)</option>
              <option value="berat">Berat (Olahraga rutin / kerja fisik)</option>
            </select>
            <div class="form-text small" style="font-size: 0.75rem;">
              *Target air harian akan otomatis dihitung oleh sistem.
            </div>
          </div>

          <button type="submit" class="btn btn-hydro w-100 py-2">
            <i class="bi bi-check2-circle me-1"></i> Selesaikan Pendaftaran
          </button>
        </form>

        <div class="text-center mt-4 small">
          Sudah punya akun? <a href="<?= BASE_URL ?>pages/login.php" class="text-primary fw-semibold">Masuk di sini</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

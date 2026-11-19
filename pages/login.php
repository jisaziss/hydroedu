<?php
// ========================================================
// FILE: pages/login.php
// Halaman Masuk Akun HydroEdu
// ========================================================
require_once __DIR__ . '/../config/koneksi.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "dashboard.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email dan password wajib diisi!";
    } else {
        $query = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email' LIMIT 1");
        if ($query && mysqli_num_rows($query) > 0) {
            $user = mysqli_fetch_assoc($query);
            // Verifikasi password hash, fallback plain password, atau auto-heal akun demo responsi
            $is_demo_pass = ($password === 'password123' && in_array($user['email'], ['admin@hydroedu.id', 'budi@gmail.com', 'siti@gmail.com']));
            
            if (password_verify($password, $user['password']) || $password === $user['password'] || $is_demo_pass) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];

                // Jika akun demo, otomatis perbarui ke hash bcrypt resmi agar selalu valid
                if ($is_demo_pass) {
                    $new_hash = password_hash('password123', PASSWORD_BCRYPT);
                    mysqli_query($conn, "UPDATE users SET password = '$new_hash' WHERE id = " . (int)$user['id']);
                }

                header("Location: " . BASE_URL . "dashboard.php");
                exit;
            } else {
                $error = "Password yang Anda masukkan salah!";
            }
        } else {
            $error = "Akun dengan email tersebut tidak ditemukan!";
        }
    }
}

$page_title = "Masuk Akun";
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card card-custom p-4 p-md-5 border-0 shadow">
        <div class="text-center mb-4">
          <div class="d-inline-flex p-3 bg-primary-subtle text-primary rounded-circle mb-2">
            <i class="bi bi-droplet-fill fs-2"></i>
          </div>
          <h3 class="fw-bold">Selamat Datang</h3>
          <p class="text-muted small">Masuk untuk memantau hidrasi dan belajar bersama HydroEdu</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger small py-2 rounded-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= $error ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Email Pengguna</label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
              <input type="email" name="email" class="form-control" placeholder="nama@email.com" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Password</label>
            <div class="input-group">
              <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
              <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
          </div>

          <button type="submit" class="btn btn-hydro w-100 py-2 mt-2">
            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
          </button>
        </form>

        <div class="mt-4 p-3 bg-light rounded-3 border small">
          <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle text-primary me-1"></i> Akun Pengujian Responsi:</div>
          <div class="text-muted"><strong>Admin:</strong> <code>admin@hydroedu.id</code> (pass: <code>password123</code>)</div>
          <div class="text-muted"><strong>User:</strong> <code>budi@gmail.com</code> (pass: <code>password123</code>)</div>
        </div>

        <div class="text-center mt-4 small">
          Belum punya akun? <a href="<?= BASE_URL ?>pages/register.php" class="text-primary fw-semibold">Daftar sekarang</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

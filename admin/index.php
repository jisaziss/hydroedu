<?php
// ========================================================
// FILE: admin/index.php
// Modul 9: Admin Control Panel (CMS Kelola Konten & Data)
// ========================================================
require_once __DIR__ . '/../config/koneksi.php';

// Cek hak akses admin
if (!isLoggedIn() || !isAdmin()) {
    die("<div style='font-family: Arial; padding: 30px; text-align: center;'>
            <h2 style='color: #dc2626;'>⛔ Akses Ditolak!</h2>
            <p>Halaman ini hanya dapat diakses oleh akun dengan peran <strong>Admin</strong>.</p>
            <p><a href='" . BASE_URL . "pages/login.php'>Silakan Masuk sebagai Admin</a> atau <a href='" . BASE_URL . "'>Kembali ke Beranda</a></p>
         </div>");
}

$admin = getCurrentUser($conn);
$msg_success = "";
$msg_error = "";

// --------------------------------------------------------
// HANDLER: TAMBAH ARTIKEL BARU
// --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_article') {
    $judul = trim(mysqli_real_escape_string($conn, $_POST['judul']));
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);
    $ringkasan = trim(mysqli_real_escape_string($conn, $_POST['ringkasan']));
    $konten = trim(mysqli_real_escape_string($conn, $_POST['konten']));
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul))) . '-' . time();
    $author_id = (int)$admin['id'];

    if (!empty($judul) && !empty($konten)) {
        $ins = mysqli_query($conn, "INSERT INTO articles (author_id, judul, slug, kategori, ringkasan, konten) 
                                   VALUES ($author_id, '$judul', '$slug', '$kategori', '$ringkasan', '$konten')");
        if ($ins) {
            $msg_success = "Artikel edukasi berhasil ditambahkan!";
        } else {
            $msg_error = "Gagal menambah artikel: " . mysqli_error($conn);
        }
    }
}

// --------------------------------------------------------
// HANDLER: HAPUS ARTIKEL
// --------------------------------------------------------
if (isset($_GET['del_art'])) {
    $del_id = (int)$_GET['del_art'];
    mysqli_query($conn, "DELETE FROM articles WHERE id = $del_id");
    header("Location: " . BASE_URL . "admin/index.php?msg=art_deleted");
    exit;
}

// --------------------------------------------------------
// HANDLER: TAMBAH SOAL KUIS BARU
// --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_quiz') {
    $pertanyaan = trim(mysqli_real_escape_string($conn, $_POST['pertanyaan']));
    $opsi_a = trim(mysqli_real_escape_string($conn, $_POST['opsi_a']));
    $opsi_b = trim(mysqli_real_escape_string($conn, $_POST['opsi_b']));
    $opsi_c = trim(mysqli_real_escape_string($conn, $_POST['opsi_c']));
    $opsi_d = trim(mysqli_real_escape_string($conn, $_POST['opsi_d']));
    $kunci = mysqli_real_escape_string($conn, $_POST['kunci_jawaban']);
    $penjelasan = trim(mysqli_real_escape_string($conn, $_POST['penjelasan']));

    if (!empty($pertanyaan) && !empty($opsi_a) && !empty($kunci)) {
        $ins_q = mysqli_query($conn, "INSERT INTO quiz_questions (pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, kunci_jawaban, penjelasan) 
                                     VALUES ('$pertanyaan', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$kunci', '$penjelasan')");
        if ($ins_q) {
            $msg_success = "Soal kuis baru berhasil ditambahkan ke bank soal!";
        } else {
            $msg_error = "Gagal menambah soal: " . mysqli_error($conn);
        }
    }
}

// --------------------------------------------------------
// HANDLER: HAPUS SOAL KUIS
// --------------------------------------------------------
if (isset($_GET['del_quiz'])) {
    $del_qid = (int)$_GET['del_quiz'];
    mysqli_query($conn, "DELETE FROM quiz_questions WHERE id = $del_qid");
    header("Location: " . BASE_URL . "admin/index.php?msg=quiz_deleted");
    exit;
}

// Hitung Statistik
$stat_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))['total'];
$stat_articles = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM articles"))['total'];
$stat_quiz = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM quiz_questions"))['total'];
$stat_logs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM hydration_logs"))['total'];

// Ambil list artikel & kuis
$all_articles = mysqli_query($conn, "SELECT * FROM articles ORDER BY created_at DESC");
$all_quizzes = mysqli_query($conn, "SELECT * FROM quiz_questions ORDER BY id ASC");

$page_title = "Admin Control Panel (CMS)";
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
  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'art_deleted'): ?>
    <div class="alert alert-info alert-dismissible fade show rounded-3 small" role="alert">
      <i class="bi bi-trash me-2"></i> Artikel telah berhasil dihapus.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'quiz_deleted'): ?>
    <div class="alert alert-info alert-dismissible fade show rounded-3 small" role="alert">
      <i class="bi bi-trash me-2"></i> Soal kuis berhasil dihapus dari bank data.
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <span class="badge bg-danger text-white px-3 py-1 rounded-pill mb-1">
        <i class="bi bi-shield-lock me-1"></i> Modul 9: CMS Admin
      </span>
      <h2 class="fw-bold mb-0">Admin Control Panel</h2>
      <p class="text-muted small mb-0">Kelola publikasi materi edukasi, bank soal kuis, dan pemantauan sistem.</p>
    </div>
    <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill">
      <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
    </a>
  </div>

  <!-- STATISTIK RINGKAS -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card card-custom p-3 border-0 border-start border-primary border-4">
        <div class="text-muted small fw-semibold">Total Pengguna</div>
        <div class="fs-3 fw-bold text-dark"><?= $stat_users ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-custom p-3 border-0 border-start border-warning border-4">
        <div class="text-muted small fw-semibold">Artikel Edukasi</div>
        <div class="fs-3 fw-bold text-dark"><?= $stat_articles ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-custom p-3 border-0 border-start border-success border-4">
        <div class="text-muted small fw-semibold">Bank Soal Kuis</div>
        <div class="fs-3 fw-bold text-dark"><?= $stat_quiz ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-custom p-3 border-0 border-start border-info border-4">
        <div class="text-muted small fw-semibold">Log Minum Tercatat</div>
        <div class="fs-3 fw-bold text-dark"><?= $stat_logs ?></div>
      </div>
    </div>
  </div>

  <!-- TAB KELOLA KONTEN -->
  <ul class="nav nav-tabs mb-4" id="adminTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active fw-semibold" id="art-tab" data-bs-toggle="tab" data-bs-target="#tab-articles" type="button" role="tab">
        <i class="bi bi-journal-richtext me-1"></i> Kelola Artikel Edukasi
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link fw-semibold" id="quiz-tab" data-bs-toggle="tab" data-bs-target="#tab-quizzes" type="button" role="tab">
        <i class="bi bi-question-diamond me-1"></i> Kelola Bank Soal Kuis
      </button>
    </li>
  </ul>

  <div class="tab-content" id="adminTabContent">
    <!-- TAB 1: KELOLA ARTIKEL -->
    <div class="tab-pane fade show active" id="tab-articles" role="tabpanel">
      <div class="card card-custom p-4 border-0 mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle text-primary me-2"></i>Tambah Artikel Edukasi Baru</h5>
        <form method="POST" action="">
          <input type="hidden" name="action" value="add_article">
          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label small fw-semibold">Judul Artikel</label>
              <input type="text" name="judul" class="form-control" placeholder="Contoh: Manfaat Air bagi Metabolisme Sel" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Kategori</label>
              <select name="kategori" class="form-select">
                <option value="Kesehatan Ginjal">Kesehatan Ginjal</option>
                <option value="Performa Otak">Performa Otak</option>
                <option value="Tips Hidrasi">Tips Hidrasi</option>
                <option value="Mitos & Fakta">Mitos & Fakta</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Ringkasan Singkat (Highlight)</label>
            <input type="text" name="ringkasan" class="form-control" placeholder="Ringkasan 1-2 kalimat untuk preview card..." required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Konten Lengkap</label>
            <textarea name="konten" class="form-control" rows="5" placeholder="Tuliskan isi materi edukasi lengkap di sini..." required></textarea>
          </div>
          <button type="submit" class="btn btn-hydro btn-sm px-4">
            <i class="bi bi-cloud-upload me-1"></i> Terbitkan Artikel
          </button>
        </form>
      </div>

      <!-- TABEL DAFTAR ARTIKEL -->
      <div class="card card-custom p-4 border-0">
        <h5 class="fw-bold mb-3">Daftar Artikel Tersedia</h5>
        <div class="table-responsive">
          <table class="table table-hover align-middle small mb-0">
            <thead class="table-light">
              <tr>
                <th>Judul Artikel</th>
                <th>Kategori</th>
                <th>Views</th>
                <th>Tanggal Rilis</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($art = mysqli_fetch_assoc($all_articles)): ?>
                <tr>
                  <td class="fw-bold text-dark"><?= htmlspecialchars($art['judul']) ?></td>
                  <td><span class="badge bg-info-subtle text-info"><?= $art['kategori'] ?></span></td>
                  <td><?= $art['views'] ?></td>
                  <td class="text-muted"><?= date('d M Y', strtotime($art['created_at'])) ?></td>
                  <td class="text-end">
                    <a href="<?= BASE_URL ?>pages/edukasi.php?id=<?= $art['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 me-1">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="<?= BASE_URL ?>admin/index.php?del_art=<?= $art['id'] ?>" onclick="return confirm('Hapus artikel ini?')" class="btn btn-sm btn-outline-danger py-0 px-2">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: KELOLA SOAL KUIS -->
    <div class="tab-pane fade" id="tab-quizzes" role="tabpanel">
      <div class="card card-custom p-4 border-0 mb-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle text-primary me-2"></i>Tambah Soal Kuis Baru</h5>
        <form method="POST" action="">
          <input type="hidden" name="action" value="add_quiz">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Pertanyaan Kuis</label>
            <input type="text" name="pertanyaan" class="form-control" placeholder="Contoh: Berapa liter kapasitas rata-rata lambung manusia?" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Opsi A</label>
              <input type="text" name="opsi_a" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Opsi B</label>
              <input type="text" name="opsi_b" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Opsi C</label>
              <input type="text" name="opsi_c" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Opsi D</label>
              <input type="text" name="opsi_d" class="form-control form-control-sm" required>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Kunci Jawaban</label>
              <select name="kunci_jawaban" class="form-select form-select-sm">
                <option value="a">A</option>
                <option value="b">B</option>
                <option value="c">C</option>
                <option value="d">D</option>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-semibold">Pembahasan Ilmiah (Feedback Edukasi)</label>
              <input type="text" name="penjelasan" class="form-control form-control-sm" placeholder="Penjelasan kenapa opsi ini benar..." required>
            </div>
          </div>
          <button type="submit" class="btn btn-warning btn-sm text-dark fw-bold px-4">
            <i class="bi bi-save me-1"></i> Simpan ke Bank Soal
          </button>
        </form>
      </div>

      <!-- TABEL DAFTAR KUIS -->
      <div class="card card-custom p-4 border-0">
        <h5 class="fw-bold mb-3">Daftar Soal Kuis Aktif</h5>
        <div class="table-responsive">
          <table class="table table-hover align-middle small mb-0">
            <thead class="table-light">
              <tr>
                <th>Pertanyaan</th>
                <th>Kunci</th>
                <th>Pembahasan Singkat</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($qz = mysqli_fetch_assoc($all_quizzes)): ?>
                <tr>
                  <td class="fw-semibold text-dark"><?= htmlspecialchars($qz['pertanyaan']) ?></td>
                  <td><span class="badge bg-success"><?= strtoupper($qz['kunci_jawaban']) ?></span></td>
                  <td class="text-muted"><?= htmlspecialchars(substr($qz['penjelasan'], 0, 80)) ?>...</td>
                  <td class="text-end">
                    <a href="<?= BASE_URL ?>admin/index.php?del_quiz=<?= $qz['id'] ?>" onclick="return confirm('Hapus soal ini?')" class="btn btn-sm btn-outline-danger py-0 px-2">
                      <i class="bi bi-trash"></i>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

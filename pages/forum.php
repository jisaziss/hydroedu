<?php
// ========================================================
// FILE: pages/forum.php
// Modul 8: HydroCommunity - Forum Diskusi & Berbagi Tips
// ========================================================
require_once __DIR__ . '/../config/koneksi.php';

$page_title = "HydroCommunity - Forum Diskusi";
$logged_in = isLoggedIn();
$user = $logged_in ? getCurrentUser($conn) : null;
$user_id = $logged_in ? (int)$user['id'] : 0;

$msg_success = "";
$msg_error = "";

// --------------------------------------------------------
// HANDLER: TAMBAH TOPIK DISKUSI BARU
// --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_post') {
    if (!$logged_in) {
        header("Location: " . BASE_URL . "pages/login.php");
        exit;
    }

    $judul = trim(mysqli_real_escape_string($conn, $_POST['judul']));
    $konten = trim(mysqli_real_escape_string($conn, $_POST['konten']));
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori']);

    if (!empty($judul) && !empty($konten)) {
        $insert = mysqli_query($conn, "INSERT INTO forum_posts (user_id, judul, konten, kategori) 
                                       VALUES ($user_id, '$judul', '$konten', '$kategori')");
        if ($insert) {
            $msg_success = "Topik diskusi Anda berhasil dipublikasikan!";
            
            // Otomatisasi Badge 5: Sahabat Komunitas (community_star)
            mysqli_query($conn, "INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES ($user_id, 5)");
        } else {
            $msg_error = "Gagal memposting diskusi: " . mysqli_error($conn);
        }
    } else {
        $msg_error = "Judul dan isi diskusi tidak boleh kosong!";
    }
}

// --------------------------------------------------------
// HANDLER: TAMBAH KOMENTAR PADA POST
// --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    if (!$logged_in) {
        header("Location: " . BASE_URL . "pages/login.php");
        exit;
    }

    $post_id = (int)$_POST['post_id'];
    $komentar = trim(mysqli_real_escape_string($conn, $_POST['komentar']));

    if (!empty($komentar) && $post_id > 0) {
        $ins_com = mysqli_query($conn, "INSERT INTO forum_comments (post_id, user_id, komentar) 
                                        VALUES ($post_id, $user_id, '$komentar')");
        if ($ins_com) {
            $msg_success = "Komentar berhasil ditambahkan!";
        }
    }
}

// Ambil postingan forum beserta jumlah komentar
$posts_query = mysqli_query($conn, "
    SELECT p.*, u.nama AS author_name, u.role,
           (SELECT COUNT(*) FROM forum_comments c WHERE c.post_id = p.id) AS total_comments
    FROM forum_posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
");

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

  <div class="row align-items-center mb-4">
    <div class="col-md-8">
      <span class="badge bg-info-subtle text-info fw-bold px-3 py-1 rounded-pill mb-2">
        <i class="bi bi-chat-heart me-1"></i> Modul 8: HydroCommunity
      </span>
      <h2 class="fw-bold mb-1">Forum Komunitas & Tips Hidrasi</h2>
      <p class="text-muted mb-0">Tempat saling berbagi kebiasaan minum sehat, motivasi hidup aktif, dan tanya jawab seputar kesehatan.</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
      <?php if ($logged_in): ?>
        <button type="button" class="btn btn-hydro rounded-pill" data-bs-toggle="modal" data-bs-target="#modalNewPost">
          <i class="bi bi-pencil-square me-1"></i> Buat Diskusi Baru
        </button>
      <?php else: ?>
        <a href="<?= BASE_URL ?>pages/login.php" class="btn btn-outline-primary rounded-pill">
          <i class="bi bi-box-arrow-in-right me-1"></i> Masuk untuk Berdiskusi
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- FEED POSTINGAN FORUM -->
  <div class="row justify-content-center">
    <div class="col-lg-9">
      <?php if ($posts_query && mysqli_num_rows($posts_query) > 0): ?>
        <?php while ($post = mysqli_fetch_assoc($posts_query)): ?>
          <?php $pid = (int)$post['id']; ?>
          <div class="card card-custom p-4 border-0 mb-4">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div class="d-flex align-items-center gap-2">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle" style="width: 42px; height: 42px;">
                  <i class="bi bi-person-fill fs-5"></i>
                </div>
                <div>
                  <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($post['author_name']) ?></h6>
                  <span class="text-muted small"><?= date('d M Y, H:i', strtotime($post['created_at'])) ?> WIB</span>
                </div>
              </div>
              <span class="badge bg-light text-primary border">
                <?= htmlspecialchars($post['kategori']) ?>
              </span>
            </div>

            <h5 class="fw-bold my-2 text-dark"><?= htmlspecialchars($post['judul']) ?></h5>
            <p class="text-secondary small lh-base mb-3"><?= nl2br(htmlspecialchars($post['konten'])) ?></p>

            <div class="pt-3 border-top d-flex justify-content-between align-items-center">
              <span class="small text-muted">
                <i class="bi bi-chat-dots-fill text-info me-1"></i> <?= $post['total_comments'] ?> Komentar
              </span>
              <button class="btn btn-sm btn-link text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#comments-<?= $pid ?>">
                Lihat Komentar & Balas <i class="bi bi-chevron-down ms-1"></i>
              </button>
            </div>

            <!-- AREA KOMENTAR COLLAPSIBLE -->
            <div class="collapse mt-3 pt-3 border-top" id="comments-<?= $pid ?>">
              <?php
                $q_com = mysqli_query($conn, "SELECT c.*, u.nama FROM forum_comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = $pid ORDER BY c.created_at ASC");
              ?>
              
              <div class="d-flex flex-column gap-2 mb-3">
                <?php if ($q_com && mysqli_num_rows($q_com) > 0): ?>
                  <?php while ($comment = mysqli_fetch_assoc($q_com)): ?>
                    <div class="p-3 bg-light rounded-3 small">
                      <div class="d-flex justify-content-between mb-1">
                        <strong class="text-primary"><?= htmlspecialchars($comment['nama']) ?></strong>
                        <span class="text-muted" style="font-size: 0.75rem;"><?= date('d M, H:i', strtotime($comment['created_at'])) ?></span>
                      </div>
                      <div class="text-dark"><?= nl2br(htmlspecialchars($comment['komentar'])) ?></div>
                    </div>
                  <?php endwhile; ?>
                <?php else: ?>
                  <div class="text-muted small fst-italic">Belum ada tanggapan. Jadilah yang pertama berkomentar!</div>
                <?php endif; ?>
              </div>

              <!-- FORM TAMBAH KOMENTAR -->
              <?php if ($logged_in): ?>
                <form method="POST" action="" class="input-group input-group-sm">
                  <input type="hidden" name="action" value="add_comment">
                  <input type="hidden" name="post_id" value="<?= $pid ?>">
                  <input type="text" name="komentar" class="form-control" placeholder="Tulis tanggapan Anda..." required>
                  <button class="btn btn-primary" type="submit">
                    <i class="bi bi-send-fill"></i>
                  </button>
                </form>
              <?php else: ?>
                <div class="small text-muted text-center py-2 bg-light rounded">
                  <a href="<?= BASE_URL ?>pages/login.php" class="text-primary fw-semibold">Masuk</a> untuk menulis komentar.
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="text-center py-5">
          <i class="bi bi-chat-square-dots fs-1 text-muted d-block mb-2"></i>
          <h5 class="text-muted">Belum ada topik diskusi.</h5>
          <p class="small text-muted">Mulai topik pertama dan bagikan pengalaman hidrasi Anda!</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- MODAL BUAT TOPIK DISKUSI BARU -->
<?php if ($logged_in): ?>
<div class="modal fade" id="modalNewPost" tabindex="-1" aria-labelledby="modalNewPostLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalNewPostLabel"><i class="bi bi-pencil-fill text-primary me-2"></i>Buat Diskusi Baru</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <input type="hidden" name="action" value="new_post">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Kategori Diskusi</label>
            <select name="kategori" class="form-select">
              <option value="Tips Harian">Tips Harian</option>
              <option value="Pertanyaan">Pertanyaan & Diskusi</option>
              <option value="Cerita Sehat">Cerita Sehat & Pengalaman</option>
              <option value="Tantangan">Tantangan Hidrasi</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Judul Diskusi</label>
            <input type="text" name="judul" class="form-control" placeholder="Contoh: Tips minum air saat puasa atau olahraga" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Isi Pesan / Pengalaman</label>
            <textarea name="konten" class="form-control" rows="4" placeholder="Ceritakan detail tips atau pertanyaan Anda di sini..." required></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-hydro px-4">Publikasikan</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

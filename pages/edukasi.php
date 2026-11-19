<?php
// ========================================================
// FILE: pages/edukasi.php
// Modul 4: Pusat Edukasi & Artikel Hidrasi (SDGs 4 & SDGs 3)
// ========================================================
require_once __DIR__ . '/../config/koneksi.php';

$page_title = "Pusat Edukasi & Artikel Hidrasi";

// Cek apakah melihat detail artikel
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$detail_article = null;

if ($article_id > 0) {
    // Tambah jumlah view
    mysqli_query($conn, "UPDATE articles SET views = views + 1 WHERE id = $article_id");
    
    // Ambil data artikel lengkap
    $q_detail = mysqli_query($conn, "SELECT a.*, u.nama AS author_name FROM articles a JOIN users u ON a.author_id = u.id WHERE a.id = $article_id LIMIT 1");
    if ($q_detail && mysqli_num_rows($q_detail) > 0) {
        $detail_article = mysqli_fetch_assoc($q_detail);
        $page_title = $detail_article['judul'];
    }
}

// Filter kategori & pencarian jika halaman list
$kategori_filter = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : '';
$search_query = isset($_GET['q']) ? mysqli_real_escape_string($conn, trim($_GET['q'])) : '';

$where_clauses = [];
if (!empty($kategori_filter)) {
    $where_clauses[] = "a.kategori = '$kategori_filter'";
}
if (!empty($search_query)) {
    $where_clauses[] = "(a.judul LIKE '%$search_query%' OR a.konten LIKE '%$search_query%')";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";
$articles_query = mysqli_query($conn, "SELECT a.*, u.nama AS author_name FROM articles a JOIN users u ON a.author_id = u.id $where_sql ORDER BY a.created_at DESC");

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <?php if ($detail_article): ?>
    <!-- ============================================== -->
    <!-- TAMPILAN DETAIL ARTIKEL                        -->
    <!-- ============================================== -->
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <a href="<?= BASE_URL ?>pages/edukasi.php" class="btn btn-outline-secondary btn-sm rounded-pill mb-3">
          <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Artikel
        </a>

        <article class="card card-custom p-4 p-md-5 border-0">
          <div class="d-flex align-items-center gap-2 mb-3">
            <span class="badge bg-info-subtle text-info fw-semibold"><?= htmlspecialchars($detail_article['kategori']) ?></span>
            <span class="text-muted small"><i class="bi bi-clock me-1"></i> <?= date('d M Y', strtotime($detail_article['created_at'])) ?></span>
            <span class="text-muted small"><i class="bi bi-eye me-1"></i> <?= $detail_article['views'] ?> views</span>
          </div>

          <h2 class="fw-bold mb-3"><?= htmlspecialchars($detail_article['judul']) ?></h2>
          
          <div class="d-flex align-items-center gap-2 pb-3 mb-4 border-bottom text-muted small">
            <i class="bi bi-person-circle fs-5 text-primary"></i>
            <span>Ditulis oleh: <strong><?= htmlspecialchars($detail_article['author_name']) ?></strong></span>
          </div>

          <div class="alert alert-light border p-3 rounded-3 mb-4 fst-italic text-secondary">
            "<?= htmlspecialchars($detail_article['ringkasan']) ?>"
          </div>

          <div class="article-content lh-lg text-dark">
            <?= nl2br(htmlspecialchars($detail_article['konten'])) ?>
          </div>

          <div class="mt-5 pt-3 border-top d-flex justify-content-between align-items-center">
            <div class="sdg-badge sdg-4"><i class="bi bi-mortarboard-fill"></i> Edukasi Sains Hidrasi</div>
            <a href="<?= BASE_URL ?>pages/kuis.php" class="btn btn-warning btn-sm fw-bold rounded-pill text-dark">
              Uji Pemahaman di Kuis <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>
        </article>
      </div>
    </div>

  <?php else: ?>
    <!-- ============================================== -->
    <!-- TAMPILAN KATALOG / DAFTAR ARTIKEL              -->
    <!-- ============================================== -->
    <div class="text-center mb-5">
      <span class="badge bg-warning-subtle text-warning fw-bold px-3 py-1 rounded-pill mb-2">
        <i class="bi bi-book-half me-1"></i> Modul 4: Pusat Edukasi (SDG 4)
      </span>
      <h2 class="fw-bold">Ensiklopedia Hidrasi & Kesehatan Tubuh</h2>
      <p class="text-muted mx-auto" style="max-width: 650px;">
        Tingkatkan literasi kesehatan Anda melalui artikel ilmiah populer mengenai fungsi cairan, kesehatan ginjal, dan daya pikir.
      </p>
    </div>

    <!-- PENCARIAN & FILTER KATEGORI -->
    <div class="card card-custom p-3 border-0 mb-4">
      <div class="row g-3 align-items-center">
        <!-- Search Form -->
        <div class="col-md-6">
          <form method="GET" action="" class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Cari artikel hidrasi..." value="<?= htmlspecialchars($search_query) ?>">
            <button class="btn btn-primary" type="submit">
              <i class="bi bi-search"></i> Cari
            </button>
          </form>
        </div>

        <!-- Filter Buttons -->
        <div class="col-md-6 text-md-end">
          <div class="btn-group btn-group-sm flex-wrap" role="group">
            <a href="<?= BASE_URL ?>pages/edukasi.php" class="btn <?= empty($kategori_filter) ? 'btn-primary' : 'btn-outline-primary' ?>">Semua</a>
            <a href="<?= BASE_URL ?>pages/edukasi.php?kategori=Performa+Otak" class="btn <?= $kategori_filter == 'Performa Otak' ? 'btn-primary' : 'btn-outline-primary' ?>">Otak</a>
            <a href="<?= BASE_URL ?>pages/edukasi.php?kategori=Kesehatan+Ginjal" class="btn <?= $kategori_filter == 'Kesehatan Ginjal' ? 'btn-primary' : 'btn-outline-primary' ?>">Ginjal</a>
            <a href="<?= BASE_URL ?>pages/edukasi.php?kategori=Mitos+%26+Fakta" class="btn <?= $kategori_filter == 'Mitos & Fakta' ? 'btn-primary' : 'btn-outline-primary' ?>">Mitos & Fakta</a>
          </div>
        </div>
      </div>
    </div>

    <!-- GRID ARTIKEL -->
    <div class="row g-4">
      <?php if ($articles_query && mysqli_num_rows($articles_query) > 0): ?>
        <?php while ($art = mysqli_fetch_assoc($articles_query)): ?>
          <div class="col-lg-4 col-md-6">
            <div class="card card-custom h-100 border-0 p-4 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-info-subtle text-info fw-semibold"><?= htmlspecialchars($art['kategori']) ?></span>
                <span class="text-muted small"><i class="bi bi-eye me-1"></i><?= $art['views'] ?> views</span>
              </div>
              <h5 class="fw-bold mb-2 text-dark">
                <?= htmlspecialchars($art['judul']) ?>
              </h5>
              <p class="text-muted small mb-4 flex-grow-1">
                <?= htmlspecialchars(substr($art['ringkasan'], 0, 130)) ?>...
              </p>
              <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                <span class="small text-muted"><i class="bi bi-clock me-1"></i>3 mnt baca</span>
                <a href="<?= BASE_URL ?>pages/edukasi.php?id=<?= $art['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                  Baca Artikel <i class="bi bi-arrow-right ms-1"></i>
                </a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12 text-center py-5">
          <i class="bi bi-journal-x fs-1 text-muted d-block mb-2"></i>
          <h5 class="text-muted">Tidak ada artikel yang cocok dengan filter atau pencarian Anda.</h5>
          <a href="<?= BASE_URL ?>pages/edukasi.php" class="btn btn-sm btn-link">Reset Filter</a>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

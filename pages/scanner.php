<?php
// ========================================================
// FILE: pages/scanner.php
// Fitur Baru: AI Drink Scanner (identifikasi minuman + edukasi kesehatan)
// Backend: api/scan.php (Gemini) & api/history.php (riwayat MySQL)
// ========================================================
require_once __DIR__ . '/../api/scanner_lib.php';

// Wajib login (riwayat bersifat personal & melindungi kuota AI)
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "pages/login.php");
    exit;
}

scanner_ensure_table($conn);
$page_title = "AI Drink Scanner";
$csrf = scanner_csrf_token();

include_once __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/scanner.css">

<div class="container py-4" id="scannerApp"
     data-api-scan="<?= BASE_URL ?>api/scan.php"
     data-api-history="<?= BASE_URL ?>api/history.php"
     data-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">

  <div class="text-center mb-4">
    <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1 rounded-pill mb-2">
      <i class="bi bi-camera me-1"></i> Fitur Baru: AI Drink Scanner
    </span>
    <h2 class="fw-bold">Kenali Minumanmu, Pahami Dampaknya</h2>
    <p class="text-muted mx-auto" style="max-width: 650px;">
      Foto atau unggah gambar minuman. AI akan mengenali jenisnya lalu menjelaskan kandungan umum, dampak kesehatan, dan alternatif yang lebih sehat.
    </p>
  </div>

  <!-- TAB -->
  <ul class="nav nav-pills justify-content-center gap-2 mb-4 scanner-tabs" id="scannerTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="tab-scan" data-bs-toggle="pill" data-bs-target="#pane-scan" type="button" role="tab" aria-controls="pane-scan" aria-selected="true">
        <i class="bi bi-camera me-1"></i> Pindai Minuman
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-history" data-bs-toggle="pill" data-bs-target="#pane-history" type="button" role="tab" aria-controls="pane-history" aria-selected="false">
        <i class="bi bi-clock-history me-1"></i> Riwayat <span class="badge text-bg-light ms-1 tabular" id="historyCount">0</span>
      </button>
    </li>
  </ul>

  <div class="tab-content">
    <!-- ================================================ -->
    <!-- TAB 1: PINDAI                                     -->
    <!-- ================================================ -->
    <div class="tab-pane fade show active" id="pane-scan" role="tabpanel" aria-labelledby="tab-scan" tabindex="0">
      <div class="row g-4">

        <!-- Kolom kiri: input gambar -->
        <div class="col-lg-5">
          <div class="card card-custom border-0 p-4 scanner-input-card">
            <h4 class="fw-bold mb-3"><i class="bi bi-image text-primary me-2"></i>Foto Minuman</h4>

            <!-- Stage: idle -->
            <div id="stageIdle">
              <div class="scanner-drop text-center" id="dropZone">
                <i class="bi bi-cup-straw scanner-drop-icon" aria-hidden="true"></i>
                <p class="fw-semibold mb-1">Pilih cara memasukkan foto</p>
                <p class="small text-muted mb-3">JPG, PNG, atau WebP. Foto dikompres otomatis di perangkat Anda.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                  <button type="button" class="btn btn-hydro" id="btnCamera">
                    <i class="bi bi-camera-fill me-1"></i> Buka Kamera
                  </button>
                  <button type="button" class="btn btn-hydro-outline" id="btnGallery">
                    <i class="bi bi-upload me-1"></i> Unggah dari Galeri
                  </button>
                </div>
              </div>
              <ul class="small text-muted mt-3 mb-0 ps-3 scanner-tips">
                <li>Pastikan minuman terlihat utuh dan pencahayaan cukup.</li>
                <li>Jika ada kemasan, usahakan label terbaca agar hasil lebih akurat.</li>
              </ul>
            </div>

            <!-- Stage: kamera aktif -->
            <div id="stageCamera" hidden>
              <div class="scanner-frame">
                <video id="cameraVideo" playsinline muted autoplay></video>
              </div>
              <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="button" class="btn btn-hydro flex-grow-1" id="btnCapture">
                  <i class="bi bi-camera-fill me-1"></i> Ambil Foto
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnSwitchCam" aria-label="Ganti kamera depan/belakang">
                  <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnCloseCam">Batal</button>
              </div>
            </div>

            <!-- Stage: preview -->
            <div id="stagePreview" hidden>
              <div class="scanner-frame">
                <img id="previewImg" alt="Pratinjau foto minuman yang akan dianalisis">
              </div>
              <div class="d-flex flex-wrap gap-2 mt-3">
                <button type="button" class="btn btn-hydro flex-grow-1" id="btnAnalyze">
                  <i class="bi bi-stars me-1"></i> Analisis dengan AI
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnRetake">
                  <i class="bi bi-arrow-counterclockwise me-1"></i> Ganti Foto
                </button>
              </div>
            </div>

            <!-- Error dekat aksi -->
            <div class="alert alert-danger rounded-3 small mt-3 mb-0" id="scanError" role="alert" hidden></div>

            <p class="small text-muted mt-3 mb-0">
              <i class="bi bi-shield-lock me-1"></i>
              Saat dianalisis, foto dikirim ke layanan Google Gemini melalui server HydroEdu. Hindari memotret orang atau data pribadi.
            </p>

            <input type="file" id="fileGallery" accept="image/*" hidden>
            <input type="file" id="fileCapture" accept="image/*" capture="environment" hidden>
          </div>
        </div>

        <!-- Kolom kanan: hasil -->
        <div class="col-lg-7">
          <div class="card card-custom border-0 p-4 scanner-result-card" aria-live="polite" aria-busy="false" id="resultCard">
            <div id="resultEmpty" class="text-center text-muted py-5">
              <i class="bi bi-droplet-half scanner-empty-icon" aria-hidden="true"></i>
              <p class="fw-semibold mb-1">Hasil analisis akan tampil di sini</p>
              <p class="small mb-0">Ambil atau unggah foto minuman, lalu tekan <strong>Analisis dengan AI</strong>.</p>
            </div>

            <div id="resultLoading" hidden>
              <div class="placeholder-glow" aria-hidden="true">
                <span class="placeholder col-4 rounded mb-3"></span>
                <span class="placeholder col-8 rounded d-block mb-4 scanner-sk-title"></span>
                <span class="placeholder col-12 rounded d-block mb-2"></span>
                <span class="placeholder col-11 rounded d-block mb-2"></span>
                <span class="placeholder col-9 rounded d-block mb-4"></span>
                <span class="placeholder col-12 rounded d-block mb-2"></span>
                <span class="placeholder col-10 rounded d-block"></span>
              </div>
              <p class="small text-muted text-center mt-4 mb-0">AI sedang menganalisis foto Anda…</p>
            </div>

            <div id="resultBody" hidden></div>
          </div>
        </div>

      </div>
    </div>

    <!-- ================================================ -->
    <!-- TAB 2: RIWAYAT                                    -->
    <!-- ================================================ -->
    <div class="tab-pane fade" id="pane-history" role="tabpanel" aria-labelledby="tab-history" tabindex="0">
      <div class="alert alert-danger rounded-3 small" id="historyError" role="alert" hidden></div>

      <div id="historyLoading" class="row g-3" hidden aria-hidden="true">
        <?php for ($i = 0; $i < 4; $i++): ?>
          <div class="col-6 col-md-4 col-xl-3">
            <div class="card card-custom border-0 p-2 placeholder-glow">
              <span class="placeholder col-12 rounded scanner-sk-thumb"></span>
              <span class="placeholder col-8 rounded mt-3"></span>
              <span class="placeholder col-5 rounded mt-2 mb-1"></span>
            </div>
          </div>
        <?php endfor; ?>
      </div>

      <div id="historyEmpty" class="card card-custom border-0 text-center text-muted py-5" hidden>
        <i class="bi bi-clock-history scanner-empty-icon" aria-hidden="true"></i>
        <p class="fw-semibold mb-1">Belum ada hasil tersimpan</p>
        <p class="small mb-0">Setelah memindai minuman, tekan <strong>Simpan ke Riwayat</strong> untuk melihatnya lagi di sini.</p>
      </div>

      <div id="historyGrid" class="row g-3"></div>

      <div class="text-center mt-4">
        <button type="button" class="btn btn-hydro-outline" id="btnLoadMore" hidden>Muat lebih banyak</button>
      </div>
    </div>
  </div>

  <!-- Catatan edukasi -->
  <p class="small text-muted text-center mt-5 mb-0 mx-auto" style="max-width: 700px;">
    Informasi bersifat edukatif dan umum, bukan diagnosis atau pengganti saran tenaga kesehatan. Kandungan yang tidak terbaca pada foto tidak dapat dipastikan oleh AI.
  </p>
</div>

<!-- MODAL: Detail riwayat -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content border-0 rounded-4">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="detailModalTitle">Detail Pemindaian</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body" id="detailModalBody"></div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-danger me-auto" id="btnDeleteFromDetail">
          <i class="bi bi-trash3 me-1"></i> Hapus
        </button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL: Konfirmasi hapus (aksi destruktif) -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalTitle" aria-describedby="confirmModalDesc" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 rounded-4">
      <div class="modal-body p-4">
        <h5 class="fw-bold" id="confirmModalTitle">Hapus hasil ini?</h5>
        <p class="small text-muted mb-4" id="confirmModalDesc">Hasil pemindaian akan dihapus permanen dari riwayat Anda dan tidak dapat dikembalikan.</p>
        <div class="alert alert-danger rounded-3 small" id="confirmError" role="alert" hidden></div>
        <div class="d-flex gap-2 justify-content-end">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-danger" id="btnConfirmDelete">Hapus</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>assets/js/scanner.js" defer></script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

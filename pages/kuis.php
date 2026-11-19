<?php
// ========================================================
// FILE: pages/kuis.php
// Modul 5: Quiz & Mini Edu-Game Hidrasi (SDG 4)
// ========================================================
require_once __DIR__ . '/../config/koneksi.php';

$page_title = "Kuis Hidrasi Interaktif";
$logged_in = isLoggedIn();
$user = $logged_in ? getCurrentUser($conn) : null;

// Ambil semua soal dari database
$q_soal = mysqli_query($conn, "SELECT * FROM quiz_questions ORDER BY id ASC");
$questions = [];
while ($row = mysqli_fetch_assoc($q_soal)) {
    $questions[] = $row;
}
$total_soal = count($questions);

// Handler Submit Jawaban Kuis
$submitted = false;
$skor = 0;
$total_benar = 0;
$user_answers = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_quiz') {
    $submitted = true;
    $user_answers = $_POST['jawaban'] ?? [];

    foreach ($questions as $q) {
        $qid = $q['id'];
        $jawaban_user = $user_answers[$qid] ?? '';
        if ($jawaban_user === $q['kunci_jawaban']) {
            $total_benar++;
        }
    }

    $skor = $total_soal > 0 ? round(($total_benar / $total_soal) * 100) : 0;

    // Simpan ke database jika login
    if ($logged_in) {
        $uid = (int)$user['id'];
        mysqli_query($conn, "INSERT INTO user_quiz_attempts (user_id, skor, total_benar, total_soal) 
                             VALUES ($uid, $skor, $total_benar, $total_soal)");

        // Gamifikasi: Jika skor >= 80, berikan badge "Cendekia Hidrasi" (id: 4)
        if ($skor >= 80) {
            mysqli_query($conn, "INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES ($uid, 4)");
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
  <div class="text-center mb-5">
    <span class="badge bg-warning-subtle text-warning fw-bold px-3 py-1 rounded-pill mb-2">
      <i class="bi bi-patch-question me-1"></i> Modul 5: Edu-Game & Evaluasi (SDG 4)
    </span>
    <h2 class="fw-bold">Uji Pengetahuan Hidrasi Anda</h2>
    <p class="text-muted mx-auto" style="max-width: 650px;">
      Berapa banyak yang Anda ketahui tentang pentingnya air bagi otak dan ginjal? Kerjakan kuis ini dan raih lencana <strong>Cendekia Hidrasi</strong> (Skor $\ge$ 80).
    </p>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <?php if ($submitted): ?>
        <!-- ============================================== -->
        <!-- HASIL & REVIEW KUIS                            -->
        <!-- ============================================== -->
        <div class="card card-custom p-4 p-md-5 border-0 text-center mb-4">
          <div class="display-3 mb-2">
            <?= $skor >= 80 ? '🎉' : ($skor >= 60 ? '👍' : '📚') ?>
          </div>
          <h3 class="fw-bold mb-1">Skor Kuis Anda: <?= $skor ?> / 100</h3>
          <p class="text-muted small mb-3">
            Anda berhasil menjawab dengan benar <strong><?= $total_benar ?></strong> dari <strong><?= $total_soal ?></strong> pertanyaan.
          </p>

          <?php if ($skor >= 80): ?>
            <div class="alert alert-warning border-0 p-3 rounded-3 mb-4 text-start d-flex align-items-center gap-3">
              <i class="bi bi-award-fill fs-1 text-warning"></i>
              <div>
                <strong class="text-dark">Luar Biasa! Lencana Terbuka:</strong>
                <div class="small text-muted">Selamat! Anda telah membuka lencana prestisius <strong>Cendekia Hidrasi</strong> di akun Anda.</div>
              </div>
            </div>
          <?php endif; ?>

          <div class="d-flex justify-content-center gap-2">
            <a href="<?= BASE_URL ?>pages/kuis.php" class="btn btn-outline-primary rounded-pill px-4">
              <i class="bi bi-arrow-clockwise me-1"></i> Ulangi Kuis
            </a>
            <?php if ($logged_in): ?>
              <a href="<?= BASE_URL ?>pages/badges.php" class="btn btn-warning rounded-pill px-4 text-dark fw-bold">
                <i class="bi bi-trophy me-1"></i> Lihat Lencana Saya
              </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- REVIEW SOAL & PEMBAHASAN -->
        <h4 class="fw-bold mb-3"><i class="bi bi-journal-check text-success me-2"></i>Pembahasan Edukatif</h4>
        <?php foreach ($questions as $idx => $q): ?>
          <?php
            $qid = $q['id'];
            $user_ans = $user_answers[$qid] ?? '';
            $is_correct = ($user_ans === $q['kunci_jawaban']);
          ?>
          <div class="card card-custom p-4 border-0 mb-3 border-start <?= $is_correct ? 'border-success' : 'border-danger' ?> border-4">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="badge bg-light text-dark border">Nomor <?= $idx + 1 ?></span>
              <span class="badge <?= $is_correct ? 'bg-success' : 'bg-danger' ?>">
                <?= $is_correct ? '<i class="bi bi-check-circle me-1"></i> Benar' : '<i class="bi bi-x-circle me-1"></i> Salah' ?>
              </span>
            </div>

            <h5 class="fw-bold mb-3"><?= htmlspecialchars($q['pertanyaan']) ?></h5>

            <div class="small text-muted mb-2">
              Jawaban Anda: <strong class="<?= $is_correct ? 'text-success' : 'text-danger' ?>">
                <?= strtoupper($user_ans) ?: 'Tidak dijawab' ?> (<?= htmlspecialchars($q['opsi_' . $user_ans] ?? '-') ?>)
              </strong>
            </div>

            <?php if (!$is_correct): ?>
              <div class="small text-success mb-2">
                Kunci Jawaban: <strong><?= strtoupper($q['kunci_jawaban']) ?> (<?= htmlspecialchars($q['opsi_' . $q['kunci_jawaban']]) ?>)</strong>
              </div>
            <?php endif; ?>

            <div class="p-3 bg-light rounded-3 border small mt-2">
              <strong class="text-primary"><i class="bi bi-lightbulb-fill text-warning me-1"></i> Pembahasan Ilmiah:</strong>
              <p class="mb-0 text-secondary mt-1"><?= htmlspecialchars($q['penjelasan']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>

      <?php else: ?>
        <!-- ============================================== -->
        <!-- FORM PENGERJAAN SOAL KUIS                      -->
        <!-- ============================================== -->
        <form method="POST" action="">
          <input type="hidden" name="action" value="submit_quiz">

          <?php foreach ($questions as $idx => $q): ?>
            <div class="card card-custom p-4 p-md-5 border-0 mb-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill">
                  Pertanyaan <?= $idx + 1 ?> dari <?= $total_soal ?>
                </span>
                <span class="text-muted small"><i class="bi bi-award me-1"></i>Bobot: 20 Poin</span>
              </div>

              <h4 class="fw-bold mb-4"><?= htmlspecialchars($q['pertanyaan']) ?></h4>

              <div class="d-flex flex-column gap-3">
                <?php foreach (['a', 'b', 'c', 'd'] as $opsi): ?>
                  <label class="p-3 border rounded-3 d-flex align-items-center gap-3 cursor-pointer bg-light bg-opacity-50 hover-shadow transition">
                    <input class="form-check-input mt-0 fs-5" type="radio" name="jawaban[<?= $q['id'] ?>]" value="<?= $opsi ?>" required>
                    <span class="text-dark fw-medium">
                      <strong><?= strtoupper($opsi) ?>.</strong> <?= htmlspecialchars($q['opsi_' . $opsi]) ?>
                    </span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>

          <div class="card card-custom p-4 border-0 text-center mb-5">
            <h5 class="fw-bold mb-2">Sudah Memeriksa Semua Jawaban?</h5>
            <p class="small text-muted mb-3">Tekan tombol kirim di bawah untuk menghitung skor dan mendapatkan pembahasan ilmiah.</p>
            <button type="submit" class="btn btn-hydro btn-lg py-3 px-5 rounded-pill shadow-sm mx-auto">
              <i class="bi bi-send-check me-2"></i> Kirim Jawaban Kuis
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

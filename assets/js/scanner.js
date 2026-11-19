/* ========================================================
   FILE: assets/js/scanner.js
   AI Drink Scanner - interaksi kamera, unggah gambar, preview,
   panggilan ke backend PHP (api/scan.php), dan riwayat.
   Vanilla JS. Tidak ada API key di sisi browser.
   Semua teks dari AI ditampilkan lewat textContent (aman dari XSS).
   ======================================================== */
(function () {
  'use strict';

  var root = document.getElementById('scannerApp');
  if (!root) return;

  var API_SCAN = root.dataset.apiScan;
  var API_HIST = root.dataset.apiHistory;
  var CSRF = root.dataset.csrf;

  var MAX_SIDE = 1280;          // sisi terpanjang gambar yang dikirim ke server
  var THUMB_SIDE = 320;         // sisi terpanjang thumbnail riwayat
  var MAX_INPUT_BYTES = 25 * 1024 * 1024;
  var SCAN_TIMEOUT_MS = 70000;

  function $(id) { return document.getElementById(id); }

  // --------------------------------------------------------
  // Util DOM
  // --------------------------------------------------------
  function el(tag, attrs) {
    var n = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        var v = attrs[k];
        if (v === null || v === undefined || v === false) return;
        if (k === 'class') n.className = v;
        else if (k === 'text') n.textContent = v;
        else if (k.slice(0, 2) === 'on' && typeof v === 'function') n.addEventListener(k.slice(2), v);
        else n.setAttribute(k, v === true ? '' : v);
      });
    }
    for (var i = 2; i < arguments.length; i++) appendChild(n, arguments[i]);
    return n;
  }
  function appendChild(parent, child) {
    if (child === null || child === undefined || child === false) return;
    if (Array.isArray(child)) { child.forEach(function (c) { appendChild(parent, c); }); return; }
    parent.appendChild(child.nodeType ? child : document.createTextNode(String(child)));
  }
  function icon(cls) { return el('i', { class: 'bi ' + cls, 'aria-hidden': 'true' }); }
  function show(node, on) { if (node) node.hidden = !on; }

  // --------------------------------------------------------
  // Peta label
  // --------------------------------------------------------
  var SOURCE = {
    label_terbaca:   { text: 'Terbaca di label', tone: 'ok',   ic: 'bi-patch-check-fill' },
    umumnya:         { text: 'Umumnya',          tone: 'info', ic: 'bi-info-circle-fill' },
    estimasi_visual: { text: 'Estimasi visual',  tone: 'warn', ic: 'bi-eye-fill' }
  };
  var CONFIDENCE = {
    tinggi: { text: 'Keyakinan tinggi',  tone: 'ok' },
    sedang: { text: 'Keyakinan sedang',  tone: 'info' },
    rendah: { text: 'Keyakinan rendah',  tone: 'warn' }
  };
  var IMPACT = {
    manfaat:  { title: 'Manfaat',          tone: 'ok',      ic: 'bi-heart-pulse-fill' },
    netral:   { title: 'Dampak',           tone: 'neutral', ic: 'bi-activity' },
    waspada:  { title: 'Perlu diwaspadai', tone: 'warn',    ic: 'bi-exclamation-triangle-fill' },
    berisiko: { title: 'Berisiko',         tone: 'bad',     ic: 'bi-exclamation-octagon-fill' }
  };
  var LEVEL = {
    utama:   { text: 'Jadikan pilihan utama', tone: 'ok',   ic: 'bi-hand-thumbs-up-fill' },
    moderat: { text: 'Konsumsi secukupnya',   tone: 'info', ic: 'bi-hand-index-thumb-fill' },
    batasi:  { text: 'Perlu dibatasi',        tone: 'warn', ic: 'bi-slash-circle-fill' },
    hindari: { text: 'Sebaiknya dihindari',   tone: 'bad',  ic: 'bi-x-octagon-fill' }
  };

  function chip(text, tone, ic) {
    return el('span', { class: 'sc-chip sc-chip--' + (tone || 'neutral') }, ic ? icon(ic) : null, text);
  }

  function safeThumb(src) {
    return (typeof src === 'string' && src.indexOf('data:image/jpeg;base64,') === 0) ? src : '';
  }

  function formatDate(s) {
    var m = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(s || '');
    if (!m) return s || '';
    var d = new Date(+m[1], +m[2] - 1, +m[3], +m[4], +m[5]);
    try {
      return d.toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch (e) { return s; }
  }

  // --------------------------------------------------------
  // Elemen
  // --------------------------------------------------------
  var stageIdle = $('stageIdle'), stageCamera = $('stageCamera'), stagePreview = $('stagePreview');
  var video = $('cameraVideo'), previewImg = $('previewImg');
  var btnCamera = $('btnCamera'), btnGallery = $('btnGallery'), btnCapture = $('btnCapture');
  var btnSwitchCam = $('btnSwitchCam'), btnCloseCam = $('btnCloseCam');
  var btnAnalyze = $('btnAnalyze'), btnRetake = $('btnRetake');
  var fileGallery = $('fileGallery'), fileCapture = $('fileCapture'), dropZone = $('dropZone');
  var scanError = $('scanError');
  var resultCard = $('resultCard'), resultEmpty = $('resultEmpty'), resultLoading = $('resultLoading'), resultBody = $('resultBody');

  var state = { blob: null, thumb: '', previewUrl: '', scanId: '', hasil: null, saved: false, busy: false };
  var stream = null, facing = 'environment';

  // --------------------------------------------------------
  // Tahapan UI input
  // --------------------------------------------------------
  function showStage(name) {
    show(stageIdle, name === 'idle');
    show(stageCamera, name === 'camera');
    show(stagePreview, name === 'preview');
  }
  function showError(msg) {
    scanError.textContent = msg;
    show(scanError, true);
  }
  function clearError() { scanError.textContent = ''; show(scanError, false); }

  function showResultPanel(name) {
    show(resultEmpty, name === 'empty');
    show(resultLoading, name === 'loading');
    show(resultBody, name === 'body');
    resultCard.setAttribute('aria-busy', name === 'loading' ? 'true' : 'false');
  }

  // --------------------------------------------------------
  // Pemrosesan gambar (kompres + thumbnail di browser)
  // --------------------------------------------------------
  function decodeImage(file) {
    if (window.createImageBitmap) {
      return window.createImageBitmap(file, { imageOrientation: 'from-image' }).catch(function () { return decodeViaImg(file); });
    }
    return decodeViaImg(file);
  }
  function decodeViaImg(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () { URL.revokeObjectURL(url); resolve(img); };
      img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('decode')); };
      img.src = url;
    });
  }
  function srcSize(src) {
    return { w: src.naturalWidth || src.videoWidth || src.width, h: src.naturalHeight || src.videoHeight || src.height };
  }
  function drawScaled(src, maxSide) {
    var s = srcSize(src);
    var scale = Math.min(1, maxSide / Math.max(s.w, s.h));
    var w = Math.max(1, Math.round(s.w * scale)), h = Math.max(1, Math.round(s.h * scale));
    var c = document.createElement('canvas');
    c.width = w; c.height = h;
    var ctx = c.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, w, h);
    ctx.drawImage(src, 0, 0, w, h);
    return c;
  }
  function toBlob(canvas, type, quality) {
    return new Promise(function (resolve, reject) {
      canvas.toBlob(function (b) { b ? resolve(b) : reject(new Error('blob')); }, type, quality);
    });
  }

  function useSource(src) {
    var s = srcSize(src);
    if (!s.w || !s.h) return Promise.reject(new Error('size'));
    var main = drawScaled(src, MAX_SIDE);
    var thumbCanvas = drawScaled(src, THUMB_SIDE);
    return toBlob(main, 'image/jpeg', 0.85).then(function (blob) {
      setImage(blob, thumbCanvas.toDataURL('image/jpeg', 0.7));
    });
  }

  function setImage(blob, thumb) {
    if (state.previewUrl) URL.revokeObjectURL(state.previewUrl);
    state.blob = blob;
    state.thumb = thumb;
    state.previewUrl = URL.createObjectURL(blob);
    state.scanId = ''; state.hasil = null; state.saved = false;
    previewImg.src = state.previewUrl;
    clearError();
    showResultPanel('empty');
    showStage('preview');
    btnAnalyze.focus();
  }

  function handleFile(file) {
    clearError();
    if (!file) return;
    if (file.type && file.type.indexOf('image/') !== 0) {
      showError('File harus berupa gambar (JPG, PNG, atau WebP).');
      return;
    }
    if (file.size > MAX_INPUT_BYTES) {
      showError('Ukuran foto terlalu besar (maksimal 25 MB). Pilih foto lain.');
      return;
    }
    decodeImage(file).then(useSource).catch(function () {
      showError('Foto tidak dapat dibaca. Coba format JPG, PNG, atau WebP.');
    });
  }

  // --------------------------------------------------------
  // Kamera
  // --------------------------------------------------------
  function cameraSupported() {
    return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.isSecureContext);
  }
  function stopCamera() {
    if (stream) {
      stream.getTracks().forEach(function (t) { t.stop(); });
      stream = null;
    }
    video.srcObject = null;
  }
  function startCamera() {
    clearError();
    if (!cameraSupported()) {
      // Fallback: kamera bawaan perangkat lewat input file (mis. HTTP non-localhost)
      fileCapture.click();
      return;
    }
    stopCamera();
    navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: facing }, width: { ideal: 1920 }, height: { ideal: 1080 } },
      audio: false
    }).then(function (s) {
      stream = s;
      video.srcObject = s;
      showStage('camera');
      return video.play().catch(function () {});
    }).catch(function (err) {
      var name = err && err.name;
      if (name === 'NotAllowedError' || name === 'SecurityError') {
        showError('Izin kamera ditolak. Aktifkan izin kamera di pengaturan browser, atau gunakan "Unggah dari Galeri".');
      } else if (name === 'NotFoundError' || name === 'OverconstrainedError') {
        showError('Kamera tidak ditemukan di perangkat ini. Gunakan "Unggah dari Galeri".');
      } else if (name === 'NotReadableError') {
        showError('Kamera sedang dipakai aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.');
      } else {
        showError('Kamera tidak dapat dibuka. Gunakan "Unggah dari Galeri".');
      }
      showStage('idle');
    });
  }
  function capture() {
    if (!stream || !video.videoWidth) return;
    btnCapture.disabled = true;
    useSource(video).then(function () { stopCamera(); }).catch(function () {
      showError('Gagal mengambil foto. Coba lagi.');
    }).then(function () { btnCapture.disabled = false; });
  }

  // --------------------------------------------------------
  // Panggilan API
  // --------------------------------------------------------
  function readJson(res) {
    return res.json().catch(function () {
      return { ok: false, error: 'Server mengirim respons tak terduga. Coba lagi atau hubungi admin.' };
    });
  }
  function apiPost(url, formData, timeoutMs) {
    var ctrl = window.AbortController ? new AbortController() : null;
    var timer = ctrl ? setTimeout(function () { ctrl.abort(); }, timeoutMs || 30000) : null;
    return fetch(url, {
      method: 'POST', body: formData, credentials: 'same-origin',
      headers: { 'X-CSRF-Token': CSRF }, signal: ctrl ? ctrl.signal : undefined
    }).then(function (res) {
      return readJson(res).then(function (data) { return { res: res, data: data }; });
    }).catch(function (err) {
      if (err && err.name === 'AbortError') return { res: { ok: false, status: 0 }, data: { ok: false, error: 'Permintaan terlalu lama. Coba lagi.' } };
      return { res: { ok: false, status: 0 }, data: { ok: false, error: 'Tidak dapat terhubung ke server. Periksa koneksi Anda.' } };
    }).then(function (r) {
      if (timer) clearTimeout(timer);
      return r;
    });
  }
  function apiGet(params) {
    return fetch(API_HIST + '?' + params, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (res) { return readJson(res).then(function (data) { return { res: res, data: data }; }); })
      .catch(function () { return { res: { ok: false, status: 0 }, data: { ok: false, error: 'Tidak dapat terhubung ke server.' } }; });
  }

  // --------------------------------------------------------
  // Analisis
  // --------------------------------------------------------
  function setBusy(on) {
    state.busy = on;
    [btnAnalyze, btnRetake, btnGallery, btnCamera].forEach(function (b) { if (b) b.disabled = on; });
    btnAnalyze.innerHTML = '';
    if (on) {
      btnAnalyze.appendChild(el('span', { class: 'spinner-border spinner-border-sm me-2', 'aria-hidden': 'true' }));
      btnAnalyze.appendChild(document.createTextNode('Menganalisis…'));
    } else {
      btnAnalyze.appendChild(icon('bi-stars me-1'));
      btnAnalyze.appendChild(document.createTextNode(' Analisis dengan AI'));
    }
  }

  function analyze() {
    if (!state.blob || state.busy) return;
    clearError();
    setBusy(true);
    showResultPanel('loading');

    var fd = new FormData();
    fd.append('image', state.blob, 'minuman.jpg');

    apiPost(API_SCAN, fd, SCAN_TIMEOUT_MS).then(function (r) {
      setBusy(false);
      if (!r.data || !r.data.ok) {
        showResultPanel('empty');
        var msg = (r.data && r.data.error) || 'Analisis gagal. Coba lagi.';
        if (r.res && r.res.status === 401) msg += ' (Masuk kembali di halaman login.)';
        if (r.data && r.data.debug) msg += ' [Detail admin: ' + r.data.debug + ']';
        showError(msg);
        return;
      }
      state.scanId = r.data.scan_id;
      state.hasil = r.data.hasil;
      state.saved = false;
      renderResult(resultBody, state.hasil, { thumb: state.thumb, readonly: false });
      showResultPanel('body');
      var h = resultBody.querySelector('[data-focus]');
      if (h) h.focus({ preventScroll: false });
    });
  }

  function resetAll() {
    stopCamera();
    if (state.previewUrl) URL.revokeObjectURL(state.previewUrl);
    state = { blob: null, thumb: '', previewUrl: '', scanId: '', hasil: null, saved: false, busy: false };
    previewImg.removeAttribute('src');
    fileGallery.value = ''; fileCapture.value = '';
    clearError();
    showResultPanel('empty');
    showStage('idle');
  }

  // --------------------------------------------------------
  // Render hasil (dipakai di tab Pindai & modal Riwayat)
  // --------------------------------------------------------
  function section(title, ic) {
    var body = el('div');
    var wrap = el('section', { class: 'sc-section' },
      el('h4', { class: 'sc-section-title' }, icon(ic), title), body);
    return { wrap: wrap, body: body };
  }

  function renderResult(container, h, ctx) {
    container.replaceChildren();
    ctx = ctx || {};

    // Bukan minuman / gambar tidak jelas
    if (!h || h.status !== 'minuman') {
      var isNot = h && h.status === 'bukan_minuman';
      container.appendChild(el('div', { class: 'sc-notice', role: 'alert' },
        icon(isNot ? 'bi-question-circle-fill' : 'bi-camera-video-off-fill'),
        el('div', null,
          el('strong', { tabindex: '-1', 'data-focus': '' }, isNot ? 'Ini sepertinya bukan minuman' : 'Gambar kurang jelas'),
          el('p', { class: 'mb-2 small' }, (h && h.pesan) || 'Coba foto ulang dengan pencahayaan cukup dan minuman terlihat utuh.'),
          !ctx.readonly ? el('button', { type: 'button', class: 'btn btn-sm btn-hydro', onclick: resetAll },
            icon('bi-arrow-counterclockwise me-1'), ' Coba foto lain') : null)));
      return;
    }

    var thumb = safeThumb(ctx.thumb);
    var conf = CONFIDENCE[h.keyakinan] || CONFIDENCE.sedang;

    // Kepala
    container.appendChild(el('div', { class: 'sc-head' },
      thumb ? el('img', { class: 'sc-head-thumb', src: thumb, alt: 'Foto minuman ' + h.nama }) : null,
      el('div', { class: 'flex-grow-1' },
        el('div', { class: 'd-flex flex-wrap gap-2 mb-1' },
          chip(h.kategori_label || 'Minuman', 'info', 'bi-tag-fill'),
          chip(conf.text, conf.tone)),
        el('h3', { class: 'sc-name', tabindex: '-1', 'data-focus': '' }, h.nama),
        h.terlihat ? el('p', { class: 'sc-seen' }, 'Terlihat di foto: ' + h.terlihat) : null)));

    // Kandungan
    var sKand = section('Perkiraan kandungan', 'bi-flask');
    if (h.kandungan && h.kandungan.length) {
      var ul = el('ul', { class: 'sc-list' });
      h.kandungan.forEach(function (k) {
        var src = SOURCE[k.sumber] || SOURCE.estimasi_visual;
        ul.appendChild(el('li', { class: 'sc-item' },
          el('div', null,
            el('div', { class: 'sc-item-name' }, k.nama),
            k.keterangan ? el('div', { class: 'sc-item-note' }, k.keterangan) : null),
          chip(src.text, src.tone, src.ic)));
      });
      sKand.body.appendChild(ul);
      sKand.body.appendChild(el('p', { class: 'sc-legend mb-0' },
        'Terbaca di label = tertulis pada kemasan di foto. Umumnya = komposisi lazim jenis minuman ini, bukan hasil pembacaan foto. Estimasi visual = dugaan dari tampilan.'));
    } else {
      sKand.body.appendChild(el('p', { class: 'small text-muted mb-0' }, 'Komposisi tidak dapat diketahui dari foto ini.'));
    }
    if (h.tidak_pasti && h.tidak_pasti.length) {
      sKand.body.appendChild(el('div', { class: 'sc-unknown mt-2' },
        el('strong', null, 'Tidak dapat dipastikan dari foto:'),
        el('ul', null, h.tidak_pasti.map(function (t) { return el('li', null, t); }))));
    }
    container.appendChild(sKand.wrap);

    // Dampak kesehatan
    var imp = IMPACT[h.dampak && h.dampak.jenis] || IMPACT.netral;
    var sDampak = section('Dampak terhadap kesehatan', imp.ic);
    sDampak.body.appendChild(el('div', { class: 'sc-callout sc-callout--' + imp.tone },
      el('strong', null, imp.title),
      (h.dampak && h.dampak.teks) || 'Informasi dampak tidak tersedia.'));
    container.appendChild(sDampak.wrap);

    // Tingkat konsumsi
    var lvl = LEVEL[h.konsumsi && h.konsumsi.tingkat] || LEVEL.moderat;
    var sKons = section('Tingkat konsumsi', 'bi-speedometer2');
    sKons.body.appendChild(el('div', { class: 'mb-2' }, chip(lvl.text, lvl.tone, lvl.ic)));
    if (h.konsumsi && h.konsumsi.saran) sKons.body.appendChild(el('p', { class: 'mb-0 small' }, h.konsumsi.saran));
    container.appendChild(sKons.wrap);

    // Alternatif
    if (h.alternatif && h.alternatif.length) {
      var sAlt = section('Alternatif yang lebih sehat', 'bi-cup-straw');
      sAlt.body.appendChild(el('div', { class: 'sc-alt' }, h.alternatif.map(function (a) {
        return el('div', { class: 'sc-alt-item' }, icon('bi-check-circle-fill'), el('span', null, a));
      })));
      container.appendChild(sAlt.wrap);
    }

    if (h.catatan) container.appendChild(el('p', { class: 'small text-muted mt-3 mb-0' }, h.catatan));

    // Aksi
    if (!ctx.readonly) {
      var btnSave = el('button', { type: 'button', class: 'btn btn-hydro', id: 'btnSave' },
        icon('bi-bookmark-plus me-1'), ' Simpan ke Riwayat');
      btnSave.addEventListener('click', function () { saveCurrent(btnSave); });
      container.appendChild(el('div', { class: 'sc-actions' },
        btnSave,
        el('button', { type: 'button', class: 'btn btn-outline-secondary', onclick: resetAll },
          icon('bi-arrow-counterclockwise me-1'), ' Pindai lagi')));
      container.appendChild(el('div', { class: 'alert alert-danger rounded-3 small mt-3 mb-0', id: 'saveError', role: 'alert', hidden: true }));
    }
  }

  // --------------------------------------------------------
  // Simpan ke riwayat
  // --------------------------------------------------------
  function saveCurrent(btn) {
    var errBox = $('saveError');
    show(errBox, false);
    if (!state.scanId || state.saved) return;
    btn.disabled = true;
    btn.textContent = 'Menyimpan…';

    var fd = new FormData();
    fd.append('action', 'save');
    fd.append('scan_id', state.scanId);
    fd.append('thumbnail', state.thumb || '');

    apiPost(API_HIST, fd, 20000).then(function (r) {
      if (r.data && r.data.ok) {
        state.saved = true;
        hist.dirty = true;
        if (!r.data.already) hist.total += 1;
        updateCount();
        btn.replaceChildren(icon('bi-check-circle-fill me-1'), ' Tersimpan di Riwayat');
        btn.className = 'btn btn-success';
        btn.disabled = true;
        // Tautan cepat ke riwayat
        var link = el('button', { type: 'button', class: 'btn btn-outline-primary', onclick: function () {
          var t = $('tab-history'); if (t) t.click();
        } }, icon('bi-clock-history me-1'), ' Lihat Riwayat');
        btn.parentNode.insertBefore(link, btn.nextSibling);
      } else {
        btn.disabled = false;
        btn.replaceChildren(icon('bi-bookmark-plus me-1'), ' Simpan ke Riwayat');
        errBox.textContent = (r.data && r.data.error) || 'Gagal menyimpan. Coba lagi.';
        show(errBox, true);
      }
    });
  }

  // --------------------------------------------------------
  // Riwayat
  // --------------------------------------------------------
  var hist = { items: [], offset: 0, hasMore: false, total: 0, loading: false, dirty: false, loadedOnce: false };
  var historyGrid = $('historyGrid'), historyLoading = $('historyLoading'), historyEmpty = $('historyEmpty');
  var historyError = $('historyError'), btnLoadMore = $('btnLoadMore'), historyCount = $('historyCount');

  function updateCount() { historyCount.textContent = String(hist.total); }

  function historyCard(it) {
    var thumb = safeThumb(it.thumbnail);
    var card = el('button', { type: 'button', class: 'sc-hist-card', 'aria-label': 'Lihat detail ' + it.nama },
      thumb ? el('img', { class: 'sc-hist-thumb', src: thumb, alt: '', loading: 'lazy' })
            : el('div', { class: 'sc-hist-thumb sc-hist-thumb--empty', 'aria-hidden': 'true' }, icon('bi-cup-straw')),
      el('div', { class: 'sc-hist-body' },
        el('p', { class: 'sc-hist-name' }, it.nama),
        chip(it.kategori_label, 'info'),
        el('div', { class: 'sc-hist-date tabular' }, formatDate(it.created_at))));
    card.addEventListener('click', function () { openDetail(it.id); });
    return el('div', { class: 'col-6 col-md-4 col-xl-3', 'data-id': String(it.id) }, card);
  }

  function renderHistoryState() {
    show(historyEmpty, hist.loadedOnce && hist.items.length === 0);
    show(btnLoadMore, hist.hasMore);
  }

  function loadHistory(reset) {
    if (hist.loading) return;
    hist.loading = true;
    show(historyError, false);
    if (reset) {
      hist.items = []; hist.offset = 0;
      historyGrid.replaceChildren();
      show(historyEmpty, false);
      show(historyLoading, true);
    }
    btnLoadMore.disabled = true;

    apiGet('action=list&offset=' + hist.offset).then(function (r) {
      hist.loading = false;
      show(historyLoading, false);
      btnLoadMore.disabled = false;
      if (!r.data || !r.data.ok) {
        historyError.textContent = (r.data && r.data.error) || 'Gagal memuat riwayat.';
        show(historyError, true);
        return;
      }
      hist.loadedOnce = true;
      hist.dirty = false;
      hist.total = r.data.total;
      hist.hasMore = !!r.data.has_more;
      r.data.items.forEach(function (it) {
        hist.items.push(it);
        historyGrid.appendChild(historyCard(it));
      });
      hist.offset = hist.items.length;
      updateCount();
      renderHistoryState();
    });
  }

  btnLoadMore.addEventListener('click', function () { loadHistory(false); });

  // --------------------------------------------------------
  // Modal detail & hapus
  // --------------------------------------------------------
  var detailEl = $('detailModal'), confirmEl = $('confirmModal');
  var detailBody = $('detailModalBody'), detailTitle = $('detailModalTitle');
  var detailModal = null, confirmModal = null;
  var currentDetailId = 0;

  function getModal(elm) { return (window.bootstrap && window.bootstrap.Modal) ? window.bootstrap.Modal.getOrCreateInstance(elm) : null; }

  function openDetail(id) {
    detailModal = detailModal || getModal(detailEl);
    if (!detailModal) return;
    currentDetailId = id;
    detailTitle.textContent = 'Detail Pemindaian';
    detailBody.replaceChildren(el('div', { class: 'placeholder-glow', 'aria-hidden': 'true' },
      el('span', { class: 'placeholder col-6 rounded d-block mb-3' }),
      el('span', { class: 'placeholder col-12 rounded d-block mb-2' }),
      el('span', { class: 'placeholder col-10 rounded d-block' })));
    detailModal.show();

    apiGet('action=get&id=' + encodeURIComponent(id)).then(function (r) {
      if (!r.data || !r.data.ok) {
        detailBody.replaceChildren(el('div', { class: 'alert alert-danger rounded-3 small mb-0', role: 'alert' },
          (r.data && r.data.error) || 'Gagal memuat detail.'));
        return;
      }
      var it = r.data.item;
      detailTitle.textContent = it.hasil && it.hasil.nama ? it.hasil.nama : 'Detail Pemindaian';
      renderResult(detailBody, it.hasil, { thumb: it.thumbnail, readonly: true });
      detailBody.appendChild(el('p', { class: 'small text-muted mt-3 mb-0 tabular' }, 'Dipindai pada ' + formatDate(it.created_at)));
    });
  }

  $('btnDeleteFromDetail').addEventListener('click', function () {
    if (!currentDetailId) return;
    confirmModal = confirmModal || getModal(confirmEl);
    if (!confirmModal) return;
    // Hindari dua modal bertumpuk: tutup detail dulu, lalu tampilkan konfirmasi
    detailEl.addEventListener('hidden.bs.modal', function () {
      show($('confirmError'), false);
      confirmModal.show();
    }, { once: true });
    detailModal.hide();
  });

  $('btnConfirmDelete').addEventListener('click', function () {
    var btn = this, id = currentDetailId;
    if (!id) return;
    btn.disabled = true;
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', String(id));
    apiPost(API_HIST, fd, 20000).then(function (r) {
      btn.disabled = false;
      if (r.data && r.data.ok) {
        confirmModal.hide();
        currentDetailId = 0;
        // Muat ulang agar urutan, hitungan, dan tombol "muat lebih banyak" konsisten
        loadHistory(true);
      } else {
        var box = $('confirmError');
        box.textContent = (r.data && r.data.error) || 'Gagal menghapus. Coba lagi.';
        show(box, true);
      }
    });
  });

  // --------------------------------------------------------
  // Event input
  // --------------------------------------------------------
  btnCamera.addEventListener('click', startCamera);
  btnGallery.addEventListener('click', function () { fileGallery.click(); });
  btnCapture.addEventListener('click', capture);
  btnCloseCam.addEventListener('click', function () { stopCamera(); showStage(state.blob ? 'preview' : 'idle'); });
  btnSwitchCam.addEventListener('click', function () { facing = facing === 'environment' ? 'user' : 'environment'; startCamera(); });
  btnAnalyze.addEventListener('click', analyze);
  btnRetake.addEventListener('click', resetAll);

  fileGallery.addEventListener('change', function () { handleFile(fileGallery.files && fileGallery.files[0]); fileGallery.value = ''; });
  fileCapture.addEventListener('change', function () { handleFile(fileCapture.files && fileCapture.files[0]); fileCapture.value = ''; });

  // Seret & lepas (desktop)
  ['dragenter', 'dragover'].forEach(function (ev) {
    dropZone.addEventListener(ev, function (e) { e.preventDefault(); dropZone.classList.add('is-over'); });
  });
  ['dragleave', 'drop'].forEach(function (ev) {
    dropZone.addEventListener(ev, function (e) { e.preventDefault(); dropZone.classList.remove('is-over'); });
  });
  dropZone.addEventListener('drop', function (e) {
    var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
    if (f) handleFile(f);
  });

  // Matikan kamera saat pindah tab / halaman
  $('tab-history').addEventListener('shown.bs.tab', function () {
    stopCamera();
    if (!stageCamera.hidden) showStage(state.blob ? 'preview' : 'idle');
    if (hist.dirty || !hist.loadedOnce) loadHistory(true);
  });
  document.addEventListener('visibilitychange', function () {
    if (document.hidden && stream) { stopCamera(); showStage(state.blob ? 'preview' : 'idle'); }
  });
  window.addEventListener('pagehide', stopCamera);

  // Muat jumlah riwayat di awal
  showResultPanel('empty');
  loadHistory(true);
})();

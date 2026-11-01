<?php
// pages/laporan/index.php
requireRole(['admin', 'manager','staf_keuangan']);

// ── 1. Ambil daftar proyek (fallback ke array kosong jika gagal) ──
$proyekId  = (int)($_GET['proyek_id'] ?? 0);
$allProyek = dbFetchAll("SELECT id,kode_proyek,nama_proyek FROM proyek ORDER BY created_at DESC") ?: [];

// Set default ke proyek pertama jika belum dipilih
if (!$proyekId && !empty($allProyek)) {
    $proyekId = (int)$allProyek[0]['id'];
}

// ── 2. Ambil data proyek & hitung EVM hanya jika ID valid ──
$proyek    = ($proyekId > 0) ? dbFetchOne("SELECT * FROM proyek WHERE id=?", [$proyekId]) : null;
$mingguKe  = $proyek ? (int)($proyek['minggu_ke'] ?? 0) : 0;

// Hitung EVM & Kurva-S — bungkus dengan ?: [] agar tidak pernah null
$evm       = ($proyekId > 0 && $proyek) ? (hitungEVM($proyekId, $mingguKe) ?: []) : [];
$kurvaS    = ($proyekId > 0) ? (getKurvaS($proyekId) ?: []) : [];

// Pastikan wbs_detail selalu berupa array (mencegah PHP notice & loop)
$wbsDetail = is_array($evm['wbs_detail'] ?? null) ? $evm['wbs_detail'] : [];

$pageTitle = 'Laporan EVM';
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-800 mb-0"><i class="bi bi-file-earmark-pdf me-2 text-primary"></i>Laporan EVM</h4>
    <div class="text-muted small">Cetak laporan kinerja proyek format print-ready</div>
  </div>
  <div class="d-flex gap-2">
    <form method="GET" class="d-flex gap-2">
      <input type="hidden" name="p" value="laporan">
      <select name="proyek_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:260px">
        <?php foreach ($allProyek as $pr): ?>
        <option value="<?= (int)$pr['id'] ?>" <?= (int)$pr['id'] === $proyekId ? 'selected' : '' ?>>
          <?= e($pr['kode_proyek'] . ' — ' . mb_strimwidth($pr['nama_proyek'], 0, 45, '…')) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </form>
    <button onclick="window.print()" class="btn btn-danger fw-bold">
      <i class="bi bi-printer me-1"></i>Cetak / Print
    </button>
  </div>
</div>

<?php if (empty($evm)): ?>
  <div class="alert alert-info">Pilih proyek terlebih dahulu atau data EVM belum tersedia.</div>
<?php else:
  // ── 3. Ekstrak semua nilai EVM sekali di sini dengan fallback 0 ──
  //    Ini mencegah akses $evm['key'] yang bisa undefined di bawah
  $bac   = (float)($evm['bac']    ?? 0);
  $pv    = (float)($evm['pv']     ?? 0);
  $ev    = (float)($evm['ev']     ?? 0);
  $ac    = (float)($evm['ac']     ?? 0);
  $sv    = (float)($evm['sv']     ?? 0);
  $cv    = (float)($evm['cv']     ?? 0);
  $spi   = (float)($evm['spi']    ?? 0);
  $cpi   = (float)($evm['cpi']    ?? 0);
  $eac   = (float)($evm['eac']    ?? 0);
  $etc   = (float)($evm['etc']    ?? 0);
  $vac   = (float)($evm['vac']    ?? 0);
  $tcpi  = (float)($evm['tcpi']   ?? 0);
  $pvPct = (float)($evm['pv_pct'] ?? 0);
  $evPct = (float)($evm['ev_pct'] ?? 0);
  $acPct = (float)($evm['ac_pct'] ?? 0);

  $nilaiKontrak = (float)($proyek['nilai_kontrak'] ?? 0);
  $ppnPersen    = (float)($proyek['ppn_persen']    ?? 0);
  $totalMinggu  = max(1, (int)($proyek['total_minggu'] ?? 1)); // hindari division by zero
?>

<!-- ═══ PRINT AREA ═══ -->
<div id="printArea" class="bg-white p-4 rounded shadow-sm">

  <!-- ── Header Laporan ── -->
  <div class="text-center border-bottom pb-3 mb-4">
    <div class="fw-800 fs-5 text-dark">LAPORAN KINERJA PROYEK</div>
    <div class="fw-700 fs-6 text-primary">EARNED VALUE MANAGEMENT (EVM)</div>
    <div class="text-muted small mt-1">PT. Pesona Tamanindo</div>
    <hr class="my-2">
    <div class="fw-600"><?= e($proyek['nama_proyek'] ?? '') ?></div>
    <div class="text-muted small">
      Kode: <?= e($proyek['kode_proyek'] ?? '') ?> |
      Lokasi: <?= e($proyek['lokasi'] ?? '-') ?> |
      Pemberi Kerja: <?= e($proyek['pemberi_kerja'] ?? '-') ?>
    </div>
    <div class="text-muted small">
      Mulai: <?= date('d M Y', strtotime($proyek['tanggal_mulai'] ?? 'now')) ?> |
      Selesai Rencana: <?= date('d M Y', strtotime($proyek['tanggal_selesai'] ?? 'now')) ?> |
      Periode Laporan: Minggu ke-<?= $mingguKe ?> dari <?= $totalMinggu ?>
    </div>
    <div class="text-muted small">
      Tanggal Cetak: <?= date('d M Y H:i') ?> WIB |
      Disiapkan oleh: <?= e(currentUser()['nama_lengkap'] ?? '') ?>
    </div>
  </div>

  <!-- ── I. Informasi Proyek ── -->
  <div class="row mb-4">
    <div class="col-12">
      <h6 class="fw-700 border-bottom pb-1 mb-2">I. INFORMASI UMUM PROYEK</h6>
      <table class="table table-sm table-bordered small">
        <tr>
          <td class="fw-600 bg-light" style="width:200px">Nilai Kontrak (BAC)</td>
          <td><?= formatRupiah($nilaiKontrak) ?></td>
          <td class="fw-600 bg-light" style="width:200px">Nilai Kontrak + PPN</td>
          <td><?= formatRupiah($nilaiKontrak * (1 + $ppnPersen / 100)) ?></td>
        </tr>
        <tr>
          <td class="fw-600 bg-light">Status Proyek</td>
          <td><?= ucfirst($proyek['status'] ?? '-') ?></td>
          <td class="fw-600 bg-light">Persentase Waktu</td>
          <td><?= round($mingguKe / $totalMinggu * 100, 1) ?>%</td>
        </tr>
      </table>
    </div>
  </div>

  <!-- ── II. Parameter EVM ── -->
  <div class="mb-4">
    <h6 class="fw-700 border-bottom pb-1 mb-2">II. PARAMETER EVM — MINGGU KE-<?= $mingguKe ?></h6>
    <table class="table table-sm table-bordered small">
      <thead class="table-dark text-center">
        <tr>
          <th>Parameter</th><th>Simbol</th><th>Rumus</th>
          <th>Nilai (Rp)</th><th>%BAC</th><th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // ── FIX: Array 7 elemen konsisten — [label, simbol, rumus, nilai, pct|null, ok|null, isIndex] ──
        $params = [
          ['Planned Value',              'PV',   'Bobot Rencana × BAC',   $pv,   $pvPct, null,       false],
          ['Earned Value',               'EV',   '% Realisasi × BAC',     $ev,   $evPct, null,       false],
          ['Actual Cost',                'AC',   'Σ Biaya Aktual',        $ac,   $acPct, null,       false],
          ['Schedule Variance',          'SV',   'EV − PV',               $sv,   null,   $sv >= 0,   false],
          ['Cost Variance',              'CV',   'EV − AC',               $cv,   null,   $cv >= 0,   false],
          ['Schedule Performance Index', 'SPI',  'EV ÷ PV',               $spi,  null,   $spi >= 1,  true ],
          ['Cost Performance Index',     'CPI',  'EV ÷ AC',               $cpi,  null,   $cpi >= 1,  true ],
          ['Estimate at Completion',     'EAC',  'BAC ÷ CPI',             $eac,  null,   $eac<=$bac, false],
          ['Estimate to Complete',       'ETC',  'EAC − AC',              $etc,  null,   null,       false],
          ['Variance at Completion',     'VAC',  'BAC − EAC',             $vac,  null,   $vac >= 0,  false],
          ['To-Complete Perf. Index',    'TCPI', '(BAC−EV)÷(BAC−AC)',     $tcpi, null,   $tcpi <= 1, true ],
        ];
        foreach ($params as [$lbl, $sym, $rumus, $val, $pct, $ok, $isIdx]):
        ?>
        <tr>
          <td class="fw-600"><?= $lbl ?></td>
          <td class="text-center fw-bold"><?= $sym ?></td>
          <td class="fst-italic text-muted"><?= $rumus ?></td>
          <td class="text-end fw-bold"><?= $isIdx ? number_format($val, 4) : formatRupiah($val) ?></td>
          <td class="text-center"><?= $pct !== null ? formatPersen($pct) : '—' ?></td>
          <td class="text-center">
            <?php if ($ok === null) echo '—';
            elseif ($ok)           echo '✅ Baik';
            else                   echo '⚠️ Perlu Perhatian'; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- ── III. Kurva-S ── -->
  <div class="mb-4">
    <h6 class="fw-700 border-bottom pb-1 mb-2">III. KURVA-S (PV / EV / AC)</h6>
    <!-- FIX: Bungkus canvas dalam div berukuran tetap agar tidak infinite resize -->
    <div style="position:relative; height:220px; width:100%;">
      <canvas id="laporanChart"></canvas>
    </div>
  </div>

  <!-- ── IV. WBS Detail ── -->
  <div class="mb-4">
    <h6 class="fw-700 border-bottom pb-1 mb-2">IV. REKAPITULASI WBS PER PAKET PEKERJAAN</h6>
    <!--
      FIX: .wbs-table-wrapper membatasi tinggi tabel di layar (max-height: 380px + scroll).
      Di @media print, batasan ini dihapus agar semua baris tampil saat cetak.
    -->
    <div class="wbs-table-wrapper table-responsive">
      <table class="table table-sm table-bordered small mb-0">
        <thead class="table-dark text-center">
          <tr>
            <th>Kode</th><th>Uraian</th><th>BAC (Rp)</th><th>Bobot%</th>
            <th>PV%</th><th>EV%</th><th>EV (Rp)</th><th>AC (Rp)</th>
            <th>SPI</th><th>CPI</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($wbsDetail)): ?>
            <tr><td colspan="11" class="text-center text-muted py-2">Tidak ada data WBS.</td></tr>
          <?php else: ?>
            <?php foreach ($wbsDetail as $w): ?>
            <tr>
              <td class="fw-600"><?= e($w['kode'] ?? '') ?></td>
              <td><?= e(mb_strimwidth($w['uraian'] ?? '', 0, 45, '…')) ?></td>
              <td class="text-end"><?= formatMilliar($w['anggaran'] ?? 0) ?></td>
              <td class="text-center"><?= formatPersen($w['bobot'] ?? 0) ?></td>
              <td class="text-center"><?= round($w['pv_pct'] ?? 0, 1) ?>%</td>
              <td class="text-center fw-bold <?= ($w['ev_pct'] ?? 0) >= ($w['pv_pct'] ?? 0) ? 'text-success' : 'text-danger' ?>">
                <?= round($w['ev_pct'] ?? 0, 1) ?>%
              </td>
              <td class="text-end"><?= formatMilliar($w['ev'] ?? 0) ?></td>
              <td class="text-end"><?= formatMilliar($w['ac'] ?? 0) ?></td>
              <td class="text-center fw-bold <?= ($w['spi'] ?? 0) >= 1 ? 'text-success' : 'text-danger' ?>">
                <?= number_format($w['spi'] ?? 0, 3) ?>
              </td>
              <td class="text-center fw-bold <?= ($w['cpi'] ?? 0) >= 1 ? 'text-success' : 'text-danger' ?>">
                <?= number_format($w['cpi'] ?? 0, 3) ?>
              </td>
              <td class="text-center"><?= strtoupper($w['status'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
        <tfoot class="table-dark fw-bold">
          <tr>
            <td colspan="2" class="text-center">TOTAL</td>
            <td class="text-end"><?= formatMilliar($bac) ?></td>
            <td class="text-center">100%</td>
            <td class="text-center"><?= round($pvPct, 2) ?>%</td>
            <td class="text-center"><?= round($evPct, 2) ?>%</td>
            <td class="text-end"><?= formatMilliar($ev) ?></td>
            <td class="text-end"><?= formatMilliar($ac) ?></td>
            <td class="text-center"><?= number_format($spi, 3) ?></td>
            <td class="text-center"><?= number_format($cpi, 3) ?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <!-- ── V. Kesimpulan ── -->
  <div class="mb-4">
    <h6 class="fw-700 border-bottom pb-1 mb-2">V. KESIMPULAN & ANALISIS</h6>
    <table class="table table-sm table-bordered small">
      <tr>
        <td class="fw-600 bg-light" style="width:220px">Kondisi Jadwal</td>
        <td class="<?= $spi >= 1 ? 'text-success' : 'text-danger' ?> fw-600">
          <?= $spi >= 1
            ? '✅ ON SCHEDULE — Pekerjaan berjalan sesuai/lebih cepat dari rencana'
            : '⚠️ BEHIND SCHEDULE — SPI = ' . number_format($spi, 3) . ' (Terlambat ' . round((1 - $spi) * 100, 1) . '% dari rencana)' ?>
        </td>
      </tr>
      <tr>
        <td class="fw-600 bg-light">Kondisi Biaya</td>
        <td class="<?= $cpi >= 1 ? 'text-success' : 'text-danger' ?> fw-600">
          <?= $cpi >= 1
            ? '✅ UNDER BUDGET — Biaya lebih efisien dari anggaran'
            : '⚠️ COST OVERRUN — CPI = ' . number_format($cpi, 3) . ' (Setiap Rp1 hanya menghasilkan Rp' . number_format($cpi, 2) . ' nilai)' ?>
        </td>
      </tr>
      <tr>
        <td class="fw-600 bg-light">Estimasi Biaya Akhir (EAC)</td>
        <td class="fw-600">
          <?= formatRupiah($eac) ?>
          (<?= $eac > $bac
            ? '⚠️ Selisih +' . formatMilliar($eac - $bac) . ' dari BAC'
            : '✅ Masih dalam batas BAC' ?>)
        </td>
      </tr>
      <tr>
        <td class="fw-600 bg-light">Indeks Efisiensi Tersisa (TCPI)</td>
        <td class="fw-600">
          <?= number_format($tcpi, 4) ?>
          <?= $tcpi <= 1
            ? ' — ✅ Target biaya masih dapat dicapai'
            : ' — ⚠️ Diperlukan efisiensi tinggi untuk mencapai BAC' ?>
        </td>
      </tr>
    </table>
  </div>

  <!-- ── Tanda Tangan ── -->
  <div class="row mt-5 mb-2">
    <div class="col-4 text-center">
      <div class="small text-muted mb-4">Dibuat oleh</div>
      <div style="border-top:1px solid #333;padding-top:4px" class="small">Staf Lapangan / Keuangan</div>
    </div>
    <div class="col-4 text-center">
      <div class="small text-muted mb-4">Diperiksa oleh</div>
      <div style="border-top:1px solid #333;padding-top:4px" class="small">Manajer Proyek</div>
    </div>
    <div class="col-4 text-center">
      <div class="small text-muted mb-4">Disetujui oleh</div>
      <div style="border-top:1px solid #333;padding-top:4px" class="small">Direktur PT. Pesona Tamanindo</div>
    </div>
  </div>
  <div class="text-center text-muted" style="font-size:10px;border-top:1px dashed #ccc;padding-top:8px">
    Dicetak dari SIMPROKON EVM v<?= APP_VERSION ?> — <?= APP_NAME ?> — <?= date('d/m/Y H:i') ?> WIB
  </div>

</div><!-- /#printArea -->

<?php
// Persiapkan data chart — pastikan index numeric agar JSON valid
$labels = array_values(array_column($kurvaS, 'periode'));
$pvArr  = array_values(array_column($kurvaS, 'pv'));
$evArr  = array_values(array_column($kurvaS, 'ev'));
$acArr  = array_values(array_column($kurvaS, 'ac'));

$extraJs = '<script>
// FIX: Bungkus dalam IIFE agar variabel tidak bocor ke global scope
(function () {
  var _chartInst = null; // FIX: Simpan instance agar bisa di-destroy sebelum dibuat ulang

  function initLaporanChart() {
    var el = document.getElementById("laporanChart");
    if (!el) return;

    // FIX: Hapus chart lama jika sudah ada (mencegah double-render / canvas locked)
    if (_chartInst) {
      _chartInst.destroy();
      _chartInst = null;
    }

    _chartInst = new Chart(el, {
      type: "line",
      data: {
        labels: ' . json_encode($labels, JSON_UNESCAPED_UNICODE) . ',
        datasets: [
          {
            label: "Rencana (PV%)",
            data: ' . json_encode($pvArr) . ',
            borderColor: "#3b82f6", tension: 0.4, fill: false,
            pointRadius: 3, borderWidth: 2
          },
          {
            label: "Realisasi (EV%)",
            data: ' . json_encode($evArr) . ',
            borderColor: "#10b981", tension: 0.4, fill: false,
            pointRadius: 3, borderWidth: 2
          },
          {
            label: "Biaya Aktual (AC%)",
            data: ' . json_encode($acArr) . ',
            borderColor: "#f59e0b", borderDash: [5, 3], tension: 0.4, fill: false,
            pointRadius: 3, borderWidth: 1.5
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 400 }, // FIX: Batasi animasi agar tidak re-trigger resize loop
        plugins: {
          legend: { position: "bottom", labels: { font: { size: 10 } } }
        },
        scales: {
          y: {
            min: 0, max: 100,
            ticks: { callback: function (v) { return v + "%" }, font: { size: 9 } }
          },
          x: { ticks: { font: { size: 9 } } }
        }
      }
    });
  }

  // FIX: Gunakan DOMContentLoaded, bukan window.load, agar hanya dipanggil SEKALI
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initLaporanChart);
  } else {
    initLaporanChart();
  }
})();
</script>
<style>
/*
  FIX: Batasi tinggi tabel WBS di layar agar halaman tidak memanjang tanpa batas.
  Saat print (@media print), batasan dihapus supaya semua baris tercetak.
*/
.wbs-table-wrapper {
  overflow-x: auto;
  overflow-y: auto;
  max-height: 380px;
}

@media print {
  .topbar, .sidebar, #sidebarToggle, .btn, .dropdown,
  .d-flex.justify-content-between.mb-4,
  .alert, select, form { display: none !important; }

  #page-content    { padding: 0 !important; }
  body             { background: white !important; }
  #printArea       { box-shadow: none !important; }
  .table           { font-size: 9pt !important; }

  /* Hapus batasan scroll saat cetak — semua baris WBS harus muncul */
  .wbs-table-wrapper {
    max-height: none !important;
    overflow: visible !important;
  }
}
</style>';
?>

<?php endif; ?>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
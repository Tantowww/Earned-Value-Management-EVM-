<?php
// pages/dashboard.php
requireAuth();

// Ambil proyek aktif pertama (atau yang dipilih)
$proyekId = (int)($_GET['proyek_id'] ?? 0);
$allProyek = dbFetchAll("SELECT id, kode_proyek, nama_proyek FROM proyek ORDER BY status='aktif' DESC, created_at DESC");

if (!$proyekId && $allProyek) {
    $proyekId = (int)$allProyek[0]['id'];
}

$evm = $proyekId ? hitungEVM($proyekId) : [];
$kurvaS = $proyekId ? getKurvaS($proyekId) : [];

// Summary stats
$totalProyek   = dbFetchOne("SELECT COUNT(*) n FROM proyek")['n'] ?? 0;
$totalAktif    = dbFetchOne("SELECT COUNT(*) n FROM proyek WHERE status='aktif'")['n'] ?? 0;
$totalWBS      = $proyekId ? dbFetchOne("SELECT COUNT(*) n FROM wbs WHERE proyek_id=?", [$proyekId])['n'] : 0;
$totalMinggu   = $evm['proyek']['total_minggu'] ?? 0;
$currentMinggu = $evm['proyek']['minggu_ke'] ?? 0;

$pageTitle = 'Dashboard EVM';
include __DIR__ . '/../includes/layout/header.php';
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h4 class="fw-800 text-dark mb-0"><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard EVM</h4>
    <div class="text-muted small">Monitoring kinerja proyek berbasis Earned Value Management</div>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <form method="GET" class="d-flex gap-2">
      <input type="hidden" name="p" value="dashboard">
      <select name="proyek_id" class="form-select form-select-sm" style="min-width:260px" onchange="this.form.submit()">
        <?php foreach ($allProyek as $pr): ?>
        <option value="<?= $pr['id'] ?>" <?= $pr['id'] == $proyekId ? 'selected' : '' ?>>
          <?= e($pr['kode_proyek'] . ' — ' . mb_strimwidth($pr['nama_proyek'], 0, 50, '…')) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </form>
    <span class="badge bg-primary"><?= date('d M Y') ?></span>
  </div>
</div>

<?php if ($evm): ?>
<!-- ─── HERO BANNER ─── -->
<div class="card border-0 mb-4 overflow-hidden" style="background:linear-gradient(135deg, #08b54a, #10b981, #0d9488)">
  <div class="card-body p-4">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h6 class="text-white-50 text-uppercase fw-bold mb-1" style="letter-spacing:1px;font-size:11px">
          <i class="bi bi-buildings me-1"></i><?= e($evm['proyek']['nama_proyek']) ?>
        </h6>
        <div class="d-flex gap-4 mb-3 flex-wrap">
          <div>
            <div class="text-white-50 small">Rencana (PV)</div>
            <div class="text-white fw-800 fs-4"><?= formatPersen($evm['pv_pct']) ?></div>
          </div>
          <div>
            <div class="text-success-emphasis small">Realisasi (EV)</div>
            <div style="color:#6ee7b7" class="fw-800 fs-4"><?= formatPersen($evm['ev_pct']) ?></div>
          </div>
          <div>
            <div class="text-warning-emphasis small">Deviasi</div>
            <div style="color:<?= $evm['deviasi'] < 0 ? '#fca5a5' : '#6ee7b7' ?>" class="fw-800 fs-4">
              <?= ($evm['deviasi'] >= 0 ? '+' : '') . formatPersen($evm['deviasi']) ?>
            </div>
          </div>
          <div>
            <div class="text-white-50 small">Minggu Ke</div>
            <div style="color:#fde68a" class="fw-800 fs-4"><?= $currentMinggu ?>/<?= $totalMinggu ?></div>
          </div>
        </div>
        <!-- Progress bars -->
        <div class="mb-2">
          <div class="d-flex justify-content-between mb-1">
            <small class="text-white-50">Rencana Kumulatif (PV)</small>
            <small class="text-white"><?= formatPersen($evm['pv_pct']) ?></small>
          </div>
          <div class="progress" style="height:8px;background:rgba(255,255,255,0.15)">
            <div class="progress-bar" style="width:<?= min(100,$evm['pv_pct']) ?>%;background:#93c5fd"></div>
          </div>
        </div>
        <div>
          <div class="d-flex justify-content-between mb-1">
            <small class="text-white-50">Realisasi Kumulatif (EV)</small>
            <small class="text-white"><?= formatPersen($evm['ev_pct']) ?></small>
          </div>
          <div class="progress" style="height:8px;background:rgba(255,255,255,0.15)">
            <div class="progress-bar bg-success" style="width:<?= min(100,$evm['ev_pct']) ?>%"></div>
          </div>
        </div>
      </div>
      <div class="col-md-4 text-center d-none d-md-block">
        <div class="donut-ring mx-auto">
          <svg viewBox="0 0 120 120" class="donut-svg">
            <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="12"/>
            <circle cx="60" cy="60" r="50" fill="none" stroke="#6ee7b7" stroke-width="12"
              stroke-dasharray="<?= round($evm['ev_pct']/100*314.16, 1) ?> 314.16"
              stroke-dashoffset="78.54" stroke-linecap="round"/>
            <text x="60" y="55" text-anchor="middle" fill="white" font-size="18" font-weight="800">
              <?= round($evm['ev_pct'], 1) ?>%
            </text>
            <text x="60" y="72" text-anchor="middle" fill="rgba(255,255,255,0.6)" font-size="9">EV Progress</text>
          </svg>
        </div>
        <div class="text-white-50 small mt-2">
          Nilai Kontrak: <strong class="text-white"><?= formatMilliar($evm['bac']) ?></strong>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ─── KPI CARDS ─── -->
<div class="row g-3 mb-4">
  <?php
  $kpis = [
    ['SPI','Schedule Performance Index',number_format($evm['spi'],3),$evm['spi']>=1?'success':'danger',
     $evm['spi']>=1?'Sesuai Jadwal':'Terlambat','hourglass-split',
     'EV ÷ PV = '.formatMilliar($evm['ev']).' ÷ '.formatMilliar($evm['pv'])],
    ['CPI','Cost Performance Index',number_format($evm['cpi'],3),$evm['cpi']>=1?'success':($evm['cpi']>=0.9?'warning':'danger'),
     $evm['cpi']>=1?'Hemat Biaya':'Cost Overrun','cash-coin',
     'EV ÷ AC = '.formatMilliar($evm['ev']).' ÷ '.formatMilliar($evm['ac'])],
    ['SV','Schedule Variance',formatMilliar(abs($evm['sv'])).($evm['sv']<0?' (-)':''),$evm['sv']>=0?'success':'danger',
     $evm['sv']>=0?'Lebih Cepat':'Terlambat dari Rencana','calendar-x',
     'EV − PV'],
    ['CV','Cost Variance',formatMilliar(abs($evm['cv'])).($evm['cv']<0?' (-)':''),$evm['cv']>=0?'success':'danger',
     $evm['cv']>=0?'Di Bawah Anggaran':'Melebihi Anggaran','wallet2',
     'EV − AC'],
    ['EAC','Est. Biaya Akhir',formatMilliar($evm['eac']),$evm['eac']<=$evm['bac']?'success':'warning',
     'BAC: '.formatMilliar($evm['bac']),'bullseye',
     'BAC ÷ CPI'],
    ['TCPI','To-Complete Perf. Index',number_format($evm['tcpi'],3),$evm['tcpi']<=1.0?'success':'danger',
     $evm['tcpi']<=1?'Target Tercapai':'Perlu Efisiensi Tinggi','arrow-up-right-circle',
     '(BAC−EV) ÷ (BAC−AC)'],
  ];
  foreach ($kpis as [$sym,$label,$val,$col,$sub,$icon,$formula]):
  ?>
  <div class="col-md-4 col-6">
    <div class="card border-0 kpi-card h-100">
      <div class="card-body p-3">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <span class="badge-kpi"><?= $sym ?></span>
          <div class="icon-kpi text-<?= $col ?>"><i class="bi bi-<?= $icon ?>"></i></div>
        </div>
        <div class="kpi-value text-<?= $col ?>"><?= $val ?></div>
        <div class="kpi-label text-muted"><?= $label ?></div>
        <div class="kpi-sub text-<?= $col ?> mt-1"><?= $sub ?></div>
        <div class="text-muted mt-1" style="font-size:10px;font-style:italic"><?= $formula ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ─── CHARTS ROW ─── -->
<div class="row g-3 mb-4">
  <!-- S-Curve Chart -->
  <div class="col-md-8">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-0 pb-0">
        <h6 class="fw-700 mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Kurva-S (PV / EV / AC)</h6>
        <small class="text-muted">Kumulatif % terhadap BAC per periode monitoring</small>
      </div>
      <div class="card-body pt-2">
        <canvas id="sCurveChart" height="280"></canvas>
      </div>
    </div>
  </div>
  <!-- EVM Gauge -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-0 pb-0">
        <h6 class="fw-700 mb-0"><i class="bi bi-bar-chart me-2 text-primary"></i>Perbandingan EVM</h6>
        <small class="text-muted">PV vs EV vs AC saat ini</small>
      </div>
      <div class="card-body pt-2">
        <canvas id="evmBarChart" height="280"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- ─── WBS PROGRESS TABLE ─── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
    <h6 class="fw-700 mb-0"><i class="bi bi-diagram-3 me-2 text-primary"></i>Progress WBS — <?= count($evm['wbs_detail']) ?> Paket Pekerjaan</h6>
    <a href="index.php?p=wbs&proyek_id=<?= $proyekId ?>" class="btn btn-sm btn-outline-primary">
      <i class="bi bi-arrow-right me-1"></i>Detail WBS
    </a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle small">
        <thead class="table-dark">
          <tr>
            <th style="width:70px">Kode</th>
            <th>Uraian Pekerjaan</th>
            <th class="text-end">Anggaran (BAC)</th>
            <th class="text-center">Bobot%</th>
            <th class="text-center" style="min-width:130px">Rencana (PV%)</th>
            <th class="text-center" style="min-width:130px">Realisasi (EV%)</th>
            <th class="text-center">SPI</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($evm['wbs_detail'] as $w): ?>
          <tr>
            <td><span class="badge bg-dark"><?= e($w['kode']) ?></span></td>
            <td><?= e(mb_strimwidth($w['uraian'], 0, 60, '…')) ?></td>
            <td class="text-end fw-600"><?= formatMilliar($w['anggaran']) ?></td>
            <td class="text-center"><?= formatPersen($w['bobot']) ?></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1" style="height:6px">
                  <div class="progress-bar bg-primary" style="width:<?= min(100,$w['pv_pct']) ?>%"></div>
                </div>
                <span class="text-muted" style="width:40px"><?= round($w['pv_pct'],1) ?>%</span>
              </div>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1" style="height:6px">
                  <div class="progress-bar <?= $w['ev_pct'] >= $w['pv_pct'] ? 'bg-success' : 'bg-warning' ?>"
                       style="width:<?= min(100,$w['ev_pct']) ?>%"></div>
                </div>
                <span class="text-muted" style="width:40px"><?= round($w['ev_pct'],1) ?>%</span>
              </div>
            </td>
            <td class="text-center">
              <span class="fw-bold <?= spiColor($w['spi']) ?>"><?= number_format($w['spi'],3) ?></span>
            </td>
            <td class="text-center"><?= badgeStatus($w['status']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-dark fw-bold">
          <tr>
            <td colspan="2">TOTAL PROYEK</td>
            <td class="text-end"><?= formatMilliar($evm['bac']) ?></td>
            <td class="text-center">100%</td>
            <td class="text-center"><?= formatPersen($evm['pv_pct']) ?></td>
            <td class="text-center"><?= formatPersen($evm['ev_pct']) ?></td>
            <td class="text-center <?= spiColor($evm['spi']) ?>"><?= number_format($evm['spi'],3) ?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

<?php else: ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Pilih proyek untuk melihat dashboard EVM.</div>
<?php endif; ?>

<!-- Info Cards Bottom -->
<div class="row g-3">
  <?php foreach ([
    ['bi-buildings','text-primary','bg-primary-subtle',$totalProyek,'Total Proyek'],
    ['bi-check-circle','text-success','bg-success-subtle',$totalAktif,'Proyek Aktif'],
    ['bi-diagram-3','text-warning','bg-warning-subtle',$totalWBS,'Item WBS'],
    ['bi-calendar-week','text-info','bg-info-subtle',$currentMinggu.'/'.$totalMinggu,'Minggu Proyek'],
  ] as [$ico,$tc,$bg,$val,$lbl]): ?>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-3 p-2 <?= $bg ?>"><i class="bi <?= $ico ?> fs-4 <?= $tc ?>"></i></div>
        <div><div class="fw-800 fs-5"><?= $val ?></div><div class="text-muted small"><?= $lbl ?></div></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php
// Build chart data JSON
$chartLabels = array_column($kurvaS, 'periode');
$chartPV     = array_column($kurvaS, 'pv');
$chartEV     = array_column($kurvaS, 'ev');
$chartAC     = array_column($kurvaS, 'ac');

$extraJs = '<script>
(function(){
  // S-Curve
  const ctx = document.getElementById("sCurveChart");
  if(ctx){
    new Chart(ctx, {
      type: "line",
      data: {
        labels: ' . json_encode($chartLabels) . ',
        datasets: [
          {label:"Rencana (PV%)",data:' . json_encode($chartPV) . ',borderColor:"#3b82f6",backgroundColor:"rgba(59,130,246,0.1)",fill:true,tension:0.4,pointRadius:4,borderWidth:2.5},
          {label:"Realisasi (EV%)",data:' . json_encode($chartEV) . ',borderColor:"#10b981",backgroundColor:"rgba(16,185,129,0.1)",fill:true,tension:0.4,pointRadius:4,borderWidth:2.5},
          {label:"Biaya Aktual (AC%)",data:' . json_encode($chartAC) . ',borderColor:"#f59e0b",backgroundColor:"rgba(245,158,11,0.05)",fill:false,tension:0.4,pointRadius:3,borderWidth:2,borderDash:[6,3]},
        ]
      },
      options:{
        responsive:true,maintainAspectRatio:false,
        plugins:{legend:{position:"bottom",labels:{font:{size:11}}},
          tooltip:{callbacks:{label:function(c){return c.dataset.label+": "+c.parsed.y.toFixed(2)+"%";}}}},
        scales:{
          y:{min:0,max:100,ticks:{callback:v=>v+"%",font:{size:11}},grid:{color:"#f1f5f9"}},
          x:{ticks:{font:{size:10}},grid:{display:false}}
        }
      }
    });
  }
  // Bar Chart
  const ctx2 = document.getElementById("evmBarChart");
  if(ctx2){
    new Chart(ctx2, {
      type:"bar",
      data:{
        labels:["PV","EV","AC"],
        datasets:[{
          label:"Nilai (Rp Miliar)",
          data:[' . round($evm['pv']/1e9,2) . ',' . round($evm['ev']/1e9,2) . ',' . round($evm['ac']/1e9,2) . '],
          backgroundColor:["rgba(59,130,246,0.8)","rgba(16,185,129,0.8)","rgba(245,158,11,0.8)"],
          borderRadius:8,borderSkipped:false
        }]
      },
      options:{
        responsive:true,maintainAspectRatio:false,
        plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return "Rp "+c.parsed.y.toFixed(2)+" M";}}}},
        scales:{y:{ticks:{callback:v=>"Rp "+v+"M",font:{size:10}},grid:{color:"#f1f5f9"}},x:{grid:{display:false}}}
      }
    });
  }
})();
</script>';

include __DIR__ . '/../includes/layout/footer.php';

<?php
// pages/evm/index.php
requireRole(['admin','manager']);

$proyekId  = (int)($_GET['proyek_id'] ?? 0);
$allProyek = dbFetchAll("SELECT id,kode_proyek,nama_proyek FROM proyek ORDER BY created_at DESC");
if (!$proyekId && $allProyek) $proyekId = $allProyek[0]['id'];

$proyek    = $proyekId ? dbFetchOne("SELECT * FROM proyek WHERE id=?",[$proyekId]) : null;
$mingguKe  = (int)($_GET['minggu'] ?? ($proyek['minggu_ke'] ?? 0));
$evm       = $proyekId ? hitungEVM($proyekId, $mingguKe) : [];
$kurvaS    = $proyekId ? getKurvaS($proyekId) : [];

// Tabel EVM historis per periode
$periodeList = $proyekId ? dbFetchAll(
    "SELECT DISTINCT pf.minggu_ke, MIN(pf.periode) as tgl
     FROM progres_fisik pf JOIN wbs w ON w.id=pf.wbs_id
     WHERE w.proyek_id=? GROUP BY pf.minggu_ke ORDER BY pf.minggu_ke ASC",
    [$proyekId]
) : [];

$evmHistoris = [];
foreach ($periodeList as $p) {
    $h = hitungEVM($proyekId, (int)$p['minggu_ke']);
    $evmHistoris[] = [
        'minggu_ke' => $p['minggu_ke'],
        'periode'   => date('d/m/Y', strtotime($p['tgl'])),
        'pv'        => $h['pv'], 'ev' => $h['ev'], 'ac' => $h['ac'],
        'sv'        => $h['sv'], 'cv' => $h['cv'],
        'spi'       => $h['spi'], 'cpi' => $h['cpi'],
        'eac'       => $h['eac'],
    ];
}

$pageTitle = 'Analisis EVM';
include __DIR__ . '/../../includes/layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-800 mb-0"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Analisis Earned Value Management</h4>
    <div class="text-muted small">Evaluasi kinerja jadwal dan biaya proyek secara komprehensif</div>
  </div>
  <div class="d-flex gap-2">
    <form method="GET" class="d-flex gap-2">
      <input type="hidden" name="p" value="evm">
      <select name="proyek_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:240px">
        <?php foreach ($allProyek as $pr): ?>
        <option value="<?= $pr['id'] ?>" <?= $pr['id']==$proyekId?'selected':'' ?>>
          <?= e($pr['kode_proyek'].' — '.mb_strimwidth($pr['nama_proyek'],0,40,'…')) ?>
        </option>
        <?php endforeach; ?>
      </select>
      <select name="minggu" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:140px">
        <?php foreach ($periodeList as $p): ?>
        <option value="<?= $p['minggu_ke'] ?>" <?= $mingguKe==$p['minggu_ke']?'selected':'' ?>>
          Minggu <?= $p['minggu_ke'] ?>
        </option>
        <?php endforeach; ?>
      </select>
    </form>
    <a href="index.php?p=laporan&proyek_id=<?= $proyekId ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-printer me-1"></i>Cetak Laporan
    </a>
  </div>
</div>

<?php if ($evm): ?>

<!-- ─── EVM PARAMETER TABLE ─── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-0">
    <h6 class="fw-700 mb-0">
      <i class="bi bi-table me-2 text-primary"></i>
      Parameter EVM — Minggu Ke-<?= $mingguKe ?> | <?= e($proyek['nama_proyek']) ?>
    </h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
          <tr>
            <th>Parameter</th><th>Simbol</th><th>Rumus</th>
            <th class="text-end">Nilai (Rp)</th><th class="text-center">Nilai (%)</th>
            <th class="text-center">Status</th><th>Interpretasi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $bac = $evm['bac'];
          $rows = [
            ['Planned Value',              'PV',   'Bobot Rencana × BAC',    $evm['pv'],  $evm['pv_pct'],  null,              'Nilai pekerjaan yang seharusnya selesai sesuai jadwal baseline'],
            ['Earned Value',               'EV',   '% Realisasi × BAC',      $evm['ev'],  $evm['ev_pct'],  null,              'Nilai pekerjaan yang benar-benar telah diselesaikan'],
            ['Actual Cost',                'AC',   'Σ Biaya Aktual',          $evm['ac'],  $evm['ac_pct'],  null,              'Total biaya aktual yang telah dikeluarkan hingga periode ini'],
            ['Schedule Variance',          'SV',   'EV − PV',                 $evm['sv'],  null,            $evm['sv']>=0,    $evm['sv']>=0?'Lebih cepat dari jadwal rencana':'Terlambat dari jadwal rencana'],
            ['Cost Variance',              'CV',   'EV − AC',                 $evm['cv'],  null,            $evm['cv']>=0,    $evm['cv']>=0?'Biaya di bawah anggaran (efisien)':'Biaya melebihi anggaran (overrun)'],
            ['Schedule Perf. Index',       'SPI',  'EV ÷ PV',                 $evm['spi'], null,            $evm['spi']>=1.0, $evm['spi']>=1?'Produktivitas sesuai/melebihi rencana':'Produktivitas di bawah rencana'],
            ['Cost Perf. Index',           'CPI',  'EV ÷ AC',                 $evm['cpi'], null,            $evm['cpi']>=1.0, $evm['cpi']>=1?'Setiap Rp1 menghasilkan lebih dari Rp1 nilai':'Setiap Rp1 menghasilkan kurang dari Rp1 nilai'],
            ['Estimate at Completion',     'EAC',  'BAC ÷ CPI',               $evm['eac'], null,            $evm['eac']<=$bac,'Estimasi total biaya yang diperlukan untuk menyelesaikan proyek'],
            ['Estimate to Complete',       'ETC',  'EAC − AC',                $evm['etc'], null,            null,             'Perkiraan biaya yang masih dibutuhkan untuk menyelesaikan proyek'],
            ['Variance at Completion',     'VAC',  'BAC − EAC',               $evm['vac'], null,            $evm['vac']>=0,   $evm['vac']>=0?'Prediksi proyek under budget':'Prediksi proyek akan cost overrun'],
            ['To-Complete Perf. Index',    'TCPI', '(BAC−EV)÷(BAC−AC)',       $evm['tcpi'],null,            $evm['tcpi']<=1.0,$evm['tcpi']<=1?'Target biaya masih dapat dicapai':'Diperlukan efisiensi tinggi untuk mencapai target'],
          ];
          $isIdx = ['SPI','CPI','TCPI'];
          foreach ($rows as [$lbl,$sym,$rumus,$val,$pct,$ok,$interp]):
            $isIndex = in_array($sym, $isIdx);
          ?>
          <tr>
            <td class="fw-600"><?= $lbl ?></td>
            <td><span class="badge bg-dark fs-6 px-3"><?= $sym ?></span></td>
            <td class="text-muted fst-italic small"><?= $rumus ?></td>
            <td class="text-end fw-bold <?= $ok===null?'text-primary':($ok?'text-success':'text-danger') ?>">
              <?= $isIndex ? number_format($val,4) : formatRupiah($val) ?>
            </td>
            <td class="text-center text-muted small"><?= $pct!==null ? formatPersen($pct) : '—' ?></td>
            <td class="text-center">
              <?php
              if ($ok === null) echo '—';
              elseif ($ok) echo '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Baik</span>';
              else echo '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Perlu Perhatian</span>';
              ?>
            </td>
            <td class="small text-muted"><?= $interp ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ─── CHARTS ─── -->
<div class="row g-3 mb-4">
  <div class="col-md-8">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-0">
        <h6 class="fw-700 mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Kurva-S — Tren Kumulatif PV / EV / AC</h6>
      </div>
      <div class="card-body">
        <canvas id="sCurveEVM" height="280"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white border-0">
        <h6 class="fw-700 mb-0"><i class="bi bi-speedometer me-2 text-primary"></i>SPI & CPI Tren</h6>
      </div>
      <div class="card-body">
        <canvas id="indexChart" height="280"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- ─── WBS EVM DETAIL ─── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-0">
    <h6 class="fw-700 mb-0"><i class="bi bi-diagram-3 me-2 text-primary"></i>Analisis EVM per Paket WBS</h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle small mb-0">
        <thead class="table-dark">
          <tr>
            <th>Kode</th><th>Uraian</th>
            <th class="text-end">BAC</th>
            <th class="text-center">PV%</th><th class="text-center">EV%</th>
            <th class="text-end">EV (Rp)</th><th class="text-end">AC (Rp)</th>
            <th class="text-end">CV (Rp)</th>
            <th class="text-center">SPI</th><th class="text-center">CPI</th>
            <th class="text-center">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($evm['wbs_detail'] as $w): ?>
          <tr>
            <td><span class="badge bg-dark"><?= e($w['kode']) ?></span></td>
            <td style="max-width:200px"><?= e(mb_strimwidth($w['uraian'],0,50,'…')) ?></td>
            <td class="text-end"><?= formatMilliar($w['anggaran']) ?></td>
            <td class="text-center"><?= round($w['pv_pct'],1) ?>%</td>
            <td class="text-center">
              <span class="fw-bold <?= $w['ev_pct']>=$w['pv_pct']?'text-success':'text-warning' ?>">
                <?= round($w['ev_pct'],1) ?>%
              </span>
            </td>
            <td class="text-end text-success fw-600"><?= formatMilliar($w['ev']) ?></td>
            <td class="text-end text-warning fw-600"><?= formatMilliar($w['ac']) ?></td>
            <td class="text-end fw-bold <?= $w['cv']>=0?'text-success':'text-danger' ?>">
              <?= ($w['cv']>=0?'+':'').formatMilliar($w['cv']) ?>
            </td>
            <td class="text-center">
              <span class="badge <?= $w['spi']>=1?'bg-success':($w['spi']>=0.9?'bg-warning text-dark':'bg-danger') ?>">
                <?= number_format($w['spi'],3) ?>
              </span>
            </td>
            <td class="text-center">
              <span class="badge <?= $w['cpi']>=1?'bg-success':($w['cpi']>=0.9?'bg-warning text-dark':'bg-danger') ?>">
                <?= number_format($w['cpi'],3) ?>
              </span>
            </td>
            <td class="text-center"><?= badgeStatus($w['status']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-dark fw-bold">
          <tr>
            <td colspan="2">TOTAL PROYEK</td>
            <td class="text-end"><?= formatMilliar($evm['bac']) ?></td>
            <td class="text-center"><?= round($evm['pv_pct'],2) ?>%</td>
            <td class="text-center"><?= round($evm['ev_pct'],2) ?>%</td>
            <td class="text-end"><?= formatMilliar($evm['ev']) ?></td>
            <td class="text-end"><?= formatMilliar($evm['ac']) ?></td>
            <td class="text-end <?= $evm['cv']>=0?'text-success':'text-danger' ?>">
              <?= ($evm['cv']>=0?'+':'').formatMilliar($evm['cv']) ?>
            </td>
            <td class="text-center <?= spiColor($evm['spi']) ?>"><?= number_format($evm['spi'],3) ?></td>
            <td class="text-center <?= spiColor($evm['cpi']) ?>"><?= number_format($evm['cpi'],3) ?></td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

<!-- ─── HISTORIS TABLE ─── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-0">
    <h6 class="fw-700 mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Tabel EVM Historis per Periode</h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle small mb-0">
        <thead class="table-dark">
          <tr>
            <th>Minggu</th><th>Periode</th>
            <th class="text-end">PV (Rp)</th><th class="text-end">EV (Rp)</th><th class="text-end">AC (Rp)</th>
            <th class="text-end">SV (Rp)</th><th class="text-end">CV (Rp)</th>
            <th class="text-center">SPI</th><th class="text-center">CPI</th>
            <th class="text-end">EAC (Rp)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_reverse($evmHistoris) as $h): ?>
          <tr <?= $h['minggu_ke']==$mingguKe?'class="table-primary fw-bold"':'' ?>>
            <td><?= $h['minggu_ke'] ?></td>
            <td><?= $h['periode'] ?></td>
            <td class="text-end"><?= formatMilliar($h['pv']) ?></td>
            <td class="text-end"><?= formatMilliar($h['ev']) ?></td>
            <td class="text-end"><?= formatMilliar($h['ac']) ?></td>
            <td class="text-end <?= $h['sv']>=0?'text-success':'text-danger' ?>"><?= ($h['sv']>=0?'+':'').formatMilliar($h['sv']) ?></td>
            <td class="text-end <?= $h['cv']>=0?'text-success':'text-danger' ?>"><?= ($h['cv']>=0?'+':'').formatMilliar($h['cv']) ?></td>
            <td class="text-center"><span class="badge <?= $h['spi']>=1?'bg-success':($h['spi']>=0.9?'bg-warning text-dark':'bg-danger') ?>"><?= number_format($h['spi'],3) ?></span></td>
            <td class="text-center"><span class="badge <?= $h['cpi']>=1?'bg-success':($h['cpi']>=0.9?'bg-warning text-dark':'bg-danger') ?>"><?= number_format($h['cpi'],3) ?></span></td>
            <td class="text-end"><?= formatMilliar($h['eac']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php else: ?>
<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Pilih proyek untuk melihat analisis EVM.</div>
<?php endif; ?>

<?php
// Chart data
$labels = array_column($kurvaS,'periode');
$pvArr  = array_column($kurvaS,'pv');
$evArr  = array_column($kurvaS,'ev');
$acArr  = array_column($kurvaS,'ac');
$spiArr = array_map(fn($h)=>$h['spi'], $evmHistoris);
$cpiArr = array_map(fn($h)=>$h['cpi'], $evmHistoris);
$hLabels= array_map(fn($h)=>'Mgg '.$h['minggu_ke'], $evmHistoris);

$extraJs = '<script>
(function(){
  const ctx = document.getElementById("sCurveEVM");
  if(ctx){
    new Chart(ctx,{
      type:"line",
      data:{
        labels:'.json_encode($labels).',
        datasets:[
          {label:"PV%",data:'.json_encode($pvArr).',borderColor:"#3b82f6",backgroundColor:"rgba(59,130,246,0.08)",fill:true,tension:0.4,pointRadius:4,borderWidth:2.5},
          {label:"EV%",data:'.json_encode($evArr).',borderColor:"#10b981",backgroundColor:"rgba(16,185,129,0.08)",fill:true,tension:0.4,pointRadius:4,borderWidth:2.5},
          {label:"AC%",data:'.json_encode($acArr).',borderColor:"#f59e0b",borderDash:[6,3],fill:false,tension:0.4,pointRadius:3,borderWidth:2},
        ]
      },
      options:{responsive:true,maintainAspectRatio:false,
        plugins:{legend:{position:"bottom",labels:{font:{size:11}}}},
        scales:{y:{min:0,max:100,ticks:{callback:v=>v+"%"}},x:{ticks:{font:{size:10}}}}}
    });
  }
  const ctx2 = document.getElementById("indexChart");
  if(ctx2){
    new Chart(ctx2,{
      type:"line",
      data:{
        labels:'.json_encode($hLabels).',
        datasets:[
          {label:"SPI",data:'.json_encode($spiArr).',borderColor:"#6366f1",tension:0.3,pointRadius:4,borderWidth:2.5},
          {label:"CPI",data:'.json_encode($cpiArr).',borderColor:"#f59e0b",tension:0.3,pointRadius:4,borderWidth:2.5},
        ]
      },
      options:{responsive:true,maintainAspectRatio:false,
        plugins:{legend:{position:"bottom"},
          annotation:{annotations:{line1:{type:"line",yMin:1,yMax:1,borderColor:"#10b981",borderWidth:1.5,borderDash:[4,4]}}}},
        scales:{y:{min:0,max:1.5,ticks:{stepSize:0.25}},x:{ticks:{font:{size:9},maxRotation:45}}}
      }
    });
  }
})();
</script>';

include __DIR__ . '/../../includes/layout/footer.php';
?>

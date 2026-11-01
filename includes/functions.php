<?php
// ============================================================
// HELPER FUNCTIONS + EVM CALCULATOR
// ============================================================

require_once dirname(__DIR__) . '/config.php';

function dbExecute(string $sql, array $params = []): bool {
    try {
        return dbQuery($sql, $params)->rowCount() >= 0;
    } catch (PDOException $e) {
        error_log('dbExecute error: ' . $e->getMessage());
        return false;
    }
}

// function getDB(): PDO {
//     static $pdo = null;  // ← static agar koneksi hanya dibuat sekali

//     if ($pdo === null) {
//         $host    = 'localhost';
//         $db      = 'evm_db';   // sesuaikan nama database-mu
//         $user    = 'root';         // sesuaikan
//         $pass    = '';             // sesuaikan
//         $charset = 'utf8mb4';

//         try {
//             $pdo = new PDO(
//                 "mysql:host=$host;dbname=$db;charset=$charset",
//                 $user,
//                 $pass,
//                 [
//                     PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
//                     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//                     PDO::ATTR_EMULATE_PREPARES   => false,
//                 ]
//             );
//         } catch (PDOException $e) {
//             die('Koneksi database gagal: ' . $e->getMessage());
//         }
//     }

//     return $pdo;
// }

// ── Auth Helpers ─────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): array {
    return $_SESSION['user'] ?? [];
}

function hasRole(array $roles): bool {
    $userRole = $_SESSION['user']['role'] ?? '';
    if (is_string($roles)) $roles = [$roles];
    return in_array($userRole, $roles);
}

function requireAuth(): void {
    if (!isLoggedIn()) {
        redirect('index.php?p=login');
    }
}

function requireRole(array $roles): void {
    requireAuth();
    if (!hasRole($roles)) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Akses ditolak. Anda tidak memiliki hak untuk halaman ini.'];
        redirect('index.php?p=dashboard');
    }
}

// ── Redirect & Flash ─────────────────────────────────────────
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ── Sanitize & Validate ──────────────────────────────────────
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function sanitize(string $s): string {
    return trim(strip_tags($s));
}

function formatRupiah(float $n): string {
    return 'Rp ' . number_format($n, 0, ',', '.');
}

function formatPersen(float $n, int $dec = 2): string {
    return number_format($n, $dec, ',', '.') . '%';
}

function formatMilliar(float $n): string {
    if ($n >= 1e9) return 'Rp ' . number_format($n / 1e9, 2, ',', '.') . ' M';
    if ($n >= 1e6) return 'Rp ' . number_format($n / 1e6, 2, ',', '.') . ' Jt';
    return formatRupiah($n);
}

// ── CRUD Helpers ─────────────────────────────────────────────
function dbQuery(string $sql, array $params = []): \PDOStatement {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function dbFetchAll(string $sql, array $params = []): array {
    return dbQuery($sql, $params)->fetchAll();
}

function dbFetchOne(string $sql, array $params = []) {
    $result = dbQuery($sql, $params)->fetch();
    return $result !== false ? $result : false;
}

function dbInsert(string $table, array $data): int {
    $cols = implode(',', array_keys($data));
    $vals = implode(',', array_fill(0, count($data), '?'));
    dbQuery("INSERT INTO $table ($cols) VALUES ($vals)", array_values($data));
    return (int) getDB()->lastInsertId();
}

function dbUpdate(string $table, array $data, string $where, array $wParams = []): void {
    $set = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
    dbQuery("UPDATE $table SET $set WHERE $where", [...array_values($data), ...$wParams]);
}

function dbDelete(string $table, string $where, array $params = []): void {
    dbQuery("DELETE FROM $table WHERE $where", $params);
}

// ═══════════════════════════════════════════════════════════════
//  EVM CALCULATOR
// ═══════════════════════════════════════════════════════════════

/**
 * Ambil semua WBS dari sebuah proyek
 */
function getWBSByProyek(int $proyekId): array {
    return dbFetchAll("SELECT * FROM wbs WHERE proyek_id = ? ORDER BY urutan ASC", [$proyekId]);
}

/**
 * Hitung kumulatif progres fisik (EV%) untuk satu WBS sampai periode/minggu tertentu
 */
function getProgressKumulatif(int $wbsId, int $mingguKe): float {
    $row = dbFetchOne(
        "SELECT persentase_kumulatif FROM progres_fisik
         WHERE wbs_id = ? AND minggu_ke <= ?
         ORDER BY minggu_ke DESC LIMIT 1",
        [$wbsId, $mingguKe]
    );
    return $row ? (float)$row['persentase_kumulatif'] : 0.0;
}

/**
 * Hitung kumulatif baseline (PV%) untuk satu WBS sampai periode/minggu tertentu
 */
function getBaselineKumulatif(int $wbsId, int $mingguKe): float {
    $row = dbFetchOne(
        "SELECT persentase_kumulatif FROM jadwal_baseline
         WHERE wbs_id = ? AND minggu_ke <= ?
         ORDER BY minggu_ke DESC LIMIT 1",
        [$wbsId, $mingguKe]
    );
    return $row ? (float)$row['persentase_kumulatif'] : 0.0;
}

/**
 * Hitung kumulatif biaya aktual (AC) untuk satu WBS sampai periode/minggu tertentu
 */
function getBiayaKumulatif(int $wbsId, int $mingguKe): float {
    $row = dbFetchOne(
        "SELECT SUM(jumlah_biaya) AS total FROM biaya_aktual
         WHERE wbs_id = ? AND minggu_ke <= ?",
        [$wbsId, $mingguKe]
    );
    return $row ? (float)$row['total'] : 0.0;
}

/**
 * Hitung EVM lengkap untuk suatu proyek pada minggu tertentu
 * Return: [pv, ev, ac, sv, cv, spi, cpi, eac, etc, vac, tcpi, bac, pct_complete, wbs_detail]
 */
function hitungEVM(int $proyekId, int $mingguKe = null): array {
    $proyek = dbFetchOne("SELECT * FROM proyek WHERE id = ?", [$proyekId]);
    if (!$proyek) return [];

    $bac = (float)$proyek['nilai_kontrak'];
    if ($mingguKe === null) $mingguKe = (int)$proyek['minggu_ke'];

    $wbsList  = getWBSByProyek($proyekId);
    $pv = $ev = $ac = 0.0;
    $wbsDetail = [];

    foreach ($wbsList as $wbs) {
        $wid     = (int)$wbs['id'];
        $anggaran = (float)$wbs['anggaran'];

        $pvPct = getBaselineKumulatif($wid, $mingguKe);
        $evPct = getProgressKumulatif($wid, $mingguKe);
        $acWbs = getBiayaKumulatif($wid, $mingguKe);

        $pvWbs = ($pvPct / 100) * $anggaran;
        $evWbs = ($evPct / 100) * $anggaran;

        $pv += $pvWbs;
        $ev += $evWbs;
        $ac += $acWbs;

        $spiWbs = $pvWbs > 0 ? round($evWbs / $pvWbs, 4) : 0;
        $cpiWbs = $acWbs > 0 ? round($evWbs / $acWbs, 4) : 0;
        $cvWbs  = $evWbs - $acWbs;
        $svWbs  = $evWbs - $pvWbs;

        $wbsDetail[] = [
            'id'       => $wid,
            'kode'     => $wbs['kode_wbs'],
            'uraian'   => $wbs['uraian'],
            'bobot'    => (float)$wbs['bobot'],
            'anggaran' => $anggaran,
            'pv_pct'   => $pvPct,
            'ev_pct'   => $evPct,
            'pv'       => $pvWbs,
            'ev'       => $evWbs,
            'ac'       => $acWbs,
            'sv'       => $svWbs,
            'cv'       => $cvWbs,
            'spi'      => $spiWbs,
            'cpi'      => $cpiWbs,
            'status'   => $evPct >= 99 ? 'selesai' : ($evPct >= $pvPct - 5 ? 'normal' : ($evPct >= $pvPct - 15 ? 'terlambat' : 'kritis')),
        ];
    }

    $sv   = $ev - $pv;
    $cv   = $ev - $ac;
    $spi  = $pv  > 0 ? round($ev / $pv,  4) : 0;
    $cpi  = $ac  > 0 ? round($ev / $ac,  4) : 0;
    $eac  = $cpi > 0 ? $bac / $cpi : $bac;
    $etc  = $eac - $ac;
    $vac  = $bac - $eac;
    $tcpi = ($bac - $ev) > 0 && ($bac - $ac) > 0 ? round(($bac - $ev) / ($bac - $ac), 4) : 0;

    $pvPct = $bac > 0 ? round($pv / $bac * 100, 4) : 0;
    $evPct = $bac > 0 ? round($ev / $bac * 100, 4) : 0;
    $acPct = $bac > 0 ? round($ac / $bac * 100, 4) : 0;

    return [
        'proyek'       => $proyek,
        'bac'          => $bac,
        'minggu_ke'    => $mingguKe,
        'pv'           => $pv,   'pv_pct' => $pvPct,
        'ev'           => $ev,   'ev_pct' => $evPct,
        'ac'           => $ac,   'ac_pct' => $acPct,
        'sv'           => $sv,   'cv'     => $cv,
        'spi'          => $spi,  'cpi'    => $cpi,
        'eac'          => $eac,  'etc'    => $etc,
        'vac'          => $vac,  'tcpi'   => $tcpi,
        'deviasi'      => $evPct - $pvPct,
        'wbs_detail'   => $wbsDetail,
    ];
}

/**
 * Ambil data historis untuk kurva-S (semua periode yg ada di baseline)
 */
function getKurvaS(int $proyekId): array {
    $proyek   = dbFetchOne("SELECT * FROM proyek WHERE id = ?", [$proyekId]);
    if (!$proyek) return [];

    $bac     = (float)$proyek['nilai_kontrak'];
    $wbsList = getWBSByProyek($proyekId);

    // Ambil semua periode unik dari baseline
    $periodes = dbFetchAll(
        "SELECT DISTINCT minggu_ke, periode FROM jadwal_baseline
         WHERE wbs_id IN (SELECT id FROM wbs WHERE proyek_id = ?)
         ORDER BY minggu_ke ASC",
        [$proyekId]
    );

    $result = [];
    foreach ($periodes as $p) {
        $minggu = (int)$p['minggu_ke'];
        $pv = $ev = $ac = 0.0;

        foreach ($wbsList as $wbs) {
            $wid     = (int)$wbs['id'];
            $ang     = (float)$wbs['anggaran'];
            $pvPct   = getBaselineKumulatif($wid, $minggu);
            $evPct   = getProgressKumulatif($wid, $minggu);
            $acVal   = getBiayaKumulatif($wid, $minggu);
            $pv     += ($pvPct / 100) * $ang;
            $ev     += ($evPct / 100) * $ang;
            $ac     += $acVal;
        }

        $result[] = [
            'minggu_ke' => $minggu,
            'label'     => 'Mgg ' . $minggu,
            'periode'   => date('M \'y', strtotime($p['periode'])),
            'pv'        => round($pv / $bac * 100, 3),
            'ev'        => round($ev / $bac * 100, 3),
            'ac'        => round($ac / $bac * 100, 3),
        ];
    }
    return $result;
}

/**
 * Daftar proyek + EVM ringkasan
 */
function getDaftarProyek(): array {
    $projects = dbFetchAll("SELECT * FROM proyek ORDER BY created_at DESC");
    foreach ($projects as &$p) {
        $evm = hitungEVM((int)$p['id']);
        $p['spi']     = $evm['spi']    ?? 0;
        $p['cpi']     = $evm['cpi']    ?? 0;
        $p['ev_pct']  = $evm['ev_pct'] ?? 0;
        $p['pv_pct']  = $evm['pv_pct'] ?? 0;
        $p['deviasi'] = $evm['deviasi'] ?? 0;
    }
    return $projects;
}

// ── Status & Label helpers ───────────────────────────────────
function badgeStatus(string $status): string {
    $map = [
        'aktif'       => ['success', 'Aktif'],
        'selesai'     => ['secondary', 'Selesai'],
        'ditangguhkan'=> ['warning', 'Ditangguhkan'],
        'normal'      => ['success', 'Normal'],
        'terlambat'   => ['warning', 'Terlambat'],
        'kritis'      => ['danger', 'Kritis'],
    ];
    [$cls, $lbl] = $map[$status] ?? ['secondary', $status];
    return "<span class=\"badge bg-$cls\">$lbl</span>";
}

function spiColor(float $v): string {
    if ($v >= 1.0) return 'text-success';
    if ($v >= 0.9) return 'text-warning';
    return 'text-danger';
}

function roleLabel(string $role): string {
    $map = [
        'admin'          => 'Administrator',
        'manager'        => 'Manajer Proyek',
        'staf_lapangan'  => 'Staf Lapangan',
        'staf_keuangan'  => 'Staf Keuangan',
    ];
    return $map[$role] ?? $role;
}

function roleBadge(string $role): string {
    $map = [
        'admin'          => 'dark',
        'manager'        => 'primary',
        'staf_lapangan'  => 'success',
        'staf_keuangan'  => 'info',
    ];
    $cls = $map[$role] ?? 'secondary';
    return '<span class="badge bg-' . $cls . '">' . e(roleLabel($role)) . '</span>';
}

// ── Pagination ───────────────────────────────────────────────
function paginate(string $sql, array $params, int $page, int $perPage = 15): array {
    $total = dbFetchOne("SELECT COUNT(*) AS n FROM ($sql) t", $params)['n'] ?? 0;
    $offset = ($page - 1) * $perPage;
    $rows = dbFetchAll("$sql LIMIT $perPage OFFSET $offset", $params);
    return [
        'rows'      => $rows,
        'total'     => $total,
        'page'      => $page,
        'per_page'  => $perPage,
        'last_page' => max(1, (int) ceil($total / $perPage)),
    ];
}

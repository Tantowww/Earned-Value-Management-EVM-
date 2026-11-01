<?php
// ============================================================
// KONFIGURASI DATABASE
// Sesuaikan dengan pengaturan XAMPP/Laragon Anda
// ============================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'evm_db');
define('DB_USER', 'root');
define('DB_PASS', '');          // Kosong untuk XAMPP default
define('DB_CHAR', 'utf8mb4');

define('APP_NAME',    'SIMPROKON EVM');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://localhost/evm_proyek');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting (set ke 0 di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ── PDO Connection ──────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;background:#fee2e2;padding:20px;margin:20px;border-radius:8px;">
                <strong>❌ Koneksi Database Gagal</strong><br>
                ' . htmlspecialchars($e->getMessage()) . '<br><br>
                Pastikan: <br>
                1. MySQL/MariaDB sudah berjalan di XAMPP<br>
                2. Database <strong>evm_db</strong> sudah dibuat (import file database/evm_db.sql)<br>
                3. Username/Password di config.php sudah benar
                </div>');
        }
    }
    return $pdo;
}

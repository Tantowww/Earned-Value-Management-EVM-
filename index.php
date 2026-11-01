<?php
// ============================================================
// SIMPROKON EVM - Main Router
// ============================================================
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';


$p = trim($_GET['p'] ?? 'dashboard', '/');
$a = trim($_GET['a'] ?? 'index', '/');

// Halaman yang tidak butuh auth
$publicPages = ['login'];

// Redirect ke login jika belum auth
if (!isLoggedIn() && !in_array($p, $publicPages)) {
    redirect('index.php?p=login');
}

// Redirect ke dashboard jika sudah login tapi akses login
if (isLoggedIn() && $p === 'login') {
    redirect('index.php?p=dashboard');
}

// Route map: p => file path
$routes = [
    'login'     => 'pages/login.php',
    'logout'    => 'pages/logout.php',
    'dashboard' => 'pages/dashboard.php',
    'proyek'    => "pages/proyek/$a.php",
    'wbs'       => "pages/wbs/$a.php",
    'progres'   => "pages/progres/$a.php",
    'biaya'     => "pages/biaya/$a.php",
    'evm'       => "pages/evm/$a.php",
    'laporan'   => "pages/laporan/$a.php",
    'users'     => "pages/users/$a.php",
    'api'       => "pages/api/$a.php",
];

$file = $routes[$p] ?? 'pages/dashboard.php';

if (!file_exists(__DIR__ . '/' . $file)) {
    $file = 'pages/404.php';
}

require_once __DIR__ . '/' . $file;

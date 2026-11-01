<?php
// pages/register.php
require_once dirname(__DIR__) . '/includes/functions.php';
$error = '';

$existing = dbFetchOne("SELECT id FROM users WHERE username = ?", [$username]);
if ($existing !== false) {
    $error = 'Username sudah digunakan, pilih username lain.';

// ── Cek duplikat email ──
} elseif ($email) {
    $emailExist = dbFetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($emailExist !== false) {
        $error = 'Email sudah terdaftar.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username     = sanitize($_POST['username'] ?? '');
    $nama_lengkap = sanitize($_POST['nama_lengkap'] ?? '');
    $email        = sanitize($_POST['email'] ?? '');
    $role         = sanitize($_POST['role'] ?? 'staf_lapangan');
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    $allowed_roles = ['admin', 'manager', 'staf_lapangan', 'staf_keuangan']; // sesuaikan enum-mu

    // ── Validasi ────────────────────────────────────────────────
    if (!$username || !$nama_lengkap || !$password || !$confirm_pass) {
        $error = 'Field username, nama lengkap, dan password wajib diisi.';

    } elseif (strlen($username) < 4 || strlen($username) > 50) {
        $error = 'Username harus antara 4–50 karakter.';

    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username hanya boleh huruf, angka, dan underscore.';

    } elseif ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';

    } elseif (!in_array($role, $allowed_roles)) {
        $error = 'Role tidak valid.';

    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';

    } elseif ($password !== $confirm_pass) {
        $error = 'Konfirmasi password tidak cocok.';

    } else {
        // ── Cek duplikat username ────────────────────────────────
        $existing = dbFetchOne(
            "SELECT id FROM users WHERE username = ?",
            [$username]
        );

        if ($existing) {
            $error = 'Username sudah digunakan, pilih username lain.';

        // ── Cek duplikat email (jika diisi) ─────────────────────
        } elseif ($email && dbFetchOne("SELECT id FROM users WHERE email = ?", [$email])) {
            $error = 'Email sudah terdaftar.';

        } else {
            // ── Upload foto (opsional) ───────────────────────────
            $foto = null;
            if (!empty($_FILES['foto']['name'])) {
                $allowed_ext  = ['jpg', 'jpeg', 'png', 'webp'];
                $ext          = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $max_size     = 2 * 1024 * 1024; // 2 MB

                if (!in_array($ext, $allowed_ext)) {
                    $error = 'Foto hanya boleh format JPG, PNG, atau WEBP.';
                } elseif ($_FILES['foto']['size'] > $max_size) {
                    $error = 'Ukuran foto maksimal 2 MB.';
                } else {
                    $foto_name = uniqid('foto_', true) . '.' . $ext;
                    $upload_dir = 'uploads/foto_profil/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto_name);
                    $foto = $upload_dir . $foto_name;
                }
            }

            // ── Insert ke database (jika tidak ada error foto) ───
            if (!$error) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                $inserted = dbExecute(
                    "INSERT INTO users 
                        (username, password, nama_lengkap, role, email, foto, aktif, created_at)
                     VALUES 
                        (?, ?, ?, ?, ?, ?, 1, NOW())",
                    [
                        $username,
                        $hashed_password,
                        $nama_lengkap,
                        $role,
                        $email ?: null,   // kosong → NULL
                        $foto,            // null jika tidak upload
                    ]
                );

                if ($inserted) {
                    setFlash('success', 'Registrasi berhasil! Silakan login.');
                    redirect('index.php?p=login');
                } else {
                    $error = 'Registrasi gagal, silakan coba lagi.';
                }
            }
        }
    }
}
?>

<!-- ── Form HTML ──────────────────────────────────────────────── -->
<form method="POST" action="" enctype="multipart/form-data">
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Nama Lengkap -->
    <div class="mb-3">
        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama_lengkap" class="form-control"
               value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>" required>
    </div>

    <!-- Username -->
    <div class="mb-3">
        <label class="form-label">Username <span class="text-danger">*</span></label>
        <input type="text" name="username" class="form-control"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
    </div>

    <!-- Email (opsional) -->
    <div class="mb-3">
        <label class="form-label">Email <small class="text-muted">(opsional)</small></label>
        <input type="email" name="email" class="form-control"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>

    <!-- Role -->
    <div class="mb-3">
        <label class="form-label">Role <span class="text-danger">*</span></label>
        <select name="role" class="form-select" required>
            <option value="staf_lapangan" <?= ($_POST['role'] ?? '') === 'staf_lapangan' ? 'selected' : '' ?>>Staf Lapangan</option>
            <option value="staf_keuangan" <?= ($_POST['role'] ?? '') === 'staf_keuangan' ? 'selected' : '' ?>>Staf Keuangan</option>
            <option value="manager"       <?= ($_POST['role'] ?? '') === 'manager'       ? 'selected' : '' ?>>Manager</option>
            <option value="admin"         <?= ($_POST['role'] ?? '') === 'admin'         ? 'selected' : '' ?>>Admin</option>
        </select>
    </div>

    <!-- Foto Profil (opsional) -->
    <div class="mb-3">
        <label class="form-label">Foto Profil <small class="text-muted">(opsional, maks. 2 MB)</small></label>
        <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
    </div>

    <!-- Password -->
    <div class="mb-3">
        <label class="form-label">Password <span class="text-danger">*</span></label>
        <input type="password" name="password" class="form-control" required minlength="8">
    </div>

    <!-- Konfirmasi Password -->
    <div class="mb-3">
        <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
        <input type="password" name="confirm_password" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary w-100">Daftar</button>
    <p class="mt-3 text-center">
        Sudah punya akun? <a href="index.php?p=login">Login di sini</a>
    </p>
</form>
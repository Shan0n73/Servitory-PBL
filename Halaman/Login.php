<?php
require_once __DIR__ . '/includes/koneksi.php';
require_once __DIR__ . '/includes/auth.php';

$registered = isset($_GET['registered']) && $_GET['registered'] === '1';
$error = '';
$username = '';
$role = 'pelapor';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrfToken();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'pelapor';

    if (!in_array($role, ['pelapor', 'teknisi'], true)) {
        $error = 'Peran akun tidak valid.';
    } else {
        $statement = $pdo->prepare(
            'SELECT id, name, username, email, password_hash, role
             FROM users
             WHERE (username = :username OR email = :email) AND role = :role'
        );
        $statement->execute([
            'username' => $username,
            'email' => $username,
            'role' => $role,
        ]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: ' . ($role === 'teknisi' ? 'Teknisi/dasbor.php' : 'Pelapor/Dasbor.php'));
            exit;
        }

        $error = 'Username/email atau password tidak sesuai dengan peran yang dipilih.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Servitory</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="login-card">
        <div class="logo-box">S</div>
        
        <h1 class="app-title">Servitory<span class="brand-dot">.</span></h1>
        <p class="app-subtitle">Pelaporan Fasilitas dan Peralatan</p>

        <?php if ($registered): ?>
            <p class="form-message form-message-success" role="status">Pendaftaran berhasil. Silakan masuk menggunakan akun Anda.</p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="form-message form-message-error" role="alert"><?= h($error) ?></p>
        <?php endif; ?>

        <form id="loginForm" method="post" action="Login.php">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <div class="form-group">
                <label for="username">Username atau Email</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username/email" value="<?= h($username) ?>" autocomplete="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" autocomplete="current-password" required>
            </div>

            <div class="form-group">
                <label for="role">Masuk Sebagai</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="pelapor">Pelapor (Civitas Akademika)</option>
                    <option value="teknisi" <?= $role === 'teknisi' ? 'selected' : '' ?>>Teknisi</option>
                </select>
            </div>

            <div class="extra-options">
                <span>Butuh reset password? Hubungi admin.</span>
            </div>

            <button type="submit" class="btn-login">Masuk</button>
        </form>

        <div class="register-link">
            Belum punya akun?             <a href="Pelapor/Daftar.php">Daftar sebagai Pelapor</a>
        </div>
    </div>

</body>
</html>
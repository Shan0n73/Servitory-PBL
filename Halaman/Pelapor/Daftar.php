<?php
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/auth.php';

$errors = [];
$name = '';
$username = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    verifyCsrfToken();
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    if ($name === '' || mb_strlen($name) > 120) {
        $errors[] = 'Nama wajib diisi dan maksimal 120 karakter.';
    }

    if (!preg_match('/^[a-zA-Z0-9_.-]{3,60}$/', $username)) {
        $errors[] = 'Username harus 3-60 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'Masukkan alamat email yang valid.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password harus terdiri dari minimal 8 karakter.';
    }

    if ($password !== $passwordConfirmation) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }

    if ($errors === []) {
        try {
            $statement = $pdo->prepare(
                'INSERT INTO users (name, username, email, password_hash, role)
                 VALUES (:name, :username, :email, :password_hash, :role)'
            );
            $statement->execute([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'pelapor',
            ]);

            header('Location: ../Login/index.php?registered=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $errors[] = 'Username atau email sudah digunakan.';
            } else {
                throw $e;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pelapor - Servitory</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <main class="login-card">
        <div class="logo-box" aria-hidden="true">S</div>
        <h1 class="app-title">Buat akun Servitory<span class="brand-dot">.</span></h1>
        <p class="app-subtitle">Daftar sebagai pelapor fasilitas kampus</p>

        <?php if ($errors !== []): ?>
            <div class="form-message form-message-error" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="Daftar.php">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <div class="form-group">
                <label for="name">Nama lengkap</label>
                <input type="text" id="name" name="name" class="form-control" maxlength="120"
                    value="<?= h($name) ?>" autocomplete="name" required>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" minlength="3" maxlength="60"
                    pattern="[a-zA-Z0-9_.-]{3,60}" value="<?= h($username) ?>"
                    autocomplete="username" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" maxlength="255"
                    value="<?= h($email) ?>" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" minlength="8"
                    autocomplete="new-password" required>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Konfirmasi password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                    minlength="8" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn-login">Daftar</button>
        </form>

        <div class="register-link">
            Sudah punya akun? <a href="../Login/index.php">Masuk</a>
        </div>
    </main>
</body>
</html>

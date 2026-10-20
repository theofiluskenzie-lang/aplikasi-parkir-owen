<?php
require __DIR__ . '/config/koneksi.php';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = ambil_satu('SELECT * FROM tb_user WHERE username = ? AND status_aktif = 1', 's', [$username]);

    $cocok = false;
    if ($user && $password !== '') {
        if (password_verify($password, $user['password'])) {
            $cocok = true;
        } elseif (hash_equals($user['password'], $password)) {
            // Password lama masih teks biasa -> cocok, lalu otomatis diubah menjadi hash
            $cocok = true;
            eksekusi('UPDATE tb_user SET password = ? WHERE id_user = ?', 'si',
                     [password_hash($password, PASSWORD_DEFAULT), $user['id_user']]);
        }
    }

    if ($cocok) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id_user'      => $user['id_user'],
            'nama_lengkap' => $user['nama_lengkap'],
            'username'     => $user['username'],
            'role'         => $user['role'],
        ];
        catat_log('Login');
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Username atau password salah, atau akun tidak aktif.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Aplikasi Parkir</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body style="background:linear-gradient(135deg,#212529,#0d6efd);min-height:100vh">
  <div class="container d-flex align-items-center" style="min-height:100vh">
    <div class="card shadow mx-auto" style="width:100%;max-width:400px">
      <div class="card-body p-4">
        <h3 class="text-center mb-1">Aplikasi Parkir</h3>
        <p class="text-center text-muted mb-4">Silakan login untuk melanjutkan</p>

        <?php if ($error): ?>
          <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control mb-3" value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control mb-4" required>
          <button type="submit" class="btn btn-primary w-100">Masuk</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>

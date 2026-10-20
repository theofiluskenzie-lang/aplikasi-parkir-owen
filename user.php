<?php
require __DIR__ . '/config/koneksi.php';
wajib_login(['admin']);
$judul = 'Kelola User';
$daftar_role = ['admin', 'petugas', 'owner'];
$id_saya = (int)$_SESSION['user']['id_user'];

// Hapus //
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id = (int)$_POST['hapus'];
    if ($id === $id_saya) {
        flash('Akun yang sedang dipakai tidak bisa dihapus.', 'danger');
    } else {
        try {
            eksekusi('DELETE FROM tb_user WHERE id_user = ?', 'i', [$id]);
            catat_log('Hapus user ID ' . $id);
            flash('User berhasil dihapus.');
        } catch (mysqli_sql_exception $ex) {
            flash('User tidak bisa dihapus karena sudah dipakai data lain (transaksi/kendaraan/log). Nonaktifkan saja lewat tombol Edit.', 'danger');
        }
    }
    header('Location: user.php');
    exit;
}

// Simpan //
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id       = (int)$_POST['id_user'];
    $nama     = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role     = $_POST['role'];
    $aktif    = ((int)$_POST['status_aktif'] === 1) ? 1 : 0;

    $dobel = ambil_satu('SELECT id_user FROM tb_user WHERE username = ? AND id_user <> ?', 'si', [$username, $id]);

    if ($nama === '' || $username === '' || !in_array($role, $daftar_role)) {
        flash('Nama, username, dan role wajib diisi dengan benar.', 'danger');
    } elseif ($dobel) {
        flash('Username sudah dipakai user lain.', 'danger');
    } elseif ($id === 0 && $password === '') {
        flash('Password wajib diisi untuk user baru.', 'danger');
    } elseif ($id === $id_saya && ($role !== 'admin' || $aktif === 0)) {
        flash('Anda tidak bisa menurunkan role atau menonaktifkan akun sendiri.', 'danger');
    } elseif ($id > 0) {
        if ($password !== '') {
            eksekusi('UPDATE tb_user SET nama_lengkap = ?, username = ?, password = ?, role = ?, status_aktif = ? WHERE id_user = ?',
                     'ssssii', [$nama, $username, password_hash($password, PASSWORD_DEFAULT), $role, $aktif, $id]);
        } else {
            eksekusi('UPDATE tb_user SET nama_lengkap = ?, username = ?, role = ?, status_aktif = ? WHERE id_user = ?',
                     'sssii', [$nama, $username, $role, $aktif, $id]);
        }
        catat_log('Ubah user ' . $username);
        flash('Data user berhasil diubah.');
    } else {
        eksekusi('INSERT INTO tb_user (nama_lengkap, username, password, role, status_aktif) VALUES (?, ?, ?, ?, ?)',
                 'ssssi', [$nama, $username, password_hash($password, PASSWORD_DEFAULT), $role, $aktif]);
        catat_log('Tambah user ' . $username);
        flash('User baru berhasil ditambahkan.');
    }
    header('Location: user.php');
    exit;
}

// ---------- BACA ----------
$edit = isset($_GET['edit']) ? ambil_satu('SELECT * FROM tb_user WHERE id_user = ?', 'i', [(int)$_GET['edit']]) : null;
$data = ambil('SELECT * FROM tb_user ORDER BY id_user');

include __DIR__ . '/includes/header.php';
?>
<h3 class="mb-3">Kelola User</h3>

<div class="card mb-4">
  <div class="card-header"><?= $edit ? 'Edit User' : 'Tambah User' ?></div>
  <div class="card-body">
    <form method="post" class="row g-3">
      <input type="hidden" name="id_user" value="<?= $edit['id_user'] ?? 0 ?>">
      <div class="col-md-4">
        <label class="form-label">Nama Lengkap</label>
        <input type="text" name="nama_lengkap" class="form-control" maxlength="50" value="<?= e($edit['nama_lengkap'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" maxlength="50" value="<?= e($edit['username'] ?? '') ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" maxlength="72"
               placeholder="<?= $edit ? 'Kosongkan jika tidak diubah' : '' ?>" <?= $edit ? '' : 'required' ?>>
      </div>
      <div class="col-md-4">
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
          <?php foreach ($daftar_role as $r): ?>
            <option value="<?= $r ?>" <?= ($edit['role'] ?? '') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status_aktif" class="form-select">
          <option value="1" <?= ($edit['status_aktif'] ?? 1) == 1 ? 'selected' : '' ?>>Aktif</option>
          <option value="0" <?= ($edit['status_aktif'] ?? 1) == 0 ? 'selected' : '' ?>>Nonaktif</option>
        </select>
      </div>
      <div class="col-md-4 d-flex align-items-end gap-2">
        <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
        <?php if ($edit): ?><a href="user.php" class="btn btn-secondary">Batal</a><?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-striped align-middle mb-0">
      <thead class="table-dark">
        <tr><th>No</th><th>Nama Lengkap</th><th>Username</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
      </thead>
      <tbody>
        <?php foreach ($data as $i => $d): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= e($d['nama_lengkap']) ?></td>
          <td><?= e($d['username']) ?></td>
          <td><span class="badge text-bg-secondary"><?= e($d['role']) ?></span></td>
          <td><?= $d['status_aktif'] ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-danger">Nonaktif</span>' ?></td>
          <td>
            <a href="user.php?edit=<?= $d['id_user'] ?>" class="btn btn-sm btn-warning">Edit</a>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus user ini?')">
              <input type="hidden" name="hapus" value="<?= $d['id_user'] ?>">
              <button class="btn btn-sm btn-danger">Hapus</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

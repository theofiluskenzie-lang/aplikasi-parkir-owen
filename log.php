<?php
require __DIR__ . '/config/koneksi.php';
wajib_login(['admin']);
$judul = 'Log Aktivitas';

$data = ambil('SELECT l.*, u.nama_lengkap, u.role
               FROM tb_log_aktivitas l
               LEFT JOIN tb_user u ON u.id_user = l.id_user
               ORDER BY l.id_log DESC
               LIMIT 300');

include __DIR__ . '/includes/header.php';
?>
<h3 class="mb-1">Log Aktivitas</h3>
<p class="text-muted">300 aktivitas terbaru dari seluruh pengguna.</p>

<div class="card">
  <div class="card-body table-responsive">
    <table class="table table-striped table-sm align-middle mb-0">
      <thead class="table-dark">
        <tr><th>No</th><th>Waktu</th><th>User</th><th>Role</th><th>Aktivitas</th></tr>
      </thead>
      <tbody>
        <?php foreach ($data as $i => $d): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= tgl($d['waktu_aktivitas']) ?></td>
          <td><?= e($d['nama_lengkap'] ?? '(user dihapus)') ?></td>
          <td><span class="badge text-bg-secondary"><?= e($d['role'] ?? '-') ?></span></td>
          <td><?= e($d['aktivitas']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$data): ?><tr><td colspan="5" class="text-center text-muted">Belum ada aktivitas.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

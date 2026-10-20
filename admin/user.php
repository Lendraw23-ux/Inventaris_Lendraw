<?php
$level_boleh = ['admin'];
require_once '../includes/auth.php';

$level_valid = ['admin', 'petugas', 'peminjam'];

// ---- Proses form ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi     = isset($_POST['aksi']) ? $_POST['aksi'] : '';
    $id       = (int)(isset($_POST['id_user']) ? $_POST['id_user'] : 0);
    $nama     = trim(isset($_POST['nama']) ? $_POST['nama'] : '');
    $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $level    = isset($_POST['level']) ? $_POST['level'] : '';

    if ($aksi === 'tambah' || $aksi === 'ubah') {
        if ($nama === '' || $username === '' || !in_array($level, $level_valid, true)) {
            flash_set('error', 'Nama, username, dan level wajib diisi.');
        } elseif ($aksi === 'tambah' && strlen($password) < 6) {
            flash_set('error', 'Password minimal 6 karakter.');
        } elseif ($aksi === 'ubah' && $password !== '' && strlen($password) < 6) {
            flash_set('error', 'Password baru minimal 6 karakter.');
        } else {
            $dobel = db_val($koneksi, 'SELECT COUNT(*) FROM user WHERE username = ? AND id_user <> ?', [$username, $id]);
            if ((int)$dobel > 0) {
                flash_set('error', 'Username "' . $username . '" sudah dipakai pengguna lain.');
            } elseif ($aksi === 'tambah') {
                db_exec($koneksi, 'INSERT INTO user (nama, username, password, level) VALUES (?, ?, ?, ?)', [$nama, $username, password_hash($password, PASSWORD_DEFAULT), $level]);
                catat_log($koneksi, $user['nama'] . ' menambahkan pengguna ' . $nama . ' (' . $level . ')');
                flash_set('success', 'Pengguna ' . $nama . ' ditambahkan.');
            } else {
                // Admin tidak boleh menurunkan levelnya sendiri
                if ($id === $user['id'] && $level !== 'admin') {
                    $level = 'admin';
                }
                if ($password !== '') {
                    db_exec($koneksi, 'UPDATE user SET nama = ?, username = ?, level = ?, password = ? WHERE id_user = ?', [$nama, $username, $level, password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    db_exec($koneksi, 'UPDATE user SET nama = ?, username = ?, level = ? WHERE id_user = ?', [$nama, $username, $level, $id]);
                }
                if ($id === $user['id']) {
                    $_SESSION['nama'] = $nama;
                    $_SESSION['username'] = $username;
                }
                catat_log($koneksi, $user['nama'] . ' mengubah data pengguna ' . $nama);
                flash_set('success', 'Data ' . $nama . ' diperbarui.');
            }
        }
    } elseif ($aksi === 'hapus') {
        if ($id === $user['id']) {
            flash_set('error', 'Anda tidak bisa menghapus akun yang sedang dipakai.');
        } else {
            $target = db_row($koneksi, 'SELECT nama FROM user WHERE id_user = ?', [$id]);
            try {
                db_exec($koneksi, 'DELETE FROM user WHERE id_user = ?', [$id]);
                if ($target) {
                    catat_log($koneksi, $user['nama'] . ' menghapus pengguna ' . $target['nama']);
                }
                flash_set('success', 'Pengguna dihapus.');
            } catch (mysqli_sql_exception $ex) {
                flash_set('error', 'Pengguna ini punya riwayat peminjaman, jadi tidak bisa dihapus.');
            }
        }
    }
    redirect('admin/user.php');
}

$daftar = db_all($koneksi, 'SELECT u.*, (SELECT COUNT(*) FROM peminjaman p WHERE p.id_user = u.id_user) AS total_pinjam FROM user u ORDER BY FIELD(u.level, "admin", "petugas", "peminjam"), u.nama');
$hitung = ['admin' => 0, 'petugas' => 0, 'peminjam' => 0];
foreach ($daftar as $d) {
    $hitung[$d['level']]++;
}

$judul = 'Pengguna';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-2xl font-semibold text-zinc-900">Kelola pengguna</h2>
        <p class="mt-1 text-sm text-zinc-500"><?= $hitung['admin'] ?> admin, <?= $hitung['petugas'] ?> petugas, <?= $hitung['peminjam'] ?> peminjam</p>
    </div>
    <button type="button" class="btn btn-primary " data-modal-open="#modal-user" data-title="Tambah pengguna" data-aksi="tambah" data-json='{"id_user":""}'>
        <?= icon('plus', 'w-4 h-4') ?> Tambah pengguna
    </button>
</div>

<div class="card mt-6 overflow-hidden">
    <div class="border-b border-zinc-100 p-4">
        <div class="relative max-w-sm">
            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400"><?= icon('search', 'w-4 h-4') ?></span>
            <input type="search" class="input pl-10" placeholder="Cari nama atau username" data-filter="#tbl-user tbody tr" data-empty="#kosong-user" aria-label="Cari pengguna">
        </div>
    </div>
    <div class="overflow-x-auto scroll-thin">
        <table class="tbl" id="tbl-user">
            <thead><tr><th>Pengguna</th><th>Username</th><th>Level</th><th>Peminjaman</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $d): ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-700"><?= e(inisial($d['nama'])) ?></span>
                            <span class="font-semibold text-zinc-900"><?= e($d['nama']) ?><?= (int)$d['id_user'] === $user['id'] ? ' <span class="ml-1 text-xs font-medium text-zinc-400">(Anda)</span>' : '' ?></span>
                        </div>
                    </td>
                    <td class="text-zinc-600"><?= e($d['username']) ?></td>
                    <td><?= badge_level($d['level']) ?></td>
                    <td class="text-zinc-600"><?= (int)$d['total_pinjam'] ?>x</td>
                    <td>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn btn-soft btn-sm" data-modal-open="#modal-user" data-title="Ubah pengguna" data-aksi="ubah"
                                    data-json="<?= e(json_encode(['id_user' => (int)$d['id_user'], 'nama' => $d['nama'], 'username' => $d['username'], 'level' => $d['level']])) ?>">
                                <?= icon('edit', 'w-4 h-4') ?> Ubah
                            </button>
                            <?php if ((int)$d['id_user'] !== $user['id']): ?>
                                <form method="post" data-confirm="Pengguna <?= e($d['nama']) ?> akan dihapus permanen." data-confirm-title="Hapus pengguna?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id_user" value="<?= (int)$d['id_user'] ?>">
                                    <button class="btn btn-danger-soft btn-sm" aria-label="Hapus <?= e($d['nama']) ?>"><?= icon('trash', 'w-4 h-4') ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div id="kosong-user" class="hidden py-12 text-center text-sm text-zinc-500">Tidak ada pengguna yang cocok dengan pencarian.</div>
</div>

<!-- Modal tambah / ubah -->
<div id="modal-user" class="modal" role="dialog" aria-modal="true" aria-labelledby="judul-user">
    <div class="modal-bg" data-modal-close></div>
    <div class="modal-box">
        <form method="post" class="p-6">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="tambah">
            <input type="hidden" name="id_user" value="">
            <div class="flex items-center justify-between">
                <h3 id="judul-user" class="text-xl font-semibold text-zinc-900" data-modal-title>Tambah pengguna</h3>
                <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100" data-modal-close aria-label="Tutup"><?= icon('x') ?></button>
            </div>
            <div class="mt-6 space-y-4">
                <div><label class="label" for="u-nama">Nama lengkap</label><input class="input" id="u-nama" name="nama" required></div>
                <div><label class="label" for="u-username">Username</label><input class="input" id="u-username" name="username" autocomplete="off" required></div>
                <div>
                    <label class="label" for="u-password">Password</label>
                    <input class="input" type="password" id="u-password" name="password" autocomplete="new-password" placeholder="Minimal 6 karakter">
                    <p class="mt-1.5 text-xs text-zinc-500">Saat mengubah pengguna, kosongkan jika password tidak diganti.</p>
                </div>
                <div>
                    <label class="label" for="u-level">Level</label>
                    <select class="input" id="u-level" name="level" required>
                        <option value="peminjam">Peminjam</option>
                        <option value="petugas">Petugas</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="mt-7 flex gap-3">
                <button type="button" class="btn btn-ghost flex-1" data-modal-close>Batal</button>
                <button class="btn btn-primary flex-1">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

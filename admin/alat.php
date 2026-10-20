<?php
$level_boleh = ['admin'];
require_once '../includes/auth.php';

$kondisi_valid = ['Baik', 'Rusak Ringan', 'Rusak Berat'];
$kategori_list = db_all($koneksi, 'SELECT * FROM kategori ORDER BY nama_kategori');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi    = isset($_POST['aksi']) ? $_POST['aksi'] : '';
    $id      = (int)(isset($_POST['id_alat']) ? $_POST['id_alat'] : 0);
    $nama    = trim(isset($_POST['nama_alat']) ? $_POST['nama_alat'] : '');
    $id_kat  = (int)(isset($_POST['id_kategori']) ? $_POST['id_kategori'] : 0);
    $stok    = (int)(isset($_POST['stok']) ? $_POST['stok'] : 0);
    $kondisi = isset($_POST['kondisi']) ? $_POST['kondisi'] : '';

    if ($aksi === 'tambah' || $aksi === 'ubah') {
        $ada_kat = (int)db_val($koneksi, 'SELECT COUNT(*) FROM kategori WHERE id_kategori = ?', [$id_kat]);
        if ($nama === '' || !$ada_kat || !in_array($kondisi, $kondisi_valid, true)) {
            flash_set('error', 'Lengkapi nama, kategori, dan kondisi alat.');
        } elseif ($stok < 0) {
            flash_set('error', 'Stok tidak boleh negatif.');
        } elseif ($aksi === 'tambah') {
            db_exec($koneksi, 'INSERT INTO alat (nama_alat, id_kategori, stok, kondisi) VALUES (?, ?, ?, ?)', [$nama, $id_kat, $stok, $kondisi]);
            catat_log($koneksi, $user['nama'] . ' menambahkan alat ' . $nama . ' (stok ' . $stok . ')');
            flash_set('success', $nama . ' ditambahkan ke daftar alat.');
        } else {
            db_exec($koneksi, 'UPDATE alat SET nama_alat = ?, id_kategori = ?, stok = ?, kondisi = ? WHERE id_alat = ?', [$nama, $id_kat, $stok, $kondisi, $id]);
            catat_log($koneksi, $user['nama'] . ' mengubah data alat ' . $nama);
            flash_set('success', 'Data ' . $nama . ' diperbarui.');
        }
    } elseif ($aksi === 'hapus') {
        $a = db_row($koneksi, 'SELECT nama_alat FROM alat WHERE id_alat = ?', [$id]);
        try {
            db_exec($koneksi, 'DELETE FROM alat WHERE id_alat = ?', [$id]);
            if ($a) {
                catat_log($koneksi, $user['nama'] . ' menghapus alat ' . $a['nama_alat']);
            }
            flash_set('success', 'Alat dihapus.');
        } catch (mysqli_sql_exception $ex) {
            flash_set('error', 'Alat ini sudah punya riwayat peminjaman, jadi tidak bisa dihapus. Ubah stoknya menjadi 0 bila sudah tidak dipakai.');
        }
    }
    redirect('admin/alat.php' . (!empty($_GET['kategori']) ? '?kategori=' . (int)$_GET['kategori'] : ''));
}

$filter_kat = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$sql = 'SELECT a.*, k.nama_kategori FROM alat a JOIN kategori k ON k.id_kategori = a.id_kategori';
$par = [];
if ($filter_kat) {
    $sql .= ' WHERE a.id_kategori = ?';
    $par[] = $filter_kat;
}
$sql .= ' ORDER BY a.nama_alat';
$daftar = db_all($koneksi, $sql, $par);

$judul = 'Data Alat';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-2xl font-semibold text-zinc-900">Data alat</h2>
        <p class="mt-1 text-sm text-zinc-500"><?= count($daftar) ?> alat <?= $filter_kat ? 'dalam kategori ini' : 'terdaftar' ?></p>
    </div>
    <button type="button" class="btn btn-primary " data-modal-open="#modal-alat" data-title="Tambah alat" data-aksi="tambah"
            data-json='{"id_alat":"","stok":1,"kondisi":"Baik"}' <?= $kategori_list ? '' : 'disabled' ?>>
        <?= icon('plus', 'w-4 h-4') ?> Tambah alat
    </button>
</div>

<?php if (!$kategori_list): ?>
    <div class="card mt-6 flex items-center gap-4 border-l-4 border-amber-500 p-5">
        <span class="text-amber-600"><?= icon('alert', 'w-6 h-6') ?></span>
        <p class="text-sm text-zinc-700">Belum ada kategori. <a class="font-semibold text-accent-700 underline" href="<?= url('admin/kategori.php') ?>">Buat kategori</a> dulu sebelum menambah alat.</p>
    </div>
<?php endif; ?>

<div class="mt-6 flex gap-2 overflow-x-auto pb-1 scroll-thin">
    <a href="<?= url('admin/alat.php') ?>" class="chip <?= !$filter_kat ? 'chip-on' : '' ?>">Semua</a>
    <?php foreach ($kategori_list as $k): ?>
        <a href="<?= url('admin/alat.php?kategori=' . (int)$k['id_kategori']) ?>" class="chip <?= $filter_kat === (int)$k['id_kategori'] ? 'chip-on' : '' ?>"><?= e($k['nama_kategori']) ?></a>
    <?php endforeach; ?>
</div>

<div class="card mt-4 overflow-hidden">
    <div class="border-b border-zinc-100 p-4">
        <div class="relative max-w-sm">
            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400"><?= icon('search', 'w-4 h-4') ?></span>
            <input type="search" class="input pl-10" placeholder="Cari nama alat" data-filter="#tbl-alat tbody tr" data-empty="#kosong-alat" aria-label="Cari alat">
        </div>
    </div>
    <div class="overflow-x-auto scroll-thin">
        <table class="tbl" id="tbl-alat">
            <thead><tr><th>Alat</th><th>Kategori</th><th>Stok</th><th>Kondisi</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($daftar as $a): ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-accent-100 text-accent-600"><?= icon(kategori_ikon($a['nama_kategori']), 'w-5 h-5') ?></span>
                            <span class="font-semibold text-zinc-900"><?= e($a['nama_alat']) ?></span>
                        </div>
                    </td>
                    <td class="text-zinc-600"><?= e($a['nama_kategori']) ?></td>
                    <td>
                        <?php if ((int)$a['stok'] === 0): ?>
                            <?= badge('Habis', 'red') ?>
                        <?php else: ?>
                            <span class="font-semibold text-zinc-900"><?= (int)$a['stok'] ?></span> <span class="text-xs text-zinc-500">unit</span>
                        <?php endif; ?>
                    </td>
                    <td><?= badge_kondisi($a['kondisi']) ?></td>
                    <td>
                        <div class="flex justify-end gap-2">
                            <button type="button" class="btn btn-soft btn-sm" data-modal-open="#modal-alat" data-title="Ubah alat" data-aksi="ubah"
                                    data-json="<?= e(json_encode(['id_alat' => (int)$a['id_alat'], 'nama_alat' => $a['nama_alat'], 'id_kategori' => (int)$a['id_kategori'], 'stok' => (int)$a['stok'], 'kondisi' => $a['kondisi']])) ?>">
                                <?= icon('edit', 'w-4 h-4') ?> Ubah
                            </button>
                            <form method="post" data-confirm="<?= e($a['nama_alat']) ?> akan dihapus dari daftar." data-confirm-title="Hapus alat?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id_alat" value="<?= (int)$a['id_alat'] ?>">
                                <button class="btn btn-danger-soft btn-sm" aria-label="Hapus <?= e($a['nama_alat']) ?>"><?= icon('trash', 'w-4 h-4') ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div id="kosong-alat" class="<?= $daftar ? 'hidden ' : '' ?>py-12 text-center text-sm text-zinc-500">Tidak ada alat yang cocok.</div>
</div>

<div id="modal-alat" class="modal" role="dialog" aria-modal="true" aria-labelledby="judul-alat">
    <div class="modal-bg" data-modal-close></div>
    <div class="modal-box">
        <form method="post" class="p-6">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="tambah">
            <input type="hidden" name="id_alat" value="">
            <div class="flex items-center justify-between">
                <h3 id="judul-alat" class="text-xl font-semibold text-zinc-900" data-modal-title>Tambah alat</h3>
                <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100" data-modal-close aria-label="Tutup"><?= icon('x') ?></button>
            </div>
            <div class="mt-6 space-y-4">
                <div><label class="label" for="a-nama">Nama alat</label><input class="input" id="a-nama" name="nama_alat" required></div>
                <div>
                    <label class="label" for="a-kat">Kategori</label>
                    <select class="input" id="a-kat" name="id_kategori" required>
                        <?php foreach ($kategori_list as $k): ?>
                            <option value="<?= (int)$k['id_kategori'] ?>"><?= e($k['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="label" for="a-stok">Stok</label><input class="input" type="number" min="0" id="a-stok" name="stok" value="1" required></div>
                    <div>
                        <label class="label" for="a-kondisi">Kondisi</label>
                        <select class="input" id="a-kondisi" name="kondisi">
                            <?php foreach ($kondisi_valid as $kv): ?><option><?= e($kv) ?></option><?php endforeach; ?>
                        </select>
                    </div>
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

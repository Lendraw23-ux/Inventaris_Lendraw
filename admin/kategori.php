<?php
$level_boleh = ['admin'];
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';
    $id   = (int)(isset($_POST['id_kategori']) ? $_POST['id_kategori'] : 0);
    $nama = trim(isset($_POST['nama_kategori']) ? $_POST['nama_kategori'] : '');

    if ($aksi === 'tambah' || $aksi === 'ubah') {
        if ($nama === '') {
            flash_set('error', 'Nama kategori wajib diisi.');
        } else {
            $dobel = (int)db_val($koneksi, 'SELECT COUNT(*) FROM kategori WHERE nama_kategori = ? AND id_kategori <> ?', [$nama, $id]);
            if ($dobel > 0) {
                flash_set('error', 'Kategori "' . $nama . '" sudah ada.');
            } elseif ($aksi === 'tambah') {
                db_exec($koneksi, 'INSERT INTO kategori (nama_kategori) VALUES (?)', [$nama]);
                catat_log($koneksi, $user['nama'] . ' menambahkan kategori ' . $nama);
                flash_set('success', 'Kategori ' . $nama . ' ditambahkan.');
            } else {
                db_exec($koneksi, 'UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?', [$nama, $id]);
                catat_log($koneksi, $user['nama'] . ' mengubah kategori menjadi ' . $nama);
                flash_set('success', 'Kategori diperbarui.');
            }
        }
    } elseif ($aksi === 'hapus') {
        $k = db_row($koneksi, 'SELECT nama_kategori FROM kategori WHERE id_kategori = ?', [$id]);
        try {
            db_exec($koneksi, 'DELETE FROM kategori WHERE id_kategori = ?', [$id]);
            if ($k) {
                catat_log($koneksi, $user['nama'] . ' menghapus kategori ' . $k['nama_kategori']);
            }
            flash_set('success', 'Kategori dihapus.');
        } catch (mysqli_sql_exception $ex) {
            flash_set('error', 'Kategori masih dipakai oleh data alat. Pindahkan atau hapus alatnya dulu.');
        }
    }
    redirect('admin/kategori.php');
}

$daftar = db_all($koneksi, 'SELECT k.*, COUNT(a.id_alat) AS jenis, COALESCE(SUM(a.stok), 0) AS unit
                             FROM kategori k LEFT JOIN alat a ON a.id_kategori = k.id_kategori
                             GROUP BY k.id_kategori ORDER BY k.nama_kategori');

$judul = 'Kategori';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-2xl font-semibold text-zinc-900">Kategori alat</h2>
        <p class="mt-1 text-sm text-zinc-500">Kelompokkan alat agar peminjam mudah mencarinya.</p>
    </div>
    <button type="button" class="btn btn-primary " data-modal-open="#modal-kategori" data-title="Tambah kategori" data-aksi="tambah" data-json='{"id_kategori":""}'>
        <?= icon('plus', 'w-4 h-4') ?> Tambah kategori
    </button>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($daftar as $i => $k): ?>
        <article class="card overflow-hidden">
            <div class="flex items-center justify-between px-5 pt-5">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-accent-100 text-accent-600"><?= icon(kategori_ikon($k['nama_kategori']), 'w-5 h-5') ?></span>
                <span class="text-3xl font-semibold text-zinc-900"><?= (int)$k['jenis'] ?></span>
            </div>
            <div class="p-5 pt-4">
                <h3 class="text-base font-semibold text-zinc-900"><?= e($k['nama_kategori']) ?></h3>
                <p class="mt-1 text-sm text-zinc-500"><?= (int)$k['jenis'] ?> jenis alat · <?= (int)$k['unit'] ?> unit tersedia</p>
                <div class="mt-5 flex gap-2">
                    <button type="button" class="btn btn-soft btn-sm flex-1" data-modal-open="#modal-kategori" data-title="Ubah kategori" data-aksi="ubah"
                            data-json="<?= e(json_encode(['id_kategori' => (int)$k['id_kategori'], 'nama_kategori' => $k['nama_kategori']])) ?>">
                        <?= icon('edit', 'w-4 h-4') ?> Ubah
                    </button>
                    <form method="post" data-confirm="Kategori <?= e($k['nama_kategori']) ?> akan dihapus." data-confirm-title="Hapus kategori?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="hapus">
                        <input type="hidden" name="id_kategori" value="<?= (int)$k['id_kategori'] ?>">
                        <button class="btn btn-danger-soft btn-sm" aria-label="Hapus <?= e($k['nama_kategori']) ?>"><?= icon('trash', 'w-4 h-4') ?></button>
                    </form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php if (!$daftar): ?>
    <div class="card mt-6 py-16 text-center">
        <span class="mx-auto grid h-10 w-10 place-items-center rounded-xl bg-zinc-100 text-zinc-600"><?= icon('tag', 'w-5 h-5') ?></span>
        <p class="mt-4 text-base font-semibold text-zinc-900">Belum ada kategori</p>
        <p class="mt-1 text-sm text-zinc-500">Buat kategori pertama sebelum menambahkan alat.</p>
    </div>
<?php endif; ?>

<div id="modal-kategori" class="modal" role="dialog" aria-modal="true" aria-labelledby="judul-kategori">
    <div class="modal-bg" data-modal-close></div>
    <div class="modal-box">
        <form method="post" class="p-6">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="tambah">
            <input type="hidden" name="id_kategori" value="">
            <div class="flex items-center justify-between">
                <h3 id="judul-kategori" class="text-xl font-semibold text-zinc-900" data-modal-title>Tambah kategori</h3>
                <button type="button" class="grid h-9 w-9 place-items-center rounded-lg text-zinc-500 hover:bg-zinc-100" data-modal-close aria-label="Tutup"><?= icon('x') ?></button>
            </div>
            <div class="mt-6">
                <label class="label" for="k-nama">Nama kategori</label>
                <input class="input" id="k-nama" name="nama_kategori" placeholder="Contoh: Elektronik" required>
            </div>
            <div class="mt-7 flex gap-3">
                <button type="button" class="btn btn-ghost flex-1" data-modal-close>Batal</button>
                <button class="btn btn-primary flex-1">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

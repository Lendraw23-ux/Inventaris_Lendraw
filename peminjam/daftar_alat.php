<?php
$level_boleh = ['peminjam'];
require_once '../includes/auth.php';

$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$id_kat = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$hanya_tersedia = !empty($_GET['tersedia']);

$kategori = db_all($koneksi, 'SELECT * FROM kategori ORDER BY nama_kategori');

$sql = 'SELECT a.*, k.nama_kategori FROM alat a JOIN kategori k ON k.id_kategori = a.id_kategori WHERE 1=1';
$par = [];
if ($q !== '') {
    $sql .= ' AND a.nama_alat LIKE ?';
    $par[] = '%' . $q . '%';
}
if ($id_kat) {
    $sql .= ' AND a.id_kategori = ?';
    $par[] = $id_kat;
}
if ($hanya_tersedia) {
    $sql .= " AND a.stok > 0 AND a.kondisi NOT LIKE '%berat%'";
}
// Yang tersedia tampil lebih dulu
$sql .= " ORDER BY (a.stok > 0 AND a.kondisi NOT LIKE '%berat%') DESC, a.nama_alat";
$daftar = db_all($koneksi, $sql, $par);

function link_filter($ubah)
{
    $p = array_merge($_GET, $ubah);
    $p = array_filter($p, function ($v) { return $v !== '' && $v !== null && $v !== 0 && $v !== '0'; });
    return url('peminjam/daftar_alat.php') . ($p ? '?' . http_build_query($p) : '');
}

$judul = 'Katalog Alat';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div>
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-semibold text-zinc-900">Katalog alat</h1>
        <p class="text-zinc-500">Temukan alat yang kamu butuhkan, lalu ajukan peminjamannya.</p>
    </div>

    <form method="get" class="mt-6 flex flex-col gap-3 sm:flex-row">
        <?php if ($id_kat): ?><input type="hidden" name="kategori" value="<?= $id_kat ?>"><?php endif; ?>
        <div class="relative flex-1">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"><?= icon('search', 'w-4 h-4') ?></span>
            <input type="search" name="q" value="<?= e($q) ?>" class="input pl-10" placeholder="Cari nama alat" aria-label="Cari alat">
        </div>
        <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700">
            <input type="checkbox" name="tersedia" value="1" class="h-4 w-4 rounded border-zinc-300 text-zinc-900 focus:ring-accent-500" <?= $hanya_tersedia ? 'checked' : '' ?> onchange="this.form.submit()">
            Hanya yang tersedia
        </label>
        <button class="btn btn-primary px-5">Cari</button>
    </form>

    <div class="mt-5 flex gap-2 overflow-x-auto pb-1 scroll-thin">
        <a href="<?= link_filter(['kategori' => 0]) ?>" class="chip <?= !$id_kat ? 'chip-on' : '' ?>">Semua</a>
        <?php foreach ($kategori as $k): ?>
            <a href="<?= link_filter(['kategori' => (int)$k['id_kategori']]) ?>" class="chip <?= $id_kat === (int)$k['id_kategori'] ? 'chip-on' : '' ?>">
                <?= icon(kategori_ikon($k['nama_kategori']), 'w-4 h-4') ?> <?= e($k['nama_kategori']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <p class="mt-6 text-sm text-zinc-500"><?= count($daftar) ?> alat ditemukan<?= $q !== '' ? ' untuk "' . e($q) . '"' : '' ?></p>

    <?php if ($daftar): ?>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($daftar as $i => $a) { echo kartu_alat($a); } ?>
        </div>
    <?php else: ?>
        <div class="card mt-4 py-16 text-center">
            <span class="mx-auto grid h-10 w-10 place-items-center rounded-xl bg-zinc-100 text-zinc-600"><?= icon('search', 'w-5 h-5') ?></span>
            <p class="mt-4 text-base font-semibold text-zinc-900">Alat tidak ditemukan</p>
            <p class="mt-1 text-sm text-zinc-500">Coba kata kunci lain atau hapus filter kategori.</p>
            <a href="<?= url('peminjam/daftar_alat.php') ?>" class="btn btn-soft mt-5">Tampilkan semua alat</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>

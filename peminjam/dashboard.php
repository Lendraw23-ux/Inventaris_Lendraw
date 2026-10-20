<?php
$level_boleh = ['peminjam'];
require_once '../includes/auth.php';

$id = $user['id'];
$aktif     = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman WHERE id_user = ? AND persetujuan = 1 AND status = 1', [$id]);
$menunggu  = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman WHERE id_user = ? AND persetujuan = 0', [$id]);
$selesai   = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman WHERE id_user = ? AND status = 2', [$id]);

$kategori = db_all($koneksi, 'SELECT k.*, COUNT(a.id_alat) AS jenis FROM kategori k LEFT JOIN alat a ON a.id_kategori = k.id_kategori GROUP BY k.id_kategori ORDER BY k.nama_kategori');
$pilihan = db_all($koneksi, "SELECT a.*, k.nama_kategori FROM alat a JOIN kategori k ON k.id_kategori = a.id_kategori
                             WHERE a.stok > 0 AND a.kondisi NOT LIKE '%berat%'
                             ORDER BY (SELECT COUNT(*) FROM peminjaman p WHERE p.id_alat = a.id_alat) DESC, a.nama_alat LIMIT 4");
$pinjaman = db_all($koneksi, 'SELECT p.*, a.nama_alat, a.id_kategori, k.nama_kategori
                              FROM peminjaman p JOIN alat a ON a.id_alat = p.id_alat JOIN kategori k ON k.id_kategori = a.id_kategori
                              WHERE p.id_user = ? AND ((p.persetujuan = 1 AND p.status = 1) OR p.persetujuan = 0)
                              ORDER BY p.tanggal_kembali ASC LIMIT 4', [$id]);

$judul = 'Beranda';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<!-- Pencarian -->
<section class="card relative overflow-hidden p-6 sm:p-8">
    <div class="pointer-events-none absolute -right-10 -top-16 h-56 w-56 rounded-full bg-accent-200/50 blur-3xl"></div>
    <div class="relative flex flex-col gap-6 md:flex-row md:items-center">
        <div class="hidden shrink-0 md:block"><?= maskot('h-36 w-36') ?></div>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-accent-600">Halo, <?= e(explode(' ', $user['nama'])[0]) ?>! <span class="text-zinc-400">こんにちは</span></p>
            <h1 class="mt-1 text-3xl font-black text-zinc-900 sm:text-4xl">Butuh alat apa hari ini?</h1>
            <form action="daftar_alat.php" method="get" class="mt-5 flex w-full gap-2 md:max-w-lg">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-accent-400"><?= icon('search', 'w-4 h-4') ?></span>
                    <input type="search" name="q" class="input pl-9" placeholder="Cari laptop, proyektor, kamera" aria-label="Cari alat">
                </div>
                <button class="btn btn-primary">Cari</button>
            </form>
        </div>
    </div>
</section>

<!-- Ringkasan saya -->
<section class="mt-6 grid grid-cols-3 gap-3 sm:gap-4">
    <?php foreach ([['Sedang dipinjam', $aktif], ['Menunggu persetujuan', $menunggu], ['Selesai', $selesai]] as $s): ?>
        <div class="card p-4">
            <p class="text-xs text-zinc-500 sm:text-sm"><?= e($s[0]) ?></p>
            <p class="mt-1.5 text-2xl font-semibold text-zinc-900 sm:text-3xl" data-count="<?= (int)$s[1] ?>"><?= (int)$s[1] ?></p>
        </div>
    <?php endforeach; ?>
</section>

<!-- Pinjaman saya -->
<?php if ($pinjaman): ?>
<section class="mt-10">
    <div class="flex items-baseline justify-between">
        <h2 class="text-lg font-semibold text-zinc-900">Pinjaman saya</h2>
        <a href="<?= url('peminjam/riwayat.php') ?>" class="text-sm font-medium text-accent-700 hover:underline">Semua riwayat</a>
    </div>
    <div class="card mt-3 divide-y divide-zinc-100">
        <?php foreach ($pinjaman as $p):
            $telat = (int)$p['persetujuan'] === 1 && $p['tanggal_kembali'] < date('Y-m-d');
            $sisa = hari_selisih(date('Y-m-d'), $p['tanggal_kembali']); ?>
            <div class="flex items-center gap-4 p-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-accent-100 text-accent-600"><?= icon(kategori_ikon($p['nama_kategori']), 'w-5 h-5') ?></span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-zinc-900"><?= e($p['nama_alat']) ?></p>
                    <p class="text-sm <?= $telat ? 'text-red-600' : 'text-zinc-500' ?>">
                        <?php if ((int)$p['persetujuan'] === 0): ?>Diajukan untuk <?= tgl($p['tanggal_pinjam']) ?>
                        <?php elseif ($telat): ?>Seharusnya kembali <?= tgl($p['tanggal_kembali']) ?>
                        <?php else: ?>Kembalikan <?= $sisa <= 0 ? 'hari ini' : 'dalam ' . $sisa . ' hari' ?><?php endif; ?>
                    </p>
                </div>
                <?= badge_status($p) ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Kategori -->
<section class="mt-10">
    <div class="flex items-baseline justify-between">
        <h2 class="text-lg font-semibold text-zinc-900">Kategori</h2>
        <a href="<?= url('peminjam/daftar_alat.php') ?>" class="text-sm font-medium text-accent-700 hover:underline">Lihat semua alat</a>
    </div>
    <div class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
        <?php foreach ($kategori as $k): ?>
            <a href="<?= url('peminjam/daftar_alat.php?kategori=' . (int)$k['id_kategori']) ?>" class="card flex items-center gap-3 p-3.5 transition-colors hover:border-zinc-400">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-accent-100 text-accent-600"><?= icon(kategori_ikon($k['nama_kategori']), 'w-[18px] h-[18px]') ?></span>
                <span class="min-w-0 leading-tight">
                    <span class="block truncate text-sm font-semibold text-zinc-900"><?= e($k['nama_kategori']) ?></span>
                    <span class="text-xs text-zinc-500"><?= (int)$k['jenis'] ?> jenis</span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Pilihan populer -->
<section class="mt-10">
    <h2 class="text-lg font-semibold text-zinc-900">Paling sering dipinjam</h2>
    <p class="mt-0.5 text-sm text-zinc-500">Alat yang tersedia dan paling banyak diminta.</p>
    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <?php foreach ($pilihan as $i => $a) { echo kartu_alat($a); } ?>
    </div>
    <?php if (!$pilihan): ?><div class="card py-12 text-center text-sm text-zinc-500">Belum ada alat yang tersedia.</div><?php endif; ?>
</section>

<!-- Alur -->
<section class="card mt-10 p-6">
    <h2 class="text-lg font-semibold text-zinc-900">Cara meminjam</h2>
    <ol class="mt-4 grid gap-5 md:grid-cols-3">
        <?php foreach ([
            ['Pilih alat', 'Cari di katalog, lalu tekan Pinjam pada alat yang kamu perlukan.'],
            ['Tunggu persetujuan', 'Petugas memeriksa stok dan menyetujui pengajuanmu.'],
            ['Ambil dan kembalikan', 'Ambil alatnya, lalu kembalikan sebelum batas tanggal.'],
        ] as $n => $s): ?>
            <li class="flex gap-3">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-gradient-to-br from-accent-400 to-accent-600 text-xs font-bold text-white shadow-glow"><?= $n + 1 ?></span>
                <div>
                    <p class="text-sm font-semibold text-zinc-900"><?= e($s[0]) ?></p>
                    <p class="mt-1 text-sm leading-relaxed text-zinc-500"><?= e($s[1]) ?></p>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<?php require_once '../includes/footer.php'; ?>

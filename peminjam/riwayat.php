<?php
$level_boleh = ['peminjam'];
require_once '../includes/auth.php';

// Batalkan pengajuan yang masih menunggu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $id = (int)(isset($_POST['id_peminjaman']) ? $_POST['id_peminjaman'] : 0);
    $p = db_row($koneksi, 'SELECT p.*, a.nama_alat FROM peminjaman p JOIN alat a ON a.id_alat = p.id_alat WHERE p.id_peminjaman = ? AND p.id_user = ? AND p.persetujuan = 0', [$id, $user['id']]);
    if ($p) {
        db_exec($koneksi, 'DELETE FROM peminjaman WHERE id_peminjaman = ? AND id_user = ? AND persetujuan = 0', [$id, $user['id']]);
        catat_log($koneksi, $user['nama'] . ' membatalkan pengajuan ' . $p['nama_alat']);
        flash_set('success', 'Pengajuan ' . $p['nama_alat'] . ' dibatalkan.');
    } else {
        flash_set('error', 'Pengajuan tidak bisa dibatalkan.');
    }
    redirect('peminjam/riwayat.php');
}

$filters = [
    'semua'        => ['Semua', ''],
    'menunggu'     => ['Menunggu', ' AND p.persetujuan = 0'],
    'dipinjam'     => ['Dipinjam', ' AND p.persetujuan = 1 AND p.status = 1'],
    'dikembalikan' => ['Selesai', ' AND p.persetujuan = 1 AND p.status = 2'],
    'ditolak'      => ['Ditolak', ' AND p.persetujuan = 2'],
];
$filter = isset($_GET['filter']) && isset($filters[$_GET['filter']]) ? $_GET['filter'] : 'semua';

$daftar = db_all($koneksi, 'SELECT p.*, a.nama_alat, a.id_kategori, k.nama_kategori, g.tanggal_kembali AS tgl_dikembalikan
                            FROM peminjaman p
                            JOIN alat a ON a.id_alat = p.id_alat
                            JOIN kategori k ON k.id_kategori = a.id_kategori
                            LEFT JOIN pengembalian g ON g.id_peminjaman = p.id_peminjaman
                            WHERE p.id_user = ?' . $filters[$filter][1] . '
                            ORDER BY p.id_peminjaman DESC', [$user['id']]);

$jumlah = [];
foreach ($filters as $kode => $f) {
    $jumlah[$kode] = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman p WHERE p.id_user = ?' . $f[1], [$user['id']]);
}

$judul = 'Riwayat';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900">Riwayat peminjaman</h1>
            <p class="mt-1 text-sm text-zinc-500">Semua pengajuan dan statusnya, dari yang terbaru.</p>
        </div>
        <a href="<?= url('peminjam/pinjam.php') ?>" class="btn btn-primary "><?= icon('plus', 'w-4 h-4') ?> Ajukan baru</a>
    </div>

    <div class="mt-6 flex gap-2 overflow-x-auto pb-1 scroll-thin">
        <?php foreach ($filters as $kode => $f): ?>
            <a href="<?= url('peminjam/riwayat.php?filter=' . $kode) ?>" class="chip <?= $filter === $kode ? 'chip-on' : '' ?>"><?= e($f[0]) ?> <span class="text-xs opacity-70"><?= $jumlah[$kode] ?></span></a>
        <?php endforeach; ?>
    </div>

    <?php if ($daftar): ?>
        <ol class="mt-5 space-y-3">
            <?php foreach ($daftar as $i => $p):
                $s = status_info($p);
                $lama = max(1, hari_selisih($p['tanggal_pinjam'], $p['tanggal_kembali'])); ?>
                <li>
                    <article class="card p-5">
                        <div class="flex items-start gap-4">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-accent-100 text-accent-600"><?= icon(kategori_ikon($p['nama_kategori']), 'w-5 h-5') ?></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <h3 class="text-base font-semibold text-zinc-900"><?= e($p['nama_alat']) ?></h3>
                                    <?= badge($s[0], $s[1]) ?>
                                </div>
                                <p class="mt-1 text-sm text-zinc-500"><?= e($p['nama_kategori']) ?> · #<?= (int)$p['id_peminjaman'] ?></p>
                            </div>
                        </div>
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                            <div><dt class="text-xs text-zinc-500">Tanggal pinjam</dt><dd class="font-medium text-zinc-900"><?= tgl($p['tanggal_pinjam']) ?></dd></div>
                            <div><dt class="text-xs text-zinc-500">Batas kembali</dt><dd class="font-medium text-zinc-900"><?= tgl($p['tanggal_kembali']) ?></dd></div>
                            <div><dt class="text-xs text-zinc-500">Lama</dt><dd class="font-medium text-zinc-900"><?= $lama ?> hari</dd></div>
                            <div><dt class="text-xs text-zinc-500">Dikembalikan</dt><dd class="font-medium text-zinc-900"><?= $p['tgl_dikembalikan'] ? tgl($p['tgl_dikembalikan']) : '-' ?></dd></div>
                        </dl>
                        <?php if ((int)$p['persetujuan'] === 0): ?>
                            <form method="post" class="mt-4 border-t border-zinc-100 pt-4" data-confirm="Pengajuan <?= e($p['nama_alat']) ?> akan dibatalkan." data-confirm-title="Batalkan pengajuan?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id_peminjaman" value="<?= (int)$p['id_peminjaman'] ?>">
                                <button class="btn btn-danger-soft btn-sm">Batalkan pengajuan</button>
                            </form>
                        <?php endif; ?>
                    </article>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php else: ?>
        <div class="card mt-5 py-16 text-center">
            <span class="mx-auto grid h-10 w-10 place-items-center rounded-xl bg-zinc-100 text-zinc-600"><?= icon('clock', 'w-5 h-5') ?></span>
            <p class="mt-4 text-base font-semibold text-zinc-900">Belum ada riwayat</p>
            <p class="mt-1 text-sm text-zinc-500">Pengajuan yang kamu buat akan muncul di sini.</p>
            <a href="<?= url('peminjam/daftar_alat.php') ?>" class="btn btn-soft mt-5">Lihat katalog alat</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>

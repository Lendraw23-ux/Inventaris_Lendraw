<?php
$level_boleh = ['petugas'];
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';
    $id   = (int)(isset($_POST['id_peminjaman']) ? $_POST['id_peminjaman'] : 0);
    if ($aksi === 'setujui' || $aksi === 'tolak') {
        $hasil = proses_persetujuan($koneksi, $id, $aksi, $user['nama']);
    } elseif ($aksi === 'kembali') {
        $hasil = proses_pengembalian($koneksi, $id, $user['nama']);
    } else {
        $hasil = [false, 'Aksi tidak dikenal.'];
    }
    flash_set($hasil[0] ? 'success' : 'error', $hasil[1]);
    redirect('petugas/dashboard.php');
}

$menunggu   = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman WHERE persetujuan = 0');
$dipinjam   = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman WHERE persetujuan = 1 AND status = 1');
$terlambat  = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman WHERE persetujuan = 1 AND status = 1 AND tanggal_kembali < CURDATE()');
$kembali_hr = (int)db_val($koneksi, 'SELECT COUNT(*) FROM pengembalian WHERE tanggal_kembali = CURDATE()');

$antrean = db_all($koneksi, 'SELECT p.*, u.nama AS peminjam, a.nama_alat, a.stok, a.id_kategori, k.nama_kategori
                             FROM peminjaman p
                             JOIN user u ON u.id_user = p.id_user
                             JOIN alat a ON a.id_alat = p.id_alat
                             JOIN kategori k ON k.id_kategori = a.id_kategori
                             WHERE p.persetujuan = 0 ORDER BY p.id_peminjaman ASC LIMIT 4');

$jatuh_tempo = db_all($koneksi, 'SELECT p.*, u.nama AS peminjam, a.nama_alat
                                 FROM peminjaman p
                                 JOIN user u ON u.id_user = p.id_user
                                 JOIN alat a ON a.id_alat = p.id_alat
                                 WHERE p.persetujuan = 1 AND p.status = 1 AND p.tanggal_kembali <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
                                 ORDER BY p.tanggal_kembali ASC LIMIT 6');

$judul = 'Dashboard';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-2xl font-semibold text-zinc-900">Dashboard</h2>
        <p class="mt-1 text-sm text-zinc-500">
            Halo, <?= e(explode(' ', $user['nama'])[0]) ?>.
            <?= $menunggu > 0 ? 'Ada ' . $menunggu . ' pengajuan yang menunggu keputusan Anda.' : 'Tidak ada antrean pengajuan.' ?>
        </p>
    </div>
    <a href="<?= url('petugas/setujui.php') ?>" class="btn btn-primary"><?= icon('check', 'w-4 h-4') ?> Buka persetujuan</a>
</div>

<section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    <?php foreach ([
        ['Menunggu', $menunggu, 'text-zinc-900'],
        ['Sedang dipinjam', $dipinjam, 'text-zinc-900'],
        ['Terlambat', $terlambat, $terlambat > 0 ? 'text-red-600' : 'text-zinc-900'],
        ['Kembali hari ini', $kembali_hr, 'text-zinc-900'],
    ] as $s): ?>
        <div class="card p-4">
            <p class="text-sm text-zinc-500"><?= e($s[0]) ?></p>
            <p class="mt-2 text-3xl font-semibold <?= $s[2] ?>" data-count="<?= (int)$s[1] ?>"><?= (int)$s[1] ?></p>
        </div>
    <?php endforeach; ?>
</section>

<section class="mt-8 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-zinc-900">Antrean persetujuan</h3>
            <a href="<?= url('petugas/setujui.php') ?>" class="text-sm font-medium text-accent-700 hover:underline">Lihat semua</a>
        </div>
        <div class="space-y-3">
            <?php foreach ($antrean as $i => $r): ?>
                <article class="card flex flex-col gap-4 p-4 sm:flex-row sm:items-center">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-accent-100 text-accent-600"><?= icon(kategori_ikon($r['nama_kategori']), 'w-5 h-5') ?></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-zinc-900"><?= e($r['nama_alat']) ?> <span class="text-sm font-medium text-zinc-400">oleh</span> <?= e($r['peminjam']) ?></p>
                        <p class="text-sm text-zinc-500"><?= tgl($r['tanggal_pinjam']) ?> → <?= tgl($r['tanggal_kembali']) ?> · stok <?= (int)$r['stok'] ?></p>
                    </div>
                    <div class="flex gap-2">
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="aksi" value="setujui"><input type="hidden" name="id_peminjaman" value="<?= (int)$r['id_peminjaman'] ?>"><button class="btn btn-primary btn-sm"><?= icon('check', 'w-4 h-4') ?> Setujui</button></form>
                        <form method="post" data-confirm="Pengajuan <?= e($r['nama_alat']) ?> oleh <?= e($r['peminjam']) ?> akan ditolak." data-confirm-title="Tolak pengajuan?"><?= csrf_field() ?><input type="hidden" name="aksi" value="tolak"><input type="hidden" name="id_peminjaman" value="<?= (int)$r['id_peminjaman'] ?>"><button class="btn btn-danger-soft btn-sm">Tolak</button></form>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$antrean): ?>
                <div class="card py-12 text-center">
                    <span class="mx-auto grid h-10 w-10 place-items-center rounded-xl bg-zinc-100 text-zinc-600"><?= icon('check', 'w-5 h-5') ?></span>
                    <p class="mt-3 font-semibold text-zinc-900">Antrean kosong</p>
                    <p class="text-sm text-zinc-500">Pengajuan baru akan muncul di sini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <h3 class="mb-3 text-lg font-semibold text-zinc-900">Jatuh tempo 2 hari ke depan</h3>
        <div class="card divide-y divide-zinc-100 overflow-hidden">
            <?php foreach ($jatuh_tempo as $r):
                $telat = $r['tanggal_kembali'] < date('Y-m-d');
                $d = abs(hari_selisih(date('Y-m-d'), $r['tanggal_kembali'])); ?>
                <div class="flex items-center gap-3 p-4">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-zinc-900"><?= e($r['nama_alat']) ?></p>
                        <p class="text-xs text-zinc-500"><?= e($r['peminjam']) ?> · <?= tgl($r['tanggal_kembali']) ?></p>
                    </div>
                    <?= $telat ? badge('Telat ' . $d . ' hari', 'red') : badge($d === 0 ? 'Hari ini' : $d . ' hari lagi', 'amber') ?>
                </div>
            <?php endforeach; ?>
            <?php if (!$jatuh_tempo): ?><p class="p-6 text-center text-sm text-zinc-500">Tidak ada yang mendekati jatuh tempo.</p><?php endif; ?>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>

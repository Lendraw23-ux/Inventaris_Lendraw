<?php
$level_boleh = ['admin'];
require_once '../includes/auth.php';

// ---- Data ringkasan ----
$total_user      = (int)db_val($koneksi, 'SELECT COUNT(*) FROM user');
$jenis_alat      = (int)db_val($koneksi, 'SELECT COUNT(*) FROM alat');
$unit_tersedia   = (int)db_val($koneksi, 'SELECT COALESCE(SUM(stok),0) FROM alat');
$sedang_dipinjam = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman WHERE persetujuan = 1 AND status = 1');
$terlambat       = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman WHERE persetujuan = 1 AND status = 1 AND tanggal_kembali < CURDATE()');
$menunggu        = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman WHERE persetujuan = 0');

// Peminjaman 6 bulan terakhir
$nama_bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$bulan = [];
for ($i = 5; $i >= 0; $i--) {
    $t = strtotime("first day of -$i month");
    $bulan[date('Y-m', $t)] = ['label' => $nama_bulan[(int)date('n', $t)], 'jumlah' => 0];
}
$rows = db_all($koneksi, "SELECT DATE_FORMAT(tanggal_pinjam, '%Y-%m') AS ym, COUNT(*) AS n
                          FROM peminjaman
                          WHERE tanggal_pinjam >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 5 MONTH)
                          GROUP BY ym");
foreach ($rows as $r) {
    if (isset($bulan[$r['ym']])) {
        $bulan[$r['ym']]['jumlah'] = (int)$r['n'];
    }
}
$maks = max(1, max(array_column($bulan, 'jumlah')));

// Sebaran status
$s_menunggu = $menunggu;
$s_dipinjam = $sedang_dipinjam;
$s_kembali  = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman WHERE persetujuan = 1 AND status = 2');
$s_tolak    = (int)db_val($koneksi, 'SELECT COUNT(*) FROM peminjaman WHERE persetujuan = 2');
$s_total    = max(1, $s_menunggu + $s_dipinjam + $s_kembali + $s_tolak);
$sebaran = [
    ['Menunggu', $s_menunggu, '#F59E0B'],
    ['Dipinjam', $s_dipinjam, '#2748CF'],
    ['Dikembalikan', $s_kembali, '#10B981'],
    ['Ditolak', $s_tolak, '#DC2626'],
];

$terbaru = db_all($koneksi, 'SELECT p.*, u.nama AS peminjam, a.nama_alat
                             FROM peminjaman p
                             JOIN user u ON u.id_user = p.id_user
                             JOIN alat a ON a.id_alat = p.id_alat
                             ORDER BY p.id_peminjaman DESC LIMIT 6');
$log = db_all($koneksi, 'SELECT * FROM log_aktivitas ORDER BY id_log DESC LIMIT 6');
$stok_tipis = db_all($koneksi, 'SELECT a.*, k.nama_kategori FROM alat a JOIN kategori k ON k.id_kategori = a.id_kategori WHERE a.stok <= 2 ORDER BY a.stok ASC, a.nama_alat LIMIT 5');

$judul = 'Dashboard';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<!-- Ringkasan -->
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h2 class="text-2xl font-semibold text-zinc-900">Dashboard</h2>
        <p class="mt-1 text-sm text-zinc-500">
            Halo, <?= e(explode(' ', $user['nama'])[0]) ?>.
            <?= $menunggu > 0 ? $menunggu . ' pengajuan menunggu persetujuan' : 'Semua pengajuan sudah diproses' ?>,
            <?= $sedang_dipinjam ?> alat sedang dipinjam<?= $terlambat > 0 ? ' (' . $terlambat . ' terlambat)' : '' ?>.
        </p>
    </div>
    <a href="<?= url('admin/alat.php') ?>" class="btn btn-primary"><?= icon('plus', 'w-4 h-4') ?> Tambah alat</a>
</div>

<!-- Statistik -->
<section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    <?php
    $stat = [
        ['Total pengguna', $total_user, 'admin/user.php', 'Admin, petugas, peminjam', 'text-zinc-900'],
        ['Unit tersedia', $unit_tersedia, 'admin/alat.php', $jenis_alat . ' jenis alat', 'text-zinc-900'],
        ['Sedang dipinjam', $sedang_dipinjam, 'admin/peminjaman.php?filter=dipinjam', $terlambat . ' terlambat', $terlambat > 0 ? 'text-red-600' : 'text-zinc-900'],
        ['Menunggu persetujuan', $menunggu, 'admin/peminjaman.php?filter=menunggu', 'Perlu ditindaklanjuti', 'text-zinc-900'],
    ];
    foreach ($stat as $s): ?>
        <a href="<?= url($s[2]) ?>" class="card p-4 transition-colors hover:border-zinc-300">
            <p class="text-sm text-zinc-500"><?= e($s[0]) ?></p>
            <p class="mt-2 text-3xl font-semibold <?= $s[4] ?>" data-count="<?= (int)$s[1] ?>"><?= (int)$s[1] ?></p>
            <p class="mt-1 text-xs text-zinc-500"><?= e($s[3]) ?></p>
        </a>
    <?php endforeach; ?>
</section>

<!-- Grafik -->
<section class="mt-4 grid gap-4 lg:grid-cols-3">
    <div class="card p-6 lg:col-span-2">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-zinc-900">Peminjaman per bulan</h3>
                <p class="text-sm text-zinc-500">Jumlah pengajuan dalam 6 bulan terakhir</p>
            </div>
            <span class="badge badge-gray"><?= array_sum(array_column($bulan, 'jumlah')) ?> total</span>
        </div>
        <div class="mt-8 flex h-52 items-end gap-3 sm:gap-5">
            <?php $n = 0; foreach ($bulan as $b):
                $tinggi = $b['jumlah'] > 0 ? max(8, round($b['jumlah'] / $maks * 100)) : 4; ?>
                <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                    <span class="text-xs font-semibold text-zinc-700"><?= $b['jumlah'] ?></span>
                    <div class="bar-grow w-full max-w-[3.5rem] rounded-t-xl <?= $b['jumlah'] === $maks && $maks > 0 ? 'bg-gradient-to-t from-accent-600 to-accent-300 shadow-glow' : 'bg-accent-100' ?>" style="height:<?= $tinggi ?>%;--d:<?= 200 + $n * 90 ?>ms"></div>
                    <span class="text-xs font-semibold text-zinc-500"><?= e($b['label']) ?></span>
                </div>
            <?php $n++; endforeach; ?>
        </div>
    </div>

    <div class="card p-6">
        <h3 class="text-base font-semibold text-zinc-900">Status peminjaman</h3>
        <p class="text-sm text-zinc-500">Seluruh pengajuan yang pernah masuk</p>
        <div class="mt-6 flex h-4 overflow-hidden rounded-full bg-zinc-100">
            <?php foreach ($sebaran as $s): if ($s[1] > 0): ?>
                <div class="seg-grow h-full" style="width:<?= round($s[1] / $s_total * 100, 1) ?>%;background:<?= $s[2] ?>;--d:300ms" title="<?= e($s[0]) ?>: <?= $s[1] ?>"></div>
            <?php endif; endforeach; ?>
        </div>
        <ul class="mt-6 space-y-3">
            <?php foreach ($sebaran as $s): ?>
                <li class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2.5 font-semibold text-zinc-700"><span class="h-3 w-3 rounded-full" style="background:<?= $s[2] ?>"></span><?= e($s[0]) ?></span>
                    <span class="font-semibold text-zinc-900"><?= $s[1] ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<!-- Tabel & aktivitas -->
<section class="mt-6 grid gap-4 lg:grid-cols-3">
    <div class="card overflow-hidden lg:col-span-2">
        <div class="flex items-center justify-between p-6 pb-4">
            <h3 class="text-base font-semibold text-zinc-900">Peminjaman terbaru</h3>
            <a href="<?= url('admin/peminjaman.php') ?>" class="text-sm font-medium text-accent-700 hover:underline">Lihat semua</a>
        </div>
        <div class="overflow-x-auto scroll-thin">
            <table class="tbl">
                <thead><tr><th>Peminjam</th><th>Alat</th><th>Tanggal pinjam</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($terbaru as $r): ?>
                    <tr>
                        <td class="font-semibold text-zinc-900"><?= e($r['peminjam']) ?></td>
                        <td><?= e($r['nama_alat']) ?></td>
                        <td class="whitespace-nowrap text-zinc-500"><?= tgl($r['tanggal_pinjam']) ?></td>
                        <td><?= badge_status($r) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$terbaru): ?><tr><td colspan="4" class="py-10 text-center text-zinc-500">Belum ada peminjaman.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-6">
            <h3 class="text-base font-semibold text-zinc-900">Stok menipis</h3>
            <ul class="mt-4 space-y-3">
                <?php foreach ($stok_tipis as $a): ?>
                    <li class="flex items-center gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-accent-100 text-accent-600"><?= icon(kategori_ikon($a['nama_kategori']), 'w-[18px] h-[18px]') ?></span>
                        <div class="min-w-0 flex-1 leading-tight">
                            <p class="truncate text-sm font-semibold text-zinc-900"><?= e($a['nama_alat']) ?></p>
                            <p class="text-xs text-zinc-500"><?= e($a['nama_kategori']) ?></p>
                        </div>
                        <span class="badge <?= (int)$a['stok'] === 0 ? 'badge-red' : 'badge-amber' ?>"><?= (int)$a['stok'] === 0 ? 'Habis' : 'Sisa ' . (int)$a['stok'] ?></span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$stok_tipis): ?><li class="text-sm text-zinc-500">Semua stok aman.</li><?php endif; ?>
            </ul>
        </div>

        <div class="card p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-zinc-900">Aktivitas terakhir</h3>
                <a href="<?= url('admin/log_aktivitas.php') ?>" class="text-sm font-medium text-accent-700 hover:underline">Semua</a>
            </div>
            <ol class="relative mt-4 space-y-4 border-l border-zinc-100 pl-5">
                <?php foreach ($log as $l): ?>
                    <li class="relative">
                        <span class="absolute -left-[26px] top-1.5 h-2.5 w-2.5 rounded-full bg-accent-400 ring-4 ring-white"></span>
                        <p class="text-sm leading-snug text-zinc-700"><?= e($l['aktivitas']) ?></p>
                        <p class="text-xs text-zinc-400"><?= tgl($l['tanggal']) ?></p>
                    </li>
                <?php endforeach; ?>
                <?php if (!$log): ?><li class="text-sm text-zinc-500">Belum ada aktivitas.</li><?php endif; ?>
            </ol>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>

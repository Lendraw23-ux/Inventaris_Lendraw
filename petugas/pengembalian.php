<?php
$level_boleh = ['petugas'];
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $id = (int)(isset($_POST['id_peminjaman']) ? $_POST['id_peminjaman'] : 0);
    $hasil = proses_pengembalian($koneksi, $id, $user['nama']);
    flash_set($hasil[0] ? 'success' : 'error', $hasil[1]);
    redirect('petugas/pengembalian.php');
}

$aktif = db_all($koneksi, 'SELECT p.*, u.nama AS peminjam, a.nama_alat, a.id_kategori, k.nama_kategori
                           FROM peminjaman p
                           JOIN user u ON u.id_user = p.id_user
                           JOIN alat a ON a.id_alat = p.id_alat
                           JOIN kategori k ON k.id_kategori = a.id_kategori
                           WHERE p.persetujuan = 1 AND p.status = 1
                           ORDER BY p.tanggal_kembali ASC');

$riwayat = db_all($koneksi, 'SELECT g.*, u.nama AS peminjam, a.nama_alat, p.tanggal_kembali AS batas
                             FROM pengembalian g
                             JOIN user u ON u.id_user = g.id_user
                             JOIN alat a ON a.id_alat = g.id_alat
                             JOIN peminjaman p ON p.id_peminjaman = g.id_peminjaman
                             ORDER BY g.id_pengembalian DESC LIMIT 15');

$judul = 'Pengembalian';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-2xl font-semibold text-zinc-900">Terima pengembalian</h2>
        <p class="mt-1 text-sm text-zinc-500"><?= count($aktif) ?> alat belum kembali. Yang paling mendesak ada di atas.</p>
    </div>
    <div class="relative w-full sm:w-72">
        <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-400"><?= icon('search', 'w-4 h-4') ?></span>
        <input type="search" class="input pl-10" placeholder="Cari peminjam atau alat" data-filter="#tbl-aktif tbody tr" data-empty="#kosong-aktif" aria-label="Cari">
    </div>
</div>

<div class="card mt-6 overflow-hidden">
    <div class="overflow-x-auto scroll-thin">
        <table class="tbl" id="tbl-aktif">
            <thead><tr><th>Peminjam</th><th>Alat</th><th>Batas kembali</th><th>Kondisi waktu</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($aktif as $r):
                $telat = $r['tanggal_kembali'] < date('Y-m-d');
                $d = abs(hari_selisih(date('Y-m-d'), $r['tanggal_kembali'])); ?>
                <tr>
                    <td class="font-semibold text-zinc-900"><?= e($r['peminjam']) ?></td>
                    <td><div class="flex items-center gap-2.5"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-accent-100 text-accent-600"><?= icon(kategori_ikon($r['nama_kategori']), 'w-4 h-4') ?></span><?= e($r['nama_alat']) ?></div></td>
                    <td class="whitespace-nowrap text-zinc-600"><?= tgl($r['tanggal_kembali']) ?></td>
                    <td><?= $telat ? badge('Terlambat ' . $d . ' hari', 'red') : badge($d === 0 ? 'Jatuh tempo hari ini' : $d . ' hari lagi', $d <= 1 ? 'amber' : 'blue') ?></td>
                    <td class="text-right">
                        <form method="post" class="inline" data-confirm="Catat <?= e($r['nama_alat']) ?> dari <?= e($r['peminjam']) ?> sebagai sudah dikembalikan." data-confirm-title="Terima pengembalian?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id_peminjaman" value="<?= (int)$r['id_peminjaman'] ?>">
                            <button class="btn btn-primary btn-sm"><?= icon('return', 'w-4 h-4') ?> Terima</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div id="kosong-aktif" class="<?= $aktif ? 'hidden ' : '' ?>py-14 text-center text-sm text-zinc-500">Tidak ada alat yang menunggu dikembalikan.</div>
</div>

<section class="mt-10">
    <h3 class="mb-3 text-lg font-semibold text-zinc-900">Pengembalian terakhir</h3>
    <div class="card divide-y divide-zinc-100 overflow-hidden">
        <?php foreach ($riwayat as $g): $t = hari_selisih($g['batas'], $g['tanggal_kembali']); ?>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3.5 text-sm">
                <span class="font-semibold text-zinc-900"><?= e($g['peminjam']) ?></span>
                <span class="text-zinc-500"><?= e($g['nama_alat']) ?></span>
                <span class="text-xs text-zinc-400"><?= tgl($g['tanggal_kembali']) ?></span>
                <span class="ml-auto"><?= $t > 0 ? badge('Terlambat ' . $t . ' hari', 'red') : badge('Tepat waktu', 'green') ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (!$riwayat): ?><p class="p-8 text-center text-sm text-zinc-500">Belum ada pengembalian.</p><?php endif; ?>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>

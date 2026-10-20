<?php
$level_boleh = ['admin'];
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $id = (int)(isset($_POST['id_peminjaman']) ? $_POST['id_peminjaman'] : 0);
    $hasil = proses_pengembalian($koneksi, $id, $user['nama']);
    flash_set($hasil[0] ? 'success' : 'error', $hasil[1]);
    redirect('admin/pengembalian.php');
}

// Alat yang masih dipinjam
$aktif = db_all($koneksi, 'SELECT p.*, u.nama AS peminjam, a.nama_alat, a.id_kategori, k.nama_kategori
                           FROM peminjaman p
                           JOIN user u ON u.id_user = p.id_user
                           JOIN alat a ON a.id_alat = p.id_alat
                           JOIN kategori k ON k.id_kategori = a.id_kategori
                           WHERE p.persetujuan = 1 AND p.status = 1
                           ORDER BY p.tanggal_kembali ASC');

// Riwayat pengembalian
$riwayat = db_all($koneksi, 'SELECT g.*, u.nama AS peminjam, a.nama_alat, p.tanggal_pinjam, p.tanggal_kembali AS batas
                             FROM pengembalian g
                             JOIN user u ON u.id_user = g.id_user
                             JOIN alat a ON a.id_alat = g.id_alat
                             JOIN peminjaman p ON p.id_peminjaman = g.id_peminjaman
                             ORDER BY g.id_pengembalian DESC LIMIT 50');

$judul = 'Pengembalian';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="">
    <h2 class="text-2xl font-semibold text-zinc-900">Pengembalian alat</h2>
    <p class="mt-1 text-sm text-zinc-500">Terima alat yang kembali dan lihat riwayatnya.</p>
</div>

<section class="mt-6">
    <h3 class="mb-3 text-lg font-semibold text-zinc-900">Belum dikembalikan <span class="ml-1 text-sm font-semibold text-zinc-400"><?= count($aktif) ?></span></h3>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <?php foreach ($aktif as $i => $r):
            $telat = $r['tanggal_kembali'] < date('Y-m-d');
            $selisih = abs(hari_selisih(date('Y-m-d'), $r['tanggal_kembali']));
        ?>
            <article class="card p-5 <?= $telat ? 'ring-2 ring-red-500/40' : '' ?>">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-accent-100 text-accent-600"><?= icon(kategori_ikon($r['nama_kategori']), 'w-5 h-5') ?></span>
                    <div class="min-w-0 flex-1">
                        <h4 class="truncate font-semibold text-zinc-900"><?= e($r['nama_alat']) ?></h4>
                        <p class="text-sm text-zinc-500"><?= e($r['peminjam']) ?></p>
                    </div>
                    <?= $telat ? badge('Telat ' . $selisih . ' hari', 'red') : badge($selisih === 0 ? 'Hari ini' : $selisih . ' hari lagi', 'blue') ?>
                </div>
                <p class="mt-4 flex items-center gap-2 text-xs text-zinc-500"><?= icon('calendar', 'w-4 h-4') ?> Batas kembali <?= tgl($r['tanggal_kembali']) ?></p>
                <form method="post" class="mt-4" data-confirm="Catat <?= e($r['nama_alat']) ?> dari <?= e($r['peminjam']) ?> sebagai sudah dikembalikan." data-confirm-title="Terima pengembalian?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_peminjaman" value="<?= (int)$r['id_peminjaman'] ?>">
                    <button class="btn btn-primary w-full"><?= icon('return', 'w-4 h-4') ?> Terima pengembalian</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (!$aktif): ?>
        <div class="card py-14 text-center">
            <span class="mx-auto grid h-10 w-10 place-items-center rounded-xl bg-zinc-100 text-zinc-600"><?= icon('check', 'w-5 h-5') ?></span>
            <p class="mt-4 text-base font-semibold text-zinc-900">Semua alat sudah kembali</p>
            <p class="mt-1 text-sm text-zinc-500">Tidak ada peminjaman yang masih berjalan.</p>
        </div>
    <?php endif; ?>
</section>

<section class="mt-10">
    <h3 class="mb-3 text-lg font-semibold text-zinc-900">Riwayat pengembalian</h3>
    <div class="card overflow-hidden">
        <div class="overflow-x-auto scroll-thin">
            <table class="tbl">
                <thead><tr><th>Peminjam</th><th>Alat</th><th>Dipinjam</th><th>Dikembalikan</th><th>Keterangan</th></tr></thead>
                <tbody>
                <?php foreach ($riwayat as $g):
                    $telat_hari = hari_selisih($g['batas'], $g['tanggal_kembali']); ?>
                    <tr>
                        <td class="font-semibold text-zinc-900"><?= e($g['peminjam']) ?></td>
                        <td><?= e($g['nama_alat']) ?></td>
                        <td class="whitespace-nowrap text-zinc-500"><?= tgl($g['tanggal_pinjam']) ?></td>
                        <td class="whitespace-nowrap text-zinc-500"><?= tgl($g['tanggal_kembali']) ?></td>
                        <td><?= $telat_hari > 0 ? badge('Terlambat ' . $telat_hari . ' hari', 'red') : badge('Tepat waktu', 'green') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$riwayat): ?><tr><td colspan="5" class="py-12 text-center text-zinc-500">Belum ada pengembalian.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>

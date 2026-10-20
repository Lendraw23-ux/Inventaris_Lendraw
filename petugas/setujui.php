<?php
$level_boleh = ['petugas'];
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';
    $id   = (int)(isset($_POST['id_peminjaman']) ? $_POST['id_peminjaman'] : 0);
    if ($aksi === 'setujui' || $aksi === 'tolak') {
        $hasil = proses_persetujuan($koneksi, $id, $aksi, $user['nama']);
    } else {
        $hasil = [false, 'Aksi tidak dikenal.'];
    }
    flash_set($hasil[0] ? 'success' : 'error', $hasil[1]);
    redirect('petugas/setujui.php');
}

$antrean = db_all($koneksi, 'SELECT p.*, u.nama AS peminjam, a.nama_alat, a.stok, a.kondisi, a.id_kategori, k.nama_kategori,
                                    (SELECT COUNT(*) FROM peminjaman x WHERE x.id_user = p.id_user AND x.persetujuan = 1 AND x.status = 1 AND x.tanggal_kembali < CURDATE()) AS tunggakan
                             FROM peminjaman p
                             JOIN user u ON u.id_user = p.id_user
                             JOIN alat a ON a.id_alat = p.id_alat
                             JOIN kategori k ON k.id_kategori = a.id_kategori
                             WHERE p.persetujuan = 0 ORDER BY p.id_peminjaman ASC');

$selesai = db_all($koneksi, 'SELECT p.*, u.nama AS peminjam, a.nama_alat
                             FROM peminjaman p
                             JOIN user u ON u.id_user = p.id_user
                             JOIN alat a ON a.id_alat = p.id_alat
                             WHERE p.persetujuan IN (1, 2) ORDER BY p.id_peminjaman DESC LIMIT 6');

$judul = 'Persetujuan';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="">
    <h2 class="text-2xl font-semibold text-zinc-900">Persetujuan peminjaman</h2>
    <p class="mt-1 text-sm text-zinc-500">Menyetujui pengajuan otomatis mengurangi stok alat satu unit.</p>
</div>

<div class="mt-6 grid gap-5 md:grid-cols-2">
    <?php foreach ($antrean as $i => $r):
        $habis = (int)$r['stok'] < 1;
        $lama = max(1, hari_selisih($r['tanggal_pinjam'], $r['tanggal_kembali']));
    ?>
        <article class="card flex flex-col overflow-hidden">
            <div class="flex items-center gap-4 p-5">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-accent-100 text-accent-600"><?= icon(kategori_ikon($r['nama_kategori']), 'w-5 h-5') ?></span>
                <div class="min-w-0 flex-1">
                    <h3 class="truncate text-lg font-semibold text-zinc-900"><?= e($r['nama_alat']) ?></h3>
                    <p class="text-sm text-zinc-500"><?= e($r['nama_kategori']) ?> · <?= badge_kondisi($r['kondisi']) ?></p>
                </div>
            </div>
            <dl class="grid grid-cols-3 gap-px bg-zinc-100 text-center text-sm">
                <div class="bg-white p-3"><dt class="text-xs text-zinc-500">Peminjam</dt><dd class="mt-0.5 truncate font-semibold text-zinc-900"><?= e($r['peminjam']) ?></dd></div>
                <div class="bg-white p-3"><dt class="text-xs text-zinc-500">Lama</dt><dd class="mt-0.5 font-semibold text-zinc-900"><?= $lama ?> hari</dd></div>
                <div class="bg-white p-3"><dt class="text-xs text-zinc-500">Stok</dt><dd class="mt-0.5 font-semibold <?= $habis ? 'text-red-600' : 'text-zinc-900' ?>"><?= (int)$r['stok'] ?> unit</dd></div>
            </dl>
            <div class="flex-1 px-5 pt-4 text-sm text-zinc-600">
                <p class="flex items-center gap-2"><?= icon('calendar', 'w-4 h-4 text-accent-600') ?> <?= tgl($r['tanggal_pinjam']) ?> sampai <?= tgl($r['tanggal_kembali']) ?></p>
                <?php if ((int)$r['tunggakan'] > 0): ?>
                    <p class="mt-2 flex items-center gap-2 font-semibold text-red-600"><?= icon('alert', 'w-4 h-4') ?> Peminjam masih punya <?= (int)$r['tunggakan'] ?> alat yang terlambat dikembalikan.</p>
                <?php endif; ?>
                <?php if ($habis): ?>
                    <p class="mt-2 flex items-center gap-2 font-semibold text-red-600"><?= icon('ban', 'w-4 h-4') ?> Stok habis, pengajuan hanya bisa ditolak.</p>
                <?php endif; ?>
            </div>
            <div class="flex gap-3 p-5">
                <form method="post" class="flex-1"><?= csrf_field() ?><input type="hidden" name="aksi" value="setujui"><input type="hidden" name="id_peminjaman" value="<?= (int)$r['id_peminjaman'] ?>">
                    <button class="btn btn-primary w-full" <?= $habis ? 'disabled' : '' ?>><?= icon('check', 'w-4 h-4') ?> Setujui</button></form>
                <form method="post" class="flex-1" data-confirm="Pengajuan <?= e($r['nama_alat']) ?> oleh <?= e($r['peminjam']) ?> akan ditolak." data-confirm-title="Tolak pengajuan?"><?= csrf_field() ?><input type="hidden" name="aksi" value="tolak"><input type="hidden" name="id_peminjaman" value="<?= (int)$r['id_peminjaman'] ?>">
                    <button class="btn btn-danger-soft w-full">Tolak</button></form>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php if (!$antrean): ?>
    <div class="card mt-6 py-16 text-center">
        <span class="mx-auto grid h-10 w-10 place-items-center rounded-xl bg-zinc-100 text-zinc-600"><?= icon('check', 'w-5 h-5') ?></span>
        <p class="mt-4 text-base font-semibold text-zinc-900">Tidak ada pengajuan baru</p>
        <p class="mt-1 text-sm text-zinc-500">Semua pengajuan sudah Anda proses.</p>
    </div>
<?php endif; ?>

<?php if ($selesai): ?>
<section class="mt-10">
    <h3 class="mb-3 text-lg font-semibold text-zinc-900">Baru saja diputuskan</h3>
    <div class="card divide-y divide-zinc-100 overflow-hidden">
        <?php foreach ($selesai as $r): ?>
            <div class="flex flex-wrap items-center gap-3 px-4 py-3.5 text-sm">
                <span class="font-semibold text-zinc-900"><?= e($r['peminjam']) ?></span>
                <span class="text-zinc-500">· <?= e($r['nama_alat']) ?></span>
                <span class="ml-auto"><?= badge_status($r) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>

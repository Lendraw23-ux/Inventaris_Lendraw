<?php
$level_boleh = ['admin'];
require_once '../includes/auth.php';

$per_halaman = 25;
$halaman = max(1, (int)(isset($_GET['hal']) ? $_GET['hal'] : 1));
$total = (int)db_val($koneksi, 'SELECT COUNT(*) FROM log_aktivitas');
$total_hal = max(1, (int)ceil($total / $per_halaman));
$halaman = min($halaman, $total_hal);
$offset = ($halaman - 1) * $per_halaman;

$daftar = db_all($koneksi, 'SELECT * FROM log_aktivitas ORDER BY id_log DESC LIMIT ' . (int)$per_halaman . ' OFFSET ' . (int)$offset);

// Kelompokkan per tanggal
$grup = [];
foreach ($daftar as $l) {
    $grup[$l['tanggal']][] = $l;
}

function ikon_log($teks)
{
    $t = strtolower($teks);
    if (strpos($t, 'masuk') !== false || strpos($t, 'keluar dari') !== false) return ['user', 'bg-zinc-100 text-zinc-600'];
    if (strpos($t, 'menyetujui') !== false) return ['check', 'bg-emerald-100 text-emerald-700'];
    if (strpos($t, 'menolak') !== false || strpos($t, 'menghapus') !== false) return ['ban', 'bg-red-100 text-red-700'];
    if (strpos($t, 'pengembalian') !== false) return ['return', 'bg-sky-100 text-sky-700'];
    if (strpos($t, 'mengajukan') !== false || strpos($t, 'menambahkan') !== false) return ['plus', 'bg-amber-100 text-amber-600'];
    return ['activity', 'bg-accent-50 text-accent-700'];
}

$judul = 'Log Aktivitas';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
?>

<div class="flex flex-col gap-1">
    <h2 class="text-2xl font-semibold text-zinc-900">Log aktivitas</h2>
    <p class="text-sm text-zinc-500"><?= $total ?> catatan tersimpan. Setiap login, persetujuan, dan perubahan data tercatat otomatis.</p>
</div>

<div class="mt-6 space-y-8">
    <?php foreach ($grup as $tanggal => $items): ?>
        <section>
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-zinc-700"><?= icon('calendar', 'w-4 h-4 text-accent-600') ?> <?= tgl($tanggal) ?><?= $tanggal === date('Y-m-d') ? ' <span class="badge badge-green">Hari ini</span>' : '' ?></h3>
            <div class="card divide-y divide-zinc-100 overflow-hidden">
                <?php foreach ($items as $l): $ik = ikon_log($l['aktivitas']); ?>
                    <div class="flex items-center gap-4 px-4 py-3.5 transition hover:bg-accent-50/40">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full <?= $ik[1] ?>"><?= icon($ik[0], 'w-4 h-4') ?></span>
                        <p class="text-sm text-zinc-700"><?= e($l['aktivitas']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    <?php if (!$grup): ?>
        <div class="card py-16 text-center text-sm text-zinc-500">Belum ada aktivitas tercatat.</div>
    <?php endif; ?>
</div>

<?php if ($total_hal > 1): ?>
    <nav class="mt-8 flex items-center justify-center gap-2" aria-label="Halaman">
        <?php if ($halaman > 1): ?><a class="btn btn-ghost btn-sm" href="?hal=<?= $halaman - 1 ?>">Sebelumnya</a><?php endif; ?>
        <span class="px-3 text-sm font-semibold text-zinc-600">Halaman <?= $halaman ?> dari <?= $total_hal ?></span>
        <?php if ($halaman < $total_hal): ?><a class="btn btn-ghost btn-sm" href="?hal=<?= $halaman + 1 ?>">Berikutnya</a><?php endif; ?>
    </nav>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>

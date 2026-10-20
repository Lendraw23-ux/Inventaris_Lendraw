<?php
require_once __DIR__ . '/../config/koneksi.php';

// Sudah login? langsung ke dashboard masing-masing.
if (!empty($_SESSION['id_user']) && !empty($_SESSION['level'])) {
    redirect(dashboard_path($_SESSION['level']));
}

$judul = 'Masuk';
$tanpa_layout = true;
require_once __DIR__ . '/../includes/header.php';

// Posisi ikon yang mengorbit maskot: [ikon, sudut]
$orbit = [['monitor', 0], ['camera', 120], ['tool', 240]];
?>
<div class="relative z-10 flex min-h-screen items-center justify-center px-4 py-10">
    <div class="grid w-full max-w-5xl items-center gap-10 lg:grid-cols-[1.05fr_1fr]">

        <!-- Panggung maskot (layar lebar) -->
        <section class="masuk hidden text-center lg:block" aria-hidden="true">
            <div class="relative mx-auto h-[22rem] w-[22rem]">
                <div class="orbit">
                    <?php foreach ($orbit as $o): ?>
                        <span style="transform: rotate(<?= $o[1] ?>deg) translateY(-10.5rem) rotate(-<?= $o[1] ?>deg)"><?= icon($o[0], 'w-6 h-6') ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="absolute inset-0 grid place-items-center"><?= maskot('h-64 w-64') ?></div>
            </div>
            <h2 class="mt-4 font-display text-5xl text-accent-700">ようこそ</h2>
            <p class="mx-auto mt-3 max-w-xs text-base font-medium leading-relaxed text-zinc-600">Pinjam proyektor, laptop, dan kamera sekolah tanpa antre kertas.</p>
        </section>

        <!-- Formulir -->
        <section class="masuk mx-auto w-full max-w-sm" style="--d:120ms">
            <div class="flex items-center gap-3">
                <?= logo_svg_login() ?>
                <span class="text-xl font-black text-zinc-900">Pinjamin</span>
            </div>
            <div class="mt-4 lg:hidden"><?= maskot('mx-auto h-28 w-28') ?></div>

            <h1 class="mt-6 text-3xl font-black text-zinc-900">Masuk ke akun</h1>
            <p class="mt-1 text-sm text-zinc-500">Gunakan username dan password dari admin.</p>

            <form action="proses_login.php" method="post" class="card mt-6 space-y-4 p-6">
                <?= csrf_field() ?>
                <div>
                    <label class="label" for="username">Username</label>
                    <input class="input" type="text" id="username" name="username" autocomplete="username" required autofocus>
                </div>
                <div>
                    <label class="label" for="password">Password</label>
                    <div class="relative">
                        <input class="input pr-10" type="password" id="password" name="password" autocomplete="current-password" required>
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1 text-zinc-400 transition hover:text-accent-600" data-pass-toggle="#password" aria-label="Tampilkan password"><?= icon('eye', 'w-[18px] h-[18px]') ?></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-full py-3 text-base">Masuk</button>
            </form>

            <!-- Akun contoh: hapus blok ini saat aplikasi dipakai sungguhan -->
            <div class="mt-6 flex flex-wrap items-center gap-2 text-sm text-zinc-500">
                <span class="font-bold">Akun contoh:</span>
                <?php foreach ([['admin', 'admin123'], ['petugas', 'petugas123'], ['peminjam', 'peminjam123']] as $ak): ?>
                    <button type="button" class="chip py-1" data-akun="<?= e($ak[0]) ?>|<?= e($ak[1]) ?>"><?= e(ucfirst($ak[0])) ?></button>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>

<script>
document.querySelectorAll('[data-akun]').forEach(function (b) {
    b.addEventListener('click', function () {
        var p = b.getAttribute('data-akun').split('|');
        document.getElementById('username').value = p[0];
        document.getElementById('password').value = p[1];
        document.getElementById('password').focus();
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

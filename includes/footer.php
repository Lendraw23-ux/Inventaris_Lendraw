<?php
/**
 * includes/footer.php
 * Menutup layout, menampilkan notifikasi (flash), dan memuat JS global.
 * Set $tanpa_layout = true di halaman tanpa navbar (login).
 */
$tanpa_layout = isset($tanpa_layout) ? $tanpa_layout : false;
$flash = flash_ambil();

if (!$tanpa_layout): ?>
</main>

<footer class="no-print mx-auto max-w-6xl px-4 pb-10 pt-6 text-sm text-zinc-500 sm:px-6 lg:px-8">
    <div class="seigaiha mb-5 opacity-80" aria-hidden="true"></div>
    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
        <p class="font-bold text-zinc-600">© <?= date('Y') ?> Pinjamin <span class="ml-1 text-xs font-bold text-accent-500">ありがとう</span></p>
        <p>Layanan Senin–Jumat, 07.30–16.00 WIB. Surel: inventaris@sekolah.id</p>
    </div>
</footer>
<?php endif; ?>

<?php if ($flash): ?>
<div class="pointer-events-none fixed inset-x-0 top-4 z-[70] flex justify-center px-4 no-print" role="status" aria-live="polite">
    <div id="toast" class="toast-auto pointer-events-auto flex max-w-md items-start gap-3 rounded-2xl border-2 bg-white px-4 py-3 text-sm font-medium text-zinc-800 shadow-glow <?= $flash['tipe'] === 'error' ? 'border-red-200' : 'border-accent-200' ?>">
        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full text-white <?= $flash['tipe'] === 'error' ? 'bg-red-600' : 'bg-gradient-to-br from-accent-400 to-accent-600' ?>">
            <?= icon($flash['tipe'] === 'error' ? 'alert' : 'check', 'w-3 h-3') ?>
        </span>
        <p class="leading-snug"><?= e($flash['pesan']) ?></p>
        <button type="button" class="-mr-1 ml-1 text-zinc-400 hover:text-zinc-800" onclick="this.closest('#toast').remove()" aria-label="Tutup"><?= icon('x', 'w-4 h-4') ?></button>
    </div>
</div>
<?php endif; ?>

<!-- Dialog konfirmasi (dipakai tombol hapus, tolak, dll) -->
<div id="confirm-modal" class="modal no-print" role="dialog" aria-modal="true" aria-labelledby="confirm-judul">
    <div class="modal-bg" data-modal-close></div>
    <div class="modal-box p-6 sm:max-w-sm">
        <span class="mb-3 grid h-11 w-11 place-items-center rounded-2xl bg-accent-100 text-accent-600"><?= icon('alert', 'w-5 h-5') ?></span>
        <h3 id="confirm-judul" class="text-lg font-black text-zinc-900">Lanjutkan tindakan ini?</h3>
        <p id="confirm-teks" class="mt-1.5 text-sm text-zinc-500"></p>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" class="btn btn-ghost" data-modal-close>Batal</button>
            <button type="button" id="confirm-ok" class="btn btn-danger">Ya, lanjutkan</button>
        </div>
    </div>
</div>

<script>
(function () {
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

    /* Toggle umum (menu mobile) */
    $$('[data-toggle]').forEach(function (el) {
        el.addEventListener('click', function () { var t = $(el.getAttribute('data-toggle')); if (t) t.classList.toggle('hidden'); });
    });

    /* Modal */
    function modal(m, buka) {
        if (!m) return;
        m.classList.toggle('open', buka);
        document.body.style.overflow = buka ? 'hidden' : '';
        if (buka) { var f = $('input:not([type=hidden]),select,textarea', m); if (f) setTimeout(function () { f.focus(); }, 60); }
    }
    $$('[data-modal-open]').forEach(function (el) {
        el.addEventListener('click', function () {
            var m = $(el.getAttribute('data-modal-open'));
            if (!m) return;
            var form = $('form', m);
            if (form) {
                form.reset();
                var data = el.getAttribute('data-json');
                data = data ? JSON.parse(data) : {};
                $$('[name]', form).forEach(function (inp) {
                    if (inp.name === 'csrf' || inp.name === 'aksi') return;
                    if (Object.prototype.hasOwnProperty.call(data, inp.name)) inp.value = data[inp.name];
                });
                var judul = $('[data-modal-title]', m);
                if (judul && el.getAttribute('data-title')) judul.textContent = el.getAttribute('data-title');
                var aksi = $('input[name=aksi]', form);
                if (aksi && el.getAttribute('data-aksi')) aksi.value = el.getAttribute('data-aksi');
            }
            modal(m, true);
        });
    });
    $$('[data-modal-close]').forEach(function (el) {
        el.addEventListener('click', function () { modal(el.closest('.modal'), false); });
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape') { $$('.modal.open').forEach(function (m) { modal(m, false); }); }
    });

    /* Konfirmasi sebelum submit */
    var cm = $('#confirm-modal'), pending = null;
    $$('form[data-confirm]').forEach(function (f) {
        f.addEventListener('submit', function (ev) {
            if (f.dataset.ok === '1') return;
            ev.preventDefault();
            pending = f;
            $('#confirm-judul').textContent = f.getAttribute('data-confirm-title') || 'Lanjutkan tindakan ini?';
            $('#confirm-teks').textContent = f.getAttribute('data-confirm');
            modal(cm, true);
        });
    });
    var ok = $('#confirm-ok');
    if (ok) ok.addEventListener('click', function () { if (pending) { pending.dataset.ok = '1'; pending.submit(); } });

    /* Filter tabel / daftar langsung */
    $$('[data-filter]').forEach(function (inp) {
        inp.addEventListener('input', function () {
            var q = inp.value.toLowerCase().trim();
            $$(inp.getAttribute('data-filter')).forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
            });
            var kosong = $(inp.getAttribute('data-empty') || '#none');
            if (kosong) {
                var tampil = $$(inp.getAttribute('data-filter')).some(function (r) { return r.style.display !== 'none'; });
                kosong.classList.toggle('hidden', tampil);
            }
        });
    });

    var kurangGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Gerak masuk: satu rangkaian berurutan saat halaman dibuka */
    try {
        var blok = $$('main > *, .masuk');
        blok.forEach(function (el, i) { el.style.setProperty('--d', Math.min(i, 9) * 80 + 'ms'); });
        requestAnimationFrame(function () { requestAnimationFrame(function () { blok.forEach(function (el) { el.classList.add('tampil'); }); }); });
        setTimeout(function () { blok.forEach(function (el) { el.classList.add('tampil'); }); }, 1500);
    } catch (err) { $$('main > *, .masuk').forEach(function (el) { el.classList.add('tampil'); }); }

    /* Angka statistik berhitung naik */
    $$('[data-count]').forEach(function (el) {
        var akhir = parseInt(el.getAttribute('data-count'), 10) || 0;
        if (kurangGerak || akhir === 0) { el.textContent = akhir; return; }
        var mulai = null, lama = 1000;
        el.textContent = 0;
        function langkah(t) {
            if (mulai === null) mulai = t;
            var p = Math.min((t - mulai) / lama, 1), e = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(akhir * e);
            if (p < 1) requestAnimationFrame(langkah);
        }
        setTimeout(function () { requestAnimationFrame(langkah); }, 250);
    });

    /* Percikan bintang saat tombol / chip / tab ditekan */
    if (!kurangGerak) {
        document.addEventListener('click', function (ev) {
            if (!ev.target.closest || !ev.target.closest('.btn, .chip, .tab, a.card, article.card')) return;
            for (var i = 0; i < 7; i++) {
                var s = document.createElement('i');
                s.className = 'spark';
                var a = (Math.PI * 2 * i) / 7 + Math.random() * 0.6, r = 28 + Math.random() * 26;
                s.style.left = ev.clientX + 'px'; s.style.top = ev.clientY + 'px';
                s.style.setProperty('--x', Math.cos(a) * r + 'px'); s.style.setProperty('--y', Math.sin(a) * r + 'px');
                if (i % 2) s.style.background = '#fff';
                document.body.appendChild(s);
                setTimeout(function (n) { n.remove(); }.bind(null, s), 750);
            }
        });
    }

    /* Kelopak sakura biru + bintang berkelip di latar */
    var cv = $('#sakura');
    if (cv && !kurangGerak) {
        var cx = cv.getContext('2d'), W = 0, H = 0, dpr = Math.min(window.devicePixelRatio || 1, 2);
        var warna = ['#BCD9FF', '#8EC0FF', '#DBEBFF', '#A5D8FF', '#FFFFFF'];
        function ukur() { W = window.innerWidth; H = window.innerHeight; cv.width = W * dpr; cv.height = H * dpr; cx.setTransform(dpr, 0, 0, dpr, 0, 0); }
        ukur(); window.addEventListener('resize', ukur);
        var jumlah = W < 640 ? 16 : 30;
        function kelopak(acak) {
            return { x: Math.random() * W, y: acak ? Math.random() * H : -20, s: 5 + Math.random() * 8, v: 0.35 + Math.random() * 0.7,
                     g: Math.random() * Math.PI * 2, gs: 0.01 + Math.random() * 0.02, a: Math.random() * Math.PI * 2, as: (Math.random() - 0.5) * 0.04,
                     w: warna[(Math.random() * warna.length) | 0], o: 0.5 + Math.random() * 0.4 };
        }
        var k = []; for (var i = 0; i < jumlah; i++) k.push(kelopak(true));
        var bt = []; for (var j = 0; j < 14; j++) bt.push({ x: Math.random() * W, y: Math.random() * H * 0.8, r: 2 + Math.random() * 3, f: Math.random() * 6, v: 0.015 + Math.random() * 0.02 });
        function gambarKelopak(p) {
            cx.save(); cx.translate(p.x, p.y); cx.rotate(p.a); cx.scale(1, 0.7 + 0.3 * Math.sin(p.g * 2)); cx.globalAlpha = p.o; cx.fillStyle = p.w;
            cx.beginPath(); cx.moveTo(0, -p.s);
            cx.bezierCurveTo(p.s * 0.95, -p.s * 0.6, p.s * 0.8, p.s * 0.7, 0, p.s);
            cx.bezierCurveTo(-p.s * 0.8, p.s * 0.7, -p.s * 0.95, -p.s * 0.6, 0, -p.s);
            cx.fill(); cx.restore();
        }
        function gambarBintang(b) {
            var t = 0.5 + 0.5 * Math.sin(b.f); cx.save(); cx.translate(b.x, b.y); cx.globalAlpha = 0.25 + 0.65 * t; cx.fillStyle = '#fff';
            var r = b.r * (0.6 + 0.6 * t); cx.beginPath();
            cx.moveTo(0, -r * 2); cx.lineTo(r * 0.5, -r * 0.5); cx.lineTo(r * 2, 0); cx.lineTo(r * 0.5, r * 0.5);
            cx.lineTo(0, r * 2); cx.lineTo(-r * 0.5, r * 0.5); cx.lineTo(-r * 2, 0); cx.lineTo(-r * 0.5, -r * 0.5);
            cx.closePath(); cx.shadowColor = '#7DE3FF'; cx.shadowBlur = 8; cx.fill(); cx.restore();
        }
        (function loop() {
            cx.clearRect(0, 0, W, H);
            bt.forEach(function (b) { b.f += b.v; gambarBintang(b); });
            k.forEach(function (p, n) {
                p.y += p.v; p.g += p.gs; p.x += Math.sin(p.g) * 0.6 + 0.25; p.a += p.as;
                if (p.y > H + 20 || p.x > W + 20) k[n] = kelopak(false);
                gambarKelopak(p);
            });
            requestAnimationFrame(loop);
        })();
    }

    /* Tampilkan / sembunyikan password */
    $$('[data-pass-toggle]').forEach(function (b) {
        b.addEventListener('click', function () {
            var i = $(b.getAttribute('data-pass-toggle'));
            if (i) i.type = i.type === 'password' ? 'text' : 'password';
        });
    });
})();
</script>
</body>
</html>

<?php
/**
 * includes/header.php  →  <head> + Tailwind CDN + tema "Ruri" (anime biru)
 * Variabel opsional sebelum include:
 *   $judul       judul halaman
 *   $body_class  kelas tambahan untuk <body>
 */
$judul = isset($judul) ? $judul : 'Beranda';
$body_class = isset($body_class) ? $body_class : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> – Pinjamin</title>
    <meta name="theme-color" content="#DBEBFF">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E%3Cstop offset='0' stop-color='%238EC0FF'/%3E%3Cstop offset='1' stop-color='%231F63DB'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='64' height='64' rx='18' fill='url(%23g)'/%3E%3Cpath d='M32 10c2 12 8 18 20 22-12 4-18 10-20 22-2-12-8-18-20-22 12-4 18-10 20-22z' fill='white'/%3E%3C/svg%3E">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@400;500;700;900&family=Dela+Gothic+One&display=swap" rel="stylesheet">

    <script>document.documentElement.classList.add('js');</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        /* Palet "Ruri" (瑠璃, biru lazuardi): abu-abu netral diganti biru tinta
           supaya seluruh kelas zinc-* yang sudah dipakai di halaman ikut bertema biru. */
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Zen Maru Gothic"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['"Dela Gothic One"', '"Zen Maru Gothic"', 'sans-serif']
                    },
                    colors: {
                        zinc: {50:'#F4F9FF',100:'#E6F0FF',200:'#CFDEF5',300:'#AEC5E8',400:'#7088B4',500:'#506794',600:'#3C5280',700:'#2B3E69',800:'#1B2C57',900:'#12204A'},
                        accent: {50:'#EEF6FF',100:'#DBEBFF',200:'#BCD9FF',300:'#8EC0FF',400:'#5AA0FF',500:'#3382F5',600:'#1F63DB',700:'#1A4EB5',800:'#1B428F',900:'#1B3870'}
                    },
                    boxShadow: {
                        glow: '0 10px 30px -10px rgba(31,99,219,.55)',
                        soft: '0 12px 32px -16px rgba(31,99,219,.35)'
                    }
                }
            }
        };
    </script>

    <style type="text/tailwindcss">
        @layer base {
            html { -webkit-tap-highlight-color: transparent; scroll-behavior: smooth; }
            body { @apply font-sans text-zinc-800 antialiased; }
            h1, h2, h3 { @apply font-bold tracking-tight; }
            ::selection { background: #BCD9FF; color: #12204A; }
            :focus-visible { outline: 3px solid #5AA0FF; outline-offset: 2px; border-radius: 10px; }
        }
        @layer components {
            .btn { @apply relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-xl px-4 py-2 text-sm font-bold transition duration-200 active:scale-95 disabled:opacity-50 disabled:pointer-events-none whitespace-nowrap; }
            .btn-sm { @apply px-3 py-1.5 text-xs gap-1.5 rounded-lg; }
            .btn-primary { @apply bg-gradient-to-br from-accent-400 to-accent-600 text-white shadow-glow hover:-translate-y-0.5 hover:from-accent-500 hover:to-accent-700; }
            .btn-primary::after { content: ''; position: absolute; inset: 0; background: linear-gradient(105deg, transparent 35%, rgba(255,255,255,.55) 50%, transparent 65%); transform: translateX(-120%); }
            .btn-primary:hover::after { transform: translateX(120%); transition: transform .7s ease; }
            .btn-soft { @apply bg-accent-100 text-accent-800 hover:bg-accent-200; }
            .btn-ghost { @apply border-2 border-accent-200 bg-white/80 text-accent-800 hover:border-accent-400 hover:bg-white; }
            .btn-danger { @apply bg-gradient-to-br from-rose-400 to-red-600 text-white hover:-translate-y-0.5 hover:from-rose-500 hover:to-red-700; }
            .btn-danger-soft { @apply bg-red-50 text-red-700 hover:bg-red-100; }
            .input { @apply w-full rounded-xl border-2 border-accent-100 bg-white px-3.5 py-2 text-sm text-zinc-900 placeholder:text-zinc-400 transition focus:border-accent-400 focus:outline-none focus:ring-4 focus:ring-accent-400/20; }
            .label { @apply mb-1.5 block text-sm font-bold text-zinc-700; }
            .card { @apply rounded-2xl border border-accent-100 bg-white/90 shadow-soft backdrop-blur-sm; }
            .badge { @apply inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-bold; }
            .badge i { @apply block h-1.5 w-1.5 rounded-full bg-current; }
            .badge-amber { @apply bg-amber-50 text-amber-700; }
            .badge-green { @apply bg-emerald-50 text-emerald-700; }
            .badge-red { @apply bg-red-50 text-red-700; }
            .badge-blue { @apply bg-accent-100 text-accent-700; }
            .badge-violet { @apply bg-violet-50 text-violet-700; }
            .badge-gray { @apply bg-zinc-100 text-zinc-600; }
            .tbl { @apply w-full text-sm; }
            .tbl th { @apply whitespace-nowrap border-b-2 border-accent-100 bg-accent-50/70 px-4 py-3 text-left text-xs font-bold text-zinc-500; }
            .tbl td { @apply border-b border-accent-50 px-4 py-3 align-middle; }
            .tbl tbody tr:last-child td { @apply border-b-0; }
            .tbl tbody tr { @apply transition-colors hover:bg-accent-50/80; }
            .chip { @apply inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border-2 border-accent-100 bg-white px-3.5 py-1.5 text-sm font-bold text-zinc-600 transition duration-200 hover:-translate-y-0.5 hover:border-accent-300 hover:text-accent-700; }
            .chip-on { @apply border-transparent bg-gradient-to-br from-accent-400 to-accent-600 text-white shadow-glow hover:border-transparent hover:text-white; }
            .tab { @apply relative inline-flex h-9 items-center gap-1.5 whitespace-nowrap rounded-full px-3.5 text-sm font-bold text-zinc-500 transition-colors duration-200 hover:bg-accent-50 hover:text-accent-700; }
            .tab-on { @apply bg-accent-100 text-accent-700; }
        }
    </style>

    <style>
        /* ---------- Latar langit ---------- */
        body {
            background-color: #EAF3FF;
            background-image:
                radial-gradient(60rem 36rem at 85% -10%, rgba(125,227,255,.45), transparent 60%),
                radial-gradient(50rem 30rem at -10% 10%, rgba(142,192,255,.5), transparent 60%),
                linear-gradient(180deg, #F4F9FF 0%, #E3EEFF 60%, #D6E6FF 100%);
            background-attachment: fixed;
            min-height: 100vh;
        }
        #langit { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        #langit .awan { position: absolute; border-radius: 999px; background: rgba(255,255,255,.7); filter: blur(28px); animation: awan 60s linear infinite; }
        #langit .awan:nth-child(1) { width: 26rem; height: 7rem; top: 12%; left: -30rem; }
        #langit .awan:nth-child(2) { width: 34rem; height: 9rem; top: 46%; left: -40rem; animation-duration: 90s; animation-delay: -30s; opacity: .7; }
        #langit .awan:nth-child(3) { width: 22rem; height: 6rem; top: 78%; left: -26rem; animation-duration: 75s; animation-delay: -55s; opacity: .8; }
        #sakura { position: absolute; inset: 0; width: 100%; height: 100%; }
        body > header, body > main, body > footer { position: relative; z-index: 10; }
        body > header { position: sticky; }

        /* ---------- Header kaca + gelombang seigaiha ---------- */
        .header-kaca { background: rgba(255,255,255,.78); backdrop-filter: blur(14px) saturate(1.4); -webkit-backdrop-filter: blur(14px) saturate(1.4); }
        .seigaiha {
            height: 12px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='32' height='16' viewBox='0 0 32 16'%3E%3Cg fill='none' stroke='%233382F5' stroke-opacity='.45' stroke-width='1.2'%3E%3Ccircle cx='0' cy='16' r='14'/%3E%3Ccircle cx='0' cy='16' r='9'/%3E%3Ccircle cx='0' cy='16' r='4'/%3E%3Ccircle cx='32' cy='16' r='14'/%3E%3Ccircle cx='32' cy='16' r='9'/%3E%3Ccircle cx='32' cy='16' r='4'/%3E%3Ccircle cx='16' cy='8' r='14'/%3E%3Ccircle cx='16' cy='8' r='9'/%3E%3Ccircle cx='16' cy='8' r='4'/%3E%3C/g%3E%3C/svg%3E");
            background-size: 32px 16px;
            animation: ombak 14s linear infinite;
            -webkit-mask-image: linear-gradient(180deg, #000 30%, transparent);
            mask-image: linear-gradient(180deg, #000 30%, transparent);
        }

        /* ---------- Logo & maskot ---------- */
        .logo-kilau { animation: kilau 3.2s ease-in-out infinite; }
        .melayang { animation: melayang 4.2s ease-in-out infinite; }
        .kedip { transform-box: fill-box; transform-origin: center; animation: kedip 5s infinite; }
        .lambai { transform-box: fill-box; transform-origin: 80% 90%; animation: lambai 2.4s ease-in-out infinite; }
        .bayangan { transform-box: fill-box; transform-origin: center; animation: bayangan 4.2s ease-in-out infinite; }
        .bintang { transform-box: fill-box; transform-origin: center; animation: berkelip 2.6s ease-in-out infinite; }
        .orbit { position: absolute; inset: 0; animation: putar 26s linear infinite; }
        .orbit > span { position: absolute; top: 50%; left: 50%; width: 3rem; height: 3rem; margin: -1.5rem; display: grid; place-items: center; border-radius: 1rem; background: rgba(255,255,255,.85); color: #1F63DB; box-shadow: 0 10px 24px -10px rgba(31,99,219,.6); }
        .orbit > span > svg { animation: putar 26s linear infinite reverse; }

        /* ---------- Gerak masuk halaman (satu rangkaian) ---------- */
        .js .masuk, .js main > * { opacity: 0; transform: translateY(14px); }
        .js .tampil { opacity: 1; transform: none; transition: opacity .6s cubic-bezier(.2,.7,.2,1), transform .6s cubic-bezier(.2,.7,.2,1); transition-delay: var(--d, 0ms); }

        /* ---------- Grafik tumbuh ---------- */
        .bar-grow { transform-origin: bottom; animation: tumbuh .9s cubic-bezier(.2,.8,.2,1) both; animation-delay: var(--d, 0ms); }
        .seg-grow { transform-origin: left; animation: lebar .9s cubic-bezier(.2,.8,.2,1) both; animation-delay: var(--d, 0ms); }

        /* ---------- Kartu yang bisa diklik ---------- */
        a.card, article.card { transition: transform .25s cubic-bezier(.2,.7,.2,1), box-shadow .25s, border-color .25s; }
        a.card:hover, article.card:hover { transform: translateY(-3px); border-color: #8EC0FF; box-shadow: 0 18px 36px -16px rgba(31,99,219,.5); }

        /* ---------- Percikan saat klik ---------- */
        .spark { position: fixed; z-index: 90; width: 10px; height: 10px; margin: -5px; pointer-events: none; background: #7DE3FF; clip-path: polygon(50% 0, 62% 38%, 100% 50%, 62% 62%, 50% 100%, 38% 62%, 0 50%, 38% 38%); animation: percik .7s ease-out forwards; }

        .scroll-thin::-webkit-scrollbar { width: 6px; height: 6px; }
        .scroll-thin::-webkit-scrollbar-thumb { background: #AEC5E8; border-radius: 999px; }
        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-thumb { background: #BCD9FF; border-radius: 999px; border: 2px solid #EAF3FF; }

        /* ---------- Modal ---------- */
        .modal { position: fixed; inset: 0; z-index: 60; display: none; align-items: center; justify-content: center; padding: 1rem; }
        .modal.open { display: flex; }
        .modal-bg { position: absolute; inset: 0; background: rgba(18,32,74,.5); backdrop-filter: blur(4px); animation: fade .2s ease-out both; }
        :where(.modal-box) { max-width: 32rem; }
        .modal-box { position: relative; width: 100%; max-height: 90vh; overflow-y: auto; border-radius: 1.25rem; background: #fff; border: 2px solid #DBEBFF; box-shadow: 0 30px 60px -20px rgba(18,32,74,.5); animation: pop .35s cubic-bezier(.2,1.2,.3,1) both; }

        /* ---------- Toast ---------- */
        .toast-auto { animation: pop .4s cubic-bezier(.2,1.2,.3,1) both, toastOut .3s ease 4.7s forwards; }

        @keyframes fade { from { opacity: 0 } to { opacity: 1 } }
        @keyframes pop { from { opacity: 0; transform: translateY(-10px) scale(.94) } to { opacity: 1; transform: none } }
        @keyframes toastOut { to { opacity: 0; transform: translateY(-10px) } }
        @keyframes awan { to { transform: translateX(calc(100vw + 70rem)); } }
        @keyframes ombak { to { background-position-x: 32px; } }
        @keyframes kilau { 0%,100% { transform: rotate(0) scale(1) } 50% { transform: rotate(14deg) scale(1.12) } }
        @keyframes melayang { 0%,100% { transform: translateY(0) } 50% { transform: translateY(-12px) } }
        @keyframes bayangan { 0%,100% { transform: scale(1); opacity: .18 } 50% { transform: scale(.82); opacity: .1 } }
        @keyframes kedip { 0%,92%,100% { transform: scaleY(1) } 95% { transform: scaleY(.08) } }
        @keyframes lambai { 0%,100% { transform: rotate(0) } 50% { transform: rotate(-22deg) } }
        @keyframes berkelip { 0%,100% { transform: scale(.6) rotate(0); opacity: .5 } 50% { transform: scale(1.15) rotate(20deg); opacity: 1 } }
        @keyframes putar { to { transform: rotate(360deg) } }
        @keyframes tumbuh { from { transform: scaleY(0) } to { transform: scaleY(1) } }
        @keyframes lebar { from { transform: scaleX(0) } to { transform: scaleX(1) } }
        @keyframes percik { from { opacity: 1; transform: translate(0,0) scale(1) rotate(0) } to { opacity: 0; transform: translate(var(--x), var(--y)) scale(.2) rotate(180deg) } }

        @media print { .no-print, #langit { display: none !important; } body { background: #fff !important; } .js main > *, .js .masuk { opacity: 1 !important; transform: none !important; } }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .001ms !important; animation-iteration-count: 1 !important; transition-duration: .001ms !important; scroll-behavior: auto !important; }
            .js .masuk, .js main > * { opacity: 1; transform: none; }
            #sakura { display: none; }
        }
    </style>
</head>
<body class="<?= e($body_class) ?>">
<div id="langit" class="no-print" aria-hidden="true">
    <span class="awan"></span><span class="awan"></span><span class="awan"></span>
    <canvas id="sakura"></canvas>
</div>

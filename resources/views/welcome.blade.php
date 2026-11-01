<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ff7600">
    <title>Salamaid — #JembatanKebaikan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<main class="app-shell">
    <header class="topbar">
        <a class="brand" href="{{ url('/') }}"><span class="brand-mark">✦</span><span><strong>salamaid</strong><small>#JembatanKebaikan</small></span></a>
        <label class="search-box"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7.5"/><path d="m17 17 4 4"/></svg><input id="campaign-search" type="search" placeholder="Cari Program"></label>
    </header>

    <section class="hero-section">
        <div class="hero-track" id="hero-track">
            <a class="hero-card active" href="#program" style="--hero-image:url('https://reimg.kresek.site/s3/amalsholehfiles/files/user-media/PPM9zH0SIxdI07aMSoc7IvrCJIQq8SezPDJVrLfs.jpg?w=960&fit=crop&auto=format,compress')"><div class="hero-copy"><span>Hadirkan Mushaf Layak</span><b>Sedekah<br>Jariyah</b><small>untuk Pelosok Negeri</small><em>YUK JARIYAH</em></div></a>
            <a class="hero-card" href="#program" style="--hero-image:url('https://amalsholeh-s3.imgix.net/user-media/H7E2Bip1MfMviZVwg25sFMwN70Yc6ONXW7sXmv5C.jpg?w=960&fit=crop&auto=format,compress')"><div class="hero-copy"><span>Bareng wujudkan</span><b>Pesantren<br>untuk Negeri</b><small>Bangun masa depan bersama</small><em>IKUT WAKAF</em></div></a>
            <a class="hero-card" href="#program" style="--hero-image:url('https://reimg.kresek.site/s3/amalsholehfiles/files/user-media/dhWlVHg7xLfm3iZUMVcRlAFsAq3sAPHXPAh7M46l.png?w=960&fit=crop&auto=format,compress')"><div class="hero-copy"><span>Ringankan duka</span><b>Bantu<br>Sesama</b><small>Satu kebaikan berarti</small><em>BERDONASI</em></div></a>
        </div>
        <div class="dots"><button class="active" data-slide="0"></button><button data-slide="1"></button><button data-slide="2"></button></div>
    </section>

    <section class="quick-actions">
        <a class="wallet" href="#login"><span class="wallet-icon">♧</span><span><small>Saldo Saku Kebaikan</small><strong>Bareng kamu</strong></span><b>Isi saldo</b></a>
        <div class="action-grid"><a href="#program"><span class="action-icon orange">☀</span><b>Sedekah Rutin</b></a><a href="#program"><span class="action-icon blue">☾</span><b>Zakat</b></a><a href="#program"><span class="action-icon yellow">◒</span><b>Tabungan Qurban</b></a><a href="#program"><span class="action-icon green">♧</span><b>Galang Dana</b></a></div>
    </section>

    <section class="content-section">
        <div class="section-heading"><div><h2>Bareng kamu</h2><p>Kebaikan terasa lebih mewah saat kita melangkah bersama.</p></div><a href="#program">Lihat semua</a></div>
        <div class="campaign-row">
            @foreach ([['Pahala Mengalir Deras, Wakaf Air Pelosok Negeri','Yayasan Desa Hijau','Rp 47.163.395','https://reimg.kresek.site/s3/amalsholehfiles/files/user-media/fNnuQVzLWLchEWz2HVoQP6ZqFB4DtlXXqAY8ZLhW.jpg?w=960'],['Alirkan Kembali Air untuk Gaza','Yayasan Dai Peduli','Rp 2.276.953.286','https://reimg.kresek.site/s3/amalsholehfiles/files/user-media/OtpHsGu7Mlj5pFt5zG3PgfYJpzc6jK4TEysKAxaZ.jpg?w=960'],['Wakaf Quran untuk Muslim Minoritas Pedalaman','Marwah Wakaf Indonesia','Rp 60.343.012','https://reimg.kresek.site/s3/amalsholehfiles/files/user-media/CNzf1yoxKrEzMwU1JqdjO6RaG8xO2MC6XsvH8Xnd.jpg?w=960']] as $campaign)
                <a class="campaign-card" href="#program"><img src="{{ $campaign[3] }}" alt="{{ $campaign[0] }}"><div class="campaign-body"><h3>{{ $campaign[0] }}</h3><p>{{ $campaign[1] }}</p><div class="progress"><span style="width:{{ $loop->index === 1 ? 78 : 42 }}%"></span></div><strong>{{ $campaign[2] }}</strong><small>Donasi Terkumpul</small></div></a>
            @endforeach
        </div>
    </section>

    <section class="content-section">
        <div class="section-heading"><div><h2>Bareng kamu</h2><p>Semangat berbagi bisa menjangkau lebih banyak hati.</p></div><a href="#program">Lihat semua</a></div>
        <div class="campaign-row">
            @foreach ([['Gerakan Sedekah Makan Untuk Anak Yatim dan Dhuafa','Yayasan Sahabat Yatim Indonesia','Rp 98.892.420','https://reimg.kresek.site/s3/amalsholehfiles/files/user-media/PlRV4jjVt9fDS9LH3BhtdffhecKT58ykODbTfmoQ.jpg?w=960'],['Mari Bersama Membangun Pondok!','Ustadzah Oki Setiana Dewi','Rp 311.733.023','https://amalsholeh-s3.imgix.net/user-media/fNnuQVzLWLchEWz2HVoQP6ZqFB4DtlXXqAY8ZLhW.jpg?w=960'],['Bantu Bangun Kembali Masjid Jabal Nur, NTT','Faruq Naufal Abdullah','Rp 9.747.588','https://reimg.kresek.site/s3/amalsholehfiles/files/user-media/CNzf1yoxKrEzMwU1JqdjO6RaG8xO2MC6XsvH8Xnd.jpg?w=960']] as $campaign)
                <a class="campaign-card" href="#program"><img src="{{ $campaign[3] }}" alt="{{ $campaign[0] }}"><div class="campaign-body"><h3>{{ $campaign[0] }}</h3><p>{{ $campaign[1] }}</p><div class="progress"><span style="width:{{ 35 + ($loop->index * 18) }}%"></span></div><strong>{{ $campaign[2] }}</strong><small>Donasi Terkumpul · ∞ Status</small></div></a>
            @endforeach
        </div>
    </section>

    <section class="program-section" id="program">
        <div class="section-heading"><div><h2>Program</h2><p>Pilih kebaikan yang ingin kamu dukung.</p></div></div>
        <div class="category-list">@foreach (['Semua','Kesehatan & Medis','Difabel','Sosial','Kemanusiaan','Pendidikan','Masjid','Panti Asuhan','Infrastruktur','Bencana Alam','Lainnya'] as $category)<button class="category {{ $loop->first ? 'selected' : '' }}" data-category="{{ $category }}">{{ $category }}</button>@endforeach</div>
        <div class="program-grid" id="program-grid">
            @foreach ([['Bantu kebutuhan anak yatim','Yayasan Darul Fadly Al Islami Semarang','Rp 1.774.885','Sosial','dhWlVHg7xLfm3iZUMVcRlAFsAq3sAPHXPAh7M46l.png'],['Bantu Ringankan Beban Dhuafa','Jamiyah Tahfidz Quran','Rp 220.625','Kemanusiaan','OtpHsGu7Mlj5pFt5zG3PgfYJpzc6jK4TEysKAxaZ.jpg'],['Wakaf Pesantren Cetak Penghafal Quran, Da’i, dan Pemakmur Masjid','Cinta masjid Indonesia','Rp 8.403.988','Pendidikan','CNzf1yoxKrEzMwU1JqdjO6RaG8xO2MC6XsvH8Xnd.jpg'],['RUTIN WAKAF, Bantu Bangun Masjid Pelosok','Yayasan Kreatif Muda Darangdan','Rp 1.033.942','Masjid','PlRV4jjVt9fDS9LH3BhtdffhecKT58ykODbTfmoQ.jpg'],['DARURAT! Selasar Masjid ECO Pesantren Rusak Parah','Wakaf Daarut Tauhiid','Rp 7.000','Masjid','fNnuQVzLWLchEWz2HVoQP6ZqFB4DtlXXqAY8ZLhW.jpg'],['Wakaf dan Infak Sarana Prasarana Masjid','Wakaf Salman','Rp 22.057.261','Infrastruktur','PPM9zH0SIxdI07aMSoc7IvrCJIQq8SezPDJVrLfs.jpg']] as $program)
                <a class="program-card" data-category="{{ $program[3] }}" href="#donate"><img src="https://reimg.kresek.site/s3/amalsholehfiles/files/user-media/{{ $program[4] }}?w=600" alt="{{ $program[0] }}"><div><h3>{{ $program[0] }}</h3><p>{{ $program[1] }}</p><strong>{{ $program[2] }}</strong><small>Donasi Terkumpul · ∞ Status</small></div></a>
            @endforeach
        </div><a class="all-programs" href="#program">Lihat +11.803 program lainnya <span>→</span></a>
    </section>

    <section class="download-section"><span class="download-orb">✦</span><div><h2>Bergerak Wujudkan Niat Baik Bersama Salamaid</h2><p>Berbagi bisa dimana aja, beramal sholeh bisa kapan aja.</p><div class="store-buttons"><a href="#download"> <span>Download di<br><b>App Store</b></span></a><a href="#download">▶ <span>Download di<br><b>Google Play</b></span></a></div></div></section>
    <footer><p>© 2026 <a href="#">Yayasan Bersama Beramal Sholeh</a></p><div class="socials"><a href="#">f</a><a href="#">◎</a><a href="#">𝕏</a><a href="#">▶</a></div></footer>
</main>
<nav class="bottom-nav"><a class="active" href="#"><span>⌂</span>Home</a><a href="#program"><span>◉</span>Program</a><a href="#donate"><span>▣</span>Donasi Saya</a><a href="#inbox"><span>✉</span>Inbox</a><a href="#account"><span>●</span>Akun</a></nav>
<div class="modal-backdrop" id="campaign-modal"><div class="campaign-modal"><button class="modal-close" aria-label="Tutup">×</button><img src="https://reimg.kresek.site/s3/amalsholehfiles/files/user-media/H7E2Bip1MfMviZVwg25sFMwN70Yc6ONXW7sXmv5C.jpg?w=960&fit=crop&auto=format,compress" alt="Ayo ikut wakaf"><div><small>Barengan wujudkan</small><h2>Saatnya<br>Berbagi Berkah</h2><p>Bangun Pesantren Qur'an bersama-sama.</p><a href="#program">Ikut Wakaf</a></div></div></div>
</body>
</html>

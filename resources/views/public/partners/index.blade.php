@extends('public.layouts.app', ['title' => 'Menjadi Mitra'])

@section('content')
<div class="public-partner-page">
    <section class="public-partner-hero">
        <span class="public-eyebrow"><span class="material-symbols-outlined">handshake</span> Kemitraan Salamaid</span>
        <h1>Perluas dampak kebaikan bersama Salamaid.</h1>
        <p>Salamaid membantu yayasan, lembaga sosial, komunitas, dan organisasi terpercaya menggalang dukungan secara transparan.</p>
        <div class="public-partner-actions">
            <a class="public-button" href="{{ route('public.partner.register') }}">Daftar sebagai lembaga <span class="material-symbols-outlined">arrow_forward</span></a>
            <a class="public-partner-link" href="{{ route('admin.login') }}">Login admin lembaga <span class="material-symbols-outlined">login</span></a>
        </div>
    </section>

    <section class="public-partner-section">
        <div class="public-partner-section__heading"><span class="material-symbols-outlined">stars</span><div><h2>Manfaat menjadi mitra</h2><p>Semua fitur dirancang untuk membantu lembaga bekerja lebih rapi dan dipercaya donatur.</p></div></div>
        <div class="public-partner-grid">
            <article><span class="material-symbols-outlined">campaign</span><h3>Kelola campaign</h3><p>Buat dan kelola program kebaikan lembaga melalui satu dashboard.</p></article>
            <article><span class="material-symbols-outlined">visibility</span><h3>Bangun kepercayaan</h3><p>Tampilkan profil lembaga, perkembangan program, dan transparansi penggunaan dana.</p></article>
            <article><span class="material-symbols-outlined">groups</span><h3>Jangkau lebih banyak</h3><p>Hubungkan cerita dan kebutuhan penerima manfaat dengan donatur yang peduli.</p></article>
        </div>
    </section>

    <section class="public-partner-section public-partner-section--muted">
        <div class="public-partner-section__heading"><span class="material-symbols-outlined">fact_check</span><div><h2>Syarat umum</h2><p>Siapkan informasi berikut untuk proses verifikasi.</p></div></div>
        <ul class="public-partner-checklist"><li>Profil dan identitas lembaga yang dapat diverifikasi.</li><li>Data penanggung jawab dan kontak yang aktif.</li><li>Rencana program serta komitmen pelaporan yang transparan.</li><li>Kesediaan mengikuti kebijakan Salamaid dan ketentuan yang berlaku.</li></ul>
    </section>

    <section class="public-partner-section">
        <div class="public-partner-section__heading"><span class="material-symbols-outlined">route</span><div><h2>Proses verifikasi</h2><p>Pengajuan ditinjau oleh Admin Salamaid sebelum akun dapat digunakan.</p></div></div>
        <ol class="public-partner-steps"><li><b>Daftar</b><span>Isi data dasar lembaga dan penanggung jawab.</span></li><li><b>Ditinjau</b><span>Tim Salamaid memeriksa kelengkapan dan kesesuaian data.</span></li><li><b>Mulai berkarya</b><span>Setelah disetujui, lembaga dapat masuk admin dan membuat campaign.</span></li></ol>
    </section>

    <section class="public-partner-faq" id="faq">
        <h2>Pertanyaan umum</h2>
        <details><summary>Berapa lama proses verifikasi?</summary><p>Waktu verifikasi bergantung pada kelengkapan data. Status pengajuan dapat dipantau setelah fitur pendaftaran selesai diaktifkan.</p></details>
        <details><summary>Apakah semua lembaga langsung dapat membuat campaign?</summary><p>Tidak. Campaign hanya dapat dikelola setelah lembaga diverifikasi dan akun admin lembaga diaktifkan.</p></details>
        <details><summary>Apakah data rekening tampil di halaman publik?</summary><p>Tidak. Data sensitif hanya digunakan untuk proses operasional yang berwenang.</p></details>
    </section>
</div>
@endsection

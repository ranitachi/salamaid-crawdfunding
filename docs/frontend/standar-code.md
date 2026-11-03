
# Standar Code Layout Frontend Salamaid

Dokumen ini menjadi acuan saat membuat atau mengubah halaman publik Salamaid. Tujuannya agar `/`, `/program`, `/inbox`, checkout, akun, dan detail campaign memakai pola visual yang sama.

## 1. Shell halaman publik

- Gunakan `resources/views/public/layouts/app.blade.php` sebagai layout utama.
- Jangan membuat header, bottom navigation, atau footer baru di halaman individual.
- Branding, logo, favicon, tagline, warna, dan navigasi publik diambil dari `AppSettingsService`/database melalui layout.
- Gunakan `@section('content')` untuk isi halaman dan `@push('head')` hanya untuk meta SEO atau style khusus yang benar-benar diperlukan.
- Script halaman diletakkan pada `@push('scripts')`; stack script sudah dirender oleh layout.

## 2. Container dan gutter

- Semua halaman berada di dalam `.public-shell`.
- Gunakan `.public-main` sebagai area konten utama.
- Jangan membuat konten menempel ke tepi viewport. Gunakan gutter bawaan layout: desktop `24px`, mobile `16px`.
- Slider boleh memakai overflow horizontal, tetapi kartu dan section tetap mengikuti lebar container.
- Untuk halaman detail campaign, hero, kartu, tab, dan isi cerita harus berada dalam gutter yang sama dengan halaman program.
- Hindari `margin-left: -16px` atau `width: 100vw` kecuali untuk komponen yang memang sengaja full-bleed dan sudah diuji pada mobile.

## 3. Header dan navigasi

- Header publik selalu berasal dari `.public-header`.
- Bottom navigation selalu berasal dari `.public-bottom-nav`.
- Halaman detail boleh memiliki toolbar kontekstual seperti “Detail Kampanye”, tetapi tidak boleh menyembunyikan header dan bottom navigation global.
- CTA sticky tidak boleh menutupi bottom navigation. Letakkan di atas navigasi dengan `bottom: 76px`.
- Active state menu harus menggunakan `request()->routeIs(...)` dari konfigurasi navigasi, bukan hard-code per halaman.

## 4. Typography

Gunakan font `Plus Jakarta Sans` dari `public.css`. Jangan memakai ukuran 7–8px untuk teks yang perlu dibaca pengguna.

| Elemen | Ukuran acuan mobile |
| --- | ---: |
| Judul halaman | 24–30px |
| Judul campaign | 18–22px |
| Judul section/card | 15–18px |
| Isi/deskripsi | 13–15px |
| Label, metadata, caption | 10–12px |
| Tombol dan chip | 12–14px |
| Ikon | 16–22px |

Metadata boleh lebih kecil, tetapi harus tetap terbaca dan tidak menjadi ukuran default seluruh komponen. Gunakan `line-height` minimal `1.4` untuk paragraf.

## 5. Komponen dan class

Gunakan class yang sudah ada sebelum membuat class baru:

- `.public-card` untuk kartu permukaan putih.
- `.public-button` untuk CTA utama.
- `.public-chip` untuk filter kategori.
- `.public-progress` untuk progress bar.
- `.public-muted` untuk teks sekunder.
- `.public-trust` untuk informasi kepercayaan/verifikasi.
- `.public-campaign-*` untuk komponen khusus detail campaign.
- `.public-program-*` untuk halaman program.
- `.public-inbox-*` untuk inbox.

CSS halaman baru diletakkan pada file yang sesuai:

- `public.css`: fondasi dan komponen lintas halaman.
- `public-responsive.css`: layout home/program/inbox dan aturan mobile.
- `public-detail.css`: detail campaign.
- `public-checkout.css`, `public-payment.css`, `public-account.css`: area khusus masing-masing.

## 6. Data dan keamanan tampilan

- Ambil konten dari query/service dan database, bukan menulis dummy langsung di Blade.
- Escape semua teks user-generated dengan `{{ }}` atau `e()`; gunakan raw HTML hanya untuk konten yang sudah diproses dan memang diperlukan.
- Halaman publik hanya boleh menampilkan campaign published dari organisasi verified.
- Jangan menampilkan email donor, payload payment, webhook token, rekening payout, audit internal, atau data private lainnya.
- Jika schema belum mendukung fitur seperti bookmark, doa, komentar, atau like, tampilkan empty state dan jangan membuat penyimpanan semu.

## 7. Checklist sebelum selesai

- Bandingkan halaman pada lebar mobile dan desktop.
- Pastikan header, gutter, typography, card radius, dan bottom navigation konsisten dengan `/program`.
- Pastikan sticky CTA tidak menutupi konten atau navigasi.
- Jalankan:

```bash
rtk npm run build
rtk php artisan view:cache
rtk php artisan route:cache
rtk php artisan test
rtk git diff --check
```

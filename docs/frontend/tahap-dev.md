# Tahapan Pengembangan Frontend Publik Salamaid

Dokumen ini menjadi acuan pengembangan halaman publik Salamaid Crowdfunding. Frontend publik tetap dibuat di project Laravel yang sama menggunakan Blade, route web, model, service, dan database yang sudah digunakan oleh admin.

Sumber desain utama berada di [docs/desain-public](../desain-public). Mockup tersebut merupakan export desain Stitch dengan baseline mobile sekitar 390px. Referensi visual crowdfunding seperti amalsoleh digunakan sebagai inspirasi pola halaman, bukan sebagai sumber data atau kode.

## 1. Keputusan teknologi

- Framework: Laravel 12 yang sudah digunakan project.
- Rendering: Blade server-side untuk SEO, performa, dan integrasi langsung dengan database.
- Asset: Vite dengan entry asset publik terpisah dari asset admin.
- UI: mobile-first, responsive desktop/tablet/mobile.
- Font: Plus Jakarta Sans.
- Primary color: merah Salamaid, dengan hijau untuk status verified/success dan amber untuk warning.
- Admin tetap berada di prefix `/admin` dan tidak dicampur dengan layout publik.

Struktur yang disarankan:

```text
resources/views/public/
resources/views/public/layouts/
resources/views/public/components/
resources/css/public/
resources/js/public/
routes/public.php
```

Jika route publik tetap memakai `routes/web.php`, seluruh route publik harus menggunakan nama route `public.*` agar tidak bentrok dengan route admin.

## 2. Prinsip data publik

Halaman publik hanya boleh mengambil data yang memang boleh dilihat publik:

- Campaign: hanya `status = published` dan belum melewati deadline bila deadline digunakan sebagai filter aktif.
- Organisasi: tampilkan badge verified hanya jika status organisasi `verified`.
- Campaign update: hanya update dengan status `published`, menggunakan scope `CampaignUpdate::public()`.
- Donasi: hanya agregat dan donasi `paid` yang boleh menjadi social proof.
- Donor anonim: tampilkan sebagai `Hamba Allah` atau label anonim.
- Payment: hanya tampilkan status dan instruksi milik user yang sedang login atau token/status page yang sah.

Jangan pernah menampilkan email donor, nomor rekening, raw payload Mayar, signature, token webhook, audit log, atau data payout internal.

## 3. Pemetaan mockup ke halaman dan database

| Mockup | Route yang disarankan | Sumber utama |
|---|---|---|
| Home | `/` | `campaigns`, `organizations`, `categories`, `campaign_media` |
| Program | `/program` | Campaign published + kategori |
| Detail campaign | `/campaigns/{campaign:slug}` | Campaign, organization, category, media, donations |
| Cerita campaign | `/campaigns/{campaign:slug}/cerita` | `campaigns.description` |
| Kabar terbaru | `/campaigns/{campaign:slug}/updates` | `campaign_updates` published |
| Alokasi dana | `/campaigns/{campaign:slug}/allocation` | `campaign_updates` bertipe `fund_report` |
| Checkout donasi | `/campaigns/{campaign:slug}/donate` | Donation, Payment, Payment Channel |
| Status pembayaran | `/payments/{payment}` | Payment, Webhook, Payment Sync Log |
| Donasi saya | `/account/donations` | Donation milik user |
| Inbox | `/inbox` | Notifications milik user |
| Akun | `/account` | User dan app settings |

## 4. Tahapan pengembangan

Progress implementasi pada branch `feat/frontend`: B0, B1, B2, B3, B4, dan B5 sudah selesai secara lokal. B3 sudah diuji dengan fake Mayar; transaksi sandbox nyata tetap menjadi langkah operasional sebelum production.

### B0 — Public shell dan design system

Tujuan: menyiapkan fondasi layout tanpa fitur bisnis baru.

Pekerjaan:

- Buat layout publik terpisah dari `admin.layouts.app`.
- Buat header publik dengan logo, nama aplikasi, pencarian, notifikasi, dan akses akun.
- Buat bottom navigation lima menu:
  - Beranda
  - Program
  - Donasi Saya
  - Inbox
  - Akun
- Buat komponen reusable:
  - campaign card
  - progress bar
  - verified badge
  - category chip
  - currency display
  - empty state
  - status payment
  - sticky donation CTA
- Adaptasi warna `Amanah Nusantara` dari mockup:
  - primary merah sekitar `#BC0004` atau token brand dari settings
  - success hijau
  - background warm white/soft gray
  - surface card putih
- Tambahkan responsive breakpoint untuk desktop dan tablet.
- Pastikan asset publik tidak mengubah asset admin.

Acceptance criteria:

- Layout publik dapat dirender tanpa database data campaign.
- Navigasi aktif sesuai route.
- Mobile menyerupai mockup baseline 390px.
- Desktop tidak memiliki whitespace berlebihan.
- `npm run build` berhasil.

### B1 — Home dan Program

Tujuan: menampilkan campaign yang sudah dipublish.

Pekerjaan:

- Home hero atau campaign prioritas dari `is_featured = true`.
- Section campaign prioritas.
- Section campaign darurat berdasarkan kategori, deadline, atau metadata yang tersedia.
- Section program pilihan.
- Halaman `/program` dengan:
  - pencarian judul campaign
  - filter kategori
  - filter lokasi bila tersedia
  - filter status aktif
  - pagination
- Hitung progress dari `collected_amount / target_amount` dengan batas maksimal 100%.
- Tampilkan organisasi dan badge verified.
- Gunakan eager loading agar tidak terjadi N+1 query.

Catatan: belum ada tabel khusus hero/banner. Untuk MVP gunakan campaign published yang featured. Banner CMS dapat ditambahkan pada fase terpisah.

Acceptance criteria:

- Campaign draft, rejected, suspended, dan soft deleted tidak tampil.
- Campaign dapat dibuka berdasarkan slug.
- Filter dan pagination tetap mempertahankan query string.
- Empty state tersedia.

### B2 — Detail campaign dan transparansi publik

Tujuan: menyediakan informasi yang cukup sebelum donor melakukan pembayaran.

Pekerjaan:

- Hero image dan kategori campaign.
- Nama organisasi dan status verified.
- Target, terkumpul, persentase progress, jumlah donor, dan sisa waktu.
- Tab atau section:
  - Cerita
  - Kabar terbaru
  - Alokasi dana
- Cerita mengambil `campaigns.description`.
- Kabar terbaru hanya mengambil `CampaignUpdate::public()`.
- Alokasi dana mengambil update `fund_report` yang sudah published.
- Tampilkan media campaign yang aman dan valid.
- Tambahkan share link dan bookmark hanya sebagai UI non-persisten sampai schema bookmark tersedia.
- Tambahkan sticky CTA `Donasi Sekarang` pada mobile.

Catatan schema:

- Tidak ada tabel budget allocation khusus. Gunakan fund report published untuk MVP.
- Tidak ada sistem bookmark. Jangan menyimpan bookmark sebelum migration dan policy disiapkan.

Acceptance criteria:

- Konten yang belum disetujui tidak muncul.
- XSS pada judul, deskripsi, dan update ter-escape.
- Detail tetap dapat dibaca pada mobile dan desktop.
- Campaign tanpa update atau laporan memiliki empty state yang wajar.

### B3 — Checkout donasi

Tujuan: mengubah niat donor menjadi donation dan payment Mayar.

Pekerjaan:

- Form nominal preset dan nominal custom.
- Pilihan donasi anonim.
- Pesan donasi maksimal sesuai schema `message`.
- Ringkasan:
  - nominal donasi
  - fee platform
  - fee channel
  - total pembayaran
- Tampilkan payment channel aktif dari database.
- Buat Donation berstatus `pending`.
- Buat Payment dengan provider `mayar`.
- Redirect ke Mayar atau gunakan instruksi yang dikembalikan adapter.
- Validasi nominal, campaign published, dan channel aktif di server.
- Jangan menerima nominal/fee final dari client tanpa perhitungan ulang server.

Fitur yang belum didukung schema dan tidak dimasukkan MVP:

- Saku Kebaikan atau saldo donor.
- Donasi rutin.
- Metode pembayaran tersimpan.
- Kalkulator zakat dan benefit membership.

Acceptance criteria:

- Donasi tidak langsung berstatus `paid` sebelum callback atau sinkronisasi provider.
- Double submit tidak membuat payment ganda yang tidak terkontrol.
- Total dan fee tercatat konsisten pada donation/payment.
- Secret Mayar tidak pernah dikirim ke view.

### B4 — Status pembayaran dan receipt

Tujuan: memberi kepastian kepada donor setelah checkout.

Pekerjaan:

- Halaman status untuk `pending`, `paid`, `expired`, dan `failed`.
- Tampilkan instruksi pembayaran sesuai channel.
- Tombol cek ulang status bila payment masih pending.
- Tampilkan receipt setelah payment paid.
- Gunakan webhook dan `SyncPaymentStatusAction` yang sudah ada.
- Tampilkan transaction/provider reference tanpa raw payload.
- Sediakan fallback ketika inquiry Mayar belum mengembalikan detail langsung.

Acceptance criteria:

- User tidak dapat melihat payment milik user lain.
- Status lokal mengikuti hasil webhook/sinkronisasi yang tervalidasi.
- Refresh halaman tidak membuat payment atau donation baru.
- Expired dan failed memiliki CTA yang jelas.

Status implementasi: `DONE` untuk status page, signed URL guest, owner scoping, refresh inquiry Mayar, dan receipt print. Test: `tests/Feature/PublicPaymentStatusTest.php`.

### B5 — Akun donor

Tujuan: menghubungkan pengalaman publik dengan user yang sudah login.

Pekerjaan:

- `/account/donations` untuk riwayat donasi user.
- Filter status donasi.
- Detail donasi dan receipt.
- `/inbox` untuk notification milik user.
- `/account` untuk profil dasar dan preferensi yang memang sudah tersedia.
- Login/register hanya jika kebutuhan publik sudah diputuskan.
- Guest tetap dapat melakukan donasi anonim bila aturan bisnis mengizinkan.

Acceptance criteria:

- Semua query donation dan notification scoped ke user login.
- IDOR test untuk donation, payment, dan notification.
- Guest tidak diarahkan ke fitur akun tanpa pesan yang jelas.

Status implementasi: `DONE` untuk login/register role user, akun donor, riwayat donasi, receipt, inbox, mark read, dan IDOR scoping. Test: `tests/Feature/PublicAccountTest.php`.

### B6 — Transparansi publik dan SEO

Status implementasi: `DONE` untuk halaman transparansi campaign, filter publik berbasis status `published`, agregasi laporan dana, metadata canonical/Open Graph/JSON-LD, sitemap dinamis, dan robots policy. Test: `tests/Feature/PublicTransparencySeoTest.php`.

Route yang tersedia:

- `/campaigns/{slug}/transparency` menampilkan update dan laporan dana yang sudah dipublikasikan.
- `/sitemap.xml` hanya memuat home, program, dan campaign dari organisasi terverifikasi yang berstatus published.
- `/robots.txt` menutup area admin dan akun dari crawler serta menunjuk ke sitemap.

Data draft, rejected, unpublished, campaign non-published, dan organisasi non-verified tidak boleh masuk ke halaman transparansi atau sitemap.

### B7 — Responsive QA dan visual polish

Status implementasi: `READY FOR VISUAL REVIEW`. Fondasi responsive tambahan dan style pagination publik sudah tersedia melalui `public-responsive.css`. Review manual tetap perlu dilakukan pada viewport mobile 390px, tablet, dan desktop untuk home, program, detail campaign, checkout, payment status, auth, account, dan transparency.

### B8 — Mayar sandbox smoke test

Status implementasi: `READY FOR MANUAL SMOKE TEST`. Automated test tetap menggunakan `Http::fake()` dan tidak membuat transaksi eksternal. Smoke test nyata harus dijalankan manual dengan credential sandbox yang tersimpan di `.env`: buat satu donation, selesaikan pembayaran pada Mayar sandbox, pastikan webhook diterima, lalu verifikasi status lokal menjadi `paid` dan receipt dapat dibuka. Jangan memasukkan credential atau payload provider ke repository.

### B9 — Public auth hardening

Status implementasi: `DONE` untuk forgot password dan reset password dengan token Laravel, response generik anti user-enumeration, session regeneration, dan test login setelah reset. Email verification belum diaktifkan karena membutuhkan perubahan kontrak model `User` dan konfigurasi email yang eksplisit.

### B10 — Production readiness

Status implementasi: `IN REVIEW`. Sitemap, robots, canonical, Open Graph, JSON-LD, cache route/view, build Vite, dan test suite sudah tersedia. Sebelum production masih perlu verifikasi konfigurasi `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`, mail transport, queue worker, storage private/public, webhook Mayar production, HTTPS, backup database, rate limit, dan rotasi credential sandbox ke production.

## 5. Fitur yang ditunda

Fitur berikut terlihat pada mockup tetapi belum didukung database atau business rule saat ini:

- Wallet/Saku Kebaikan.
- Top up dan tarik saldo donor.
- Donasi rutin/autodebet.
- Saved payment method.
- Bookmark campaign persisten.
- Komentar, reaction, dan doa donor persisten.
- Kalkulator zakat.
- Muhsin level, benefit, dan gamifikasi.
- Banner/hero CMS terpisah.

Fitur tersebut harus memiliki migration, model, policy, service, route, dan test sendiri sebelum ditampilkan sebagai fitur aktif.

## 6. Prioritas implementasi

Urutan yang direkomendasikan:

```text
B0 Public shell
  ↓
B1 Home + Program
  ↓
B2 Detail campaign + transparency
  ↓
B3 Checkout + Mayar
  ↓
B4 Payment status + receipt
  ↓
B5 Account + donation history + inbox
```

Setiap fase harus ditutup dengan:

- route dan controller/query
- Blade view responsive
- validasi dan authorization
- loading, empty, error, dan success state
- feature test
- pemeriksaan N+1
- `npm run build`
- `git diff --check`

## 7. Hal yang perlu diputuskan sebelum B3

1. Apakah guest boleh berdonasi tanpa membuat akun?
2. Apakah fee platform dibebankan kepada donor atau dipotong dari donasi?
3. Apakah tip sukarela seperti pada mockup akan menjadi fitur resmi atau hanya elemen desain?
4. Apakah halaman publik memakai nama “Salamaid” atau “Salamaid Nusantara”?
5. Apakah campaign darurat ditentukan oleh kategori, deadline, metadata, atau field baru?
6. Apakah receipt publik dapat diakses dengan link/token atau wajib login?

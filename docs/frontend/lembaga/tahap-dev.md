# Tahapan Pengembangan Modul Lembaga

Dokumen ini menjadi acuan pengembangan fitur pendaftaran lembaga, verifikasi oleh Admin Salamaid, dan manajemen data lembaga pada area publik serta admin.

## Tujuan dan aturan utama

- Donatur tetap menggunakan `/register` dan dibuat sebagai `user` biasa.
- Pendaftar lembaga menggunakan alur terpisah melalui `/mitra`.
- Akun lembaga hanya dapat masuk ke `/admin` setelah lembaganya berstatus `verified`.
- Admin Salamaid menjadi pihak yang memverifikasi, menolak, menangguhkan, dan memulihkan lembaga.
- Admin lembaga hanya dapat melihat dan mengelola data milik lembaganya sendiri.
- Data rekening dan dokumen legal disimpan privat dan tidak boleh tampil pada halaman publik.
- Setiap perubahan status dan data penting dicatat pada audit log.

## Status dan aktor

Status lembaga yang digunakan:

```text
pending → verified
pending → rejected
verified → suspended
suspended → verified
rejected → pending
```

Aktor:

| Aktor | Tanggung jawab |
| --- | --- |
| Pendaftar | Mengisi pengajuan, memperbaiki data, dan memantau status |
| Admin lembaga | Mengelola profil, branding, campaign, donasi, dan transparansi milik lembaga |
| Reviewer/Admin Salamaid | Memeriksa dan memutuskan pengajuan lembaga |
| Super Admin | Mengelola seluruh data, role, pengaturan global, dan override operasional |

## Tahap M0 — Audit existing dan aturan akses

Status: `READY`

- [x] Pastikan tabel `users`, `organizations`, dan `organization_members` digunakan sebagai fondasi.
- [x] Pastikan `organizations.status` mendukung `pending`, `verified`, `rejected`, dan `suspended`.
- [x] Pastikan role `organization_admin` dan permission `organizations.manage` tersedia.
- [x] Pastikan admin review organisasi yang sudah ada tetap digunakan.
- [ ] Tetapkan bahwa akun `organization_admin` pending/rejected/suspended tidak dapat login admin.
- [ ] Pastikan akun `organization_admin` tanpa membership organisasi juga ditolak.

Kriteria selesai: aturan akses tertulis dalam policy/middleware dan memiliki feature test.

## Tahap M1 — Halaman publik informasi mitra

Target route:

```text
/mitra
/mitra/register
/mitra/status
```

Pekerjaan:

- [ ] Buat halaman informasi “Menjadi Mitra Salamaid”.
- [ ] Jelaskan manfaat, syarat, proses verifikasi, dan aturan membuat campaign.
- [ ] Tambahkan tombol `Daftar sebagai Lembaga` dan `Login Admin Lembaga`.
- [ ] Gunakan layout publik, branding Salamaid global, dan copy berbahasa Indonesia yang natural.
- [ ] Tambahkan FAQ singkat terkait proses verifikasi dan status pengajuan.

Kriteria selesai: halaman responsif, dapat diakses publik, dan tidak mencampur alur donatur.

## Tahap M2 — Form registrasi lembaga

Field minimum:

- Nama lembaga
- Jenis lembaga: yayasan, lembaga sosial, komunitas, atau organisasi
- Nama penanggung jawab
- Email akun
- Nomor WhatsApp
- Password dan konfirmasi password
- Alamat lengkap
- Kota/provinsi
- Deskripsi lembaga
- Website dan media sosial bila tersedia
- Logo lembaga bila tersedia
- Dokumen pendukung bila diwajibkan oleh kebijakan verifikasi

Pekerjaan:

- [ ] Buat Form Request khusus pendaftaran lembaga.
- [ ] Validasi email unik, nomor telepon, format file, ukuran file, dan data wajib.
- [ ] Tampilkan error dalam bahasa Indonesia.
- [ ] Simpan password menggunakan hashing Laravel.
- [ ] Jangan menampilkan password atau dokumen privat di response, log, atau audit.
- [ ] Gunakan upload teroptimasi untuk logo dan private storage untuk dokumen legal.

Kriteria selesai: pengajuan valid dapat disimpan dan pengajuan invalid tidak membuat data setengah jadi.

## Tahap M3 — Pembuatan data dan relasi

Saat form berhasil dikirim, buat dalam satu database transaction:

```text
users
  role = organization_admin
  is_active = false

organizations
  status = pending

organization_members
  role = owner
```

Pekerjaan:

- [ ] Buat service `OrganizationRegistrationService`.
- [ ] Buat user pendaftar sebagai `organization_admin` tetapi belum aktif.
- [ ] Buat organisasi dengan status `pending`.
- [ ] Hubungkan pendaftar sebagai owner pada `organization_members`.
- [ ] Pastikan email dan slug organisasi tidak duplikat.
- [ ] Jika proses gagal, rollback seluruh user, organisasi, dan membership.
- [ ] Simpan nomor pengajuan atau token status yang tidak mudah ditebak.

Kriteria selesai: setiap pengajuan memiliki tepat satu pemilik awal dan dapat dilacak.

## Tahap M4 — Halaman status pengajuan

Pekerjaan:

- [ ] Setelah submit, tampilkan nomor pengajuan dan status `Menunggu verifikasi`.
- [ ] Sediakan halaman `/mitra/status` dengan akses aman.
- [ ] Tampilkan status `pending`, `verified`, `rejected`, atau `suspended`.
- [ ] Jika ditolak, tampilkan alasan penolakan secara aman.
- [ ] Sediakan `Perbaiki Pengajuan` untuk status rejected.
- [ ] Jangan membuka detail pengajuan hanya berdasarkan ID berurutan.

Kriteria selesai: pemohon dapat mengetahui status tanpa harus memiliki akses admin.

## Tahap M5 — Review dan verifikasi Admin Salamaid

Pekerjaan:

- [ ] Tampilkan pengajuan pending pada `/admin/organizations?status=pending`.
- [ ] Tampilkan profil, penanggung jawab, kontak, branding, dan dokumen privat melalui endpoint terotorisasi.
- [ ] Sediakan aksi approve, reject dengan alasan, dan request revision bila diperlukan.
- [ ] Catat actor, waktu, alasan, dan perubahan status ke audit log.
- [ ] Kirim notifikasi in-app untuk hasil review.
- [ ] Email notification tetap opt-in dan tidak aktif otomatis.

Kriteria selesai: hanya role yang memiliki permission review yang dapat memutuskan status.

## Tahap M6 — Aktivasi akun dan pembatasan login

Ketika approve:

```text
organizations.status = verified
organizations.verified_at = now()
users.is_active = true
```

Ketika reject atau suspend:

```text
users.is_active = false
```

Pekerjaan:

- [ ] Tambahkan pengecekan status organisasi pada proses login admin.
- [ ] Organisasi pending/rejected/suspended harus menerima pesan yang aman dan informatif.
- [ ] Jangan membocorkan apakah email tertentu terdaftar pada status error publik.
- [ ] Jika pemilik memiliki beberapa organisasi, tentukan aturan: login aktif bila minimal satu organisasi verified atau minta pemilihan organisasi.
- [ ] Pastikan middleware, policy, dan query tetap membatasi data berdasarkan membership.

Kriteria selesai: akun lembaga pending tidak dapat masuk admin dan akun verified dapat masuk sesuai scope.

## Tahap M7 — Manajemen profil dan branding lembaga

Fitur admin lembaga:

- [ ] Edit nama, tagline, deskripsi, alamat, kontak, website, dan media sosial.
- [ ] Upload logo dan banner dengan optimasi ukuran.
- [ ] Atur warna utama dan warna sekunder.
- [ ] Preview perubahan sebelum disimpan bila memungkinkan.
- [ ] Terapkan branding hanya pada halaman publik lembaga dan campaign lembaga.
- [ ] Header/footer global Salamaid tetap menggunakan konfigurasi Salamaid.
- [ ] Audit perubahan profil dan branding.

Kriteria selesai: perubahan lembaga A tidak memengaruhi lembaga B atau branding global Salamaid.

## Tahap M8 — Campaign, donasi, dan transparansi berdasarkan lembaga

- [ ] Admin lembaga dapat membuat dan mengedit campaign miliknya.
- [ ] Campaign baru tetap mengikuti workflow review/publikasi yang berlaku.
- [ ] Admin lembaga dapat melihat donor hanya untuk campaign/lembaganya.
- [ ] Admin lembaga dapat membuat update transparansi untuk campaign miliknya.
- [ ] Admin Salamaid tetap dapat melihat seluruh lembaga dan campaign.
- [ ] Transaksi Mayar global hanya terlihat oleh role yang berwenang.
- [ ] Semua query memakai scope `organization_id` dan memiliki test IDOR.

## Tahap M9 — Dokumen verifikasi privat

Jika dokumen legal diwajibkan:

- [ ] Buat tabel `organization_verification_documents`.
- [ ] Simpan path pada private disk, bukan `public/storage`.
- [ ] Sediakan download melalui controller dan policy.
- [ ] Batasi MIME type, ukuran, nama file, dan akses reviewer.
- [ ] Jangan mencatat isi dokumen atau token file ke audit log.
- [ ] Hapus atau arsipkan dokumen sesuai kebijakan retensi.

## Tahap M10 — Notifikasi, pengajuan ulang, dan operasional

- [ ] Notifikasi saat pengajuan diterima.
- [ ] Notifikasi saat pengajuan perlu diperbaiki.
- [ ] Notifikasi saat disetujui, ditolak, atau ditangguhkan.
- [ ] Fitur pengajuan ulang hanya tersedia untuk rejected.
- [ ] Tambahkan rate limit pada submit dan upload.
- [ ] Tambahkan audit trail lengkap untuk setiap perubahan.
- [ ] Dokumentasikan prosedur reviewer dan SLA verifikasi.

## Tahap M11 — Testing dan release

Feature test minimum:

- [ ] Pendaftar lembaga berhasil membuat user, organization pending, dan owner membership.
- [ ] Pendaftar biasa tetap dibuat sebagai role `user`.
- [ ] Email pendaftar tidak dapat didaftarkan dua kali.
- [ ] Pengajuan invalid tidak membuat data parsial.
- [ ] User pending tidak dapat login admin.
- [ ] User rejected/suspended tidak dapat login admin.
- [ ] User verified dapat login admin.
- [ ] Reviewer dapat approve dan reject dengan alasan.
- [ ] User tanpa permission tidak dapat memverifikasi.
- [ ] Admin lembaga tidak dapat melihat data lembaga lain.
- [ ] Dokumen privat tidak dapat diakses melalui URL publik.
- [ ] Branding lembaga tidak memengaruhi branding global.
- [ ] Campaign, donor, report, dan transparansi ter-scope ke lembaga.

Validasi release:

```bash
rtk php artisan migrate:fresh --seed
rtk php artisan test
rtk php artisan view:cache
rtk php artisan route:cache
rtk npm run build
rtk git diff --check
```

## Urutan implementasi yang direkomendasikan

```text
M0 → M1 → M2 → M3 → M4
                 ↓
          M5 → M6 → M7 → M8
                       ↓
                  M9 → M10 → M11
```

MVP dapat dirilis setelah M0–M8 selesai. M9–M10 menjadi penguatan verifikasi dan operasional sebelum digunakan secara produksi.

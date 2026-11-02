# Tahapan Pengembangan Admin Salamaid

Dokumen ini menjadi roadmap implementasi halaman admin Salamaid Crowdfunding. Fokusnya adalah panel internal untuk operator/admin: moderasi campaign, verifikasi organisasi, monitoring donasi, rekonsiliasi pembayaran, persetujuan payout, transparansi, dan pengelolaan sistem.

Template UI yang digunakan adalah [BLOK Admin UI Kit](https://ranitachi.github.io/blok-admin-ui-kit/index.html). Template tersebut bersifat HTML/CSS/JavaScript statis dan akan diadaptasi menjadi layout Blade Laravel dengan asset yang dikelola melalui Vite.

## 1. Prinsip pengembangan

- Admin panel dipisahkan dari halaman publik melalui prefix dan route `/admin`.
- UI menggunakan pola Blok: sidebar, topbar, breadcrumb, card statistik, table, filter, modal, toast, empty state, dan halaman detail.
- Data nyata berasal dari model dan database Salamaid, bukan data dummy setelah modul terkait selesai.
- Semua aksi sensitif wajib melalui authentication, authorization, validation, dan audit log.
- Fitur dikembangkan secara vertikal: route, controller, query, action/service, view, validasi, dan test diselesaikan per fitur.
- Setiap fase memiliki acceptance criteria sehingga progres dapat dipantau tanpa menilai tampilan saja.

## 2. Status proyek

Gunakan status berikut pada tabel roadmap:

- `TODO`: belum dimulai.
- `IN PROGRESS`: sedang dikerjakan.
- `BLOCKED`: tertahan dependensi atau keputusan.
- `REVIEW`: menunggu pemeriksaan.
- `DONE`: acceptance criteria sudah terpenuhi dan test lulus.

Status awal seluruh fase: `TODO`.

Progress per 2026-11-02: A0, A1, A2, A3, A4, A5, A7, A9, A10, dan A11 `DONE`. A7 sudah memiliki scheduler sinkronisasi payment pending; uji sandbox nyata tetap menjadi langkah operasional. A8 sudah memiliki private evidence, download terotorisasi, dan balance validation tetapi settlement reconciliation/fraud flag masih terbuka. A12 sudah terintegrasi, Laravel sudah di-upgrade ke 12, dan seluruh 154 test lulus; backup/restore drill, responsive QA, serta settlement/fraud hardening masih menjadi risiko release.

## 3. Temuan awal dan pekerjaan pendahuluan

Project saat ini menggunakan Laravel 12, PHP 8.2+, Vite, nwidart modules, serta modul Core, Catalog, Donation, Payment, Fundraising, Transparency, dan Operations.

Temuan yang harus dibereskan sebelum fitur admin berjalan penuh:

- Migration Payment berada di `Modules/Payment/Database/Migration`, sementara provider mencari `Database/migrations`.
- Tabel `payments`, `payment_channels`, `payment_fees`, `webhooks`, dan `refunds` belum tersedia pada database saat analisis dilakukan.
- Controller modul masih berupa scaffold generator; sebagian besar method belum memiliki business logic.
- View `create`, `show`, dan `edit` dipanggil controller tetapi belum tersedia.
- Belum ada route `/admin`, admin authentication, role/permission, policy, atau audit log.
- README masih menggunakan dokumentasi default Laravel.
- Test yang ada masih test bawaan Laravel.

## 4. Roadmap utama

| ID | Fase | Fokus | Dependensi | Status |
|---|---|---|---|---|
| A0 | Persiapan teknis | Baseline, perbaikan struktur, dan keputusan arsitektur | Tidak ada | DONE |
| A1 | Admin shell | Layout Blok, sidebar, topbar, responsive state | A0 | DONE |
| A2 | Authentication & access | Login admin, session, role, permission, policy | A0 | DONE |
| A3 | Dashboard | Statistik dan aktivitas operasional | A1, A2 | IN PROGRESS |
| A4 | Organization review | Verifikasi lembaga dan anggota | A2, A3 | DONE |
| A5 | Campaign moderation | Review, publish, reject, feature, close | A2, A4 | DONE |
| A6 | Donation monitoring | Daftar donasi, status, detail, receipt | A2, A3 | DONE |
| A7 | Payment operations | Channel, callback, webhook, rekonsiliasi | A2, A6 | DONE |
| A8 | Payout operations | Request, approval, evidence, settlement | A2, A4, A7 | REVIEW |
| A9 | Transparency & content | Update campaign, laporan, bukti penggunaan | A5, A8 | DONE |
| A10 | User, category & configuration | Master data dan pengaturan admin | A2 | DONE |
| A11 | Audit, notification & reporting | Jejak perubahan, notifikasi, export | A3-A10 | DONE |
| A12 | Hardening & release | Security, performance, QA, deployment | A1-A11 | REVIEW |

---

## A0 — Persiapan teknis dan baseline

### Tujuan

Menyiapkan fondasi agar pengerjaan admin tidak menghasilkan duplikasi layout, data yang tidak konsisten, atau fitur sensitif tanpa kontrol akses.

### Pekerjaan

- [ ] Salin/adaptasi asset Blok Admin UI Kit ke struktur asset project. *(Masuk A1 karena merupakan implementasi UI.)*
- [x] Tetapkan layout admin pada `resources/views/admin/layouts`.
- [x] Tetapkan prefix route `/admin` dan namespace route `admin.*`.
- [x] Perbaiki path migration Payment menjadi `Database/Migrations`.
- [x] Normalisasi seluruh provider agar mencari folder `Database/Migrations`.
- [x] Jalankan migration dan pastikan seluruh tabel domain tersedia.
- [x] Perbaiki lokasi entity modul agar sesuai PSR-4 dan pastikan class dapat di-autoload.
- [x] Periksa ulang nama tabel, namespace entity, cast, relasi, dan primary key UUID.
- [x] Dokumentasikan bahwa foreign key database akan menjadi pekerjaan hardening; konsistensi awal dijaga melalui action/service dan policy.
- [x] Tetapkan role minimum: `super_admin`, `admin`, `reviewer`, `finance`, `support`.
- [x] Tetapkan status dan transisi resmi untuk campaign, donation, payment, dan payout.
- [x] Pastikan environment lokal, database testing, storage, queue, dan mail testing memiliki konfigurasi dasar.
- [x] Tambahkan seed data development yang aman dan tidak memakai data produksi.

### Output

- Struktur asset dan layout admin.
- Dokumen status/transisi domain.
- Database lokal lengkap.
- Daftar role dan kemampuan awal.

### Acceptance criteria

- `php artisan about` berjalan.
- `php artisan migrate:status` menampilkan semua migration domain yang diharapkan.
- `php artisan route:list` tidak menghasilkan konflik route.
- Asset UI akan divalidasi pada A1 setelah adaptasi template Blok.
- Seed data dapat diulang pada database testing tanpa error.

### Validasi yang sudah dilakukan

- `composer dump-autoload` berhasil tanpa peringatan PSR-4.
- Migration Payment berhasil dijalankan.
- Seluruh migration domain berstatus `Ran`.
- Entity Core, Catalog, Donation, dan Payment berhasil di-autoload.
- Seed dijalankan dua kali tanpa duplikasi atau error.
- Test bawaan: 2 test lulus.

## A1 — Admin shell dan design system

### Tujuan

Membangun kerangka visual dan navigasi yang dipakai seluruh halaman admin.

### Pekerjaan

- [x] Adaptasi sidebar Blok dengan menu Salamaid.
- [x] Buat topbar, breadcrumb, page header, user menu, notification menu, dan logout action.
- [x] Buat komponen Blade reusable: `stat-card`, `data-table`, `status-badge`, `empty-state`, `filter-bar`, `modal`, `toast`, `pagination`, `confirm-dialog`.
- [x] Tetapkan token warna status: draft, pending, approved, rejected, paid, failed, expired.
- [x] Buat responsive behavior untuk desktop, tablet, dan mobile.
- [x] Tambahkan preview route `/admin` tanpa authentication; protection route masuk A2.
- [ ] Buat halaman 403, 404, 419, 429, 500, dan maintenance.
- [ ] Tambahkan active state sidebar berdasarkan route.
- [ ] Tambahkan loading, empty, error, dan success state.
- [ ] Hindari hardcode menu yang tidak boleh dilihat role tertentu.

### Menu awal

- Dashboard
- Campaign
- Organisasi
- Donasi
- Pembayaran
- Payout
- Transparansi
- Kategori
- Pengguna & Role
- Laporan
- Pengaturan
- Audit Log

### Acceptance criteria

- Admin yang terautentikasi dapat melihat shell pada seluruh route admin.
- Sidebar aktif sesuai halaman.
- Komponen tabel, badge, filter, modal, dan pagination dapat digunakan ulang.
- Layout tidak rusak pada lebar desktop dan mobile.

## A2 — Authentication, role, permission, dan policy

### Tujuan

Memastikan hanya user berwenang yang dapat masuk dan melakukan aksi admin.

### Pekerjaan

- [x] Buat route login dan logout admin. *(Forgot/reset password belum; dipindah ke A10.)*
- [x] Tambahkan middleware admin (`admin`, `admin.permission:<permission>`).
- [x] Tambahkan field atau relasi role pada user.
- [x] Buat permission matrix untuk setiap menu dan aksi (`config/admin.php`).
- [x] Buat Policy untuk campaign, organization, donation, payment, payout, dan update (`Modules/*/Policies`, `app/Policies/UserPolicy`).
- [x] Terapkan authorization pada route dan controller, bukan hanya menyembunyikan tombol. *(Pola wajib untuk route area baru dicatat di `routes/admin.php`.)*
- [x] Tambahkan rate limit login (5 percobaan gagal/menit per email+IP).
- [x] Tambahkan session invalidation setelah logout. *(Perubahan password masuk A10.)*
- [x] Pastikan CSRF dan session cookie aktif.
- [x] Siapkan seed user admin lokal tanpa password produksi.

### Permission minimum

| Area | Super Admin | Admin | Reviewer | Finance | Support |
|---|---:|---:|---:|---:|---:|
| Dashboard | penuh | lihat | lihat | lihat | lihat |
| Organisasi | penuh | review | review | lihat | lihat |
| Campaign | penuh | review/publish | review | lihat | lihat |
| Donasi | penuh | lihat | lihat | lihat | lihat |
| Payment | penuh | lihat | - | kelola | lihat |
| Payout | penuh | lihat | - | approve | lihat |
| User/role | penuh | terbatas | - | - | - |
| Pengaturan | penuh | terbatas | - | terbatas | - |
| Audit log | penuh | lihat | lihat | lihat | lihat |

### Acceptance criteria

- User tanpa role admin ditolak dari `/admin`.
- Setiap aksi sensitif memiliki test authorization.
- Reviewer tidak dapat menyetujui payout.
- Finance tidak dapat mengubah role user.
- Password dan token tidak pernah muncul pada response atau log.

## A3 — Dashboard admin

### Tujuan

Memberikan ringkasan operasional yang membantu admin menentukan tindakan berikutnya.

### Widget awal

- Total campaign aktif.
- Campaign menunggu review.
- Organisasi menunggu verifikasi.
- Total donasi hari ini, minggu ini, dan bulan ini.
- Donasi pending/failed/expired.
- Payout menunggu approval.
- Nominal payout dalam proses.
- Campaign hampir berakhir.
- Aktivitas admin terbaru.
- Alert integritas data atau payment webhook gagal.

### Pekerjaan

- [ ] Buat query agregasi terpisah dari controller.
- [x] Tampilkan periode dan timezone secara jelas.
- [x] Tambahkan filter periode.
- [x] Tambahkan link dari setiap card ke daftar terfilter.
- [x] Buat activity feed berdasarkan audit log database persistent dengan fallback timestamp domain saat belum ada log.
- [x] Tambahkan cache opsional untuk statistik yang mahal dengan TTL; default lokal tetap nonaktif.
- [ ] Pastikan dashboard tidak mengekspos data donor sensitif.

### Acceptance criteria

- Semua angka berasal dari database.
- Setiap widget dapat ditelusuri ke halaman detailnya.
- Dashboard memiliki empty state saat database belum memiliki data.
- Query dashboard tidak melakukan N+1 query.

## A4 — Review organisasi dan anggota

### Tujuan

Menyediakan proses verifikasi lembaga sebelum organisasi dapat mengelola campaign.

### Pekerjaan

- [ ] Daftar organisasi dengan filter status verifikasi, lokasi, dan tanggal pengajuan.
- [ ] Detail organisasi, kontak, rekening, anggota, dan dokumen.
- [ ] Status `pending`, `verified`, `rejected`, `suspended`.
- [ ] Aksi approve, reject dengan alasan, suspend, dan restore.
- [ ] Validasi rekening dan pemisahan data sensitif.
- [ ] Daftar anggota dan role organisasi.
- [ ] Audit seluruh keputusan reviewer.
- [ ] Notifikasi kepada organisasi setelah keputusan.

### Acceptance criteria

- Organisasi belum verified tidak dapat mempublikasikan campaign.
- Reject wajib memiliki alasan.
- Setiap keputusan menyimpan actor, waktu, status lama, status baru, dan alasan.
- Data rekening tidak tampil pada role yang tidak berwenang.

## A5 — Moderasi dan pengelolaan campaign

### Tujuan

Mengelola lifecycle campaign dari draft hingga selesai dengan alur review yang jelas.

### Status yang disarankan

```text
draft → under_review → published → closed
                  └→ rejected → draft
published → suspended
```

### Pekerjaan

- [ ] Daftar campaign dengan filter status, kategori, organisasi, tanggal, dan featured.
- [ ] Detail campaign dengan ringkasan dana, media, organisasi, update, dan histori status.
- [ ] Review deskripsi, target dana, deadline, media, dan dokumen pendukung.
- [ ] Aksi approve/publish, reject, suspend, feature/unfeature, close, dan restore bila relevan.
- [ ] Simpan alasan reject/suspend.
- [ ] Tambahkan bulk action dengan confirmation.
- [ ] Tambahkan preview tampilan publik tanpa mencampur route publik.
- [ ] Validasi target, deadline, status organisasi, dan kelengkapan media.
- [ ] Pisahkan perubahan editorial dan perubahan finansial.

### Acceptance criteria

- Campaign hanya dapat dipublish oleh role yang memiliki permission.
- Campaign milik organisasi belum verified tidak dapat dipublish.
- Semua perubahan status tercatat di audit log.
- Campaign yang sudah memiliki donasi tidak dapat dihapus secara permanen.
- Daftar campaign mendukung pagination, filter, sort, dan empty state.

## A6 — Monitoring donasi

### Tujuan

Memberikan visibilitas transaksi donasi tanpa mengubah status pembayaran secara manual sembarangan.

### Pekerjaan

- [x] Daftar donasi dengan filter campaign, status, method, nominal, tanggal, dan anonymous.
- [x] Detail donasi dengan campaign, donor, fee, net amount, payment, dan timeline.
- [x] Tampilkan data donor sesuai permission dan masking yang diperlukan.
- [x] Tampilkan status `pending`, `paid`, `expired`, `refund`, `failed`.
- [x] Tambahkan pencarian berdasarkan ID, ref code, atau order ID.
- [x] Tampilkan dan verifikasi receipt melalui halaman printable yang dapat disimpan sebagai PDF.
- [x] Sediakan export CSV terbatas untuk role yang berwenang dan mengikuti filter aktif.
- [x] Jangan menganggap redirect browser sebagai bukti pembayaran.

### Acceptance criteria

- Status donasi berasal dari payment flow atau webhook tervalidasi.
- Donasi anonymous tidak menampilkan identitas donor pada UI yang tidak perlu.
- Export mencatat actor, filter, waktu, dan jumlah data.
- Nominal ditampilkan konsisten dalam Rupiah.

## A7 — Payment, webhook, dan rekonsiliasi

### Tujuan

Menjadikan payment operation dapat dipantau, diulang secara aman, dan diaudit.

### Pekerjaan

- [x] Perbaiki dan jalankan migration Payment.
- [x] Buat service dasar untuk pencatatan webhook dan rekonsiliasi payment.
- [x] Kelola payment channel aktif dan biaya channel dengan effective date serta pencegahan periode fee tumpang tindih.
- [x] Buat endpoint webhook provider (`POST /api/v1/payment/webhooks/{provider}`).
- [x] Verifikasi signature webhook provider melalui HMAC dan konfigurasi provider.
- [x] Simpan payload webhook yang aman untuk audit.
- [x] Terapkan idempotency berdasarkan provider, order ID, dan event.
- [x] Buat halaman webhook masuk dan processed state.
- [x] Buat halaman rekonsiliasi payment vs donation.
- [x] Buat queue/job untuk callback dan sinkronisasi provider dengan retry/backoff (`docs/admin-a7.md`). *(Notifikasi via queue mengikuti A11.)*
- [x] Tambahkan inquiry status Mayar (`GET /transactions/{id}`) dengan mapping status eksplisit dan aksi admin `payments.manage`.

Catatan integrasi: route fitur tersedia di `Modules/Donation/routes/admin.php` dan `Modules/Payment/routes/admin.php`. File tersebut perlu di-include ke route group admin oleh Agent B setelah route authentication stabil.

### Acceptance criteria

- Webhook tidak dapat mengubah data tanpa signature valid.
- Webhook yang sama tidak menggandakan pembayaran.
- Payment sukses memperbarui donation secara konsisten.
- Payment gagal atau expired tidak menandai donasi sebagai paid.
- Error provider dapat dilihat admin tanpa membuka secret credential.

## A8 — Payout dan operations

### Tujuan

Mengendalikan pencairan dana dengan pemisahan tugas dan jejak audit.

### Pekerjaan

- [x] Daftar payout berdasarkan status dan organisasi.
- [x] Detail payout berisi nominal, saldo tersedia, dan evidence (privat atau URL lama).
- [x] Status `requested`, `approved`, `rejected`, `paid`.
- [x] Validasi saldo saat approve dan ulang sebelum paid.
- [ ] Approval dua tahap bila dibutuhkan.
- [x] Aksi reject wajib memiliki alasan.
- [x] Upload bukti transfer dengan storage aman (disk `private`, unduh hanya via policy; lihat `docs/admin-a8.md`).
- [x] Tandai paid hanya setelah bukti dan actor tersedia; saldo divalidasi ulang sebelum paid.
- [ ] Rekonsiliasi total payout dengan donation settled.
- [x] Blokir payout jika organisasi tidak verified/suspended (approve dan paid). *(Fraud flag belum memiliki model data.)*

### Acceptance criteria

- User yang meminta payout tidak dapat menyetujui payout yang sama jika segregation of duties aktif.
- Payout tidak dapat melebihi saldo yang tersedia.
- Setiap perubahan status tercatat.
- Bukti transfer hanya dapat diakses role berwenang.
- Payout yang sudah paid tidak dapat diedit tanpa prosedur koreksi/audit.

## A9 — Transparency dan konten campaign

### Tujuan

Memastikan laporan penggunaan dana dan update campaign dapat ditinjau sebelum tampil publik.

### Pekerjaan

- [ ] Daftar update campaign dan status review.
- [ ] Detail update dengan konten, media, tanggal publikasi, dan campaign.
- [ ] Aksi approve, request revision, publish, unpublish.
- [ ] Kelola bukti penggunaan dana.
- [ ] Validasi file, ukuran, tipe, dan virus scanning bila tersedia.
- [ ] Hubungkan update dengan campaign dan payout terkait.
- [ ] Tampilkan histori perubahan.

### Acceptance criteria

- Update belum disetujui tidak muncul sebagai konten publik.
- File upload tersimpan melalui storage yang sesuai, bukan nama file bebas di public root.
- Reviewer dapat meminta revisi dengan alasan.
- Publikasi update tercatat sebagai event/audit.

## A10 — User, role, category, dan configuration

### Tujuan

Menyediakan master data dan pengaturan yang dibutuhkan operasi harian.

### Pekerjaan

- [ ] CRUD user admin dan status aktif/nonaktif.
- [ ] Kelola role dan permission.
- [ ] Reset password atau kirim reset link tanpa melihat password.
- [ ] CRUD kategori campaign dan slug.
- [ ] Pengaturan banner/featured campaign bila diperlukan.
- [ ] Pengaturan fee platform dan payment channel dengan histori effective date.
- [ ] Pengaturan notifikasi dan template email.
- [ ] Validasi agar kategori yang dipakai campaign tidak dapat dihapus sembarangan.

### Acceptance criteria

- User nonaktif tidak dapat login.
- Role tidak dapat dihapus jika masih dipakai.
- Perubahan fee tidak mengubah transaksi historis.
- Kategori memiliki unique slug dan status aktif.

## A11 — Audit log, notifikasi, dan laporan

### Tujuan

Menyediakan kontrol operasional dan bukti perubahan untuk keamanan serta pelaporan.

### Pekerjaan

- [x] Buat tabel dan service audit log.
- [x] Simpan actor, action, subject, old values, new values, IP, user agent, dan timestamp.
- [x] Buat daftar audit dengan filter actor, area, action, dan periode.
- [x] Buat notifikasi in-app untuk pekerjaan yang membutuhkan tindakan.
- [x] Buat email notification opt-in untuk event penting bila mail sudah siap.
- [x] Buat laporan campaign, donation, payment, payout, dan fee.
- [x] Tambahkan export CSV sesuai kebutuhan dan permission.
- [x] Buat dashboard alert untuk webhook gagal, payout tertunda, dan data mismatch.

### Acceptance criteria

- Aksi sensitif memiliki audit log.
- Audit log tidak dapat diedit melalui UI normal.
- Export dibatasi permission dan tidak membocorkan data sensitif.
- Notifikasi dapat ditandai read/unread.
- Laporan memiliki periode, timezone, filter, dan total yang jelas.

## A12 — Hardening, QA, dan release

### Tujuan

Memastikan admin panel aman, stabil, dan dapat dipelihara sebelum digunakan.

### Pekerjaan

- [x] Feature test untuk login, authorization, campaign review, payment webhook, dan payout.
- [x] Test status transition, fee, idempotency, dan saldo payout (feature/integration level).
- [x] Test validasi form dan upload.
- [x] Test route tidak bocor tanpa middleware (invariant seluruh route admin).
- [x] Uji XSS pada input campaign/update, organisasi, alasan, audit, notifikasi.
- [x] Uji CSRF, mass assignment, IDOR, rate limit, dan privilege escalation.
- [x] Uji query N+1 (query count konstan) dan pagination.
- [ ] Uji responsive pada halaman utama admin.
- [ ] Uji backup dan restore database.
- [x] Nonaktifkan debug pada production (checklist + test error disclosure).
- [x] Pastikan secret payment tidak masuk repository atau log.
- [x] Buat checklist deployment dan rollback (`docs/admin-a12.md`).
- [x] Perbarui README dengan setup admin.

### Acceptance criteria

- Semua test wajib lulus.
- Tidak ada route admin sensitif yang dapat diakses tanpa authorization.
- Tidak ada error blocker pada log saat smoke test.
- Migration production dapat dijalankan dengan prosedur backup.
- Release memiliki catatan perubahan dan prosedur rollback.

---

## 5. Urutan MVP yang disarankan

Jika ingin segera memiliki admin yang dapat digunakan, kerjakan urutan berikut:

1. A0 — Persiapan teknis.
2. A1 — Admin shell.
3. A2 — Authentication dan access control.
4. A3 — Dashboard dengan data campaign dan organisasi.
5. A4 — Review organisasi.
6. A5 — Moderasi campaign.
7. A6 — Monitoring donasi.
8. A8 — Payout secara manual dengan audit.
9. A7 — Payment dan webhook.
10. A9 — Transparency.
11. A10/A11 — Master data, laporan, notifikasi, dan audit lengkap.
12. A12 — Hardening dan release.

Urutan tersebut memungkinkan admin mulai mengelola organisasi dan campaign lebih dahulu, sementara integrasi payment dapat diselesaikan dengan aman setelah model operasionalnya jelas.

## 6. Matriks halaman admin

| Halaman | Route | Modul utama | Aksi penting | Status |
|---|---|---|---|---|
| Login | `/admin/login` | App/Auth | login/logout/reset | TODO |
| Dashboard | `/admin` | lintas modul | filter periode, drill-down | TODO |
| Campaign list | `/admin/campaigns` | Catalog | filter, bulk action | TODO |
| Campaign detail | `/admin/campaigns/{campaign}` | Catalog | review, publish, reject | TODO |
| Organization list | `/admin/organizations` | Core | filter status | TODO |
| Organization detail | `/admin/organizations/{organization}` | Core | verify, reject, suspend | TODO |
| Donation list | `/admin/donations` | Donation | filter, export | TODO |
| Donation detail | `/admin/donations/{donation}` | Donation/Payment | timeline, receipt | TODO |
| Payment list | `/admin/payments` | Payment | retry, inspect webhook | TODO |
| Payout list | `/admin/payouts` | Operations | approve, reject, mark paid | TODO |
| Payout detail | `/admin/payouts/{payout}` | Operations | evidence, timeline | TODO |
| Transparency | `/admin/transparency` | Transparency | review, publish | TODO |
| Categories | `/admin/categories` | Core | CRUD, activate/deactivate | TODO |
| Users & roles | `/admin/users` | Core/App | CRUD, role assignment | TODO |
| Reports | `/admin/reports` | lintas modul | filter, export | TODO |
| Audit log | `/admin/audit-logs` | App | search, filter | TODO |
| Settings | `/admin/settings` | App | configuration | TODO |

## 7. Checklist monitoring per fitur

Setiap fitur harus ditutup dengan checklist berikut:

- [ ] Database/migration tersedia.
- [ ] Model/entity dan relasi tersedia.
- [ ] Form Request/validasi tersedia.
- [ ] Policy/permission diterapkan.
- [ ] Action/service untuk business rule tersedia.
- [ ] Route dan middleware tersedia.
- [ ] Controller tidak memuat business logic berlebihan.
- [ ] Blade view menggunakan komponen admin yang konsisten.
- [ ] Loading, empty, error, dan success state tersedia.
- [ ] Audit log untuk aksi sensitif tersedia.
- [ ] Feature/unit test tersedia.
- [ ] Manual smoke test lulus.
- [ ] Dokumentasi route dan keputusan bisnis diperbarui.

## 8. Format laporan progres

Gunakan format ini pada setiap update pengembangan:

```text
Tanggal:
Fase:
Status: TODO | IN PROGRESS | BLOCKED | REVIEW | DONE

Selesai:
-

Sedang dikerjakan:
-

Blocker:
- Tidak ada / jelaskan blocker

File atau route yang berubah:
-

Validasi:
- Test:
- Migration:
- Manual smoke test:

Langkah berikutnya:
-
```

## 9. Definition of Done admin panel

Admin panel dianggap siap untuk release awal apabila:

- Admin dapat login dan hanya melihat menu sesuai role.
- Dashboard menampilkan data nyata dengan filter periode.
- Organisasi dapat diverifikasi dan seluruh keputusan tercatat.
- Campaign dapat direview, dipublish, ditolak, disuspend, dan ditutup.
- Donasi dapat dicari dan dipantau tanpa membocorkan data sensitif.
- Payment webhook tervalidasi dan idempotent.
- Payout memiliki approval, validasi saldo, dan bukti transfer.
- Update transparansi dapat direview dan dipublikasikan.
- Audit log tersedia untuk seluruh aksi sensitif.
- Test keamanan dan feature utama lulus.
- Error page, empty state, responsive layout, dan notifikasi dasar tersedia.
- Dokumentasi setup dan deployment diperbarui.

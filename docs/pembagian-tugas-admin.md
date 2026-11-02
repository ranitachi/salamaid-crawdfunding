# Pembagian Tugas Admin Berikutnya

Dokumen ini dibuat setelah review `docs/admin-a4-a5.md`. A4 dan A5 dinyatakan `DONE`; pekerjaan berikutnya dibagi berdasarkan bounded context agar agent dapat bekerja paralel tanpa mengambil alih file agent lain.

## Status dan review singkat

- A0, A1, A2, A4, A5: `DONE`.
- A3: `IN PROGRESS`; filter periode, drill-down widget, dan cache opsional sudah selesai. Profiling lanjutan dan activity feed audit database masih menunggu A11.
- A6/A7: monitoring dasar, receipt printable, CSV export, webhook idempotent, signature HMAC, dan rekonsiliasi dasar sudah tersedia. Sisa pekerjaan ada pada audit export, payment channel/fee, queue callback, notifikasi, dan inquiry provider.
- A8: `REVIEW` — evidence privat (disk `private`, download via policy), kompatibel URL lama, blokir organisasi suspended; 20 test payout lulus. Sisa: rekonsiliasi settlement dan fraud flag. Lihat `docs/admin-a8.md`.
- A9, A10, A11: belum dikerjakan penuh.
- A12: `REVIEW` — dependency audit, Laravel 12 upgrade, route/CSRF/permission invariant test, XSS/secret/CSV-injection/N+1 test, checklist deployment/rollback. Sisa risiko: route scaffold modul publik, drill backup/restore, uji responsive, dan settlement/fraud hardening. Lihat `docs/admin-a12.md`.

Review A4/A5 tidak menemukan blocker fungsional. Catatan keputusan: `campaigns.review` masih menjadi permission moderasi yang juga mengizinkan publish untuk reviewer. Jika publish harus dipisah menjadi approval khusus, lakukan sebagai perubahan permission dan test terpisah.

## Aturan kolaborasi

- Semua agent wajib membaca `docs/tahapan-admin.md`, `docs/analisa-crowdfunding.md`, dan `config/admin.php`.
- Semua command shell diprefix `rtk`.
- Gunakan `apply_patch` untuk edit file.
- Jangan mengubah `app/Models/User.php`, migration payment, atau route milik agent lain tanpa koordinasi.
- Setiap fitur sensitif wajib memakai route middleware, policy, Form Request, dan test authorization.
- Jangan mengubah halaman publik.
- Sebelum mulai, periksa perubahan lokal yang sudah ada dan jangan menghapus pekerjaan agent lain.
- Setiap agent melaporkan file yang diubah, test yang dijalankan, hasil validasi, dan blocker.

## Pembagian paralel

| Agent | Paket kerja | Dependensi | Area file utama |
|---|---|---|---|
| A | A3 Dashboard completion dan UI hardening | A11 audit feed opsional | `app/Queries/Admin`, `app/Http/Controllers/Admin/DashboardController.php`, `resources/views/admin/dashboard.blade.php` |
| B | A9 Transparency management | A5 DONE, A8 read-only payout relation | `Modules/Transparency`, `routes/admin-transparency.php`, `resources/views/admin/transparency` |
| C | A10 User, role, category, configuration | A2 DONE | `Modules/Core`/`Catalog` admin HTTP, `routes/admin-master.php`, views terkait |
| D | A6/A7 payment operations lanjutan | A6/A7 baseline DONE | `Modules/Payment`, `Modules/Donation`, queue/job, config payment |
| E | A11 Audit database, notification, reporting/export | Kontrak `AdminAuditLogger` sudah tersedia | migration/model/service audit, notification, report query |
| F | A12 QA/security/release hardening | Dapat mulai read-only sejak sekarang | `tests`, docs release, dependency audit; jangan ubah business feature tanpa temuan |

Agent A, B, C, dan D dapat dimulai bersamaan. Agent E dapat dimulai bersamaan karena kontrak audit sudah tersedia. Agent F melakukan audit read-only terlebih dahulu; patch keamanan yang menyentuh area agent lain dikerjakan setelah pemilik area menyelesaikan merge.

## Prompt eksekusi paralel: Codex, Claude, dan OpenCode

Ketiga agent wajib menjadikan file ini sebagai sumber koordinasi utama. Prompt di bawah adalah pembagian Wave 1 dan dapat dijalankan bersamaan pada workspace yang sama hanya jika masing-masing agent memakai branch/worktree terpisah. Jangan menjalankan tiga agent pada branch yang sama karena dapat menimbulkan konflik file.

### Prompt untuk Codex — A3 Dashboard

```text
Anda adalah Agent Codex untuk project Salamaid Crowdfunding.

Workspace: /Users/fcn/Documents/WEB/salamaid-crowdfunding
Sumber koordinasi wajib: docs/pembagian-tugas-admin.md

Sebelum bekerja, baca seluruh dokumen berikut:
- docs/pembagian-tugas-admin.md
- docs/tahapan-admin.md
- docs/analisa-crowdfunding.md
- config/admin.php

Ambil ownership paket: Agent A — A3 Dashboard completion dan UI hardening.
Jangan mengerjakan paket Agent B, C, D, E, atau F.

Konteks:
- Laravel 12, PHP 8.2+, Blade, Vite, MySQL, Sanctum, nwidart/laravel-modules.
- A0, A1, A2, A4, dan A5 selesai.
- Dashboard sudah memakai app/Queries/Admin/DashboardQuery.php dan data database nyata.
- Admin UI memakai layout/komponen Blade yang sudah tersedia.

Kerjakan:
1. Tambahkan filter periode dashboard yang tervalidasi dan timezone-nya jelas.
2. Pastikan setiap widget mempunyai link ke halaman detail dengan filter yang sesuai.
3. Audit query untuk N+1; tambahkan cache hanya bila aman, memiliki TTL, dan tidak menampilkan data lintas permission.
4. Tambahkan empty, loading, dan error state yang konsisten dengan komponen admin.
5. Tambahkan test untuk filter, permission, empty state, dan query behavior.

Batasan:
- Jangan mengubah app/Models/User.php.
- Jangan mengubah route A4/A5, authentication, payment, payout, atau halaman publik.
- Jangan membuat audit logger baru; jika activity feed menunggu A11, dokumentasikan dependency tersebut.
- Gunakan apply_patch untuk edit file.
- Semua command shell wajib diprefix rtk.

Validasi:
- rtk php artisan test
- rtk php artisan view:cache
- rtk npm run build

Di akhir, laporkan file yang berubah, test/command yang dijalankan, hasilnya, dan blocker. Jangan mengubah file di luar ownership tanpa alasan dan dokumentasi.
```

### Prompt untuk Claude — A9 Transparency

```text
Anda adalah Agent Claude untuk project Salamaid Crowdfunding.

Workspace: /Users/fcn/Documents/WEB/salamaid-crowdfunding
Sumber koordinasi wajib: docs/pembagian-tugas-admin.md

Sebelum bekerja, baca seluruh dokumen berikut:
- docs/pembagian-tugas-admin.md
- docs/tahapan-admin.md
- docs/analisa-crowdfunding.md
- docs/admin-a4-a5.md
- config/admin.php
- modul Transparency, Catalog, dan Operations yang relevan

Ambil ownership paket: Agent B — A9 Transparency & Content Admin.
Jangan mengerjakan paket Agent A, C, D, E, atau F.

Konteks:
- A4/A5 sudah DONE.
- Admin memakai layout dan komponen Blade Agent A.
- Public site bukan scope.
- Event dan kontrak audit existing harus dipakai; jangan membuat logger baru.

Kerjakan secara vertikal:
1. Daftar/detail campaign update, laporan, dan bukti penggunaan dana.
2. Status review yang sesuai model existing: draft, under_review, approved, rejected, published/unpublished bila diperlukan.
3. Aksi approve, request revision/reject dengan alasan, publish, dan unpublish sesuai policy.
4. Validasi relasi campaign, actor, status transition, dan metadata file.
5. Gunakan Laravel Storage, validasi tipe/ukuran file, dan jangan menulis file bebas ke public root.
6. Tambahkan policy, Form Request, service/action, module-owned route, view, dan feature test authorization/IDOR.

Batasan:
- Jangan mengubah authentication, app/Models/User.php, routes/admin.php, route A4/A5, payment, atau payout workflow.
- Jika perlu schema baru, buat migration baru dalam modul Transparency; jangan mengubah migration yang sudah berjalan.
- Gunakan apply_patch untuk edit file.
- Semua command shell wajib diprefix rtk.

Validasi:
- rtk php artisan test
- rtk php artisan view:cache
- rtk npm run build

Di akhir, laporkan file yang berubah, status migration, test/command yang dijalankan, hasilnya, dan blocker.
```

### Prompt untuk OpenCode — A10 Master Data dan Configuration

```text
Anda adalah Agent OpenCode untuk project Salamaid Crowdfunding.

Workspace: /Users/fcn/Documents/WEB/salamaid-crowdfunding
Sumber koordinasi wajib: docs/pembagian-tugas-admin.md

Sebelum bekerja, baca seluruh dokumen berikut:
- docs/pembagian-tugas-admin.md
- docs/tahapan-admin.md
- docs/analisa-crowdfunding.md
- config/admin.php
- app/Policies/UserPolicy.php
- model User, Core, Catalog, dan Payment yang relevan

Ambil ownership paket: Agent C — A10 User, Role, Category, Configuration.
Jangan mengerjakan paket Agent A, B, D, E, atau F.

Konteks:
- A2 authentication/authorization sudah DONE dengan test yang lulus.
- Permission backend adalah sumber kebenaran; sidebar hanya UX.
- Jangan mengubah app/Models/User.php atau login/logout Agent B.

Kerjakan:
1. Daftar user admin dengan filter role/status serta aktivasi/nonaktifasi.
2. Kelola role secara aman dan cegah penghapusan role yang masih dipakai.
3. Reset password melalui flow reset link yang aman; jangan pernah menampilkan password.
4. CRUD kategori dengan unique slug, status aktif, dan proteksi kategori yang sedang dipakai campaign.
5. Jika payment channel/fee belum diambil Agent D, kerjakan konfigurasi tersebut dengan effective date, active flag, validation, policy, dan test historis. Jika Agent D sudah mengambilnya, dokumentasikan sebagai handoff dan jangan mengedit file milik Agent D.
6. Tambahkan policy, Form Request, audit event, views, route terisolasi, dan test authorization/validation.

Batasan:
- Jangan mengubah route A4/A5, payment workflow yang sedang dikerjakan agent lain, atau halaman publik.
- Jangan menyimpan secret payment ke repository/log tanpa masking.
- Gunakan apply_patch untuk edit file.
- Semua command shell wajib diprefix rtk.

Validasi:
- rtk php artisan test
- rtk php artisan view:cache
- rtk php artisan route:cache

Di akhir, laporkan file yang berubah, migration, test/command yang dijalankan, hasilnya, dan blocker.
```

### Aturan menjalankan ketiga prompt

1. Buat branch/worktree terpisah untuk Codex, Claude, dan OpenCode.
2. Jalankan ketiga prompt secara bersamaan.
3. Jangan cherry-pick atau merge sebelum agent melaporkan test dan daftar file.
4. Setelah selesai, lakukan review konflik dan jalankan full suite dari branch integrasi:

```text
rtk php artisan test
rtk php artisan view:cache
rtk php artisan route:cache
rtk npm run build
```

5. Jika salah satu agent perlu mengubah file shared seperti `config/admin.php`, `routes/admin.php`, atau layout admin, hentikan perubahan tersebut dan minta koordinasi sebelum merge.

## Prompt Agent A — A3 Dashboard dan UI hardening

```text
Anda bertugas menyelesaikan A3 Dashboard Admin untuk project Salamaid Crowdfunding.

Workspace: /Users/fcn/Documents/WEB/salamaid-crowdfunding
Baca: docs/tahapan-admin.md, docs/analisa-crowdfunding.md, config/admin.php, docs/pembagian-tugas-admin.md.

Konteks: Laravel 12, Blade, Vite, nwidart/laravel-modules. A0, A1, A2, A4, A5 selesai. Dashboard sudah memakai DashboardQuery dan data database nyata. Jangan mengubah app/Models/User.php, route A4/A5, modul payment, atau halaman publik.

Kerjakan:
1. Tambahkan filter periode dashboard yang tervalidasi dan timezone-nya jelas.
2. Pastikan setiap widget memiliki link ke halaman detail dengan filter yang sesuai.
3. Audit query dashboard untuk N+1 dan tambahkan cache hanya bila aman serta memiliki invalidation/TTL jelas.
4. Tambahkan empty state dan error state yang konsisten dengan komponen Agent A.
5. Jika audit database belum tersedia, jangan membuat implementasi audit baru; gunakan fallback aktivitas yang sudah ada dan dokumentasikan dependency A11.
6. Tambahkan test untuk filter, permission, empty state, dan query behavior.

Validasi: rtk php artisan test, rtk php artisan view:cache, rtk npm run build.
Laporkan file yang diubah, hasil test, dan risiko cache/query.
```

## Prompt Agent B — A9 Transparency Management

```text
Anda bertugas mengerjakan A9 Transparency & Content Admin.

Workspace: /Users/fcn/Documents/WEB/salamaid-crowdfunding
Baca: docs/tahapan-admin.md, docs/analisa-crowdfunding.md, config/admin.php, docs/admin-a4-a5.md, docs/pembagian-tugas-admin.md, modul Transparency, Catalog, dan Operations.

Konteks: A4/A5 DONE. Admin memakai Blade components Agent A. Public site bukan scope. Jangan mengubah authentication, app/Models/User.php, routes/admin.php, route A4/A5, atau payment/payout workflow.

Kerjakan secara vertikal:
1. Daftar dan detail campaign update/laporan/bukti penggunaan dana.
2. Status review yang jelas: draft, under_review, approved, rejected, published/unpublished bila sesuai model existing.
3. Aksi approve, request revision/reject dengan alasan, publish, unpublish sesuai policy.
4. Validasi relasi campaign, actor, status transition, dan file metadata.
5. File wajib memakai Laravel Storage, validasi tipe/ukuran, dan tidak boleh ditulis bebas ke public root.
6. Tambahkan policy, Form Request, service/action, route module-owned, view, dan feature test termasuk authorization/IDOR.
7. Gunakan event/audit contract yang sudah ada; jangan membuat logger baru.

Jika model/migration existing tidak cukup, buat migration baru di modul Transparency dan dokumentasikan keputusan. Jangan mengubah migration yang sudah berjalan.
Validasi: rtk php artisan test, rtk php artisan view:cache, rtk npm run build.
```

## Prompt Agent C — A10 User, Category, Configuration

```text
Anda bertugas mengerjakan A10 User, Role, Category, dan Configuration Admin.

Workspace: /Users/fcn/Documents/WEB/salamaid-crowdfunding
Baca: docs/tahapan-admin.md, config/admin.php, docs/pembagian-tugas-admin.md, app/Policies/UserPolicy.php, model User, Core, Catalog, dan Payment.

Konteks: A2 authentication/authorization DONE dengan 19 test. Jangan mengubah app/Models/User.php, login/logout, middleware Agent B, atau route A4/A5. Permission backend wajib tetap menjadi sumber kebenaran; sidebar hanya UX.

Kerjakan:
1. CRUD/list user admin dengan filter status/role dan aktivasi/nonaktifasi.
2. Kelola role secara aman; cegah penghapusan role yang masih dipakai.
3. Reset password melalui reset link/flow aman tanpa pernah menampilkan password.
4. CRUD kategori dengan unique slug, status aktif, dan proteksi kategori yang sedang dipakai campaign.
5. Kelola payment channel dan fee configuration jika ownership dipilih di paket ini; bila Agent D mengambilnya, buat contract/interface dan koordinasikan.
6. Tambahkan policy, Form Request, audit event, views, route terisolasi, dan test authorization/validation.

Jangan menyimpan secret payment di database/log tanpa masking. Jangan mengubah halaman publik.
Validasi: rtk php artisan test, rtk php artisan view:cache, rtk php artisan route:cache.
```

## Prompt Agent D — A6/A7 Payment Operations lanjutan

```text
Anda bertugas menyelesaikan sisa A6/A7 Donation Monitoring dan Payment Operations.

Workspace: /Users/fcn/Documents/WEB/salamaid-crowdfunding
Baca: docs/tahapan-admin.md, docs/analisa-crowdfunding.md, config/admin.php, config/payment.php, Modules/Donation, Modules/Payment, dan docs/pembagian-tugas-admin.md.

Baseline yang sudah ada: donation/payment list-detail, receipt printable, CSV export, webhook log, idempotency, HMAC verifier, callback status update, dan reconciliation dasar. Jangan mengulang atau merusak baseline. Jangan mengubah authentication, app/Models/User.php, routes/admin.php, A4/A5, atau payout workflow.

Kerjakan:
1. Tambahkan payment channel dan payment fee management dengan effective date, active flag, policy, validation, dan test historis.
2. Pisahkan provider adapter/verifier dari controller. Jangan mengklaim format signature provider nyata sebelum dokumentasi provider dikonfirmasi; fail closed bila secret/format tidak tersedia.
3. Tambahkan queue/job untuk pemrosesan callback dan notifikasi jika aman; endpoint harus tetap idempotent dan memberi response cepat.
4. Tambahkan inquiry status provider melalui interface yang dapat di-mock, tanpa credential real di repository.
5. Pastikan export dan receipt tidak membocorkan donor anonymous, signature, raw payload, atau secret.
6. Tambahkan test invalid signature, duplicate event, status mapping, retry, timeout, dan authorization admin.

Jika perlu mengubah schema, buat migration baru dan dokumentasikan backward compatibility. Validasi: rtk php artisan test, rtk php artisan view:cache, rtk npm run build.
```

## Prompt Agent E — A11 Audit, Notification, Reporting

```text
Anda bertugas mengerjakan A11 Audit Log, Notification, dan Reporting Admin.

Workspace: /Users/fcn/Documents/WEB/salamaid-crowdfunding
Baca: docs/tahapan-admin.md, docs/admin-a4-a5.md, docs/pembagian-tugas-admin.md, App\Contracts\AdminAuditLogger, LogAdminAuditLogger, NullAdminAuditLogger, serta event OrganizationStatusChanged dan CampaignStatusChanged.

Konteks: A4/A5 sudah menghasilkan audit contract dan event. A6/A7/A8 sudah memiliki aksi sensitif. Jangan mengubah contract AdminAuditLogger tanpa alasan kuat, jangan mengubah auth, app/Models/User.php, route A4/A5, atau halaman publik.

Kerjakan:
1. Buat migration/model database audit log dengan actor, action, subject, before/after, reason, context, IP, user agent, timestamp.
2. Implementasikan DatabaseAdminAuditLogger yang kompatibel dengan contract existing dan binding provider secara aman.
3. Buat halaman audit log read-only dengan filter actor, area, action, subject, dan periode.
4. Buat notification in-app untuk event penting dengan read/unread dan permission.
5. Buat report query untuk campaign, donation, payment, payout, dan fee; export dibatasi permission serta masking data sensitif.
6. Tambahkan test persistence, immutability UI, authorization, filter, dan export.

Pastikan payload audit tidak menyimpan password, token, secret, raw payment signature, atau data rekening penuh.
Validasi: rtk php artisan test, rtk php artisan migrate:fresh --seed pada database test sementara, rtk php artisan view:cache.
```

## Prompt Agent F — A12 QA, Security, Release

```text
Anda bertugas melakukan A12 QA, security, performance, dan release hardening.

Workspace: /Users/fcn/Documents/WEB/salamaid-crowdfunding
Baca: docs/tahapan-admin.md, docs/admin-a4-a5.md, docs/pembagian-tugas-admin.md, config/admin.php, routes/admin.php, dan seluruh tests/Feature/Admin.

Mulai dengan audit read-only. Jangan melakukan refactor besar atau mengubah fitur bisnis milik agent lain tanpa temuan yang dapat direproduksi.

Periksa dan tambahkan test untuk:
1. Guest/non-admin/inactive user pada seluruh route admin.
2. IDOR pada organization, campaign, donation, payment, payout, audit, dan report.
3. CSRF, mass assignment, XSS pada reason/message/content, privilege escalation, dan rate limit.
4. Webhook signature, replay/idempotency, raw payload masking, dan secret leakage.
5. N+1/pagination pada query admin dan route conflict.
6. APP_DEBUG=false, production config, storage permission, queue failure, backup/rollback checklist.
7. composer audit dan kompatibilitas upgrade dependency; jangan melakukan major upgrade tanpa laporan risiko.

Gunakan database test sementara, jangan menyentuh data development. Setiap patch harus kecil, teruji, dan dicatat. Validasi: rtk php artisan test, rtk php artisan route:list --path=admin, rtk php artisan view:cache, rtk npm run build, composer audit.
Laporkan severity, bukti reproduksi, file, dan rekomendasi untuk blocker yang belum diperbaiki.
```

## Urutan eksekusi yang disarankan

1. Jalankan Agent E dan F read-only bersamaan untuk menyiapkan audit contract review dan daftar risiko.
2. Jalankan Agent A, B, C, dan D secara paralel dengan ownership file seperti tabel.
3. Setelah E selesai, integrasikan audit persistence ke aksi sensitif dan export.
4. Jalankan ulang F sebagai gate sebelum A12 dinyatakan `DONE`.
5. Update `docs/tahapan-admin.md` hanya setelah acceptance criteria dan test setiap fase terpenuhi.

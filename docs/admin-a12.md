# A12 — Hardening, QA, dan Release Readiness

Status: `DONE` untuk hardening dan release review yang dapat dijalankan lokal. Smoke test sandbox Mayar dan restore drill tetap menjadi aktivitas deployment/staging, bukan dijalankan terhadap database lokal ini.

Branch `agent-c/a12-hardening` sudah diintegrasikan ke `main` melalui merge commit. Urutannya mencakup A7, A8, snapshot A11, dan hardening A12.

## 1. Dependency audit (`composer audit`)

Sebelum perbaikan ada 14 package dengan advisory, antara lain:

- `guzzlehttp/guzzle` 7.10.0: 1 high
- `league/commonmark` 2.7.1: 9 high
- `symfony/http-foundation` 6.4.24: 2 high
- `symfony/mime`, `symfony/yaml` (dev): high
- `laravel/framework` 10.48.29: 1 critical, 1 high

**Perbaikan tanpa breaking change:** update minor/patch di dalam constraint `composer.json` yang ada. File `composer.json` tidak diubah; hanya `composer.lock`. Contohnya:

| Package | Sebelum → sesudah |
|---|---|
| guzzlehttp/guzzle | 7.10.0 → 7.16.1 |
| guzzlehttp/psr7 | 2.8.0 → 2.13.2 |
| league/commonmark | 2.7.1 → 2.10.7 |
| league/flysystem | 3.30 → 3.36 |
| symfony/* | 6.4.24 → 6.4.49 |
| psy/psysh | 0.12.10 → 0.12.24 |
| phpunit | 10.5.53 → 10.5.66 |
| laravel/framework | 10.48.29 → 10.50.4 |

Full suite lulus setelah update.

Pada validasi setelah merge, `composer audit` masih perlu dijalankan ulang sebagai bagian release gate. Aplikasi sudah di-upgrade ke Laravel 12 dan dependency mayor yang terkait sudah diselaraskan; advisory yang tersisa harus dievaluasi berdasarkan lockfile terbaru.

| Advisory | Keterjangkauan di aplikasi | Mitigasi |
|---|---|---|
| CVE-2026-114509 TokenGuard auth bypass (critical) | Tidak terjangkau. `config/auth.php` hanya memakai guard `session`; tidak ada driver `token`. Sanctum memakai guard-nya sendiri, bukan TokenGuard. | Jangan menambah guard `token`. |
| Temporary Signed URL path confusion (medium) | Tidak terjangkau. Tidak ada `signedRoute`/`temporarySignedRoute` maupun middleware `signed` yang dipakai. | Hindari signed URL sampai upgrade. |
| CRLF injection pada rule `email` (high, 2 entri) | Rule `email` hanya dipakai di form login, dan nilainya tidak pernah dipakai sebagai header email. | Untuk input email baru yang akan dikirimi email, pakai `email:rfc,filter`. |
| CVE-2026-102279 XSS di halaman debug (low) | Hanya muncul bila `APP_DEBUG=true`. | `APP_DEBUG=false` di production (ada di checklist). |

**Rekomendasi:** jalankan `composer audit` pada CI/release gate dan minta review security owner sebelum production release.

## 2. Audit route dan authorization

`php artisan route:list --path=admin` menghasilkan 79 route. Semua berada di grup `web` + `auth` (atau `admin.review.guest`) + `admin` + `admin.permission:*`. Pengecualiannya `admin.login`, `admin.login.store`, dan `admin.logout` (yang terakhir cukup memakai `auth`).

Invariant ini sekarang **dikunci oleh test** `test_every_admin_route_is_behind_web_auth_admin_and_permission_middleware`. Route admin baru yang lupa diberi middleware akan membuat suite gagal.

- **Aksi pada objek:** policy dipanggil di controller atau di `authorize()` Form Request, dan sudah ada test per area (A4–A11).
- **Matriks permission:** `test_route_permission_matrix_matches_config_for_every_role` menguji semua route GET tanpa parameter untuk 5 role + `user`. Role tanpa permission mendapat 403; role berizin tidak mendapat 403/500.
- **Export:** `donations.export`, `payments.export`, dan `reports.export` dibatasi permission dan ikut dicek matriks.
- **Download file privat:** policy `downloadEvidence` (payout) dan `view` + scoped binding (transparency).

## 3. Security test

File test:

- `tests/Feature/Admin/AdminSecurityHardeningTest.php` (10 test)
- `tests/Feature/Admin/AdminQueryPerformanceTest.php` (2 test)
- Test per area: A2, A4–A11, A7 (`MayarPaymentSyncTest`), A8 (`PayoutOperationsTest`)

| Area | Bukti |
|---|---|
| CSRF | Semua route admin POST/PATCH/DELETE (lebih dari 40) mengembalikan 419 tanpa token, memakai `VerifyCsrfToken` sungguhan (bypass unit test dimatikan). `$except` kosong. |
| IDOR | Payout evidence (path payout lain, row yang dimanipulasi, file hilang), transparency attachment (scoped binding), notifikasi (`markRead` milik user lain → 404). Objek admin lain bersifat global per permission, bukan per tenant, dan dijaga policy. |
| Privilege escalation | Reviewer ditolak untuk payment sync, payment channel, detail payment, dan export donasi. Finance, admin, reviewer, dan support ditolak mengubah role (hanya super_admin). Support ditolak publish campaign dan approve organisasi. |
| Data sensitif | Support tidak melihat nomor rekening organisasi. |
| Mass assignment | Field `role`/`status` yang diselundupkan diabaikan (CSRF/route test, sync payment, transparency create). Field workflow tidak `fillable`. |
| XSS | Payload `<script>` dan `onerror` di nama/deskripsi organisasi, judul/deskripsi campaign, alasan penolakan, judul/isi/review update transparansi, alasan audit, serta judul/pesan notifikasi selalu ter-escape di 8 halaman. `href="javascript:` tidak pernah dirender. |
| Secret | Hash password, signature payment, raw payload, dan email di raw request tidak muncul di halaman user/payment maupun di 3 export CSV. Audit tidak memuat hash. API key Mayar tidak masuk log/exception (test A7). |
| CSV injection | Diuji dan diperbaiki (lihat §4). Uji mutasi: test gagal bila sanitizer dimatikan. |
| Error disclosure | Dengan `APP_DEBUG=false`, error 500/404/403 tidak memuat pesan maupun kelas exception. |

## 4. Perbaikan yang dibuat di A12

1. **Formula injection pada CSV:** `App\Support\CsvSanitizer` diterapkan di export donasi, payment, dan report. Sel teks berawalan `= + - @ TAB CR` diberi prefiks `'`; angka tidak diubah.
2. **Link notifikasi:** `href` hanya dirender untuk path relatif atau http(s). Data `javascript:` tidak bisa menjadi link (pertahanan berlapis; saat ini URL berasal dari `route()`).
3. **A8:** payout tidak dapat ditandai paid bila organisasi berubah status (mis. suspended) setelah approval. Ditambah test regresi.
4. **Config:** `dashboard.payout_pending_days` dibaca dari `ADMIN_DASHBOARD_PAYOUT_PENDING_DAYS`. Sebelumnya dipakai di kode tetapi tidak ada di config.
5. **Dependency:** update minor/patch (§1).

## 5. Input dan output

- Semua area admin memakai Form Request atau validasi query (filter tanggal memakai `date` + `after_or_equal`, `per_page` dibatasi 5–100).
- Upload privat (payout, transparency) memvalidasi tipe dari isi file, ukuran, dan path (anti traversal).
- Blade selalu memakai `{{ }}`. Satu-satunya raw output admin adalah `{!! nl2br(e(...)) !!}` yang sudah di-escape. View `index` scaffold modul memakai `{!! config(...) !!}` dengan nilai config tepercaya.

## 6. Query dan performa

`test_admin_list_pages_have_constant_query_count` membuktikan jumlah query **identik** antara 2 dan 8 baris data pada 10 halaman:

- organizations, campaigns, donations, payments, webhooks, payouts, users, audit-logs, reports, dan dashboard.

Artinya tidak ada N+1, dan semua halaman di bawah 40 query. List memakai pagination. Export memakai `lazy()` (chunked).

Cache dashboard default-nya nonaktif (`ADMIN_DASHBOARD_CACHE_SECONDS=0`), sehingga tidak ada risiko data antar-permission tercampur.

## 7. Queue, scheduler, dan webhook

Detail ada di `docs/admin-a7.md`.

- **Job:** `ProcessPaymentWebhookJob` dan `SyncPaymentStatusJob`, keduanya `ShouldBeUnique`, `tries=5`, backoff 30s/2m/10m/30m. Failed job tercatat di `failed_jobs`. Webhook duplikat aman.
- **Akses:** payment sync dan retry webhook hanya untuk `payments.manage`.
- **Queue:** produksi wajib `QUEUE_CONNECTION=database` (tabel `jobs` sudah tersedia) dan menjalankan worker:
  `php artisan queue:work database --tries=5 --max-time=3600`, dijalankan lewat supervisor/systemd.
- **Scheduler belum tersedia:** belum ada penyisiran terjadwal untuk payment `pending` yang webhook-nya tidak datang. Sementara ini, pakai tombol "Sinkronkan" di detail payment. Rekomendasinya command terjadwal yang mendispatch `SyncPaymentStatusJob` untuk payment `pending` Mayar berusia lebih dari 15 menit.

## 8. Konfigurasi production

Nilai rahasia **tidak** disimpan di repository; `.env` di-ignore, dan `.env.example` hanya berisi placeholder.

| Key | Production |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` (wajib) |
| `APP_URL` | URL https publik (dipakai di link notifikasi/queue) |
| `DB_*` | Kredensial dari secret manager; user DB tanpa hak `DROP` di luar deploy |
| `QUEUE_CONNECTION` | `database` (lihat §7) |
| `MAIL_*` | SMTP produksi; `MAIL_FROM_ADDRESS` domain sendiri |
| `FILESYSTEM_DISK` | `local`. Bukti privat memakai disk `private`, jangan `public` |
| `PAYOUT_EVIDENCE_DISK` / `TRANSPARENCY_EVIDENCE_DISK` | `private` / `local` (keduanya di luar `public/`) |
| `MAYAR_ENVIRONMENT` / `MAYAR_API_BASE_URL` | `production` / `https://api.mayar.id/hl/v2` |
| `MAYAR_API_KEY` / `MAYAR_WEBHOOK_TOKEN` | Secret manager; rotasi bila bocor |
| `MAYAR_TIMEOUT` / `MAYAR_CONNECT_TIMEOUT` | `10` / `5` |
| `ADMIN_NOTIFICATION_EMAIL_ENABLED` | `false` sampai mailer terverifikasi |
| `ADMIN_DASHBOARD_PAYOUT_PENDING_DAYS` | `3` (default) |
| `ADMIN_DASHBOARD_TIMEZONE` | `Asia/Jakarta` |

Key yang sebelumnya belum ada di `.env.example` sudah ditambahkan: `MAYAR_TIMEOUT`, `MAYAR_CONNECT_TIMEOUT`, `PAYOUT_EVIDENCE_DISK`, `TRANSPARENCY_EVIDENCE_DISK`, dan `ADMIN_DASHBOARD_*`.

## 9. Error page dan kesiapan operasional

- **Halaman error:** 403, 404, 419, 429, 500, dan 503 (maintenance) memakai view bawaan Laravel. View kustom sengaja **tidak** ditambahkan, karena `resources/views/errors` berlaku global dan akan mengubah halaman publik (di luar scope). Detail exception tidak bocor saat `APP_DEBUG=false` (ada test-nya).
- **Logging:** log payment direduksi (A7), audit me-redact secret (A11), dan export disanitasi.
- **Private storage:** `storage/app/private` harus dimiliki user web-server (mode 750), di-backup bersama database, dan tidak boleh di-symlink ke `public/`.

### Checklist deployment

1. Backup database (`mysqldump --single-transaction`) dan `storage/app` (termasuk `private/`).
2. `php artisan down --render="errors::503"`.
3. Deploy kode, lalu `composer install --no-dev --optimize-autoloader` dan `npm ci && npm run build`.
4. `php artisan migrate --force`. Semua migration A7–A12 aditif; jalankan `--pretend` terlebih dahulu.
5. `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
6. Restart queue worker: `php artisan queue:restart`.
7. `php artisan up`. Smoke test: login admin, dashboard, detail payment, sinkronisasi Mayar sandbox/produksi, dan download bukti payout.

### Rollback

1. `php artisan down`.
2. Kembalikan rilis kode sebelumnya.
3. `php artisan migrate:rollback --step=N` (migration A7: `payment_sync_logs`, kolom webhook, `jobs`). Bila rollback gagal, restore dump database.
4. Bersihkan cache (`optimize:clear`), restart worker, lalu `php artisan up`.

### Backup dan restore

Prosedurnya terdokumentasi di atas, tetapi **drill restore belum dilakukan** (lihat §10).

## 10. Risiko rilis yang tersisa

1. **Dependency advisory:** jalankan `composer audit` terhadap lockfile Laravel 12 terbaru dan selesaikan advisory yang masih memiliki jalur upgrade.
2. **Route scaffold modul publik:** sekitar 56 route `Route::resource` bawaan generator (`/catalog`, `/core`, `/donation`, `/fundraising`, `/operations`, `/payment`, `/transparency`) aktif tanpa autentikasi.
   - GET mengembalikan 500 karena view scaffold tidak ada.
   - POST/PUT/DELETE berupa method kosong yang tetap dilindungi CSRF.
   - Tidak ada data yang bocor, tetapi route ini menambah attack surface dan error 500. **Perlu keputusan pemilik halaman publik** untuk menghapusnya sebelum rilis; tidak diubah di sini karena halaman publik di luar scope.
3. **Scheduler sinkronisasi payment pending:** sudah tersedia melalui `payment:sync-pending`, tetapi production tetap harus menjalankan scheduler worker dan queue worker.
4. **Responsive UI (desktop/mobile) dan drill backup/restore** belum dapat dibuktikan dari test otomatis; lakukan pada staging dengan backup target yang aman.
5. **Smoke test Mayar sandbox** tetap memerlukan credential dan endpoint sandbox aktif; jangan diarahkan ke production.
6. **Integrasi selesai:** A7, A8, A11, dan A12 berada di `main`; A8 reconciliation/risk flag menambah 4 test feature.

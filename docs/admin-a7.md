# A7 — Payment Operations Lanjutan (Mayar inquiry, queue, admin sync)

Status: `DONE` (scheduler pending payment sudah ditambahkan). Detail integrasi provider ada di [`payment-mayar.md`](payment-mayar.md).

## Yang ditambahkan

- **Inquiry:** `MayarClient::getTransaction()` memanggil `GET /transactions/{transactionId}` dengan timeout yang bisa dikonfigurasi dan klasifikasi error (`MayarRequestException`: `httpStatus`, `retryable`, `retryAfter`).
- **Sync satu payment:** `SyncPaymentStatusAction`. Setiap percobaan dicatat di `payment_sync_logs`, dan sync manual dicatat ke audit log (`payment.sync`).
- **Transisi status tunggal:** `ApplyPaymentStatusAction` dipakai oleh inquiry dan webhook provider bertanda tangan (generic/faspay). Ini sekaligus menutup celah lama di mana callback `failed` bisa menimpa payment yang sudah `paid`.
- **Job:**
  - `ProcessPaymentWebhookJob`: memproses webhook yang sudah tercatat. Untuk Mayar dikonfirmasi lewat inquiry, untuk provider lain memakai status payload.
  - `SyncPaymentStatusJob`: inquiry untuk satu payment, dengan trigger `manual`, `webhook`, atau `retry`.
  - Keduanya `ShouldBeUnique`, `tries = 5`, backoff `[30, 120, 600, 1800]`, `timeout = 30`.
- **Admin:**
  - Detail payment menampilkan riwayat sinkronisasi (10 terakhir) serta percobaan dan error terakhir webhook. Ini terlihat oleh semua role dengan `payments.view`.
  - Tombol sinkronisasi dan proses ulang hanya muncul untuk `payments.manage`.

## Route baru (`payments.manage`)

| Method | URI | Name |
|---|---|---|
| POST | `/admin/payments/{payment}/sync` | `admin.payments.sync` |
| POST | `/admin/webhooks/{webhook}/retry` | `admin.payments.webhooks.retry` |

Kedua route juga dicek oleh `PaymentPolicy::manage` di controller. Tidak ada route untuk mengubah status payment secara manual, dan parameter `status` dari request diabaikan.

## Migration tambahan

Migration payment yang sudah ada tidak diubah.

- `Modules/Payment/Database/Migrations/2026_11_02_000090_add_sync_tracking_to_payment_tables.php`
  - Tabel `payment_sync_logs`: trigger, attempt, outcome, status provider, status before/after, status HTTP, error, ringkasan respons tanpa data customer, dan actor.
  - Kolom `webhooks.attempts`, `webhooks.last_error`, dan `webhooks.failed_at`.
- `database/migrations/2026_11_02_000091_create_jobs_table.php`: tabel queue `database`. Migration ini dilewati bila tabelnya sudah ada.

Keduanya sudah diuji `migrate:fresh --seed`, rollback 2 langkah, lalu migrate ulang (SQLite).

## Menjalankan queue

```bash
# .env: QUEUE_CONNECTION=database
php artisan migrate
php artisan queue:work database --tries=5
```

Dengan `QUEUE_CONNECTION=sync` (default lokal saat ini), job berjalan inline. Retry otomatis dengan backoff hanya berlaku pada queue async. Pada mode sync, retry bergantung pada pengiriman ulang webhook oleh Mayar (respons 503) atau tombol "Proses ulang" dan "Sinkronkan".

## Test

`tests/Feature/Admin/MayarPaymentSyncTest.php` berisi 14 test:

- Inquiry sukses.
- Inquiry gagal: log yang dicatat sudah direduksi dan tidak memuat API key.
- Klasifikasi error 404, 429 + `Retry-After`, timeout, dan key kosong.
- Mapping eksplisit.
- `unmapped` dan `mismatch` (ID dan nominal).
- Transisi monotonik.
- Retry job dengan backoff nyata di queue `database`.
- Job gagal langsung pada error yang tidak bisa di-retry.
- Webhook duplikat diproses sekali dengan satu inquiry.
- `payment.reminder` dengan `SUCCESS` tidak menandai paid.
- Webhook yang gagal dicatat lalu di-retry oleh finance.
- Webhook bertanda tangan tidak bisa menurunkan status paid.
- Otorisasi: guest, admin, support, reviewer, dan user ditolak. Finance diizinkan, dan finance tidak bisa memaksa status.

`DonationPaymentMonitoringTest` juga disesuaikan: webhook Mayar sekarang memerlukan inquiry (di-fake).

## Catatan lanjutan

- Notifikasi berbasis queue/email dikelola pada A11 dan tetap opt-in melalui konfigurasi mail.

## Scheduler payment pending

Command `payment:sync-pending` berjalan setiap lima menit melalui Laravel Scheduler. Command hanya memilih payment provider Mayar yang masih `pending` dan lebih tua dari `PAYMENT_PENDING_SYNC_MINUTES` (default 15 menit), dengan batas batch `PAYMENT_PENDING_SYNC_LIMIT` (default 100). Setiap payment didispatch ke `SyncPaymentStatusJob` dengan trigger `scheduled`; job tetap unique sehingga tidak menumpuk inquiry yang sama.

Konfigurasi dapat dimatikan dengan `PAYMENT_PENDING_SYNC_ENABLED=false`. Production perlu menjalankan scheduler worker, misalnya `php artisan schedule:work`, dan queue worker database sesuai bagian sebelumnya.

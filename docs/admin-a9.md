# A9 — Transparency & Content Admin

Status: `REVIEW` (Agent B / Claude). Fitur, authorization, UI, dan test sudah berjalan. Menunggu review integrasi.

## Cakupan

- Daftar update campaign dan laporan penggunaan dana. Filter tersedia untuk status, jenis, campaign, pencarian, dan rentang tanggal, ditambah ringkasan jumlah per status.
- Detail update: konten, campaign, organisasi, payout terkait, reviewer, timeline, dan bukti.
- Admin dapat mencatat update atas nama organisasi. Update selalu dibuat sebagai `draft`.
- Workflow review: submit, approve, minta revisi (reject) dengan alasan, publish, dan unpublish dengan alasan.
- Bukti penggunaan dana: upload, download terotorisasi, dan hapus (hanya saat draft atau perlu revisi).

Di luar cakupan:

- Halaman publik. Pakai scope `CampaignUpdate::public()` saat halaman publik dibangun.
- Edit konten update.
- Portal organisasi.
- Virus scanning, karena scanner belum tersedia.
- Approval dua orang.

## Jenis dan status

`type`: `update`, `fund_report`. `fund_report` wajib memiliki `amount_used` dan minimal satu bukti sebelum dikirim review.

```text
draft ──submit──▶ under_review ──approve──▶ approved ──publish──▶ published
rejected ─submit─▶     │                                   ▲            │
   ▲                   └──reject (alasan wajib)──▶ rejected │        unpublish (alasan wajib)
   │                                                        └── publish ◀── unpublished
```

Aturan:

- Transisi dijalankan di `CampaignUpdateReviewService` dalam transaksi dengan `lockForUpdate()`. Transisi ilegal ditolak (`AdminActionException`) dan dikembalikan sebagai flash `error`.
- Publish hanya diizinkan bila campaign berstatus `published` atau `closed`.
- Payout terkait (khusus `fund_report`) harus milik organisasi pemilik campaign, berstatus `paid`, dan `amount_used` tidak boleh melebihi nominal payout.
- Field workflow (`status`, `reviewed_by`, `published_at`, dst.) tidak dapat diisi lewat mass assignment.
- Hanya `status = published` yang dianggap konten publik (`scopePublic`).

## File bukti

- Disimpan dengan Laravel Storage di disk `config('transparency.evidence.disk')`, default `local` (`storage/app`, privat). Path-nya `transparency/evidence/{update_id}/<nama-acak>`.
- Validasi lewat `mimes:jpg,jpeg,png,webp,pdf` (deteksi dari konten file), ukuran maks 5 MB, dan maks 10 file per update. Konfigurasinya ada di `Modules/Transparency/config/config.php`.
- Metadata yang disimpan: nama asli yang sudah disanitasi, MIME hasil deteksi, ukuran, checksum sha256, dan pengunggah.
- Download hanya lewat route admin dengan `Content-Disposition: attachment` dan `X-Content-Type-Options: nosniff`.
- Route attachment memakai scoped binding, sehingga attachment milik update lain menghasilkan 404 (anti-IDOR).

## Route

Didaftarkan oleh `Modules/Transparency/App/Providers/RouteServiceProvider::mapAdminRoutes()` dengan middleware `web, auth, admin`, prefix `admin/transparency`, dan nama `admin.transparency.*`. `routes/admin.php` tidak diubah. Akses area memakai `admin.permission:transparency.view,transparency.review`, lalu setiap aksi diperiksa lagi oleh `CampaignUpdatePolicy`.

| Method | URI | Name | Policy |
|---|---|---|---|
| GET | `/admin/transparency` | `index` | `viewAny` |
| GET | `/admin/transparency/create` | `create` | `create` |
| POST | `/admin/transparency` | `store` | `create` |
| GET | `/admin/transparency/{update}` | `show` | `view` |
| POST | `/admin/transparency/{update}/submit` | `submit` | `review` |
| POST | `/admin/transparency/{update}/approve` | `approve` | `review` |
| POST | `/admin/transparency/{update}/reject` | `reject` | `review` |
| POST | `/admin/transparency/{update}/publish` | `publish` | `publish` |
| POST | `/admin/transparency/{update}/unpublish` | `unpublish` | `publish` |
| POST | `/admin/transparency/{update}/attachments` | `attachments.store` | `manageAttachments` |
| GET | `/admin/transparency/{update}/attachments/{attachment}` | `attachments.download` | `view` |
| DELETE | `/admin/transparency/{update}/attachments/{attachment}` | `attachments.destroy` | `manageAttachments` |

Permission: semua aksi memakai `transparency.review` (super_admin, admin, reviewer). `transparency.view` juga diterima untuk akses baca, karena sidebar sudah memakai nama permission itu, tetapi saat ini belum diberikan ke role mana pun di `config/admin.php`.

## Audit dan event

Memakai kontrak `App\Contracts\AdminAuditLogger` yang sudah ada, dengan context `['module' => 'Transparency', 'entity' => 'campaign_update']`. Action yang dicatat:

- `transparency.create`, `submit`, `approve`, `reject`, `publish`, `unpublish`
- `transparency.attachment.upload`, `transparency.attachment.delete`

Event `Modules\Transparency\App\Events\CampaignUpdateStatusChanged` dipancarkan setelah setiap transisi status, untuk dipakai A11 (notifikasi).

## Migration

`Modules/Transparency/Database/Migrations/2026_11_01_000050_add_review_workflow_to_campaign_updates_table.php`

- Menambah `type`, `status`, `payout_id`, `amount_used`, `created_by`, `submitted_at`, `reviewed_by`, `reviewed_at`, `review_note`, dan `unpublished_at` ke `campaign_updates`.
- Backfill: baris lama yang sudah memiliki `published_at` diberi status `published`.
- Membuat tabel `campaign_update_attachments`.
- `down()` menghapus tabel attachment dan kolom-kolom tersebut. Sudah diuji rollback lalu migrate ulang di SQLite.

## Test

`tests/Feature/Admin/TransparencyManagementTest.php` berisi 14 test:

- Akses guest, non-admin, finance, support, dan user nonaktif.
- List, filter, dan validasi filter.
- Pengecekan N+1.
- Create beserta validasi relasi: payout dari organisasi lain, payout belum dibayar, dan nominal melebihi payout.
- Workflow lengkap beserta audit dan event.
- Revisi dengan alasan wajib.
- Transisi ilegal.
- Publish untuk campaign yang bukan publik.
- Upload: storage privat, nama acak, dan sanitasi nama.
- Validasi tipe, ukuran, dan status untuk upload.
- IDOR pada download dan hapus attachment.
- Hapus attachment hanya saat editable.
- ID tidak dikenal atau formatnya salah.

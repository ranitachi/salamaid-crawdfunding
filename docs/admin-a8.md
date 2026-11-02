# A8 — Payout Hardening: Private Evidence Storage

Status: `REVIEW` (Agent C). Cakupan dokumen ini adalah perbaikan bukti transfer payout yang sebelumnya ditolak di review.

## Perbaikan atas review

| Temuan review | Perbaikan |
|---|---|
| `PayoutOperationsTest` gagal | Semua test payout lulus (4 lama + 15 baru) |
| Controller memakai `validated('evidence_url')` | Controller memakai `MarkPayoutPaidRequest::validatedEvidence()` |
| Model belum punya field evidence | `evidence_path`, `evidence_mime`, dan `evidence_size` ada di fillable dan casts. Ditambah helper `hasEvidence()`, `hasPrivateEvidence()`, `hasLegacyEvidenceUrl()`, dan `evidenceDirectory()` |
| Policy belum punya download | `PayoutPolicy::downloadEvidence()` |
| Route download belum ada | `GET /admin/payouts/{payout}/evidence` (`admin.payouts.evidence.download`) |
| View belum mendukung upload | Form `multipart/form-data` dengan field `evidence_file`, plus `evidence_url` opsional untuk format lama |
| `Modules/Operations/admin.php` | Identik dengan `Modules/Operations/routes/admin.php` dan tidak dimuat di mana pun. Duplikat mati, tidak dibawa ke branch ini (lihat catatan integrasi) |
| Kode WIP memanggil `storage('private')` | Helper tersebut tidak ada di Laravel. Diganti `Storage::disk(config('operations.evidence.disk'))` |

## Penyimpanan

- Disk baru `private` di `config/filesystems.php`, dengan root `storage/app/private`, tanpa `url` dan di luar `public/`.
- Konfigurasi ada di `Modules/Operations/config/config.php` (`operations.evidence`):
  - disk: `PAYOUT_EVIDENCE_DISK`, default `private`;
  - direktori: `payouts/evidence`;
  - ukuran maksimal 5 MB;
  - tipe: jpg/jpeg/png/pdf.
- File disimpan di `payouts/evidence/{payout_id}/<nama-acak>.<ext>`. Nama dari klien tidak pernah dipakai.
- MIME dan size dibaca dari file yang sudah tersimpan di disk (`Storage::mimeType()`/`size()`). MIME dari klien tidak dipercaya.
- Tidak pernah dibuat URL publik; `evidence_url` diisi `null` untuk bukti privat.

## Format input (`MarkPayoutPaidRequest`)

Harus tepat satu sumber bukti. Bila tidak ada sama sekali, atau lebih dari satu, request ditolak dengan error `evidence`.

| Field | Aturan |
|---|---|
| `evidence_file` | `file`, `mimes:jpg,jpeg,png,pdf` (ekstensi ditebak dari isi file), `mimetypes:image/jpeg,image/png,application/pdf`, `max:5120` |
| `evidence_url` | URL lama, `url:http,https`, maks 2048 karakter |
| `evidence` | Field string lama: URL http(s), **atau** path privat yang cocok dengan pola `payouts/evidence/{payout_id_ini}/[A-Za-z0-9]{16,64}.(jpg\|jpeg\|png\|pdf)` |

Path yang tidak cocok dengan pola tersebut ditolak sebelum menyentuh Storage. Ini mencakup `..`, path absolut, folder milik payout lain, dan ekstensi lain. Service lalu memastikan file itu ada dan memeriksa ulang MIME dari isinya.

`authorize()` pada request juga memeriksa policy `markPaid`.

## Aturan `PayoutService::markPaid()`

1. Status harus `approved`. Ini dicek sebelum file disimpan.
2. Bukti diproses:
   - file atau path: harus berada di disk privat dan di folder payout itu sendiri, dengan MIME yang diizinkan;
   - URL: harus http(s) dan disimpan ke `evidence_url`. URL tidak pernah dianggap sebagai file privat.
3. Saldo tersedia divalidasi ulang di dalam transaksi `lockForUpdate`, sebelum status menjadi `paid`.
4. Bila langkah apa pun gagal, file yang baru diunggah langsung dihapus, sehingga tidak ada file yatim.
5. Audit `payout.paid` mencatat snapshot sebelum dan sesudah: status, approver, `paid_at`, note, tipe bukti, path/MIME/size, dan URL **tanpa query string maupun fragment** (token signed URL tidak masuk audit). Isi file tidak pernah dicatat.

## Download

`PayoutAdminController::downloadEvidence()`:

- Memanggil `authorize('downloadEvidence', $payout)`. Ini hanya untuk `payouts.manage` (finance, super_admin), dan hanya bila payout itu punya bukti privat di folder miliknya sendiri. Role view-only (admin, support) hanya melihat metadata.
- Hanya membaca `evidence_path` milik payout yang di-bind, jadi path milik payout lain yang dipaksakan ke sebuah row ditolak (403).
- Mengembalikan 404 bila file tidak ada atau UUID tidak valid.
- Response berupa `Content-Disposition: attachment` dengan nama `bukti-payout-{id}.{ext}`, `Content-Type` dari deteksi server, `X-Content-Type-Options: nosniff`, dan `Cache-Control: private, no-store`.

## Kompatibilitas data lama

- Payout lama dengan `evidence_url` tetap tampil sebagai link "Buka bukti (URL lama)". Link hanya dirender bila skemanya http(s), dengan `rel="noopener noreferrer"`.
- Field `evidence_url` dan field string `evidence` tetap diterima untuk klien lama.

## Migration

Tidak ada migration baru. `Modules/Operations/Database/Migrations/2026_11_02_000010_add_evidence_fields_to_payouts_table.php` tidak diubah. Migration ini sudah diuji `migrate:fresh --seed`, rollback, lalu migrate ulang di SQLite.

## Test

`tests/Feature/Admin/PayoutEvidenceTest.php` berisi 15 test:

- Penyimpanan privat dan metadata dari server.
- Upload PNG.
- File PHP yang disamarkan sebagai PDF, GIF yang dinamai PNG, dan file terlalu besar ditolak.
- Tepat satu sumber bukti.
- URL lama melalui kedua field.
- URL tidak aman (`javascript:`, `ftp:`, `file:`).
- Path traversal dan IDOR pada path, ditambah lapis kedua: file berisi PHP di disk tetap ditolak service.
- Saldo dicek ulang dan file dibersihkan bila gagal.
- Hanya status `approved` yang bisa ditandai paid.
- Otorisasi mark paid.
- Audit tanpa token maupun isi file.
- Download untuk finance dan super_admin.
- Download ditolak untuk guest, admin, support, reviewer, dan user.
- Scoping download ke payout yang di-bind.
- Tampilan bukti privat dan URL lama.

Catatan: test upload memakai `UploadedFile` sungguhan dari file temp, karena `UploadedFile::fake()` melaporkan MIME berdasarkan nama file, bukan isinya.

## Belum dikerjakan

Prompt tugas terpotong setelah poin Policy. Bagian berikut **belum** dikerjakan:

- Rekonsiliasi settlement (total payout vs donation settled).
- Blokir payout untuk organisasi suspended atau yang punya fraud flag.

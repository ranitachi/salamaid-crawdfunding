# A11 — Audit, Notification, dan Reporting

Status: `DONE`. Audit persistence, notification center, dashboard alert, reporting, dan export sudah tersedia.

## Implementasi selesai

- Tabel `admin_audit_logs` menyimpan action, subject, actor, before/after, reason, context, IP, user agent, dan timestamp.
- `DatabaseAdminAuditLogger` menggantikan logger log-only melalui contract `AdminAuditLogger` tanpa mengubah contract.
- Nilai sensitif seperti password, token, secret, signature, dan raw payment payload direduksi menjadi `[REDACTED]`.
- Halaman `/admin/audit-logs` mendukung pencarian action/subject/actor dan filter tanggal.
- Akses memakai permission `audit.view` dan dibatasi middleware admin.
- Activity feed dashboard membaca audit log persistent dan memakai fallback timestamp domain jika belum ada log.
- Notification center in-app tersedia di `/admin/notifications` dengan status read/unread dan aksi tandai sudah dibaca.
- Campaign `under_review` dan organisasi `pending` memicu notifikasi kepada role yang memiliki permission review.
- Notification email tersedia melalui channel opt-in `ADMIN_NOTIFICATION_EMAIL_ENABLED=true`; default tetap database-only.
- Dashboard alert mendeteksi payment gagal/expired, webhook belum diproses, payment mismatch, dan payout tertunda.
- Laporan `/admin/reports` mencakup campaign, donation, payment, payout, fee platform, dan fee channel.
- Export report mencatat actor, filter tanggal, dan jumlah metric melalui audit action `report.export`.

## Catatan operasional

- Aktifkan email hanya setelah konfigurasi mail production diuji.
- Queue worker wajib aktif jika notification email dijalankan secara asynchronous.

# A11 — Audit Log Persistence

Status: `IN PROGRESS`. Audit log database dan halaman read-only sudah tersedia; notification dan reporting masih menjadi pekerjaan lanjutan.

## Implementasi selesai

- Tabel `admin_audit_logs` menyimpan action, subject, actor, before/after, reason, context, IP, user agent, dan timestamp.
- `DatabaseAdminAuditLogger` menggantikan logger log-only melalui contract `AdminAuditLogger` tanpa mengubah contract.
- Nilai sensitif seperti password, token, secret, signature, dan raw payment payload direduksi menjadi `[REDACTED]`.
- Halaman `/admin/audit-logs` mendukung pencarian action/subject/actor dan filter tanggal.
- Akses memakai permission `audit.view` dan dibatasi middleware admin.
- Activity feed dashboard membaca audit log persistent dan memakai fallback timestamp domain jika belum ada log.

## Tindak lanjut

- Notifikasi in-app dan email untuk event penting.
- Report campaign, donation, payment, payout, dan fee.
- Export report dengan permission, masking, dan audit actor/filter/count.

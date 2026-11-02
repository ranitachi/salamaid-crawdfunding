# Mayar Sandbox Smoke Test

Dokumen ini adalah checklist manual untuk B8. Test tidak dijalankan otomatis karena membuat transaksi eksternal pada akun sandbox.

## Prasyarat

- `.env` menggunakan `MAYAR_ENVIRONMENT=sandbox` dan API key sandbox yang valid.
- `APP_URL` mengarah ke URL lokal yang dapat menerima callback jika Mayar memerlukannya.
- Queue worker aktif jika webhook/job Payment menggunakan queue.
- Database lokal sudah bermigrasi dan user/campaign published tersedia.

## Skenario

1. Buka satu campaign published dari halaman publik.
2. Buat donation nominal kecil dan catat `donation_id`/order reference lokal.
3. Selesaikan pembayaran pada halaman Mayar sandbox.
4. Pastikan webhook Mayar diterima dan signature/token tervalidasi.
5. Jalankan worker queue bila job masih pending.
6. Buka halaman status pembayaran dan pastikan status lokal menjadi `paid`.
7. Pastikan receipt menampilkan nominal dan reference tanpa raw payload/token.
8. Cek admin Payment, Donation, dan Mayar Transactions untuk konsistensi.

## Kriteria lulus

- Payment lokal tidak menjadi `paid` sebelum webhook/inquiry tervalidasi.
- Webhook duplicate aman/idempotent.
- Donation dan payment memiliki reference yang sama dengan transaksi sandbox.
- Tidak ada API key, webhook token, atau raw provider payload di response/log/view.

## Catatan production

Ulangi skenario setelah mengganti endpoint dan credential ke production Mayar. Jangan memakai credential sandbox pada production.

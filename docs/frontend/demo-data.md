# Data Demo Development

`DatabaseSeeder` memanggil `DevelopmentSeeder`, sehingga data berikut dapat dibuat ulang dengan aman menggunakan:

```bash
rtk php artisan db:seed --class=DevelopmentSeeder
```

Fixture yang tersedia:

- 1 organisasi terverifikasi: Yayasan Salamaid Demo.
- 3 campaign published dengan cover lokal di `public/images/demo/`.
- 3 media campaign dan 4 update transparansi published.
- Donasi paid, pending, dan failed.
- 3 payment provider `mayar` untuk fixture paid, pending, dan failed.
- 2 webhook Mayar sandbox yang sudah diproses.
- Payout demo berstatus requested.
- Payment channel QRIS demo dan fee 0 untuk development.

Semua order ID `MAYAR-DEMO-*` adalah fixture lokal dan bukan transaksi provider nyata. Data ini tidak memanggil API Mayar dan tidak menggantikan smoke test sandbox.

Asset gambar dibuat khusus sebagai placeholder development dan dapat diganti tanpa mengubah schema atau controller.

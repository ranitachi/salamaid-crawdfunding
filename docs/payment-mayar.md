# Integrasi Payment Mayar

Project memakai Mayar Headless API V2. Base URL sandbox `https://api.mayar.io/hl/v2`, production `https://api.mayar.id/hl/v2`.

## Environment

Secret hanya diisi di `.env` lokal atau secret manager deployment. Jangan commit API key maupun webhook token.

```env
MAYAR_ENVIRONMENT=sandbox
MAYAR_API_BASE_URL=https://api.mayar.io/hl/v2
MAYAR_API_KEY=
MAYAR_WEBHOOK_TOKEN=
MAYAR_WEBHOOK_TOKEN_QUERY=token
MAYAR_TIMEOUT=10          # detik, opsional
MAYAR_CONNECT_TIMEOUT=5   # detik, opsional
QUEUE_CONNECTION=database # disarankan agar retry/backoff berjalan; `sync` tetap didukung
```

- `MAYAR_API_KEY` hanya dikirim server-side sebagai header `Authorization: Bearer`. Key ini tidak pernah masuk pesan exception, log, sync log, ataupun audit.
- `MAYAR_WEBHOOK_TOKEN` dipakai di URL callback:

```text
https://domain-anda.example/api/v1/payment/webhooks/mayar?token=WEBHOOK_TOKEN
```

## Flow pembayaran

1. `CreateMayarPaymentAction` memanggil `POST /payments/create`. Nilai `data.transactionId` disimpan sebagai `payments.order_id`, dan `data.link` sebagai `redirect_url`.
2. Customer diarahkan ke `redirect_url`.
3. Mayar mengirim webhook. Webhook diverifikasi lewat token, lalu dicatat secara idempotent (provider + order_id + event). Pemrosesannya diserahkan ke `ProcessPaymentWebhookJob`.
4. Job menjalankan **inquiry server-to-server** `GET /transactions/{transactionId}`. Status final payment hanya berasal dari inquiry ini.

> Payload webhook Mayar **tidak** dipakai untuk menentukan status. Di dokumentasi Mayar, `data.status: "SUCCESS"` juga muncul pada event `payment.reminder` yang `transactionStatus`-nya masih `created`. Jadi `SUCCESS` adalah status pengiriman, bukan status pembayaran.

## Inquiry dan mapping status

Endpoint: `GET /transactions/{id}`, dengan `id` = `transactionId` = `payments.order_id` ([Transaction Detail](https://docs.mayar.id/api-reference-v2/transaction/detail)). `GET /payments/{id}` memerlukan ID payment request yang tidak kita simpan, sehingga tidak dipakai untuk inquiry.

Mapping eksplisit di `MayarStatusMapper`:

| Mayar `data.status` | Internal |
|---|---|
| `paid` | `paid` |
| `unpaid` | `pending` |
| `created` | `pending` |
| `expired` | `expired` |
| lainnya | tidak diubah (`unmapped`) |

Aturan transisi di `ApplyPaymentStatusAction` (dengan lock, idempotent):

- `paid` bersifat final dan tidak pernah turun status.
- `pending` tidak menimpa status yang sudah settled.
- `expired`/`failed` hanya dapat berubah menjadi `paid` (pembayaran terlambat yang dikonfirmasi provider).

Pemeriksaan tambahan sebelum status diterapkan:

- `data.id` harus sama dengan `order_id`.
- `amount` tidak boleh lebih kecil dari nominal donasi.

Bila salah satu tidak terpenuhi, hasilnya `mismatch` dan status tidak diubah.

## Error, timeout, dan retry

| Kondisi | Klasifikasi |
|---|---|
| Timeout/koneksi, 5xx | retryable |
| 429 | retryable, mengikuti `Retry-After` |
| 401, 404, 4xx lain, data kosong, API key kosong | tidak di-retry, job langsung gagal |

- Kedua job memakai `tries = 5`, backoff 30 detik, 2 menit, 10 menit, dan 30 menit, serta unique per payment/webhook.
- `payment.received` yang belum dikonfirmasi inquiry (masih `unpaid`) dicek ulang dengan backoff.
- Pada `QUEUE_CONNECTION=sync`, kegagalan webhook dikembalikan sebagai HTTP 503 agar Mayar mengirim ulang. Record webhook yang sama dipakai lagi.
- Log yang dicatat sudah direduksi: `payment.sync.failed` dan `payment.webhook.processing_failed`, hanya berisi ID, status HTTP, dan pesan singkat.

Dokumentasi resmi:

- [Mayar API V2](https://docs.mayar.id/api-reference-v2/introduction)
- [Create Single Payment Request](https://docs.mayar.id/api-reference-v2/reqpayment/create)
- [Transaction Detail](https://docs.mayar.id/api-reference-v2/transaction/detail)
- [Webhook](https://docs.mayar.id/api-reference-v2/webhook/newhistory)
- [Rate limit](https://docs.mayar.id/api-reference-v2/rate-limit) (50 request/menit per API key)

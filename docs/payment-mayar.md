# Integrasi Payment Mayar

Project menggunakan Mayar Headless API V2. Sandbox menggunakan base URL `https://api.mayar.io/hl/v2`; production menggunakan `https://api.mayar.id/hl/v2`.

## Environment

Isi secret hanya pada `.env` lokal atau secret manager deployment. Jangan commit API key maupun webhook token.

```env
MAYAR_ENVIRONMENT=sandbox
MAYAR_API_BASE_URL=https://api.mayar.io/hl/v2
MAYAR_API_KEY=
MAYAR_WEBHOOK_TOKEN=
MAYAR_WEBHOOK_TOKEN_QUERY=token
```

`MAYAR_API_KEY` dikirim server-side sebagai `Authorization: Bearer`. `MAYAR_WEBHOOK_TOKEN` dipakai pada URL callback Mayar:

```text
https://domain-anda.example/api/v1/payment/webhooks/mayar?token=WEBHOOK_TOKEN
```

Endpoint webhook membaca `event`, `data.transactionId`, dan `data.status`. Status `SUCCESS` dipetakan menjadi payment/donation `paid`. Callback yang sama tetap idempotent.

## Flow

1. Buat payment request lewat `Modules\Payment\App\Services\CreateMayarPaymentAction`.
2. Simpan `data.transactionId` sebagai `payments.order_id` dan `data.link` sebagai `redirect_url`.
3. Arahkan customer ke `redirect_url`.
4. Anggap pembayaran berhasil hanya setelah webhook tervalidasi atau inquiry server-to-server.

Dokumentasi resmi: [Mayar API V2](https://docs.mayar.id/api-reference-v2/introduction), [Create Single Payment Request](https://docs.mayar.id/api-reference-v2/reqpayment/create), dan [Webhook New History](https://docs.mayar.id/api-reference-v2/webhook/newhistory).

<?php

namespace Modules\Payment\App\Http\Controllers\Admin;

use App\Contracts\AdminAuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Payment\App\Exceptions\MayarRequestException;
use Modules\Payment\App\Jobs\ProcessPaymentWebhookJob;
use Modules\Payment\App\Jobs\SyncPaymentStatusJob;
use Modules\Payment\Entities\Payment;
use Modules\Payment\Entities\Webhook;

/**
 * payments.manage only. Both actions ask the provider (or replay a verified
 * webhook); none of them lets an admin choose a payment status.
 */
class PaymentSyncAdminController extends Controller
{
    public function sync(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('manage', $payment);

        if ($payment->provider !== 'mayar') {
            return $this->back($payment, 'error', 'Sinkronisasi provider hanya tersedia untuk payment Mayar.');
        }

        try {
            $pending = SyncPaymentStatusJob::dispatch((string) $payment->getKey(), 'manual', $request->user()->getKey());
            unset($pending);
        } catch (MayarRequestException $exception) {
            return $this->back($payment, 'error', 'Sinkronisasi gagal dan akan dicoba ulang: '.$exception->getMessage());
        }

        $latest = $payment->syncLogs()->first();

        if (! $latest || $latest->created_at?->lt(now()->subSeconds(5))) {
            return $this->back($payment, 'success', 'Sinkronisasi dijadwalkan di antrean. Hasil akan tampil di riwayat sinkronisasi.');
        }

        return $this->back($payment, $latest->outcome === 'failed' ? 'error' : 'success', $this->describe($latest->outcome));
    }

    public function retryWebhook(Request $request, Webhook $webhook, AdminAuditLogger $audit): RedirectResponse
    {
        $payment = $webhook->payment;
        abort_unless($payment, 404);
        $this->authorize('manage', $payment);

        if ($webhook->processed_at) {
            return $this->back($payment, 'success', 'Webhook sudah diproses; tidak ada yang diulang.');
        }

        $webhook->forceFill(['failed_at' => null])->save();
        $audit->log('payment.webhook.retry', $webhook, ['attempts' => $webhook->attempts], [], null, $request->user(),
            ['module' => 'Payment', 'entity' => 'webhook']);

        try {
            $pending = ProcessPaymentWebhookJob::dispatch((string) $webhook->getKey());
            unset($pending);
        } catch (MayarRequestException $exception) {
            return $this->back($payment, 'error', 'Webhook belum dapat diproses: '.$exception->getMessage());
        }

        return $this->back($payment, 'success', $webhook->fresh()->processed_at
            ? 'Webhook berhasil diproses ulang.'
            : 'Pemrosesan ulang webhook dijadwalkan di antrean.');
    }

    private function describe(string $outcome): string
    {
        return match ($outcome) {
            'updated' => 'Status payment diperbarui sesuai konfirmasi Mayar.',
            'unchanged' => 'Status payment sudah sesuai dengan Mayar.',
            'unmapped' => 'Status dari Mayar tidak dikenal; status internal tidak diubah.',
            'mismatch' => 'Data Mayar tidak cocok dengan payment; status tidak diubah.',
            'failed' => 'Sinkronisasi gagal; periksa riwayat sinkronisasi.',
            default => 'Sinkronisasi selesai.',
        };
    }

    private function back(Payment $payment, string $type, string $message): RedirectResponse
    {
        return redirect()->route('admin.payments.show', $payment)->with($type, $message);
    }
}

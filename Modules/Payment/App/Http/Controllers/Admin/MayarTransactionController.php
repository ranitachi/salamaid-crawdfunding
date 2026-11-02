<?php

namespace Modules\Payment\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Payment\App\Exceptions\MayarRequestException;
use Modules\Payment\App\Http\Requests\Admin\MayarTransactionIndexRequest;
use Modules\Payment\App\Services\MayarClient;
use Modules\Payment\Entities\Payment;

class MayarTransactionController extends Controller
{
    public function __construct(private readonly MayarClient $mayar)
    {
    }

    public function index(MayarTransactionIndexRequest $request): View
    {
        $this->authorize('viewAny', Payment::class);
        $filters = $request->validated();
        $query = array_filter([
            'status' => $filters['status'] ?? null,
            'startAt' => $filters['start_at'] ?? null,
            'endAt' => $filters['end_at'] ?? null,
            'startingAfter' => $filters['starting_after'] ?? null,
            'limit' => $filters['limit'] ?? 20,
            'fields' => 'id,createdAt,updatedAt,status,amount,paymentMethod',
        ], static fn ($value): bool => $value !== null && $value !== '');

        try {
            $result = $this->mayar->listTransactions($query);
            $error = null;
        } catch (\Throwable $exception) {
            report($exception);
            $result = ['data' => [], 'nextStartingAfter' => null];
            $error = 'Transaksi Mayar belum dapat dimuat. Periksa konfigurasi API atau coba lagi.';
        }

        return view('admin.mayar-transactions.index', [
            'transactions' => $result['data'],
            'nextStartingAfter' => $result['nextStartingAfter'],
            'filters' => $filters,
            'error' => $error,
        ]);
    }

    public function show(string $transaction): View
    {
        $this->authorize('viewAny', Payment::class);
        $localPayment = Payment::query()
            ->whereKey($transaction)
            ->orWhere('order_id', $transaction)
            ->first();
        $providerTransaction = $localPayment?->order_id ?: $transaction;

        try {
            $result = $this->mayar->getTransaction($providerTransaction);
            $error = null;
        } catch (MayarRequestException $exception) {
            if ($exception->httpStatus === 404) {
                try {
                    $summary = $this->mayar->findTransaction($providerTransaction);
                    if ($summary) {
                        $result = ['data' => $summary];
                        $error = null;
                    } else {
                        // Mayar has separate IDs for payment requests and transactions.
                        // Support both when an operator opens a payment-request ID.
                        $result = $this->mayar->getPaymentRequest($providerTransaction);
                        $error = null;
                    }
                } catch (\Throwable $fallbackException) {
                    report($fallbackException);
                    $result = [];
                    $error = $fallbackException instanceof MayarRequestException && $fallbackException->httpStatus === 404
                        ? 'ID tersebut tidak ditemukan sebagai transaksi maupun payment request pada akun Mayar.'
                        : 'Detail Mayar belum dapat dimuat. Periksa konfigurasi API atau coba lagi.';
                }
            } else {
                report($exception);
                $result = [];
                $error = 'Detail transaksi Mayar belum dapat dimuat. Periksa konfigurasi API atau coba lagi.';
            }
        } catch (\Throwable $exception) {
            report($exception);
            $result = [];
            $error = 'Detail transaksi Mayar belum dapat dimuat. Periksa konfigurasi API atau coba lagi.';
        }

        return view('admin.mayar-transactions.show', compact('transaction', 'providerTransaction', 'localPayment', 'result', 'error'));
    }
}

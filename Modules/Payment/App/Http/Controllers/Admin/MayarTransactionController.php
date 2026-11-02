<?php

namespace Modules\Payment\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
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

        try {
            $result = $this->mayar->getTransaction($transaction);
            $error = null;
        } catch (\Throwable $exception) {
            report($exception);
            $result = [];
            $error = 'Detail transaksi Mayar belum dapat dimuat.';
        }

        return view('admin.mayar-transactions.show', compact('transaction', 'result', 'error'));
    }
}

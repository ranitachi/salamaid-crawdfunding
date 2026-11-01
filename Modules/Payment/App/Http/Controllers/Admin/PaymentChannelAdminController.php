<?php

namespace Modules\Payment\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Payment\App\Http\Requests\PaymentChannelRequest;
use Modules\Payment\App\Http\Requests\PaymentFeeRequest;
use Modules\Payment\App\Services\Admin\PaymentChannelManagementService;
use Modules\Payment\Entities\PaymentChannel;

class PaymentChannelAdminController extends Controller
{
    public function __construct(private readonly PaymentChannelManagementService $service) {}

    public function index(): View
    {
        return view('admin.payment-channels.index', ['channels' => PaymentChannel::with('fees')->orderBy('provider')->orderBy('label')->get()]);
    }

    public function store(PaymentChannelRequest $request): RedirectResponse
    {
        $this->service->saveChannel($request->validated(), null, $request->user());
        return back()->with('success', 'Payment channel berhasil ditambahkan.');
    }

    public function update(PaymentChannelRequest $request, PaymentChannel $channel): RedirectResponse
    {
        $this->service->saveChannel($request->validated(), $channel, $request->user());
        return back()->with('success', 'Payment channel berhasil diperbarui.');
    }

    public function activate(PaymentChannel $channel, \Illuminate\Http\Request $request): RedirectResponse
    {
        $this->service->setActive($channel, true, $request->user());
        return back()->with('success', 'Payment channel diaktifkan.');
    }

    public function deactivate(PaymentChannel $channel, \Illuminate\Http\Request $request): RedirectResponse
    {
        $this->service->setActive($channel, false, $request->user());
        return back()->with('success', 'Payment channel dinonaktifkan.');
    }

    public function storeFee(PaymentFeeRequest $request, PaymentChannel $channel): RedirectResponse
    {
        try { $this->service->saveFee($channel, $request->validated(), $request->user()); }
        catch (\InvalidArgumentException $exception) { return back()->withInput()->with('error', $exception->getMessage()); }
        return back()->with('success', 'Fee payment berhasil disimpan.');
    }
}

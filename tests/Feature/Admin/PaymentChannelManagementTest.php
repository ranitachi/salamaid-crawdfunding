<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payment\Entities\PaymentChannel;
use Modules\Payment\Entities\PaymentFee;
use Tests\TestCase;

class PaymentChannelManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_can_create_channel_and_fee(): void
    {
        $finance = User::factory()->create(['role' => 'finance']);
        $this->actingAs($finance)->post('/admin/payment-channels', ['provider' => 'mayar', 'code' => 'mayar_qris', 'method' => 'qris', 'label' => 'Mayar QRIS', 'active' => 1])->assertRedirect();
        $channel = PaymentChannel::firstOrFail();
        $this->actingAs($finance)->post('/admin/payment-channels/'.$channel->id.'/fees', ['fee_flat' => 1000, 'fee_percent' => 1.5, 'min_fee' => 1000, 'effective_from' => '2026-11-01'])->assertRedirect();
        $this->assertDatabaseHas('payment_fees', ['payment_channel_id' => $channel->id, 'fee_flat' => 1000]);
    }

    public function test_support_cannot_manage_payment_channels(): void
    {
        $support = User::factory()->create(['role' => 'support']);
        $this->actingAs($support)->get('/admin/payment-channels')->assertForbidden();
    }

    public function test_overlapping_fee_period_is_rejected(): void
    {
        $finance = User::factory()->create(['role' => 'finance']);
        $channel = PaymentChannel::create(['provider' => 'mayar', 'code' => 'mayar_qris', 'method' => 'qris', 'label' => 'Mayar QRIS', 'active' => true]);
        PaymentFee::create(['payment_channel_id' => $channel->id, 'fee_flat' => 0, 'fee_percent' => 1, 'min_fee' => 0, 'effective_from' => '2026-11-01']);
        $this->actingAs($finance)->from('/admin/payment-channels')->post('/admin/payment-channels/'.$channel->id.'/fees', ['fee_flat' => 1000, 'fee_percent' => 2, 'min_fee' => 0, 'effective_from' => '2026-12-01'])->assertRedirect()->assertSessionHas('error');
    }
}

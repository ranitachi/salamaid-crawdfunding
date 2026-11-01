<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Category;
use Modules\Core\Entities\Organization;
use Modules\Core\Entities\OrganizationMember;
use Modules\Donation\Entities\Donation;
use Modules\Fundraising\Entities\Fundraiser;
use Modules\Operations\Entities\Payout;
use Modules\Payment\Entities\Payment;
use Modules\Payment\Entities\Webhook;
use Modules\Transparency\Entities\CampaignUpdate;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => env('SALAMAID_ADMIN_EMAIL', 'admin@salamaid.test')],
            [
                'name' => 'Salamaid Admin',
                'password' => env('SALAMAID_ADMIN_PASSWORD', 'change-me-local'),
                'role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $donor = User::updateOrCreate(
            ['email' => 'donor@salamaid.test'],
            [
                'name' => 'Demo Donor',
                'password' => 'change-me-local',
                'role' => 'user',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $organization = Organization::updateOrCreate(
            ['slug' => 'yayasan-salamaid-demo'],
            [
                'name' => 'Yayasan Salamaid Demo',
                'description' => 'Data development untuk mencoba alur admin Salamaid.',
                'contact_email' => 'demo@salamaid.test',
                'contact_phone' => '+62 811 0000 0000',
                'status' => 'verified',
                'verified_at' => now(),
            ],
        );

        OrganizationMember::updateOrCreate(
            ['organization_id' => $organization->id, 'user_id' => $admin->id],
            ['role' => 'owner'],
        );

        $category = Category::updateOrCreate(
            ['slug' => 'pendidikan'],
            ['name' => 'Pendidikan', 'icon' => 'book-open'],
        );

        $campaign = Campaign::updateOrCreate(
            ['slug' => 'beasiswa-anak-negeri'],
            [
                'organization_id' => $organization->id,
                'category_id' => $category->id,
                'title' => 'Beasiswa Anak Negeri',
                'description' => 'Campaign contoh untuk pengembangan dan pengujian panel admin.',
                'target_amount' => 100000000,
                'collected_amount' => 25000000,
                'deadline_at' => Carbon::now()->addMonths(2),
                'location_label' => 'Indonesia',
                'status' => 'published',
                'is_featured' => true,
            ],
        );

        $donation = Donation::updateOrCreate(
            ['campaign_id' => $campaign->id, 'user_id' => $donor->id, 'message' => 'Donasi demo admin'],
            [
                'gross_amount' => 250000,
                'fee_platform' => 0,
                'fee_channel' => 0,
                'net_amount' => 250000,
                'method' => 'qris',
                'anonymous' => false,
                'status' => 'paid',
                'paid_at' => now(),
            ],
        );

        Payment::updateOrCreate(
            ['order_id' => 'SALAMAID-DEMO-0001'],
            [
                'donation_id' => $donation->id,
                'provider' => 'demo',
                'channel_code' => 'QRIS',
                'status' => 'paid',
                'paid_at' => now(),
                'signature' => 'demo-signature',
                'raw_request_json' => ['source' => 'DevelopmentSeeder'],
                'raw_callback_json' => ['status' => 'paid'],
            ],
        );

        Webhook::updateOrCreate(
            ['provider' => 'demo', 'order_id' => 'SALAMAID-DEMO-0001', 'event' => 'payment.paid'],
            [
                'signature' => 'demo-webhook-signature',
                'payload' => ['order_id' => 'SALAMAID-DEMO-0001', 'status' => 'paid'],
                'processed_at' => now(),
            ],
        );

        $update = CampaignUpdate::updateOrCreate(
            ['campaign_id' => $campaign->id, 'title' => 'Update awal campaign demo'],
            ['content' => 'Update contoh untuk menguji halaman transparansi admin.'],
        );
        // Workflow fields are not mass assignable (A9); seed a published update explicitly.
        $update->forceFill([
            'status' => 'published',
            'published_at' => $update->published_at ?? now(),
        ])->save();

        Fundraiser::updateOrCreate(
            ['campaign_id' => $campaign->id, 'user_id' => $donor->id],
            [
                'ref_code' => 'DEMO2026',
                'title' => 'Fundraiser Demo',
                'total_collected' => 250000,
            ],
        );

        Payout::firstOrCreate(
            ['organization_id' => $organization->id, 'note' => 'Payout demo untuk panel admin'],
            [
                'amount' => 1000000,
                'status' => 'requested',
                'requested_by' => $admin->id,
            ],
        );

        DB::table('payment_channels')->updateOrInsert(
            ['code' => 'QRIS-DEMO'],
            [
                'provider' => 'demo',
                'method' => 'qris',
                'label' => 'QRIS Demo',
                'active' => true,
                'meta_json' => json_encode(['environment' => 'development']),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $channelId = DB::table('payment_channels')->where('code', 'QRIS-DEMO')->value('id');

        DB::table('payment_fees')->updateOrInsert(
            ['payment_channel_id' => $channelId, 'effective_from' => '2026-01-01'],
            [
                'fee_flat' => 0,
                'fee_percent' => 0,
                'min_fee' => 0,
                'effective_to' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Entities\Campaign;
use Modules\Catalog\Entities\CampaignMedia;
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
        $helpCategories = [];
        foreach ([
            ['name' => 'Akun dan profil', 'slug' => 'akun-dan-profil', 'sort_order' => 10],
            ['name' => 'Donasi dan pembayaran', 'slug' => 'donasi-dan-pembayaran', 'sort_order' => 20],
            ['name' => 'Campaign dan transparansi', 'slug' => 'campaign-dan-transparansi', 'sort_order' => 30],
            ['name' => 'Keamanan dan privasi', 'slug' => 'keamanan-dan-privasi', 'sort_order' => 40],
            ['name' => 'Umum', 'slug' => 'umum', 'sort_order' => 99],
        ] as $category) {
            $helpCategories[$category['name']] = HelpCategory::updateOrCreate(['slug' => $category['slug']], $category + ['is_active' => true]);
        }

        foreach ([
            ['category' => 'Donasi dan pembayaran', 'question' => 'Bagaimana cara berdonasi?', 'answer' => 'Pilih campaign, tekan tombol Donasi Sekarang, isi nominal dan data donatur, lalu lanjutkan pembayaran melalui Mayar.', 'sort_order' => 10],
            ['category' => 'Donasi dan pembayaran', 'question' => 'Bagaimana jika pembayaran belum selesai?', 'answer' => 'Buka menu Donasi Saya, pilih receipt yang berstatus Pending, lalu gunakan tombol Lanjutkan pembayaran atau Buat ulang link pembayaran.', 'sort_order' => 20],
            ['category' => 'Donasi dan pembayaran', 'question' => 'Di mana melihat riwayat donasi?', 'answer' => 'Setelah login, buka menu Akun lalu pilih Donasi Saya. Setiap donasi memiliki receipt dan status pembayaran.', 'sort_order' => 30],
            ['category' => 'Akun dan profil', 'question' => 'Bagaimana mengubah data pribadi?', 'answer' => 'Buka Akun, gulir ke bagian Data Pribadi, ubah nama, email, atau nomor WhatsApp, lalu tekan Simpan perubahan.', 'sort_order' => 10],
            ['category' => 'Akun dan profil', 'question' => 'Bagaimana mengatur ulang password?', 'answer' => 'Gunakan menu Lupa password pada halaman login. Tautan reset akan dikirim jika email terdaftar.', 'sort_order' => 20],
        ] as $article) {
            HelpArticle::updateOrCreate(['question' => $article['question']], $article + ['help_category_id' => $helpCategories[$article['category']]->id, 'is_published' => true]);
        }

        DB::table('app_settings')->updateOrInsert(
            ['key' => 'public_navigation'],
            [
                'value' => json_encode(\App\Services\AppSettingsService::PUBLIC_NAVIGATION_DEFAULTS),
                'group' => 'branding',
                'type' => 'json',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
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
                'mobile' => '+628120000000',
                'password' => 'change-me-local',
                'role' => 'user',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $demoDonors = collect([
            ['name' => 'Aisyah Rahma', 'email' => 'aisyah.demo@salamaid.test', 'mobile' => '+628120000101'],
            ['name' => 'Budi Santoso', 'email' => 'budi.demo@salamaid.test', 'mobile' => '+628120000102'],
            ['name' => 'Citra Lestari', 'email' => 'citra.demo@salamaid.test', 'mobile' => '+628120000103'],
            ['name' => 'Dedi Kurniawan', 'email' => 'dedi.demo@salamaid.test', 'mobile' => '+628120000104'],
            ['name' => 'Eka Putri', 'email' => 'eka.demo@salamaid.test', 'mobile' => '+628120000105'],
        ])->map(fn (array $attributes) => User::updateOrCreate(
            ['email' => $attributes['email']],
            $attributes + [
                'password' => 'change-me-local',
                'role' => 'user',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        ));

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
                'cover_url' => '/images/demo/beasiswa-anak-negeri.png',
            ],
        );

        $humanity = Category::updateOrCreate(
            ['slug' => 'kemanusiaan'],
            ['name' => 'Kemanusiaan', 'icon' => 'heart', 'is_active' => true],
        );

        $environment = Category::updateOrCreate(
            ['slug' => 'lingkungan'],
            ['name' => 'Lingkungan', 'icon' => 'leaf', 'is_active' => true],
        );

        $foodCampaign = Campaign::updateOrCreate(
            ['slug' => 'paket-pangan-keluarga'],
            [
                'organization_id' => $organization->id,
                'category_id' => $humanity->id,
                'title' => 'Paket Pangan Keluarga',
                'description' => 'Bantuan pangan bulanan untuk keluarga prasejahtera di wilayah binaan.',
                'target_amount' => 75000000,
                'collected_amount' => 18500000,
                'deadline_at' => Carbon::now()->addMonths(3),
                'location_label' => 'Jawa Barat',
                'status' => 'published',
                'is_featured' => true,
                'cover_url' => '/images/demo/paket-pangan-keluarga.png',
            ],
        );

        $environmentCampaign = Campaign::updateOrCreate(
            ['slug' => 'pesisir-hijau'],
            [
                'organization_id' => $organization->id,
                'category_id' => $environment->id,
                'title' => 'Pesisir Hijau',
                'description' => 'Pemulihan pesisir melalui penanaman mangrove bersama warga dan relawan.',
                'target_amount' => 50000000,
                'collected_amount' => 9200000,
                'deadline_at' => Carbon::now()->addMonths(4),
                'location_label' => 'Demak, Jawa Tengah',
                'status' => 'published',
                'is_featured' => false,
                'cover_url' => '/images/demo/pesisir-hijau.png',
            ],
        );

        $partnerOrganization = Organization::updateOrCreate(
            ['slug' => 'komunitas-peduli-umat'],
            [
                'name' => 'Komunitas Peduli Umat',
                'description' => 'Lembaga demo kedua untuk menguji pemisahan data campaign berbasis organisasi.',
                'tagline' => 'Berbagi kepedulian, menguatkan umat.',
                'logo_url' => '/images/demo/komunitas-peduli-umat-logo.png',
                'primary_color' => '#0F766E',
                'secondary_color' => '#E6FFFB',
                'contact_email' => 'halo@peduliumat.test',
                'contact_phone' => '+62 812 2222 3333',
                'status' => 'verified',
                'verified_at' => now(),
            ],
        );
        $partnerAdmin = User::updateOrCreate(
            ['email' => 'admin@peduliumat.test'],
            [
                'name' => 'Admin Komunitas Peduli Umat',
                'password' => 'change-me-local',
                'role' => 'organization_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        OrganizationMember::updateOrCreate(
            ['organization_id' => $partnerOrganization->id, 'user_id' => $partnerAdmin->id],
            ['role' => 'owner'],
        );

        $partnerFoodCampaign = Campaign::updateOrCreate(
            ['slug' => 'paket-sembako-ramadan'],
            [
                'organization_id' => $partnerOrganization->id,
                'category_id' => $humanity->id,
                'title' => 'Paket Sembako Ramadan',
                'description' => 'Program demo Komunitas Peduli Umat untuk menyediakan paket sembako bagi keluarga penerima manfaat.',
                'target_amount' => 45000000,
                'collected_amount' => 12500000,
                'deadline_at' => Carbon::now()->addMonths(2),
                'location_label' => 'Bekasi, Jawa Barat',
                'status' => 'published',
                'is_featured' => true,
                'cover_url' => '/images/demo/paket-pangan-keluarga.png',
            ],
        );
        Campaign::updateOrCreate(
            ['slug' => 'kelas-digital-pemuda'],
            [
                'organization_id' => $partnerOrganization->id,
                'category_id' => $category->id,
                'title' => 'Kelas Digital Pemuda',
                'description' => 'Campaign demo untuk pelatihan keterampilan digital pemuda di komunitas lokal.',
                'target_amount' => 30000000,
                'collected_amount' => 0,
                'deadline_at' => Carbon::now()->addMonths(4),
                'location_label' => 'Depok, Jawa Barat',
                'status' => 'draft',
                'is_featured' => false,
                'cover_url' => '/images/demo/beasiswa-anak-negeri.png',
            ],
        );

        foreach ([
            [$campaign, 'beasiswa-anak-negeri.png', 'Ruang belajar untuk masa depan'],
            [$foodCampaign, 'paket-pangan-keluarga.png', 'Pangan untuk keluarga'],
            [$environmentCampaign, 'pesisir-hijau.png', 'Relawan menanam mangrove'],
        ] as [$demoCampaign, $filename, $caption]) {
            CampaignMedia::updateOrCreate(
                ['campaign_id' => $demoCampaign->id, 'url' => '/images/demo/'.$filename],
                ['type' => 'image', 'caption' => $caption, 'sort' => 0],
            );
        }

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

        $upsertDemoPayment = static function (Donation $donation, string $orderId, array $attributes): Payment {
            $payment = Payment::query()->where('donation_id', $donation->id)->first();
            if (! $payment && Payment::query()->where('order_id', $orderId)->where('donation_id', '!=', $donation->id)->exists()) {
                $orderId .= '-'.substr((string) $donation->id, 0, 8);
            }
            $payment ??= new Payment;
            $payment->forceFill($attributes + ['order_id' => $orderId, 'donation_id' => $donation->id])->save();
            return $payment;
        };

        $upsertDemoPayment($donation, 'SALAMAID-DEMO-0001',
            [
                'provider' => 'demo',
                'channel_code' => 'QRIS',
                'status' => 'paid',
                'paid_at' => now(),
                'signature' => 'demo-signature',
                'raw_request_json' => ['source' => 'DevelopmentSeeder'],
                'raw_callback_json' => ['status' => 'paid'],
            ]);

        // Mayar Sandbox reconciliation fixture. The order_id is the provider
        // transaction ID visible in the Mayar dashboard; the local payment
        // maps settled → paid while preserving the provider status in metadata.
        $mayarDonation = Donation::updateOrCreate(
            ['campaign_id' => $campaign->id, 'user_id' => $donor->id, 'message' => 'Fixture rekonsiliasi Mayar Sandbox'],
            [
                'gross_amount' => 10000,
                'fee_platform' => 0,
                'fee_channel' => 0,
                'net_amount' => 10000,
                'method' => 'transfer_va_bni',
                'anonymous' => false,
                'status' => 'paid',
                'paid_at' => now(),
            ],
        );

        $upsertDemoPayment($mayarDonation, 'f48688e4-1aa0-427e-9fa0-675a69289939',
            [
                'provider' => 'mayar',
                'channel_code' => 'transfer_va_bni',
                'status' => 'paid',
                'paid_at' => now(),
                'raw_request_json' => ['source' => 'DevelopmentSeeder', 'environment' => 'sandbox'],
                'raw_callback_json' => [
                    'source' => 'Mayar Sandbox fixture',
                    'provider_status' => 'settled',
                    'amount' => 10000,
                    'payment_method' => 'Transfer VA - BNI',
                ],
            ]);

        $mayarPendingDonation = Donation::updateOrCreate(
            ['campaign_id' => $foodCampaign->id, 'user_id' => $donor->id, 'message' => 'Fixture Mayar pending'],
            [
                'gross_amount' => 75000,
                'fee_platform' => 0,
                'fee_channel' => 0,
                'net_amount' => 75000,
                'method' => 'mayar_checkout',
                'anonymous' => false,
                'status' => 'pending',
                'expired_at' => now()->addHours(8),
            ],
        );

        $upsertDemoPayment($mayarPendingDonation, 'MAYAR-DEMO-PENDING-0001',
            [
                'provider' => 'mayar',
                'channel_code' => 'mayar_checkout',
                'status' => 'pending',
                'redirect_url' => 'https://web.mayar.io/payment/MAYAR-DEMO-PENDING-0001',
                'raw_request_json' => ['source' => 'DevelopmentSeeder', 'environment' => 'sandbox', 'name' => 'Demo Donor', 'email' => 'donor@salamaid.test', 'mobile' => '+628120000000'],
                'raw_callback_json' => ['source' => 'Mayar Sandbox fixture', 'provider_status' => 'created', 'amount' => 75000],
            ]);

        $mayarFailedDonation = Donation::updateOrCreate(
            ['campaign_id' => $environmentCampaign->id, 'user_id' => $donor->id, 'message' => 'Fixture Mayar failed'],
            [
                'gross_amount' => 125000,
                'fee_platform' => 0,
                'fee_channel' => 0,
                'net_amount' => 125000,
                'method' => 'mayar_checkout',
                'anonymous' => true,
                'status' => 'failed',
            ],
        );

        $upsertDemoPayment($mayarFailedDonation, 'MAYAR-DEMO-FAILED-0001',
            [
                'provider' => 'mayar',
                'channel_code' => 'mayar_checkout',
                'status' => 'failed',
                'raw_request_json' => ['source' => 'DevelopmentSeeder', 'environment' => 'sandbox'],
                'raw_callback_json' => ['source' => 'Mayar Sandbox fixture', 'provider_status' => 'expired', 'amount' => 125000],
            ]);

        // Paid donor fixtures keep the displayed collected amount consistent
        // with the donor table. Existing non-fixture donations are preserved,
        // while the five deterministic fixtures fill the remaining amount.
        $seedPaidDonations = static function (Campaign $demoCampaign, $donors): void {
            $fixturePrefix = 'Seed dummy donor '.$demoCampaign->slug.' %';
            $existingPaid = (int) Donation::query()
                ->where('campaign_id', $demoCampaign->id)
                ->where('status', 'paid')
                ->where(fn ($query) => $query->whereNull('message')->orWhere('message', 'not like', $fixturePrefix))
                ->sum('net_amount');
            $remaining = max(0, (int) $demoCampaign->collected_amount - $existingPaid);
            $shares = [20, 18, 22, 15, 25];
            $allocated = 0;

            foreach ($donors->values() as $index => $demoDonor) {
                $amount = $index === 4
                    ? max(0, $remaining - $allocated)
                    : intdiv($remaining * $shares[$index], 100);
                $allocated += $amount;

                Donation::updateOrCreate(
                    [
                        'campaign_id' => $demoCampaign->id,
                        'user_id' => $demoDonor->id,
                        'message' => 'Seed dummy donor '.$demoCampaign->slug.' '.($index + 1),
                    ],
                    [
                        'gross_amount' => $amount,
                        'fee_platform' => 0,
                        'fee_channel' => 0,
                        'net_amount' => $amount,
                        'method' => 'qris',
                        'anonymous' => false,
                        'status' => 'paid',
                        'paid_at' => now(),
                    ],
                );
            }
        };

        foreach ([$campaign, $foodCampaign, $environmentCampaign, $partnerFoodCampaign] as $demoCampaign) {
            $seedPaidDonations($demoCampaign, $demoDonors);
        }

        Webhook::updateOrCreate(
            ['provider' => 'demo', 'order_id' => 'SALAMAID-DEMO-0001', 'event' => 'payment.paid'],
            [
                'signature' => 'demo-webhook-signature',
                'payload' => ['order_id' => 'SALAMAID-DEMO-0001', 'status' => 'paid'],
                'processed_at' => now(),
            ],
        );

        Webhook::updateOrCreate(
            ['provider' => 'mayar', 'order_id' => 'MAYAR-DEMO-PENDING-0001', 'event' => 'payment.created'],
            [
                'signature' => 'demo-mayar-signature-pending',
                'payload' => ['order_id' => 'MAYAR-DEMO-PENDING-0001', 'status' => 'created', 'environment' => 'sandbox'],
                'processed_at' => now(),
            ],
        );

        Webhook::updateOrCreate(
            ['provider' => 'mayar', 'order_id' => 'MAYAR-DEMO-FAILED-0001', 'event' => 'payment.expired'],
            [
                'signature' => 'demo-mayar-signature-failed',
                'payload' => ['order_id' => 'MAYAR-DEMO-FAILED-0001', 'status' => 'expired', 'environment' => 'sandbox'],
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

        foreach ([
            [$foodCampaign, 'Distribusi pangan perdana', 'Sebanyak 120 paket pangan telah disalurkan kepada keluarga penerima manfaat.', 'update', null],
            [$foodCampaign, 'Laporan alokasi paket pangan', 'Laporan penggunaan dana untuk pembelian dan distribusi paket pangan.', 'fund_report', 6500000],
            [$environmentCampaign, 'Penanaman mangrove dimulai', 'Relawan dan warga menanam bibit mangrove di area pesisir prioritas.', 'update', null],
        ] as [$demoCampaign, $title, $content, $type, $amount]) {
            $demoUpdate = CampaignUpdate::updateOrCreate(
                ['campaign_id' => $demoCampaign->id, 'title' => $title],
                ['type' => $type, 'content' => $content, 'amount_used' => $amount],
            );
            $demoUpdate->forceFill(['status' => 'published', 'published_at' => $demoUpdate->published_at ?? now()])->save();
        }

        foreach ([
            ['00000000-0000-0000-0000-000000000101', 'Pembayaran berhasil', 'Pembayaran donasi untuk '.$campaign->title.' telah berhasil diproses.', 'payment_success', 'paid', ['campaign_slug' => $campaign->slug, 'donation_id' => $donation->id]],
            ['00000000-0000-0000-0000-000000000102', 'Transaksi kedaluwarsa', 'Batas waktu pembayaran donasi telah berakhir. Anda dapat mengulangi donasi kapan saja.', 'payment_expired', 'unread', ['campaign_slug' => $foodCampaign->slug, 'donation_id' => $mayarPendingDonation->id]],
            ['00000000-0000-0000-0000-000000000103', 'Penyaluran terbaru', 'Penyaluran dana tahap 3 untuk '.$environmentCampaign->title.' sudah dimulai.', 'distribution', 'unread', ['campaign_slug' => $environmentCampaign->slug]],
            ['00000000-0000-0000-0000-000000000104', 'Dampak terlaksana', 'Sebanyak 120 paket pangan telah disalurkan kepada keluarga penerima manfaat.', 'impact', 'read', ['campaign_slug' => $foodCampaign->slug]],
        ] as [$id, $title, $message, $kind, $state, $metadata]) {
            DB::table('notifications')->updateOrInsert(
                ['id' => $id],
                [
                    'type' => 'development_demo',
                    'notifiable_type' => User::class,
                    'notifiable_id' => $donor->id,
                    'data' => json_encode(array_merge(['title' => $title, 'message' => $message, 'kind' => $kind], $metadata)),
                    'read_at' => $state === 'read' ? now() : null,
                    'created_at' => now()->subMinutes(match ($kind) { 'payment_success' => 10, 'payment_expired' => 60, 'distribution' => 120, default => 180 }),
                    'updated_at' => now(),
                ],
            );
        }

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

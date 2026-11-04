<?php

namespace Tests\Feature\Admin;

use App\Contracts\AdminAuditLogger;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Notifications\AdminActionRequired;
use App\Support\CsvSanitizer;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Donation\Entities\Donation;
use Modules\Payment\Entities\Payment;
use Modules\Core\Entities\OrganizationMember;
use Modules\Transparency\Entities\CampaignUpdate;

/**
 * A12 hardening: route protection invariants, CSRF, role matrix, XSS,
 * secret leakage, CSV injection and error disclosure across the admin panel.
 */
class AdminSecurityHardeningTest extends AdminReviewTestCase
{
    private const XSS = '<script>alert("xss")</script><img src=x onerror=alert(1)>';

    /** Routes intentionally reachable without the admin guard. */
    private const PUBLIC_ADMIN_ROUTES = ['admin.login', 'admin.login.store', 'admin.logout'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** @return array<int, RoutingRoute> */
    private function adminRoutes(): array
    {
        return array_values(array_filter(
            Route::getRoutes()->getRoutes(),
            fn (RoutingRoute $route) => $route->uri() === 'admin' || str_starts_with($route->uri(), 'admin/'),
        ));
    }

    /** @return array<int, string> */
    private function permissionsOf(RoutingRoute $route): array
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'admin.permission:')) {
                return explode(',', Str::after($middleware, 'admin.permission:'));
            }
        }

        return [];
    }

    private function url(RoutingRoute $route): string
    {
        return '/'.preg_replace('/\{[^}]+\}/', (string) Str::uuid(), $route->uri());
    }

    // ---- Route protection ------------------------------------------------

    public function test_every_admin_route_is_behind_web_auth_admin_and_permission_middleware(): void
    {
        $routes = $this->adminRoutes();
        $this->assertGreaterThan(50, count($routes), 'Admin routes should be registered.');

        foreach ($routes as $route) {
            $name = (string) $route->getName();
            $middleware = $route->gatherMiddleware();
            $label = implode('|', $route->methods()).' '.$route->uri()." ({$name})";

            $this->assertContains('web', $middleware, "{$label} must use the web group (session + CSRF).");

            if (in_array($name, self::PUBLIC_ADMIN_ROUTES, true)) {
                continue;
            }

            $this->assertTrue(
                in_array('auth', $middleware, true) || in_array('admin.review.guest', $middleware, true),
                "{$label} must require authentication.",
            );
            $this->assertContains('admin', $middleware, "{$label} must require an active admin role.");
            $this->assertNotSame([], $this->permissionsOf($route), "{$label} must declare admin.permission.");

            foreach ($this->permissionsOf($route) as $permission) {
                $this->assertMatchesRegularExpression('/^[a-z_]+\.[a-z_.]+$/', $permission, "{$label} has a malformed permission.");
            }
        }
    }

    public function test_csrf_is_enforced_on_every_state_changing_admin_route(): void
    {
        $this->assertSame([], (fn () => $this->except)->call(app(VerifyCsrfToken::class)), 'No CSRF exceptions are allowed.');

        // Laravel skips CSRF while running tests; use the real check here.
        $this->app->instance(VerifyCsrfToken::class, new class($this->app, $this->app->make(Encrypter::class)) extends VerifyCsrfToken {
            protected function runningUnitTests()
            {
                return false;
            }
        });

        $superAdmin = $this->user('super_admin');
        $checked = 0;

        foreach ($this->adminRoutes() as $route) {
            foreach (array_diff($route->methods(), ['GET', 'HEAD', 'OPTIONS']) as $method) {
                $this->actingAs($superAdmin)
                    ->call($method, $this->url($route), ['role' => 'super_admin', 'status' => 'paid'])
                    ->assertStatus(419);
                $checked++;
            }
        }

        $this->assertGreaterThan(40, $checked);
    }

    public function test_route_permission_matrix_matches_config_for_every_role(): void
    {
        $routes = array_filter(
            $this->adminRoutes(),
            fn (RoutingRoute $route) => in_array('GET', $route->methods(), true)
                && ! str_contains($route->uri(), '{')
                && ! in_array($route->getName(), self::PUBLIC_ADMIN_ROUTES, true),
        );

        foreach ([...array_keys(config('admin.roles')), 'user'] as $role) {
            $user = $this->user($role);
            if ($role === 'organization_admin') {
                $organization = $this->organization('verified');
                OrganizationMember::create(['organization_id' => $organization->id, 'user_id' => $user->id, 'role' => 'owner']);
            }

            foreach ($routes as $route) {
                $allowed = $role !== 'user' && $user->hasAnyAdminPermission(...$this->permissionsOf($route));
                $status = $this->actingAs($user)->get('/'.$route->uri())->baseResponse->getStatusCode();

                if ($allowed) {
                    $this->assertNotContains($status, [403, 500], "{$role} should reach /{$route->uri()} (got {$status}).");
                } else {
                    $this->assertSame(403, $status, "{$role} must be denied /{$route->uri()} (got {$status}).");
                }
            }
        }
    }

    // ---- Privilege escalation -------------------------------------------

    public function test_cross_role_finance_and_role_actions_are_denied(): void
    {
        $organization = $this->organization('verified');
        $campaign = $this->campaign($organization, 'under_review');
        $donation = Donation::create(['campaign_id' => $campaign->id, 'gross_amount' => 50_000, 'net_amount' => 50_000, 'method' => 'qris', 'status' => 'pending']);
        $payment = Payment::create(['donation_id' => $donation->id, 'provider' => 'mayar', 'order_id' => 'tx-'.Str::uuid(), 'channel_code' => 'mayar_checkout', 'status' => 'pending']);
        $target = $this->user('support');

        $reviewer = $this->user('reviewer');
        $this->actingAs($reviewer)->post(route('admin.payments.sync', $payment))->assertForbidden();
        $this->actingAs($reviewer)->post(route('admin.payment-channels.store'), ['code' => 'x'])->assertForbidden();
        $this->actingAs($reviewer)->get(route('admin.payments.show', $payment))->assertForbidden();
        $this->actingAs($reviewer)->get(route('admin.donations.export'))->assertForbidden();

        foreach (['finance', 'admin', 'reviewer', 'support'] as $role) {
            $this->actingAs($this->user($role))
                ->patch(route('admin.users.role.update', $target), ['role' => 'super_admin'])
                ->assertForbidden();
        }
        $this->assertSame('support', $target->fresh()->role);

        $support = $this->user('support');
        $this->actingAs($support)->post(route('admin.campaigns.publish', $campaign))->assertForbidden();
        $this->actingAs($support)->post(route('admin.organizations.approve', $organization))->assertForbidden();
        $this->assertSame('under_review', $campaign->fresh()->status);
    }

    public function test_support_does_not_see_bank_account_details(): void
    {
        $organization = $this->organization('verified');

        $this->actingAs($this->user('support'))
            ->get(route('admin.organizations.show', $organization))
            ->assertOk()
            ->assertDontSee('1234567890');
    }

    public function test_notifications_are_scoped_to_their_owner(): void
    {
        $owner = $this->user('reviewer');
        $other = $this->user('reviewer');
        $owner->notify(new AdminActionRequired(['title' => 'Milik owner', 'message' => 'm', 'url' => '/admin']));
        $notification = $owner->notifications()->sole();

        $this->actingAs($other)->post(route('admin.notifications.read', $notification->id))->assertNotFound();
        $this->actingAs($other)->get(route('admin.notifications.index'))->assertDontSee('Milik owner');
        $this->assertNull($notification->fresh()->read_at);
    }

    // ---- XSS -------------------------------------------------------------

    public function test_user_controlled_content_is_escaped_on_admin_pages(): void
    {
        $superAdmin = $this->user('super_admin');
        $organization = $this->organization('verified', ['name' => 'Org '.self::XSS, 'description' => self::XSS]);
        $campaign = $this->campaign($organization, 'rejected', ['title' => 'Camp '.self::XSS, 'description' => self::XSS]);
        $campaign->forceFill(['rejection_reason' => 'Alasan '.self::XSS])->save();

        $update = new CampaignUpdate(['campaign_id' => $campaign->id, 'type' => 'update', 'title' => 'Upd '.self::XSS, 'content' => self::XSS]);
        $update->forceFill(['status' => 'rejected', 'review_note' => self::XSS])->save();

        app(AdminAuditLogger::class)->log('campaign.reject', $campaign, [], [], 'Audit '.self::XSS, $superAdmin, ['module' => 'Catalog']);
        $superAdmin->notify(new AdminActionRequired(['title' => 'Notif '.self::XSS, 'message' => self::XSS, 'url' => 'javascript:alert(1)']));

        $pages = [
            route('admin.organizations.index'), route('admin.organizations.show', $organization),
            route('admin.campaigns.index'), route('admin.campaigns.show', $campaign),
            route('admin.transparency.index'), route('admin.transparency.show', $update),
            route('admin.audit-logs.index'), route('admin.notifications.index'),
        ];

        foreach ($pages as $page) {
            $html = $this->actingAs($superAdmin)->get($page)->assertOk()->getContent();
            $this->assertStringNotContainsString('<script>alert("xss")</script>', $html, $page);
            $this->assertStringNotContainsString('<img src=x onerror', $html, $page);
            $this->assertStringNotContainsString('href="javascript:', $html, $page);
        }

        $this->actingAs($superAdmin)->get(route('admin.campaigns.show', $campaign))->assertSee('&lt;script&gt;', false);
    }

    // ---- Secrets ---------------------------------------------------------

    public function test_secrets_are_not_exposed_in_responses_or_exports(): void
    {
        $superAdmin = $this->user('super_admin');
        $hash = $superAdmin->getAuthPassword();
        $campaign = $this->campaign($this->organization('verified'), 'published');
        $donation = Donation::create(['campaign_id' => $campaign->id, 'gross_amount' => 75_000, 'net_amount' => 75_000, 'method' => 'qris', 'status' => 'paid']);
        $payment = Payment::create([
            'donation_id' => $donation->id, 'provider' => 'mayar', 'order_id' => 'tx-'.Str::uuid(), 'channel_code' => 'mayar_checkout',
            'status' => 'paid', 'signature' => 'SIGNATURE-SECRET-VALUE-123',
            'raw_callback_json' => ['token' => 'RAW-PAYLOAD-TOKEN'], 'raw_request_json' => ['email' => 'donor-private@example.test'],
        ]);

        $pages = [route('admin.users.index'), route('admin.users.show', $superAdmin), route('admin.payments.show', $payment)];
        foreach ($pages as $page) {
            $html = $this->actingAs($superAdmin)->get($page)->assertOk()->getContent();
            $this->assertStringNotContainsString($hash, $html, $page);
            $this->assertStringNotContainsString('SIGNATURE-SECRET-VALUE-123', $html, $page);
            $this->assertStringNotContainsString('RAW-PAYLOAD-TOKEN', $html, $page);
        }

        foreach ([route('admin.payments.export'), route('admin.donations.export'), route('admin.reports.export')] as $export) {
            $csv = $this->actingAs($superAdmin)->get($export)->assertOk()->streamedContent();
            $this->assertStringNotContainsString('SIGNATURE-SECRET-VALUE-123', $csv, $export);
            $this->assertStringNotContainsString('RAW-PAYLOAD-TOKEN', $csv, $export);
            $this->assertStringNotContainsString('donor-private@example.test', $csv, $export);
            $this->assertStringNotContainsString($hash, $csv, $export);
        }

        $audit = \App\Models\AdminAuditLog::query()->get()->toJson();
        $this->assertStringNotContainsString($hash, $audit);
    }

    // ---- CSV injection ---------------------------------------------------

    public function test_csv_exports_neutralise_formula_injection(): void
    {
        $this->assertSame("'=HYPERLINK(\"http://evil\")", CsvSanitizer::cell('=HYPERLINK("http://evil")'));
        $this->assertSame("'+1+cmd", CsvSanitizer::cell('+1+cmd'));
        $this->assertSame("'@SUM(A1)", CsvSanitizer::cell('@SUM(A1)'));
        $this->assertSame("'\tx", CsvSanitizer::cell("\tx"));
        $this->assertSame('-5000', CsvSanitizer::cell('-5000'), 'Numeric strings are not altered.');
        $this->assertSame(-5000, CsvSanitizer::cell(-5000));
        $this->assertSame('Beasiswa', CsvSanitizer::cell('Beasiswa'));

        $superAdmin = $this->user('super_admin');
        $donor = User::factory()->create(['name' => '=cmd|"/C calc"!A0']);
        $campaign = $this->campaign($this->organization('verified'), 'published', ['title' => '=HYPERLINK("http://evil","klik")']);
        Donation::create(['campaign_id' => $campaign->id, 'user_id' => $donor->id, 'gross_amount' => 10_000, 'net_amount' => 10_000, 'method' => 'qris', 'status' => 'paid']);

        $csv = $this->actingAs($superAdmin)->get(route('admin.donations.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString("\"'=HYPERLINK(\"\"http://evil\"\",\"\"klik\"\")\"", $csv);
        $this->assertStringContainsString("'=cmd|", $csv);
        $this->assertDoesNotMatchRegularExpression('/(^|,)"?=HYPERLINK/m', $csv);
    }

    // ---- Error disclosure -----------------------------------------------

    public function test_errors_do_not_leak_internals_when_debug_is_off(): void
    {
        config(['app.debug' => false]);
        Route::middleware('web')->get('/__a12-boom', fn () => throw new \RuntimeException('INTERNAL-SECRET-DETAIL'));

        $this->get('/__a12-boom')->assertStatus(500)->assertDontSee('INTERNAL-SECRET-DETAIL')->assertDontSee('RuntimeException');
        $this->get('/admin/definitely-missing-page')->assertNotFound()->assertDontSee('NotFoundHttpException');

        $this->actingAs($this->user('support'))->get(route('admin.users.index'))->assertForbidden()->assertDontSee('AuthorizationException');
    }
}

<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \Modules\Catalog\Entities\Campaign::class => \Modules\Catalog\App\Policies\CampaignPolicy::class,
        \Modules\Core\Entities\Organization::class => \Modules\Core\App\Policies\OrganizationPolicy::class,
        \Modules\Core\Entities\Category::class => \Modules\Core\App\Policies\CategoryPolicy::class,
        \Modules\Donation\Entities\Donation::class => \Modules\Donation\Policies\DonationPolicy::class,
        \Modules\Payment\Entities\Payment::class => \Modules\Payment\Policies\PaymentPolicy::class,
        \Modules\Operations\Entities\Payout::class => \Modules\Operations\Policies\PayoutPolicy::class,
        \Modules\Transparency\Entities\CampaignUpdate::class => \Modules\Transparency\Policies\CampaignUpdatePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}

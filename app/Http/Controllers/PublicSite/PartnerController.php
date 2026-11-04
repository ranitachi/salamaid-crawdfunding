<?php

namespace App\Http\Controllers\PublicSite;

use Illuminate\View\View;

class PartnerController
{
    public function index(): View
    {
        return view('public.partners.index');
    }

    public function register(): View
    {
        return view('public.partners.register');
    }

    public function status(): View
    {
        return view('public.partners.status');
    }
}

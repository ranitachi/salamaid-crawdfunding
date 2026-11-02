@props(['title' => 'Dashboard'])

<header class="admin-topbar">
    <div class="admin-topbar__left">
        <button class="admin-icon-button admin-menu-toggle" type="button" data-admin-menu-toggle aria-label="Buka menu">☰</button>
        <div>
            <div class="admin-topbar__eyebrow">{{ $brand['app_name'] ?? 'Salamaid' }} / Admin</div>
            <h2 class="admin-topbar__title">{{ $title }}</h2>
        </div>
    </div>

    <div class="admin-topbar__actions">
        <button class="admin-icon-button" type="button" title="Notifikasi" aria-label="Notifikasi">♢</button>
        @auth
            <div class="admin-user">
                <span class="admin-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 2)) }}</span>
                <span class="admin-user__details">
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>{{ config('admin.roles.'.auth()->user()->role, auth()->user()->role) }}</small>
                </span>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="admin-button admin-button--quiet" type="submit">Keluar</button>
            </form>
        @endauth
    </div>
</header>

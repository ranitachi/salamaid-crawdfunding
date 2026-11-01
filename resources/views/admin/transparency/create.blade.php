@extends('admin.layouts.app', ['title' => 'Catat Update'])

@section('content')
    <x-admin.breadcrumb :items="[['label' => 'Transparansi', 'url' => route('admin.transparency.index')], ['label' => 'Catat update']]" />
    <x-admin.page-header title="Catat update / laporan" description="Update disimpan sebagai draft dan tidak tampil publik sebelum direview dan dipublikasikan.">
        <a class="admin-button admin-button--quiet" href="{{ route('admin.transparency.index') }}">← Kembali</a>
    </x-admin.page-header>

    @if (session('error'))<x-admin.toast :message="session('error')" type="error" />@endif

    <section class="admin-panel">
        <form class="admin-panel__body" method="POST" action="{{ route('admin.transparency.store') }}" style="display:grid; gap:14px; max-width:720px;">
            @csrf

            @if ($errors->any())
                <div class="admin-alert" role="alert"><span class="admin-alert__icon">!</span>
                    <div><strong>Periksa kembali isian</strong>@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                </div>
            @endif

            <div class="admin-field">
                <label for="campaign_id">Campaign</label>
                <select id="campaign_id" name="campaign_id" required>
                    <option value="">Pilih campaign</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected(old('campaign_id', $selectedCampaign) === $campaign->id)>{{ $campaign->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="admin-field">
                <label for="type">Jenis</label>
                <select id="type" name="type" required>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', 'update') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="admin-field">
                <label for="title">Judul</label>
                <input id="title" name="title" value="{{ old('title') }}" required minlength="5" maxlength="200">
            </div>

            <div class="admin-field">
                <label for="content">Isi</label>
                <textarea id="content" name="content" required minlength="20" maxlength="20000" style="min-height:160px; padding:8px 10px; border:1px solid var(--admin-ink); font:inherit; font-size:12px;">{{ old('content') }}</textarea>
            </div>

            <fieldset style="display:grid; gap:14px; border:1px dashed var(--admin-line); padding:12px;">
                <legend style="font-size:11px;">Khusus laporan penggunaan dana</legend>
                <div class="admin-field">
                    <label for="amount_used">Nominal digunakan (Rp)</label>
                    <input id="amount_used" name="amount_used" type="number" min="1" value="{{ old('amount_used') }}">
                </div>
                <div class="admin-field">
                    <label for="payout_id">Payout terkait (opsional, harus sudah dibayar)</label>
                    <select id="payout_id" name="payout_id">
                        <option value="">Tanpa payout</option>
                        @foreach ($payouts as $payout)
                            <option value="{{ $payout->id }}" @selected(old('payout_id') === $payout->id)>
                                {{ $payout->organization?->name ?? '—' }} · Rp {{ number_format($payout->amount, 0, ',', '.') }} · {{ $payout->paid_at?->format('d M Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </fieldset>

            <div><button class="admin-button admin-button--primary" type="submit">Simpan draft</button></div>
        </form>
    </section>
@endsection

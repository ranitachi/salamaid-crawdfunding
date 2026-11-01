@extends('admin.layouts.app', ['title' => 'Tambah Kategori'])

@section('content')
    <x-admin.breadcrumb :items="[
        ['label' => 'Kategori', 'url' => route('admin.categories.index')],
        ['label' => 'Tambah'],
    ]" />

    <x-admin.page-header
        title="Tambah Kategori"
        description="Buat kategori baru untuk mengelompokkan campaign."
    />

    @if (session('error'))<x-admin.toast :message="session('error')" type="error" />@endif

    @include('admin.categories._form', ['category' => $category])
@endsection

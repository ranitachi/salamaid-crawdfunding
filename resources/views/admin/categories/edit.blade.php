@extends('admin.layouts.app', ['title' => 'Ubah Kategori'])

@section('content')
    <x-admin.breadcrumb :items="[
        ['label' => 'Kategori', 'url' => route('admin.categories.index')],
        ['label' => $category->name],
    ]" />

    <x-admin.page-header
        title="Ubah Kategori"
        :description="$category->name.' · '.$category->slug"
    />

    @if (session('success'))<x-admin.toast :message="session('success')" />@endif
    @if (session('error'))<x-admin.toast :message="session('error')" type="error" />@endif

    @include('admin.categories._form', ['category' => $category])
@endsection

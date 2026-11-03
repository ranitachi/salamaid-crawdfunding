@extends('admin.layouts.app', ['title' => 'Tambah Artikel Bantuan'])
@section('content')<x-admin.breadcrumb :items="[['label' => 'Pusat Bantuan', 'url' => route('admin.help.index')], ['label' => 'Tambah']]" /><x-admin.page-header title="Tambah Artikel Bantuan" description="Buat pertanyaan dan jawaban baru untuk publik." />@include('admin.help._form', ['editing' => false])@endsection

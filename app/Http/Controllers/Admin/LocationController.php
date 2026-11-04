<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Province;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.locations', [
            'provinces' => Province::query()->with('cities')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function storeProvince(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:provinces,code'],
            'name' => ['required', 'string', 'max:100', 'unique:provinces,name'],
        ], [], ['code' => 'kode provinsi', 'name' => 'nama provinsi']);

        Province::create($data + ['sort_order' => (int) Province::max('sort_order') + 1, 'is_active' => true]);

        return back()->with('success', 'Provinsi berhasil ditambahkan.');
    }

    public function storeCity(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'province_id' => ['required', 'exists:provinces,id'],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:Kabupaten,Kota'],
        ], [], ['province_id' => 'provinsi', 'name' => 'nama kabupaten/kota', 'type' => 'jenis']);

        $exists = City::query()->where('province_id', $data['province_id'])->where('name', $data['name'])->where('type', $data['type'])->exists();
        if ($exists) {
            return back()->withErrors(['name' => 'Kabupaten/kota tersebut sudah tersedia pada provinsi yang dipilih.']);
        }

        City::create($data + ['sort_order' => (int) City::where('province_id', $data['province_id'])->max('sort_order') + 1, 'is_active' => true]);

        return back()->with('success', 'Kabupaten/kota berhasil ditambahkan.');
    }

    public function toggleProvince(Province $province): RedirectResponse
    {
        $province->update(['is_active' => ! $province->is_active]);

        return back()->with('success', 'Status provinsi berhasil diperbarui.');
    }

    public function toggleCity(City $city): RedirectResponse
    {
        $city->update(['is_active' => ! $city->is_active]);

        return back()->with('success', 'Status kabupaten/kota berhasil diperbarui.');
    }
}

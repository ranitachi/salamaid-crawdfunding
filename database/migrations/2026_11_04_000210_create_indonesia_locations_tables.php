<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 100)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('province_id')->constrained('provinces')->cascadeOnDelete();
            $table->string('code', 10)->nullable();
            $table->string('name', 120);
            $table->string('type', 20)->default('Kabupaten');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['province_id', 'name', 'type']);
        });

        $provinces = [
            ['code' => '11', 'name' => 'Aceh'], ['code' => '12', 'name' => 'Sumatera Utara'],
            ['code' => '13', 'name' => 'Sumatera Barat'], ['code' => '14', 'name' => 'Riau'],
            ['code' => '15', 'name' => 'Jambi'], ['code' => '16', 'name' => 'Sumatera Selatan'],
            ['code' => '17', 'name' => 'Bengkulu'], ['code' => '18', 'name' => 'Lampung'],
            ['code' => '19', 'name' => 'Kepulauan Bangka Belitung'], ['code' => '21', 'name' => 'Kepulauan Riau'],
            ['code' => '31', 'name' => 'DKI Jakarta'], ['code' => '32', 'name' => 'Jawa Barat'],
            ['code' => '33', 'name' => 'Jawa Tengah'], ['code' => '34', 'name' => 'DI Yogyakarta'],
            ['code' => '35', 'name' => 'Jawa Timur'], ['code' => '36', 'name' => 'Banten'],
            ['code' => '51', 'name' => 'Bali'], ['code' => '52', 'name' => 'Nusa Tenggara Barat'],
            ['code' => '53', 'name' => 'Nusa Tenggara Timur'], ['code' => '61', 'name' => 'Kalimantan Barat'],
            ['code' => '62', 'name' => 'Kalimantan Tengah'], ['code' => '63', 'name' => 'Kalimantan Selatan'],
            ['code' => '64', 'name' => 'Kalimantan Timur'], ['code' => '65', 'name' => 'Kalimantan Utara'],
            ['code' => '71', 'name' => 'Sulawesi Utara'], ['code' => '72', 'name' => 'Sulawesi Tengah'],
            ['code' => '73', 'name' => 'Sulawesi Selatan'], ['code' => '74', 'name' => 'Sulawesi Tenggara'],
            ['code' => '75', 'name' => 'Gorontalo'], ['code' => '76', 'name' => 'Sulawesi Barat'],
            ['code' => '81', 'name' => 'Maluku'], ['code' => '82', 'name' => 'Maluku Utara'],
            ['code' => '91', 'name' => 'Papua'], ['code' => '92', 'name' => 'Papua Barat'],
            ['code' => '93', 'name' => 'Papua Selatan'], ['code' => '94', 'name' => 'Papua Tengah'],
            ['code' => '95', 'name' => 'Papua Pegunungan'], ['code' => '96', 'name' => 'Papua Barat Daya'],
        ];

        foreach ($provinces as $index => $province) {
            DB::table('provinces')->insert($province + ['sort_order' => $index + 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        $cities = [
            'DKI Jakarta' => [['Jakarta Barat', 'Kota'], ['Jakarta Pusat', 'Kota'], ['Jakarta Selatan', 'Kota'], ['Jakarta Timur', 'Kota'], ['Jakarta Utara', 'Kota'], ['Kepulauan Seribu', 'Kabupaten']],
            'Jawa Barat' => [['Bandung', 'Kota'], ['Bogor', 'Kota'], ['Bekasi', 'Kota'], ['Depok', 'Kota'], ['Cimahi', 'Kota'], ['Cirebon', 'Kota'], ['Sukabumi', 'Kota'], ['Tasikmalaya', 'Kota'], ['Banjar', 'Kota'], ['Bandung', 'Kabupaten'], ['Bogor', 'Kabupaten'], ['Garut', 'Kabupaten'], ['Karawang', 'Kabupaten'], ['Purwakarta', 'Kabupaten'], ['Subang', 'Kabupaten'], ['Sumedang', 'Kabupaten'], ['Cianjur', 'Kabupaten'], ['Ciamis', 'Kabupaten'], ['Indramayu', 'Kabupaten'], ['Kuningan', 'Kabupaten'], ['Majalengka', 'Kabupaten'], ['Pangandaran', 'Kabupaten'], ['Sukabumi', 'Kabupaten'], ['Tasikmalaya', 'Kabupaten']],
            'Jawa Tengah' => [['Semarang', 'Kota'], ['Surakarta', 'Kota'], ['Magelang', 'Kota'], ['Pekalongan', 'Kota'], ['Salatiga', 'Kota'], ['Tegal', 'Kota'], ['Cilacap', 'Kabupaten'], ['Banyumas', 'Kabupaten'], ['Kudus', 'Kabupaten'], ['Pati', 'Kabupaten'], ['Jepara', 'Kabupaten'], ['Klaten', 'Kabupaten'], ['Sukoharjo', 'Kabupaten'], ['Wonogiri', 'Kabupaten'], ['Wonosobo', 'Kabupaten']],
            'DI Yogyakarta' => [['Yogyakarta', 'Kota'], ['Bantul', 'Kabupaten'], ['Gunungkidul', 'Kabupaten'], ['Kulon Progo', 'Kabupaten'], ['Sleman', 'Kabupaten']],
            'Jawa Timur' => [['Surabaya', 'Kota'], ['Malang', 'Kota'], ['Kediri', 'Kota'], ['Madiun', 'Kota'], ['Mojokerto', 'Kota'], ['Pasuruan', 'Kota'], ['Probolinggo', 'Kota'], ['Batu', 'Kota'], ['Banyuwangi', 'Kabupaten'], ['Jember', 'Kabupaten'], ['Sidoarjo', 'Kabupaten'], ['Gresik', 'Kabupaten'], ['Kediri', 'Kabupaten'], ['Malang', 'Kabupaten'], ['Mojokerto', 'Kabupaten'], ['Pasuruan', 'Kabupaten']],
            'Banten' => [['Serang', 'Kota'], ['Cilegon', 'Kota'], ['Tangerang', 'Kota'], ['Tangerang Selatan', 'Kota'], ['Lebak', 'Kabupaten'], ['Pandeglang', 'Kabupaten'], ['Serang', 'Kabupaten'], ['Tangerang', 'Kabupaten']],
            'Sumatera Utara' => [['Medan', 'Kota'], ['Binjai', 'Kota'], ['Pematangsiantar', 'Kota'], ['Tebing Tinggi', 'Kota'], ['Deli Serdang', 'Kabupaten'], ['Karo', 'Kabupaten'], ['Langkat', 'Kabupaten'], ['Simalungun', 'Kabupaten']],
            'Sumatera Barat' => [['Padang', 'Kota'], ['Bukittinggi', 'Kota'], ['Padang Panjang', 'Kota'], ['Pariaman', 'Kota'], ['Payakumbuh', 'Kota'], ['Solok', 'Kota'], ['Agam', 'Kabupaten'], ['Tanah Datar', 'Kabupaten']],
            'Riau' => [['Pekanbaru', 'Kota'], ['Dumai', 'Kota'], ['Bengkalis', 'Kabupaten'], ['Kampar', 'Kabupaten'], ['Siak', 'Kabupaten']],
            'Kepulauan Riau' => [['Batam', 'Kota'], ['Tanjung Pinang', 'Kota'], ['Bintan', 'Kabupaten'], ['Karimun', 'Kabupaten'], ['Natuna', 'Kabupaten']],
            'Bali' => [['Denpasar', 'Kota'], ['Badung', 'Kabupaten'], ['Bangli', 'Kabupaten'], ['Buleleng', 'Kabupaten'], ['Gianyar', 'Kabupaten'], ['Jembrana', 'Kabupaten'], ['Karangasem', 'Kabupaten'], ['Klungkung', 'Kabupaten'], ['Tabanan', 'Kabupaten']],
            'Kalimantan Barat' => [['Pontianak', 'Kota'], ['Singkawang', 'Kota'], ['Kubu Raya', 'Kabupaten'], ['Sambas', 'Kabupaten'], ['Mempawah', 'Kabupaten']],
            'Kalimantan Timur' => [['Samarinda', 'Kota'], ['Balikpapan', 'Kota'], ['Bontang', 'Kota'], ['Kutai Kartanegara', 'Kabupaten'], ['Berau', 'Kabupaten']],
            'Sulawesi Selatan' => [['Makassar', 'Kota'], ['Parepare', 'Kota'], ['Palopo', 'Kota'], ['Gowa', 'Kabupaten'], ['Maros', 'Kabupaten'], ['Bone', 'Kabupaten'], ['Wajo', 'Kabupaten']],
        ];

        foreach ($cities as $provinceName => $items) {
            $provinceId = DB::table('provinces')->where('name', $provinceName)->value('id');
            foreach ($items as $index => [$name, $type]) {
                DB::table('cities')->insert([
                    'province_id' => $provinceId, 'name' => $name, 'type' => $type,
                    'sort_order' => $index + 1, 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
    }
};

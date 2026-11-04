<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $locations = [
            'Aceh' => [['Banda Aceh', 'Kota'], ['Aceh Besar', 'Kabupaten'], ['Pidie', 'Kabupaten'], ['Aceh Utara', 'Kabupaten']],
            'Jambi' => [['Jambi', 'Kota'], ['Batang Hari', 'Kabupaten'], ['Kerinci', 'Kabupaten'], ['Muaro Jambi', 'Kabupaten']],
            'Sumatera Selatan' => [['Palembang', 'Kota'], ['Lubuklinggau', 'Kota'], ['Pagar Alam', 'Kota'], ['Prabumulih', 'Kota'], ['Banyuasin', 'Kabupaten'], ['Empat Lawang', 'Kabupaten'], ['Lahat', 'Kabupaten'], ['Muara Enim', 'Kabupaten'], ['Musi Banyuasin', 'Kabupaten'], ['Musi Rawas', 'Kabupaten'], ['Ogan Ilir', 'Kabupaten'], ['Ogan Komering Ilir', 'Kabupaten'], ['Ogan Komering Ulu', 'Kabupaten']],
            'Bengkulu' => [['Bengkulu', 'Kota'], ['Bengkulu Selatan', 'Kabupaten'], ['Bengkulu Tengah', 'Kabupaten'], ['Rejang Lebong', 'Kabupaten']],
            'Lampung' => [['Bandar Lampung', 'Kota'], ['Metro', 'Kota'], ['Lampung Selatan', 'Kabupaten'], ['Lampung Tengah', 'Kabupaten'], ['Lampung Timur', 'Kabupaten'], ['Lampung Utara', 'Kabupaten'], ['Pesawaran', 'Kabupaten'], ['Pringsewu', 'Kabupaten']],
            'Kepulauan Bangka Belitung' => [['Pangkalpinang', 'Kota'], ['Bangka', 'Kabupaten'], ['Bangka Barat', 'Kabupaten'], ['Bangka Selatan', 'Kabupaten'], ['Belitung', 'Kabupaten']],
            'Nusa Tenggara Barat' => [['Mataram', 'Kota'], ['Bima', 'Kota'], ['Bima', 'Kabupaten'], ['Lombok Barat', 'Kabupaten'], ['Lombok Tengah', 'Kabupaten'], ['Lombok Timur', 'Kabupaten'], ['Sumbawa', 'Kabupaten']],
            'Nusa Tenggara Timur' => [['Kupang', 'Kota'], ['Alor', 'Kabupaten'], ['Ende', 'Kabupaten'], ['Flores Timur', 'Kabupaten'], ['Manggarai', 'Kabupaten'], ['Sikka', 'Kabupaten'], ['Timor Tengah Selatan', 'Kabupaten']],
            'Kalimantan Tengah' => [['Palangka Raya', 'Kota'], ['Barito Selatan', 'Kabupaten'], ['Kapuas', 'Kabupaten'], ['Kotawaringin Barat', 'Kabupaten'], ['Kotawaringin Timur', 'Kabupaten'], ['Katingan', 'Kabupaten']],
            'Kalimantan Selatan' => [['Banjarmasin', 'Kota'], ['Banjarbaru', 'Kota'], ['Banjar', 'Kabupaten'], ['Barito Kuala', 'Kabupaten'], ['Hulu Sungai Selatan', 'Kabupaten'], ['Tanah Laut', 'Kabupaten']],
            'Kalimantan Utara' => [['Tarakan', 'Kota'], ['Bulungan', 'Kabupaten'], ['Malinau', 'Kabupaten'], ['Nunukan', 'Kabupaten'], ['Tana Tidung', 'Kabupaten']],
            'Sulawesi Utara' => [['Manado', 'Kota'], ['Bitung', 'Kota'], ['Tomohon', 'Kota'], ['Bolaang Mongondow', 'Kabupaten'], ['Minahasa', 'Kabupaten'], ['Sangihe', 'Kabupaten']],
            'Sulawesi Tengah' => [['Palu', 'Kota'], ['Banggai', 'Kabupaten'], ['Donggala', 'Kabupaten'], ['Morowali', 'Kabupaten'], ['Poso', 'Kabupaten'], ['Sigi', 'Kabupaten']],
            'Sulawesi Tenggara' => [['Kendari', 'Kota'], ['Baubau', 'Kota'], ['Bombana', 'Kabupaten'], ['Buton', 'Kabupaten'], ['Kolaka', 'Kabupaten'], ['Konawe', 'Kabupaten']],
            'Gorontalo' => [['Gorontalo', 'Kota'], ['Boalemo', 'Kabupaten'], ['Bone Bolango', 'Kabupaten'], ['Gorontalo', 'Kabupaten'], ['Pohuwato', 'Kabupaten']],
            'Sulawesi Barat' => [['Mamuju', 'Kabupaten'], ['Majene', 'Kabupaten'], ['Mamasa', 'Kabupaten'], ['Pasangkayu', 'Kabupaten'], ['Polewali Mandar', 'Kabupaten']],
            'Maluku' => [['Ambon', 'Kota'], ['Buru', 'Kabupaten'], ['Maluku Tengah', 'Kabupaten'], ['Maluku Tenggara', 'Kabupaten'], ['Seram Bagian Barat', 'Kabupaten']],
            'Maluku Utara' => [['Ternate', 'Kota'], ['Tidore Kepulauan', 'Kota'], ['Halmahera Barat', 'Kabupaten'], ['Halmahera Selatan', 'Kabupaten'], ['Halmahera Utara', 'Kabupaten']],
            'Papua' => [['Jayapura', 'Kota'], ['Biak Numfor', 'Kabupaten'], ['Jayapura', 'Kabupaten'], ['Keerom', 'Kabupaten'], ['Mimika', 'Kabupaten']],
            'Papua Barat' => [['Manokwari', 'Kabupaten'], ['Fakfak', 'Kabupaten'], ['Kaimana', 'Kabupaten'], ['Sorong', 'Kabupaten'], ['Teluk Bintuni', 'Kabupaten']],
            'Papua Selatan' => [['Merauke', 'Kabupaten'], ['Asmat', 'Kabupaten'], ['Boven Digoel', 'Kabupaten'], ['Mappi', 'Kabupaten']],
            'Papua Tengah' => [['Nabire', 'Kabupaten'], ['Mimika', 'Kabupaten'], ['Paniai', 'Kabupaten'], ['Puncak', 'Kabupaten'], ['Timika', 'Kota']],
            'Papua Pegunungan' => [['Jayawijaya', 'Kabupaten'], ['Lanny Jaya', 'Kabupaten'], ['Pegunungan Bintang', 'Kabupaten'], ['Tolikara', 'Kabupaten']],
            'Papua Barat Daya' => [['Sorong', 'Kota'], ['Maybrat', 'Kabupaten'], ['Raja Ampat', 'Kabupaten'], ['Sorong Selatan', 'Kabupaten'], ['Tambrauw', 'Kabupaten']],
        ];

        foreach ($locations as $provinceName => $items) {
            $provinceId = DB::table('provinces')->where('name', $provinceName)->value('id');
            if (! $provinceId) {
                continue;
            }

            foreach ($items as $index => [$name, $type]) {
                DB::table('cities')->updateOrInsert(
                    ['province_id' => $provinceId, 'name' => $name, 'type' => $type],
                    ['sort_order' => $index + 1, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()],
                );
            }
        }
    }

    public function down(): void
    {
        // Location rows are master data and may have been edited after deploy.
        // They are intentionally retained on rollback.
    }
};

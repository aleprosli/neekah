<?php

/*
|--------------------------------------------------------------------------
| Negeri
|--------------------------------------------------------------------------
|
| The states and federal territories a vendor may sit in or cover, and a
| couple may hold their majlis in. The slug is what names the flag artwork in
| public/img/flag, so it is written here rather than derived: renaming a negeri
| must not silently break its flag.
|
| `districts` is what a vendor picks as their home area once the negeri is
| chosen: every official daerah (jajahan in Kelantan), taken from the
| district tables on Wikipedia's "List of districts in Malaysia" in September
| 2026, Malay spellings from "Senarai daerah di Malaysia". Sabah and Sarawak
| list their daerah, not their bahagian. Kuala Lumpur, Putrajaya, Labuan and
| Perlis have no daerah, so they list their main areas and call them kawasan.
| `area` names what the list is called.
|
| Read this through App\Support\States, never with config() directly.
|
*/

return [

    [
        'name' => 'Kedah', 'slug' => 'kedah', 'area' => 'daerah',
        'districts' => ['Baling', 'Bandar Baharu', 'Kota Setar', 'Kuala Muda', 'Kubang Pasu', 'Kulim', 'Langkawi', 'Padang Terap', 'Pendang', 'Pokok Sena', 'Sik', 'Yan'],
    ],
    [
        'name' => 'Pulau Pinang', 'slug' => 'pulau-pinang', 'area' => 'daerah',
        'districts' => ['Barat Daya', 'Seberang Perai Selatan', 'Seberang Perai Tengah', 'Seberang Perai Utara', 'Timur Laut'],
    ],
    [
        'name' => 'Perak', 'slug' => 'perak', 'area' => 'daerah',
        'districts' => ['Bagan Datuk', 'Batang Padang', 'Hilir Perak', 'Hulu Perak', 'Kampar', 'Kerian', 'Kinta', 'Kuala Kangsar', 'Larut, Matang dan Selama', 'Manjung', 'Muallim', 'Perak Tengah'],
    ],
    [
        'name' => 'Selangor', 'slug' => 'selangor', 'area' => 'daerah',
        'districts' => ['Gombak', 'Hulu Langat', 'Hulu Selangor', 'Klang', 'Kuala Langat', 'Kuala Selangor', 'Petaling', 'Sabak Bernam', 'Sepang'],
    ],
    [
        'name' => 'Kuala Lumpur', 'slug' => 'kuala-lumpur', 'area' => 'kawasan',
        'districts' => ['Bandar Tun Razak', 'Batu', 'Bukit Bintang', 'Cheras', 'Kepong', 'Lembah Pantai', 'Segambut', 'Seputeh', 'Setiawangsa', 'Titiwangsa', 'Wangsa Maju'],
    ],
    [
        'name' => 'Negeri Sembilan', 'slug' => 'negeri-sembilan', 'area' => 'daerah',
        'districts' => ['Jelebu', 'Jempol', 'Kuala Pilah', 'Port Dickson', 'Rembau', 'Seremban', 'Tampin'],
    ],
    [
        'name' => 'Melaka', 'slug' => 'melaka', 'area' => 'daerah',
        'districts' => ['Alor Gajah', 'Jasin', 'Melaka Tengah'],
    ],
    [
        'name' => 'Johor', 'slug' => 'johor', 'area' => 'daerah',
        'districts' => ['Batu Pahat', 'Johor Bahru', 'Kluang', 'Kota Tinggi', 'Kulai', 'Mersing', 'Muar', 'Pontian', 'Segamat', 'Tangkak'],
    ],
    [
        'name' => 'Pahang', 'slug' => 'pahang', 'area' => 'daerah',
        'districts' => ['Bentong', 'Bera', 'Cameron Highlands', 'Jerantut', 'Kuantan', 'Lipis', 'Maran', 'Pekan', 'Raub', 'Rompin', 'Temerloh'],
    ],
    [
        'name' => 'Terengganu', 'slug' => 'terengganu', 'area' => 'daerah',
        'districts' => ['Besut', 'Dungun', 'Hulu Terengganu', 'Kemaman', 'Kuala Nerus', 'Kuala Terengganu', 'Marang', 'Setiu'],
    ],
    [
        'name' => 'Kelantan', 'slug' => 'kelantan', 'area' => 'jajahan',
        'districts' => ['Bachok', 'Gua Musang', 'Jeli', 'Kota Bharu', 'Kuala Krai', 'Machang', 'Pasir Mas', 'Pasir Puteh', 'Tanah Merah', 'Tumpat'],
    ],
    [
        'name' => 'Sabah', 'slug' => 'sabah', 'area' => 'daerah',
        'districts' => ['Beaufort', 'Beluran', 'Kalabakan', 'Keningau', 'Kinabatangan', 'Kota Belud', 'Kota Kinabalu', 'Kota Marudu', 'Kuala Penyu', 'Kudat', 'Kunak', 'Lahad Datu', 'Membakut', 'Nabawan', 'Paitan', 'Papar', 'Penampang', 'Pitas', 'Putatan', 'Ranau', 'Sandakan', 'Semporna', 'Sipitang', 'Sook', 'Tambunan', 'Tawau', 'Telupid', 'Tenom', 'Tongod', 'Tuaran'],
    ],
    [
        'name' => 'Sarawak', 'slug' => 'sarawak', 'area' => 'daerah',
        'districts' => ['Asajaya', 'Bau', 'Belaga', 'Beluru', 'Betong', 'Bintulu', 'Bukit Mabong', 'Dalat', 'Daro', 'Gedong', 'Julau', 'Kabong', 'Kanowit', 'Kapit', 'Kuching', 'Lawas', 'Limbang', 'Lingga', 'Lubok Antu', 'Lundu', 'Marudi', 'Matu', 'Meradong', 'Miri', 'Mukah', 'Pakan', 'Pantu', 'Pusa', 'Samarahan', 'Saratok', 'Sarikei', 'Sebauh', 'Sebuyau', 'Selangau', 'Serian', 'Sibu', 'Siburan', 'Simunjan', 'Song', 'Sri Aman', 'Subis', 'Tanjung Manis', 'Tatau', 'Tebedu', 'Telang Usan'],
    ],
    [
        'name' => 'Perlis', 'slug' => 'perlis', 'area' => 'kawasan',
        'districts' => ['Arau', 'Beseri', 'Kaki Bukit', 'Kangar', 'Kuala Perlis', 'Padang Besar', 'Simpang Empat'],
    ],
    [
        'name' => 'Putrajaya', 'slug' => 'putrajaya', 'area' => 'kawasan',
        'districts' => ['Putrajaya'],
    ],
    [
        'name' => 'Labuan', 'slug' => 'labuan', 'area' => 'kawasan',
        'districts' => ['Labuan'],
    ],

];

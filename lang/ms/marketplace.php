<?php

return [

    'heading' => 'Cari vendor majlis anda',
    'subheading' => 'Vendor disahkan, harga jelas, dan anda berurusan terus dengan vendor pilihan anda.',

    'search' => [
        'label' => 'Cari',
        'placeholder' => 'Nama vendor atau pakej',
        'category' => 'Kategori',
        'any_category' => 'Semua kategori',
        'state' => 'Negeri',
        'any_state' => 'Mana-mana negeri',
        'submit' => 'Cari',
        'pill_title' => 'Cari vendor',
        'pill_hint' => 'Nama, pakej, kategori atau negeri',
        'filters' => 'Filter',
    ],

    'filters' => [
        'all' => 'Semua',
        'all_filters' => 'Semua filter',
        'clear' => 'Buang semua',
        'price' => 'Harga',
        'price_hint' => 'Harga pakej terendah vendor, dalam RM.',
        'minimum' => 'Minimum',
        'maximum' => 'Maksimum',
        'no_limit' => 'Tiada had',
        'apply' => 'Guna',
        'remove' => 'Buang',
        'rating' => 'Rating',
        'min_rating' => 'Rating minimum',
        'tier' => 'Tahap vendor',
        'sort' => 'Susun',
        'order' => 'Susunan',
        'from' => 'Dari :amount',
        'under' => 'Bawah :amount',
        'show' => 'Tunjuk vendor',
    ],

    'sorts' => [
        'recommended' => 'Disyorkan',
        'rating' => 'Rating tertinggi',
        'price_asc' => 'Harga: rendah ke tinggi',
        'price_desc' => 'Harga: tinggi ke rendah',
        'reviews' => 'Paling banyak review',
    ],

    // One form, no choices: Laravel has no plural rule for Malay and would
    // pick the first branch whatever the number was.
    'count' => ':count vendor',
    'demo' => 'Data demo',

    'card' => [
        'pro' => 'Pro',
        'pro_title' => 'Vendor Neekah Pro',
        'recommended' => 'Disyorkan',
        'top' => 'Vendor terbaik',
        'new' => 'Baru',
        'from' => 'Dari',
        'compare_add' => 'Tambah ke senarai banding',
        'compare_one' => 'Banding :name',
    ],

    'empty' => [
        'title' => 'Maaf, belum ada vendor yang sepadan',
        'body' => 'Cuba longgarkan penapis, atau beritahu kami apa yang anda cari: kategori, lokasi dan tarikh majlis. Kami akan kongsikan kepada rangkaian vendor kami dan bantu hubungkan anda.',
        'whatsapp' => 'WhatsApp kami',
        'clear' => 'Buang semua filter',
    ],

    'compare' => [
        'heading' => 'Vendor untuk dibanding',
        'clear' => 'Kosongkan',
        'open' => 'Banding',
        'limit' => 'Maksimum 4 vendor sahaja.',
    ],

    'sponsored' => [
        'heading' => 'Ditaja',
        'note' => 'Vendor Pro yang sepadan dengan carian anda. Senarai di bawah disusun mengikut prestasi, bukan bayaran.',
        'label' => 'Ditaja',
    ],

];

<?php

return [

    'announcement_audience' => [
        'everyone' => 'Semua pengguna',
        'customers' => 'Pengantin sahaja',
        'vendors' => 'Vendor sahaja',
        'custom' => 'Pilih sendiri',
    ],

    'announcement_status' => [
        'draft' => 'Draf',
        'sending' => 'Sedang dihantar',
        'sent' => 'Dihantar',
    ],

    'auth_audience' => [
        'couple' => 'Pengantin',
        'vendor' => 'Vendor',
    ],

    'booking_status' => [
        'pending_payment' => 'Pending Payment',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'enquiry_status' => [
        'open' => 'Baru',
        'replied' => 'Dibalas',
        'closed' => 'Ditutup',
    ],

    'guest_group' => [
        'family' => 'Keluarga',
        'friends' => 'Kawan',
        'work' => 'Kerja',
        'neighbours' => 'Jiran',
        'other' => 'Lain-lain',
    ],

    'guest_side' => [
        'bride' => 'Pihak perempuan',
        'groom' => 'Pihak lelaki',
        'both' => 'Kedua-dua pihak',
    ],

    'guest_status' => [
        'attending' => 'Hadir',
        'declined' => 'Tidak hadir',
        'opened' => 'Pautan peribadi dibuka',
        'shared' => 'Anda tanda hantar',
        'pending' => 'Belum ditanda hantar',
    ],

    'payment_method' => [
        'manual_transfer' => 'Rekod bayaran manual',
        'billplz' => 'Billplz',
        'bayarcash' => 'Bayarcash',
        'stripe' => 'Stripe',
    ],

    'payment_status' => [
        'pending' => 'Belum dibayar',
        'awaiting_verification' => 'Menunggu pengesahan',
        'paid' => 'Dibayar',
        'failed' => 'Gagal',
        'refunded' => 'Dipulangkan',
    ],

    'point_reason' => [
        'profile_complete' => 'Profile lengkap',
        'catalogue_complete' => 'Catalogue lengkap',
        'platform_booking' => 'Booking melalui platform',
        'deposit_paid' => 'Bayaran disahkan',
        'booking_completed' => 'Booking selesai',
        'full_payment' => 'Full payment',
        'positive_review' => 'Positive review',
        'fast_response' => 'Fast response',
        'high_completion_rate' => 'Bonus completion rate tinggi',
        'violation_penalty' => 'Potongan pelanggaran',
    ],

    'price_unit' => [
        'package' => 'pakej',
        'pax' => 'pax',
    ],

    'review_filter' => [
        'reported' => 'Dilaporkan vendor',
        'hidden' => 'Disembunyikan',
        'open' => 'Review terbuka',
        'verified' => 'Dari tempahan',
    ],

    'user_role' => [
        'customer' => 'Pengantin',
        'vendor' => 'Vendor',
        'admin' => 'Admin',
    ],

    'user_segment' => [
        'vendor_setup_complete' => 'Vendor setup lengkap',
        'vendor_setup_pending' => 'Vendor setup belum lengkap',
        'couple_no_wedding' => 'Belum cipta majlis',
        'couple_no_card' => 'Belum cipta kad digital',
        'couple_no_partner' => 'Belum jemput pasangan',
        'vendor_setup_complete_desc' => 'Profil penuh (tagline, penerangan, telefon, harga) dan katalog penuh (sekurang-kurangnya satu pakej aktif dan tiga gambar portfolio).',
        'vendor_setup_pending_desc' => 'Masih kurang sekurang-kurangnya satu daripada perkara di atas, jadi profil mereka belum layak dinilai pengantin.',
        'couple_no_wedding_desc' => 'Pengantin yang mendaftar tetapi tiada majlis langsung — bukan pemilik, bukan pasangan.',
        'couple_no_card_desc' => 'Pengantin yang sudah cipta majlis tetapi majlis itu belum ada kad digital. Mereka yang belum cipta majlis tidak dikira di sini.',
        'couple_no_partner_desc' => 'Pengantin yang cipta majlis seorang diri: pasangan belum menyertai, dan tiada jemputan yang masih sah.',
    ],

    'vendor_status' => [
        'pending' => 'Menunggu kelulusan',
        'approved' => 'Diluluskan',
        'rejected' => 'Ditolak',
        'suspended' => 'Digantung',
    ],

    'vendor_tier' => [
        'new' => 'New',
        'verified' => 'Verified',
        'trusted' => 'Trusted',
        'top' => 'Top',
        'recommended' => 'Elite',
    ],

    'violation_action' => [
        'warning' => 'Amaran',
        'point_deduction' => 'Potongan point + turun ranking',
        'suspension' => 'Penggantungan sementara',
        'removal' => 'Penyingkiran vendor',
    ],

    'violation_status' => [
        'open' => 'Menunggu semakan',
        'upheld' => 'Disahkan',
        'dismissed' => 'Ditolak',
    ],

    'violation_type' => [
        'payment_bypass' => 'Cuba bypass pembayaran platform',
        'cancelled_booking' => 'Batalkan booking tanpa sebab',
        'no_show' => 'Tidak hadir pada hari majlis',
        'misleading_listing' => 'Listing atau portfolio mengelirukan',
        'poor_service' => 'Kualiti perkhidmatan tidak memuaskan',
        'other' => 'Lain-lain',
    ],

    'song_moment' => [
        'akad' => 'Akad Nikah',
        'entrance' => 'Entrance Pengantin',
        'makan_beradab' => 'Makan Beradab',
        'potong_kek' => 'Potong Kek',
        'first_walk' => 'First Walk',
        'latar' => 'Lagu Latar Majlis',
        'ending' => 'Penutup Majlis',
    ],

    'wedding_role' => [
        'owner' => 'Pemilik majlis',
        'partner' => 'Pasangan',
    ],

    'review_source' => [
        'verified' => '✓ Tempahan disahkan',
        'vendor_added' => 'Ditambah oleh vendor',
        'platform_added' => 'Ditambah oleh Neekah',
        'open' => 'Review terbuka',
    ],

    'payment_method_desc' => [
        'manual_transfer' => 'Pengantin membayar terus kepada vendor, merekodkan bayaran berserta resit, dan vendor mengesahkannya.',
        'billplz' => 'FPX dan kad melalui Billplz.',
        'bayarcash' => 'FPX, DuitNow dan kad melalui Bayarcash.',
        'stripe' => 'Kad kredit dan debit melalui Stripe.',
    ],

    'review_filter_desc' => [
        'reported' => 'Vendor membantah review ini dan meminta admin melihatnya. Ia masih dipaparkan sehingga anda bertindak.',
        'hidden' => 'Sudah ditarik dari profil vendor. Rekodnya kekal, termasuk sebab dan siapa yang menariknya.',
        'open' => 'Ditulis terus pada profil, tanpa tempahan. Tidak menyentuh rating, mata atau ranking vendor.',
        'verified' => 'Datang daripada tempahan yang selesai di Neekah. Hanya yang ini menggerakkan rating dan ranking.',
    ],

    'announcement_audience_desc' => [
        'everyone' => 'Setiap pengantin dan vendor yang aktif.',
        'customers' => 'Akaun pengantin sahaja.',
        'vendors' => 'Akaun vendor sahaja.',
        'custom' => 'Pilih pengguna satu per satu, atau taip alamat emel sendiri.',
    ],

];

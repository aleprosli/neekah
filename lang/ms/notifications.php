<?php

return [

    'greeting' => 'Hai :name,',
    'greeting_plain' => 'Hai,',
    'salutation' => 'Terima kasih, Neekah',
    'congratulations' => 'Tahniah!',
    'welcome' => 'Selamat datang, :name!',
    'thanks_name' => 'Terima kasih, :name!',
    'event_date' => 'Tarikh majlis: :date',
    'package_total' => 'Jumlah pakej :amount.',
    'total' => 'Jumlah: :amount',
    'customer_is' => 'Pelanggan: :name',
    'vendor_is' => 'Vendor: :name',
    'actions' => [
        'reply_enquiry' => 'Balas enquiry',
        'write_review' => 'Beri review',
        'open_dashboard' => 'Buka dashboard',
        'complete_profile' => 'Lengkapkan profil',
        'view_reply' => 'Lihat balasan',
        'view_booking' => 'Lihat booking',
        'view_public_profile' => 'Lihat profil awam',
        'check_payment' => 'Semak bayaran',
        'accept_invitation' => 'Terima jemputan',
    ],

    'enquiry_received' => [
        'title' => 'Enquiry baharu daripada :name',
        'body' => 'Balas dalam 24 jam untuk mengekalkan response rate anda.',
        'subject' => 'Enquiry baharu daripada :name',
        'intro' => ':name menghantar enquiry kepada anda:',
        'reply_fast' => 'Balas dengan cepat untuk mengekalkan response rate yang tinggi.',
    ],

    'enquiry_replied' => [
        'title' => ':vendor membalas enquiry anda',
        'body' => 'Lihat balasan dan teruskan ke tempahan.',
        'subject' => ':vendor telah membalas enquiry anda',
        'intro' => ':vendor membalas:',
    ],

    'booking_cancelled' => [
        'note_prefix' => 'Dibatalkan:',
        'system' => 'Sistem Neekah',
        'expired_intro' => 'Tempahan :reference dibatalkan kerana deposit tidak dibayar dalam masa yang ditetapkan.',
        'expired_body' => 'Deposit untuk :package tidak dibayar; tarikh telah dibuka semula.',
        'vendor_refunds' => 'Deposit yang sudah dibayar akan dipulangkan oleh vendor terus kepada anda, mengikut terma vendor.',
        'title' => 'Booking :reference dibatalkan',
        'body' => ':name membatalkan tempahan :package.',
        'subject' => 'Booking :reference dibatalkan',
        'intro' => ':name telah membatalkan booking :reference.',
        'reason' => 'Sebab: :reason',
        'no_refund' => 'Tiada bayaran yang disahkan pada booking ini, jadi tiada apa yang perlu dipulangkan melalui Neekah.',
    ],

    'booking_completed' => [
        'title' => 'Majlis dengan :vendor selesai',
        'body' => 'Kongsi pengalaman anda dengan memberi review.',
        'subject' => 'Bagaimana majlis anda dengan :vendor?',
        'intro' => ':vendor telah menandakan booking :reference sebagai selesai.',
        'ask_review' => 'Kongsi pengalaman anda. Review hanya boleh diberi oleh pengantin yang benar-benar menempah, jadi ulasan anda sangat bermakna kepada pengantin lain.',
    ],

    'booking_confirmed' => [
        'title' => 'Booking :reference disahkan',
        'body' => 'Tarikh majlis :date kini terjamin.',
        'subject' => 'Booking :reference disahkan',
        'intro' => 'Bayaran telah disahkan dan booking :reference kini Confirmed.',
        'outstanding' => 'Baki :amount masih belum direkod.',
    ],

    'booking_created_customer' => [
        'title' => 'Booking :reference dibuat',
        'body' => 'Hubungi :vendor untuk berbincang, kemudian rekodkan bayaran anda di sini.',
        'subject' => 'Booking :reference dengan :vendor',
        'intro' => 'Booking anda dengan :vendor telah direkod.',
        'how_to_pay' => 'Berbincang terus dengan vendor tentang bayaran. Setelah anda membayar, rekodkan bayaran itu di halaman booking dan vendor akan mengesahkannya.',
    ],

    'booking_created_vendor' => [
        'title' => 'Booking baharu :reference',
        'body' => ':name menempah :package untuk :date.',
        'subject' => 'Booking baharu :reference daripada :name',
        'intro' => ':name telah menempah :package untuk majlis pada :date.',
        'awaiting_payment' => 'Booking akan disahkan sebaik sahaja pelanggan membayar deposit.',
    ],

    'payment_recorded' => [
        'title' => 'Bayaran direkod: :amount',
        'body' => ':name merekodkan bayaran untuk :reference. Sahkan setelah anda semak akaun anda.',
        'subject' => 'Bayaran direkod untuk :reference',
        'intro' => ':name merekodkan bayaran sebanyak :amount untuk :reference.',
        'paid_on' => 'Tarikh bayaran yang direkod: :date.',
        'verify' => 'Semak akaun anda, kemudian sahkan bayaran ini. Booking hanya menjadi Confirmed selepas anda mengesahkannya.',
    ],

    'payment_received' => [
        'title' => 'Bayaran :amount disahkan',
        'body' => 'Vendor mengesahkan bayaran anda untuk booking :reference.',
        'subject' => 'Bayaran :reference disahkan',
        'intro' => ':vendor mengesahkan bayaran :amount untuk booking :reference.',
        'detail' => 'Majlis: :date · Rujukan bayaran: :reference',
    ],

    'payment_rejected' => [
        'title' => 'Bayaran :amount tidak ditemui',
        'body' => 'Vendor tidak menemui bayaran ini dalam akaun mereka. Sila semak dan rekod semula.',
        'subject' => 'Bayaran :reference tidak dapat disahkan',
        'intro' => ':vendor tidak menemui bayaran :amount yang anda rekodkan untuk booking :reference.',
        'what_to_do' => 'Semak resit dan tarikh bayaran anda, hubungi vendor jika perlu, kemudian rekodkan semula.',
    ],

    'customer_registered' => [
        'title' => 'Selamat datang ke Neekah',
        'body' => 'Mulakan dengan menetapkan tarikh majlis anda, kemudian cari vendor.',
        'subject' => 'Selamat datang ke Neekah',
        'intro' => 'Akaun anda sudah sedia. Neekah mengumpulkan semua urusan majlis anda di satu tempat: cari dan tempah vendor yang disahkan, jejak bajet, checklist dan timeline, dan hantar kad jemputan digital.',
        'next_step' => 'Mulakan dengan menetapkan tarikh majlis anda. Selepas itu kami boleh cadangkan vendor yang masih kosong pada tarikh tersebut.',
    ],

    'vendor_registered' => [
        'title' => 'Selamat datang ke Neekah',
        'body' => 'Lengkapkan profil, pakej dan portfolio anda sementara admin menyemak permohonan.',
        'subject' => 'Terima kasih kerana mendaftar dengan Neekah',
        'received' => 'Permohonan :vendor telah kami terima dan kini menunggu semakan admin.',
        'meanwhile' => 'Sementara menunggu, lengkapkan profil, pakej dan portfolio anda. Profil yang lengkap disemak dengan lebih cepat dan muncul lebih tinggi dalam carian pengantin.',
        'will_email' => 'Kami akan emel anda sebaik sahaja permohonan diluluskan.',
    ],

    'vendor_status' => [
        'title' => 'Status vendor: :status',
        'body' => ':vendor kini :status.',
        'subject' => 'Status vendor anda: :status',
        'approved' => 'Tahniah! :vendor telah diluluskan dan kini dipaparkan di marketplace Neekah.',
        'tier' => 'Tahap anda: :tier Vendor.',
        'suspended' => ':vendor telah digantung sementara dan tidak dipaparkan di marketplace.',
        'contact_admin' => 'Sila hubungi pihak admin untuk maklumat lanjut.',
        'rejected' => 'Maaf, permohonan :vendor tidak dapat diluluskan pada masa ini.',
        'rejected_next' => 'Anda boleh melengkapkan profil dan menghubungi admin untuk semakan semula.',
        'pending' => 'Profil :vendor kini menunggu semakan admin.',
    ],

    'violation' => [
        'title' => 'Pelanggaran disahkan: :action',
        'body' => ':type · pelanggaran ke-:number.',
        'subject' => 'Pelanggaran direkod: :action',
        'intro' => 'Satu laporan terhadap :vendor telah disahkan oleh admin.',
        'type' => 'Jenis pelanggaran: :type',
        'number' => 'Pelanggaran ke-:number · Tindakan: :action',
        'admin_note' => 'Nota admin: :note',
        'warning' => 'Ini adalah amaran pertama. Pastikan semua booking dan pembayaran direkod melalui platform.',
        'point_deduction' => 'Point anda dipotong dan ranking diturunkan satu tahap.',
        'suspension' => 'Akaun anda digantung sementara dan tidak dipaparkan di marketplace.',
        'removal' => 'Akaun anda telah disingkirkan daripada marketplace kerana pelanggaran berulang.',
    ],

    'partner_invited' => [
        'subject' => ':name menjemput anda menguruskan majlis ":wedding"',
        'intro' => ':name menjemput anda menjadi pasangan dalam wedding project di Neekah.',
        'shared' => 'Setelah menerima jemputan, anda berdua akan berkongsi checklist, bajet, tempahan dan pembayaran yang sama.',
        'expires' => 'Pautan ini sah selama :days hari. Jika anda tidak mengenali jemputan ini, abaikan emel ini.',
    ],

    'pro_activated' => [
        'title' => 'Neekah Pro aktif',
        'body' => 'Pro anda aktif sehingga :date.',
        'subject' => 'Resit Neekah Pro',
        'thanks' => 'Terima kasih! :vendor kini vendor Pro di Neekah.',
        'receipt' => 'Rujukan :reference · Pelan :plan · RM:amount',
        'until' => 'Pro anda aktif sehingga :date.',
    ],

    'pro_expiring' => [
        'title' => 'Neekah Pro tamat dalam :days hari',
        'body' => 'Pro anda akan tamat pada :date.',
        'subject' => '{1} Neekah Pro tamat esok|[2,*] Neekah Pro tamat dalam :days hari',
        'renew' => 'Bayar semula untuk kekalkan slot Ditaja, analitik dan lencana Pro. Baki masa anda tidak hilang, tempoh baharu disambung selepasnya.',
        'action' => 'Sambung Pro',
    ],

    'booking_held' => [
        'title' => 'Tempahan :reference menunggu deposit',
        'subject' => 'Bayar deposit untuk :vendor',
        'intro' => 'Tarikh anda dengan :vendor sedang dipegang.',
        'body' => 'Bayar deposit :deposit sebelum :deadline, atau tarikh akan dilepaskan.',
        'pay_online' => 'Bayar melalui halaman tempahan anda (FPX). Wang terus ke akaun vendor.',
        'pay_transfer' => 'Pindahkan deposit ke akaun bank vendor (butiran di halaman tempahan), kemudian rekod bayaran dengan resit.',
    ],

    'deposit_refund' => [
        'title' => 'Deposit :reference perlu dipulangkan',
        'subject' => 'Deposit perlu dipulangkan: :reference',
        'body' => 'Deposit :amount untuk :vendor pada :date diterima selepas tempoh pegang tamat, dan tarikh itu sudah diambil. Vendor akan memulangkan deposit ini terus kepada pengantin.',
    ],

    'calendar_reminder' => [
        'weekly_title' => 'Kalendar anda masih terkini?',
        'weekly_body' => 'Ada tempahan luar minggu ini (WhatsApp, walk-in)? Tutup tarikhnya di Neekah supaya tiada pengantin menempah tarikh yang sama, kemudian sahkan kalendar.',
        'pausing_title' => 'Tempahan online akan dijeda dalam 2 hari',
        'pausing_body' => 'Kalendar anda belum disahkan. Sahkan sekarang supaya pengantin masih boleh menempah anda secara online.',
        'paused_title' => 'Tempahan online dijeda',
        'paused_body' => 'Kalendar anda tidak disahkan dalam tempoh yang ditetapkan, jadi borang tempahan disembunyikan. Semak kalendar dan sahkan untuk membukanya semula.',
        'action' => 'Buka kalendar',
    ],

    'deposit_waiting' => [
        'title' => 'Resit deposit :reference menunggu anda',
        'body' => 'Pengantin telah merekod deposit :amount dua hari lalu. Semak akaun anda dan sahkan (atau tolak) supaya tempahan tidak tergantung.',
    ],

    'ical_failed' => [
        'title' => 'Google Calendar anda tidak dapat diimport',
        'body' => 'Beberapa import terakhir gagal (:reason). Tarikh yang sudah diimport kekal ditutup, tetapi tempahan baru di kalendar anda tidak masuk ke Neekah.',
        'action' => 'Semak tetapan',
    ],

    'camera_activated' => [
        'title' => 'Kamera Majlis :tier sudah aktif',
        'body' => 'Kongsi pautan atau QR dengan tetamu. Album disimpan sehingga :date.',
        'receipt' => 'Resit :reference · RM:amount',
        'action' => 'Buka Kamera Majlis',
    ],

    'camera_export' => [
        'title' => 'ZIP Kamera Majlis anda sedia',
        'body' => 'Muat turun semua gambar dan video sebelum album dipadam pada :date.',
    ],

    'camera_retention' => [
        'after_event_title' => 'Terima kasih kerana guna Kamera Majlis',
        'after_event_body' => 'Tetamu berkongsi :count gambar dan video. Muat turun ZIP sebelum album dipadam pada :date.',
        'expiring_7_title' => 'Album Kamera Majlis dipadam dalam 7 hari',
        'expiring_7_body' => 'Semua gambar dan video akan dipadam pada :date. Muat turun ZIP sekarang.',
        'expiring_1_title' => 'Album Kamera Majlis dipadam esok',
        'expiring_1_body' => 'Ini peringatan terakhir: semua gambar dan video akan dipadam pada :date.',
        'purged_title' => 'Album Kamera Majlis telah dipadam',
        'purged_body' => 'Tempoh simpanan sudah tamat dan semua gambar serta video telah dipadam dari Neekah.',
        'action' => 'Buka Kamera Majlis',
    ],
];

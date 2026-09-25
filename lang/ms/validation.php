<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Medan :attribute mesti diterima.',
    'accepted_if' => 'Medan :attribute mesti diterima apabila :other ialah :value.',
    'active_url' => 'Medan :attribute mesti alamat URL yang sah.',
    'after' => 'Medan :attribute mesti tarikh selepas :date.',
    'after_or_equal' => 'Medan :attribute mesti tarikh pada atau selepas :date.',
    'alpha' => 'Medan :attribute hanya boleh mengandungi huruf.',
    'alpha_dash' => 'Medan :attribute hanya boleh mengandungi huruf, nombor, sengkang dan garis bawah.',
    'alpha_num' => 'Medan :attribute hanya boleh mengandungi huruf dan nombor.',
    'any_of' => 'Medan :attribute tidak sah.',
    'array' => 'Medan :attribute mesti satu array.',
    'array_keys' => 'Medan :attribute mesti mengandungi kekunci: :values.',
    'ascii' => 'Medan :attribute hanya boleh mengandungi aksara dan simbol alfanumerik satu bait.',
    'base64' => 'Medan :attribute mesti rentetan Base64 yang sah.',
    'before' => 'Medan :attribute mesti tarikh sebelum :date.',
    'before_or_equal' => 'Medan :attribute mesti tarikh pada atau sebelum :date.',
    'between' => [
        'array' => 'Medan :attribute mesti ada antara :min dan :max item.',
        'file' => 'Medan :attribute mesti antara :min dan :max kilobait.',
        'numeric' => 'Medan :attribute mesti antara :min dan :max.',
        'string' => 'Medan :attribute mesti antara :min dan :max aksara.',
    ],
    'boolean' => 'Medan :attribute mesti benar atau palsu.',
    'can' => 'Medan :attribute mengandungi nilai yang tidak dibenarkan.',
    'confirmed' => 'Pengesahan :attribute tidak sepadan.',
    'contains' => 'Medan :attribute tiada nilai yang diperlukan.',
    'current_password' => 'Kata laluan tidak betul.',
    'date' => 'Medan :attribute mesti tarikh yang sah.',
    'date_equals' => 'Medan :attribute mesti tarikh yang sama dengan :date.',
    'date_format' => 'Medan :attribute mesti mengikut format :format.',
    'decimal' => 'Medan :attribute mesti ada :decimal tempat perpuluhan.',
    'declined' => 'Medan :attribute mesti ditolak.',
    'declined_if' => 'Medan :attribute mesti ditolak apabila :other ialah :value.',
    'different' => 'Medan :attribute dan :other mesti berbeza.',
    'digits' => 'Medan :attribute mesti :digits digit.',
    'digits_between' => 'Medan :attribute mesti antara :min dan :max digit.',
    'dimensions' => 'Medan :attribute ada dimensi gambar yang tidak sah.',
    'distinct' => 'Medan :attribute ada nilai berulang.',
    'doesnt_contain' => 'Medan :attribute tidak boleh mengandungi salah satu daripada: :values.',
    'doesnt_end_with' => 'Medan :attribute tidak boleh berakhir dengan salah satu daripada: :values.',
    'doesnt_start_with' => 'Medan :attribute tidak boleh bermula dengan salah satu daripada: :values.',
    'email' => 'Medan :attribute mesti alamat emel yang sah.',
    'encoding' => 'Medan :attribute mesti menggunakan pengekodan :encoding.',
    'ends_with' => 'Medan :attribute mesti berakhir dengan salah satu daripada: :values.',
    'enum' => 'Pilihan :attribute tidak sah.',
    'exists' => 'Pilihan :attribute tidak sah.',
    'extensions' => 'Medan :attribute mesti ada salah satu sambungan ini: :values.',
    'file' => 'Medan :attribute mesti satu fail.',
    'filled' => 'Medan :attribute mesti ada nilai.',
    'gt' => [
        'array' => 'Medan :attribute mesti ada lebih daripada :value item.',
        'file' => 'Medan :attribute mesti lebih besar daripada :value kilobait.',
        'numeric' => 'Medan :attribute mesti lebih besar daripada :value.',
        'string' => 'Medan :attribute mesti lebih panjang daripada :value aksara.',
    ],
    'gte' => [
        'array' => 'Medan :attribute mesti ada :value item atau lebih.',
        'file' => 'Medan :attribute mesti :value kilobait atau lebih.',
        'numeric' => 'Medan :attribute mesti :value atau lebih.',
        'string' => 'Medan :attribute mesti :value aksara atau lebih.',
    ],
    'hex_color' => 'Medan :attribute mesti warna heksadesimal yang sah.',
    'image' => 'Medan :attribute mesti satu gambar.',
    'in' => 'Pilihan :attribute tidak sah.',
    'in_array' => 'Medan :attribute mesti wujud dalam :other.',
    'in_array_keys' => 'Medan :attribute mesti mengandungi sekurang-kurangnya satu daripada kekunci berikut: :values.',
    'integer' => 'Medan :attribute mesti satu integer.',
    'ip' => 'Medan :attribute mesti alamat IP yang sah.',
    'ipv4' => 'Medan :attribute mesti alamat IPv4 yang sah.',
    'ipv6' => 'Medan :attribute mesti alamat IPv6 yang sah.',
    'json' => 'Medan :attribute mesti rentetan JSON yang sah.',
    'list' => 'Medan :attribute mesti satu senarai.',
    'lowercase' => 'Medan :attribute mesti huruf kecil.',
    'lt' => [
        'array' => 'Medan :attribute mesti ada kurang daripada :value item.',
        'file' => 'Medan :attribute mesti lebih kecil daripada :value kilobait.',
        'numeric' => 'Medan :attribute mesti lebih kecil daripada :value.',
        'string' => 'Medan :attribute mesti lebih pendek daripada :value aksara.',
    ],
    'lte' => [
        'array' => 'Medan :attribute tidak boleh ada lebih daripada :value item.',
        'file' => 'Medan :attribute mesti :value kilobait atau kurang.',
        'numeric' => 'Medan :attribute mesti :value atau kurang.',
        'string' => 'Medan :attribute mesti :value aksara atau kurang.',
    ],
    'mac_address' => 'Medan :attribute mesti alamat MAC yang sah.',
    'max' => [
        'array' => 'Medan :attribute tidak boleh ada lebih daripada :max item.',
        'file' => 'Medan :attribute tidak boleh melebihi :max kilobait.',
        'numeric' => 'Medan :attribute tidak boleh melebihi :max.',
        'string' => 'Medan :attribute tidak boleh melebihi :max aksara.',
    ],
    'max_digits' => 'Medan :attribute tidak boleh melebihi :max digit.',
    'mimes' => 'Medan :attribute mesti fail jenis: :values.',
    'mimetypes' => 'Medan :attribute mesti fail jenis: :values.',
    'min' => [
        'array' => 'Medan :attribute mesti ada sekurang-kurangnya :min item.',
        'file' => 'Medan :attribute mesti sekurang-kurangnya :min kilobait.',
        'numeric' => 'Medan :attribute mesti sekurang-kurangnya :min.',
        'string' => 'Medan :attribute mesti sekurang-kurangnya :min aksara.',
    ],
    'min_digits' => 'Medan :attribute mesti sekurang-kurangnya :min digit.',
    'missing' => 'Medan :attribute mesti tiada.',
    'missing_if' => 'Medan :attribute mesti tiada apabila :other ialah :value.',
    'missing_unless' => 'Medan :attribute mesti tiada melainkan :other ialah :value.',
    'missing_with' => 'Medan :attribute mesti tiada apabila :values ada.',
    'missing_with_all' => 'Medan :attribute mesti tiada apabila :values ada.',
    'multiple_of' => 'Medan :attribute mesti gandaan :value.',
    'not_in' => 'Pilihan :attribute tidak sah.',
    'not_regex' => 'Format :attribute tidak sah.',
    'numeric' => 'Medan :attribute mesti satu nombor.',
    'password' => [
        'letters' => 'Medan :attribute mesti mengandungi sekurang-kurangnya satu huruf.',
        'mixed' => 'Medan :attribute mesti mengandungi sekurang-kurangnya satu huruf besar dan satu huruf kecil.',
        'numbers' => 'Medan :attribute mesti mengandungi sekurang-kurangnya satu nombor.',
        'symbols' => 'Medan :attribute mesti mengandungi sekurang-kurangnya satu simbol.',
        'uncompromised' => ':attribute yang diberi pernah bocor dalam kebocoran data. Sila pilih :attribute yang lain.',
    ],
    'present' => 'Medan :attribute mesti ada.',
    'present_if' => 'Medan :attribute mesti ada apabila :other ialah :value.',
    'present_unless' => 'Medan :attribute mesti ada melainkan :other ialah :value.',
    'present_with' => 'Medan :attribute mesti ada apabila :values ada.',
    'present_with_all' => 'Medan :attribute mesti ada apabila :values ada.',
    'prohibited' => 'Medan :attribute tidak dibenarkan.',
    'prohibited_if' => 'Medan :attribute tidak dibenarkan apabila :other ialah :value.',
    'prohibited_if_accepted' => 'Medan :attribute tidak dibenarkan apabila :other diterima.',
    'prohibited_if_declined' => 'Medan :attribute tidak dibenarkan apabila :other ditolak.',
    'prohibited_unless' => 'Medan :attribute tidak dibenarkan melainkan :other ada dalam :values.',
    'prohibits' => 'Medan :attribute menghalang :other daripada ada.',
    'regex' => 'Format :attribute tidak sah.',
    'required' => 'Medan :attribute wajib diisi.',
    'required_array_keys' => 'Medan :attribute mesti mengandungi kekunci: :values.',
    'required_if' => 'Medan :attribute wajib diisi apabila :other ialah :value.',
    'required_if_accepted' => 'Medan :attribute wajib diisi apabila :other diterima.',
    'required_if_declined' => 'Medan :attribute wajib diisi apabila :other ditolak.',
    'required_unless' => 'Medan :attribute wajib diisi melainkan :other ada dalam :values.',
    'required_with' => 'Medan :attribute wajib diisi apabila :values ada.',
    'required_with_all' => 'Medan :attribute wajib diisi apabila :values ada.',
    'required_without' => 'Medan :attribute wajib diisi apabila :values tiada.',
    'required_without_all' => 'Medan :attribute wajib diisi apabila tiada satu pun daripada :values ada.',
    'same' => 'Medan :attribute mesti sepadan dengan :other.',
    'size' => [
        'array' => 'Medan :attribute mesti mengandungi :size item.',
        'file' => 'Medan :attribute mesti :size kilobait.',
        'numeric' => 'Medan :attribute mesti :size.',
        'string' => 'Medan :attribute mesti :size aksara.',
    ],
    'starts_with' => 'Medan :attribute mesti bermula dengan salah satu daripada: :values.',
    'string' => 'Medan :attribute mesti satu rentetan.',
    'timezone' => 'Medan :attribute mesti zon waktu yang sah.',
    'unique' => ':attribute ini sudah digunakan.',
    'uploaded' => 'Medan :attribute gagal dimuat naik.',
    'uppercase' => 'Medan :attribute mesti huruf besar.',
    'phone' => 'Medan :attribute mesti nombor telefon yang sah untuk negara yang dipilih.',
    'url' => 'Medan :attribute mesti alamat URL yang sah.',
    'ulid' => 'Medan :attribute mesti ULID yang sah.',
    'uuid' => 'Medan :attribute mesti UUID yang sah.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'file_too_large' => 'Fail terlalu besar. Had server ini ialah :sizeMB setiap muat naik.',

        'remaining_balance' => 'Baki yang belum direkod hanya RM:amount.',
        'own_email' => 'Itu emel anda sendiri.',
        'wedding_already_full' => 'Majlis ini sudah ada dua ahli. Buang pasangan sedia ada dahulu.',
        'invitation_pending' => 'Jemputan ke emel ini masih menunggu jawapan.',
        'not_an_email' => '":address" bukan alamat emel yang sah.',
        'pick_a_recipient' => 'Pilih sekurang-kurangnya seorang penerima, atau taip satu alamat emel.',
        'malaysian_phone' => 'Masukkan nombor telefon Malaysia yang sah, contoh 012-345 6789.',
        'category_name_taken' => 'Kategori dengan nama ini sudah wujud.',
        'vendor_date_taken' => 'Anda sudah ada tempahan atau tarikh ditutup pada hari tersebut.',
        'vendor_unavailable' => 'Vendor ini tidak tersedia pada tarikh tersebut. Sila pilih tarikh lain.',
        'website_not_messaging' => 'Pautan laman web tidak boleh menghala ke WhatsApp atau Telegram.',
        'not_a_valid_link' => 'Pautan ini bukan pautan :platform yang sah.',
        'email_is_a_couple' => 'Emel ini sudah didaftar sebagai akaun pengantin. Log masuk dengan akaun itu untuk menukarnya ke akaun vendor.',
        'subdomain_regex' => 'Alamat web hanya boleh mengandungi huruf kecil, nombor dan tanda sengkang.',
        'subdomain_reserved' => 'Alamat web ini dikhaskan untuk platform. Sila pilih yang lain.',
        'subdomain_taken' => 'Alamat web ini telah diambil. Cuba yang lain.',
        'review_once' => 'Review hanya boleh diberi sekali selepas booking selesai.',
        'complete_confirmed_only' => 'Hanya booking yang telah disahkan boleh ditandakan selesai.',
        'rsvp_closed' => 'RSVP untuk majlis ini telah ditutup.',
        'card_not_found' => 'Kad jemputan ini tidak dijumpai.',

        'turnstile_missing' => 'Sila lengkapkan semakan keselamatan.',
        'turnstile_failed' => 'Semakan keselamatan gagal. Sila cuba sekali lagi.',

        'credentials' => 'Emel atau kata laluan tidak sepadan dengan rekod kami.',
        'too_many_attempts' => 'Terlalu banyak percubaan. Cuba lagi dalam :minutes minit.',
        'reset_link_invalid' => 'Pautan set semula tidak sah atau telah tamat tempoh.',

        'timeline_vendor_not_booked' => 'Anda hanya boleh menugaskan vendor yang telah ditempah untuk majlis ini.',
        'no_customer_account' => 'Tiada akaun pengantin dengan emel ini. Minta pelanggan daftar di Neekah dahulu.',
        'twitter_handle' => 'Akaun X hanya boleh mengandungi huruf, nombor dan garis bawah.',
        'current_password_to_change_email' => 'Masukkan kata laluan semasa anda untuk menukar emel.',
        'report_too_short' => 'Sila terangkan apa yang berlaku sekurang-kurangnya 20 aksara supaya admin boleh menyiasat.',
        'rsvp_pax_over' => 'Jemputan anda adalah untuk :max orang. Sila hubungi pengantin jika perlu tambah.',

        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];

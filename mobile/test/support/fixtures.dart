/// JSON shaped exactly like the v1 contract, for tests.
library;

Map<String, dynamic> status(String value, String label, String tone) => {'value': value, 'label': label, 'tone': tone};

Map<String, dynamic> vendorJson({bool elite = false}) => {
  'id': 7,
  'name': 'Studio Seri Kasih',
  'slug': 'studio-seri-kasih',
  'public_url': 'https://neekah.my/vendors/studio-seri-kasih',
  'logo_url': null,
  'initial': 'S',
  'category': 'Fotografi',
  'city': 'Shah Alam',
  'state': 'Selangor',
  'tier': {'value': 'trusted', 'label': 'Trusted'},
  'score': 64.5,
  'points_total': 150,
  'rating_avg': 4.8,
  'reviews_count': 12,
  'is_pro': true,
  'pro_until': '2026-11-27T02:12:37+08:00',
  'is_elite': elite,
  'boost_tokens': 12,
};

Map<String, dynamic> userJson() => {'id': 2, 'name': 'Hafiz Rahman', 'email': 'hafiz@example.com', 'phone': null, 'locale': 'ms'};

Map<String, dynamic> bookingSummaryJson({String reference = 'NK-AJSGML', String statusValue = 'confirmed'}) => {
  'reference': reference,
  'event_date': '2026-10-03',
  'customer_name': 'Nurul Izzati',
  'package_name': 'Pakej Akad Nikah Intimate',
  'total': 4200,
  'paid': 1500.0,
  'status': status(statusValue, 'Disahkan', 'emerald'),
  'is_online': false,
};

Map<String, dynamic> bookingDetailJson() => {
  ...bookingSummaryJson(statusValue: 'pending_payment'),
  'created_at': '2026-09-27T02:52:36+08:00',
  'notes': null,
  'deposit_amount': '1000.00',
  'outstanding': 2700,
  'hold_expires_at': null,
  'customer': {'name': 'Nurul Izzati', 'email': 'nurul@example.com', 'phone': '+60126354002', 'whatsapp_url': 'https://wa.me/60126354002'},
  'payments': [
    {
      'reference': 'PAY-1BWFTPTQ',
      'amount': 1200,
      'status': status('awaiting_verification', 'Menunggu pengesahan', 'amber'),
      'paid_on': '2026-09-27',
      'paid_at': null,
      'method_label': 'Pindahan bank',
      'note': 'Transfer Maybank',
      'recorded_by': 'Nurul Izzati',
      'slip_url': null,
      'receipt_url': null,
      'can': {'verify': true, 'reject': true, 'mark_refunded': false},
    },
  ],
  'timeline': [
    {'id': 1, 'time': '11:00', 'title': 'Akad nikah', 'location': 'Masjid Shah Alam', 'notes': null},
  ],
  'review': {'rating': 5, 'comment': 'Terbaik!'},
  'can': {'complete': false, 'cancel': true},
};

Map<String, dynamic> dashboardJson() => {
  'vendor': vendorJson(),
  'stats': {'upcoming_events': 4, 'awaiting_deposit': 2, 'open_enquiries': 3, 'payments_to_verify': 1, 'paid_total': 7050},
  'reach': {'days': 28, 'profile_views': 835, 'whatsapp_clicks': 90, 'phone_clicks': 36},
  'daily_views': [
    for (var day = 1; day <= 28; day++) {'date': '2026-09-${day.toString().padLeft(2, '0')}', 'label': '$day Sep', 'views': day * 3},
  ],
  'upcoming': [bookingSummaryJson()],
  'online': {'enabled': true, 'open': false, 'label': 'Tiada pakej aktif'},
  'elite': null,
};

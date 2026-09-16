---
paths:
  - 'app/**'
---

# App

## Vendor points, ranking weights and violation ladder are fixed by the kertas kerja
Vendor points: complete profile +50, complete catalogue +30, booking via platform +100, deposit paid +100, booking completed +150, full payment +150, positive review +20, fast response +20. No points for enquiries alone.
Ranking tiers: New -> Verified -> Trusted -> Top -> Recommended.
Recommended Vendor score: rating 30%, completed bookings 20%, completion rate 15%, response rate 15%, platform transactions 10%, profile & catalogue quality 10%. Rating alone must never drive ranking.
Payment-bypass violations: 1st warning, 2nd point deduction + ranking drop, 3rd temporary suspension, repeated removal. Admin reviews customer reports before action.

## Payments are manual records, verified by the vendor
No money moves through Neekah and there is no gateway. The couple deals with the vendor directly, then records what they paid (amount, paid_on, optional receipt image) on their booking; the payment lands as PaymentStatus::AwaitingVerification and changes nothing until the vendor confirms it, because only the vendor can see their own account. VerifyManualPayment is what awards points and flips a PendingPayment booking to Confirmed.

There are no deposit/balance instalments: CreateBooking creates no Payment rows at all, payments.type and bookings.deposit_amount are gone, and the old 40/60 split was the platform's invention, not something either side agreed to. Booking::paidAmount() counts verified payments only; outstandingAmount() is what is left.

A couple may cancel their own booking (BookingPolicy::cancel) only while no payment is verified — after that it is a refund, which the two sides settle themselves. CancelBooking revokes the points that booking earned. Admin → Tetapan → Bayaran (PaymentSettings) can switch manual transfer off, which hides the record form.

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

A couple may cancel their own booking (BookingPolicy::cancel) only while no payment is verified — after that it is a refund, which the two sides settle themselves. CancelBooking revokes the points that booking earned. Admin → Tetapan → Bayaran (PaymentSettings) has one on/off switch per App\Enums\PaymentMethod (manual transfer, Billplz, Bayarcash, Stripe); switching manual transfer off hides the record form. Neekah holds no bank account of its own, so there are no bank fields. A gateway is only offered once PaymentMethod::isIntegrated() returns true for it — switching one on before its checkout is built does nothing. Adding a gateway = a new enum case (its settingKey is picked up by the settings form automatically) plus its integration.

## A couple account switches to vendor only while it has no couple activity
Users have one role and emails are unique, so a vendor who signed up as a couple could not use /vendor/register. /vendor/tukar-akaun (Vendor\AccountConversionController → RegisterVendor::convert) turns the same account into a pending vendor, gated by User::canBecomeVendor(): customer role and no wedding, booking, enquiry or review. Anything with activity goes through admin, so no couple data ends up owned by a vendor. RegisterVendorRequest skips customers in its email unique rule and adds an `existing_customer` error instead, which VendorRegisterForm.vue shows with a "log masuk & tukar akaun" link. Covered by VendorAccountConversionTest.

## Admin account tools refuse anything that loses records
Admin → Pengguna → {user} (admin.users.show) offers switch couple→vendor, vendor→couple, deactivate/reactivate and delete, each gated by UserPolicy and never on admin accounts. Delete is refused when the account has bookings (either side) or created a wedding shared with another member, because cascades would erase someone else's data — deactivate instead. users.deactivated_at is enforced by EnsureAccountIsActive in the web group (signs the session out on its next request); deactivating a vendor also suspends the profile, and reactivating does not re-approve it. File cleanup on delete goes through RemoveVendorProfile/DeleteUserAccount, since DB cascades leave images on disk. Covered by UserAccountToolsTest.

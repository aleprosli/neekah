<?php

return [

    'boost_token_reason' => [
        'welcome' => 'Welcome gift',
        'pro_monthly' => 'Pro monthly tokens',
        'purchase' => 'Token purchase',
        'admin' => 'Admin adjustment',
        'spend' => 'Category boost',
        'refund' => 'Refund',
    ],

    'vendor_feature' => [
        'boost' => 'Boost',
        'online_booking' => 'Online booking',
        'packages' => 'Packages',
        'portfolio' => 'Portfolio',
        'calendar' => 'Calendar & online booking',
        'bookings' => 'Bookings',
        'enquiries' => 'Enquiries',
        'reviews' => 'Reviews',
        'points' => 'Points & ranking',
    ],

    'vendor_feature_desc' => [
        'boost' => 'Spend tokens to lift your profile to the top of your category.',
        'online_booking' => 'Couples book a date and pay the deposit right on the vendor page.',
        'packages' => 'Manage the packages and prices shown on the public page.',
        'portfolio' => 'Upload and arrange portfolio photos.',
        'calendar' => 'Block dates, take online bookings with a deposit and sync Google Calendar.',
        'bookings' => 'Record and manage customer bookings.',
        'enquiries' => 'Receive and reply to enquiries from couples.',
        'reviews' => 'See, reply to and report customer reviews.',
        'points' => 'See points, tier and ranking position.',
    ],

    'announcement_audience' => [
        'everyone' => 'Everyone',
        'customers' => 'Couples only',
        'vendors' => 'Vendors only',
        'custom' => 'Chosen by hand',
    ],

    'announcement_status' => [
        'draft' => 'Draft',
        'sending' => 'Sending',
        'sent' => 'Sent',
    ],

    'auth_audience' => [
        'couple' => 'Couple',
        'vendor' => 'Vendor',
    ],

    'booking_status' => [
        'pending_payment' => 'Pending payment',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'enquiry_status' => [
        'open' => 'New',
        'replied' => 'Replied',
        'closed' => 'Closed',
    ],

    'guest_group' => [
        'family' => 'Family',
        'friends' => 'Friends',
        'work' => 'Work',
        'neighbours' => 'Neighbours',
        'other' => 'Other',
    ],

    'guest_side' => [
        'bride' => 'Bride\'s side',
        'groom' => 'Groom\'s side',
        'both' => 'Both sides',
    ],

    'guest_status' => [
        'attending' => 'Attending',
        'declined' => 'Not attending',
        'opened' => 'Opened their link',
        'shared' => 'Marked as sent',
        'pending' => 'Not sent yet',
    ],

    'payment_method' => [
        'manual_transfer' => 'Manual payment record',
        'billplz' => 'Billplz',
        'bayarcash' => 'Bayarcash',
        'stripe' => 'Stripe',
    ],

    'payment_status' => [
        'pending' => 'Unpaid',
        'awaiting_verification' => 'Awaiting confirmation',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ],

    'point_reason' => [
        'profile_complete' => 'Profile complete',
        'catalogue_complete' => 'Catalogue complete',
        'platform_booking' => 'Booking through the platform',
        'deposit_paid' => 'Payment confirmed',
        'booking_completed' => 'Booking completed',
        'full_payment' => 'Paid in full',
        'positive_review' => 'Positive review',
        'fast_response' => 'Fast response',
        'high_completion_rate' => 'High completion rate bonus',
        'violation_penalty' => 'Violation penalty',
    ],

    'price_unit' => [
        'package' => 'package',
        'pax' => 'pax',
    ],

    'camera_album_filter' => [
        'active' => 'Active',
        'reported' => 'Reported',
        'purged' => 'Ended',
    ],

    'review_filter' => [
        'reported' => 'Reported by vendor',
        'hidden' => 'Hidden',
        'open' => 'Open reviews',
        'verified' => 'From a booking',
    ],

    'user_role' => [
        'customer' => 'Couple',
        'vendor' => 'Vendor',
        'admin' => 'Admin',
    ],

    'user_segment' => [
        'vendor_setup_complete' => 'Vendors with a complete setup',
        'vendor_setup_pending' => 'Vendors with an incomplete setup',
        'couple_no_wedding' => 'No wedding created yet',
        'couple_no_card' => 'No digital card yet',
        'couple_no_partner' => 'Partner not invited yet',
        'vendor_setup_complete_desc' => 'A full profile (tagline, description, phone, pricing) and a full catalogue (at least one active package and three portfolio photos).',
        'vendor_setup_pending_desc' => 'Still missing at least one of the above, so their profile is not ready for couples to judge.',
        'couple_no_wedding_desc' => 'Couples who registered but have no wedding at all — neither as owner nor as partner.',
        'couple_no_card_desc' => 'Couples who created a wedding but that wedding has no digital card yet. Those who have not created a wedding are not counted here.',
        'couple_no_partner_desc' => 'Couples who created the wedding alone: the partner has not joined, and no invitation is still valid.',
    ],

    'vendor_status' => [
        'pending' => 'Awaiting approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'suspended' => 'Suspended',
    ],

    'vendor_tier' => [
        'new' => 'New',
        'verified' => 'Verified',
        'trusted' => 'Trusted',
        'top' => 'Top',
        'recommended' => 'Recommended',
    ],

    'violation_action' => [
        'warning' => 'Warning',
        'point_deduction' => 'Points deducted and ranking dropped',
        'suspension' => 'Temporary suspension',
        'removal' => 'Vendor removed',
    ],

    'violation_status' => [
        'open' => 'Awaiting review',
        'upheld' => 'Upheld',
        'dismissed' => 'Dismissed',
    ],

    'violation_type' => [
        'payment_bypass' => 'Tried to bypass platform payment',
        'cancelled_booking' => 'Cancelled a booking without reason',
        'no_show' => 'Did not turn up on the day',
        'misleading_listing' => 'Misleading listing or portfolio',
        'poor_service' => 'Service quality below standard',
        'other' => 'Other',
    ],

    'wedding_role' => [
        'owner' => 'Wedding owner',
        'partner' => 'Partner',
    ],

    'review_source' => [
        'verified' => '✓ Booking verified',
        'vendor_added' => 'Added by the vendor',
        'platform_added' => 'Added by Neekah',
        'open' => 'Open review',
    ],

    'payment_method_desc' => [
        'manual_transfer' => 'The couple pays the vendor directly, records the payment with a receipt, and the vendor confirms it.',
        'billplz' => 'FPX and cards through Billplz.',
        'bayarcash' => 'FPX, DuitNow and cards through Bayarcash.',
        'stripe' => 'Credit and debit cards through Stripe.',
    ],

    'review_filter_desc' => [
        'reported' => 'The vendor disputes this review and has asked an admin to look. It stays visible until you act.',
        'hidden' => 'Already taken off the vendor’s profile. The record stays, including why and who took it off.',
        'open' => 'Written straight onto the profile, with no booking behind it. It does not touch the vendor’s rating, points or ranking.',
        'verified' => 'It comes from a booking completed on Neekah. Only these move the rating and the ranking.',
    ],

    'announcement_audience_desc' => [
        'everyone' => 'Every active couple and vendor.',
        'customers' => 'Couples’ accounts only.',
        'vendors' => 'Vendor accounts only.',
        'custom' => 'Pick users one by one, or type an email address yourself.',
    ],

    'vendor_plan' => [
        'monthly' => 'Monthly',
        'yearly' => 'Yearly',
    ],

    'subscription_status' => [
        'pending' => 'Unpaid',
        'paid' => 'Paid',
        'failed' => 'Failed',
    ],

    'booking_source' => [
        'vendor' => 'Recorded by vendor',
        'online' => 'Online booking',
    ],

    'deposit_channel' => [
        'herepay' => 'Herepay (FPX)',
        'manual' => 'Bank transfer',
    ],

    'cancellation_reason' => [
        'couple' => 'Cancelled by the couple',
        'vendor' => 'Cancelled by the vendor',
        'expired' => 'Deposit not paid',
    ],

    'deposit_type' => [
        'percent' => 'Percentage of the package price',
        'fixed' => 'Fixed amount (RM)',
    ],

    'day_status' => [
        'open' => 'Available',
        'full' => 'Full',
        'closed' => 'Closed',
        'weekday_off' => 'Not open',
        'too_soon' => 'Too soon',
        'too_far' => 'Not open yet',
        'past' => 'Past',
    ],

    'online_booking_state' => [
        'open' => 'Live: couples can book directly',
        'globally_off' => 'Neekah has not opened online booking yet',
        'not_approved' => 'Account not approved yet',
        'feature_off' => 'Online booking is for Neekah Pro vendors',
        'switched_off' => 'Switched off by you',
        'no_packages' => 'No active package',
        'no_payment_path' => 'No way to pay the deposit: connect Herepay or add bank details',
        'calendar_stale' => 'Paused: confirm your calendar',
    ],

    'camera_tier' => [
        'basic' => 'Basic',
        'pro' => 'Pro',
    ],
];

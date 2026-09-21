<?php

return [

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

];

<?php

return [

    'greeting' => 'Hi :name,',
    'greeting_plain' => 'Hi,',
    'salutation' => 'Thank you, Neekah',
    'congratulations' => 'Congratulations!',
    'welcome' => 'Welcome, :name!',
    'thanks_name' => 'Thank you, :name!',
    'event_date' => 'Wedding date: :date',
    'package_total' => 'Package total :amount.',
    'total' => 'Total: :amount',
    'customer_is' => 'Customer: :name',
    'vendor_is' => 'Vendor: :name',
    'actions' => [
        'reply_enquiry' => 'Reply to the enquiry',
        'write_review' => 'Write a review',
        'open_dashboard' => 'Open the dashboard',
        'complete_profile' => 'Complete your profile',
        'view_reply' => 'View the reply',
        'view_booking' => 'View the booking',
        'view_public_profile' => 'View public profile',
        'check_payment' => 'Check the payment',
        'accept_invitation' => 'Accept the invitation',
    ],

    'enquiry_received' => [
        'locked_title' => 'You have a new enquiry',
        'locked_body' => 'A couple sent you an enquiry. Upgrade to Neekah Pro to read and answer it.',
        'locked_action' => 'Upgrade to Pro',
        'title' => 'New enquiry from :name',
        'body' => 'Reply within 24 hours to keep your response rate up.',
        'subject' => 'New enquiry from :name',
        'intro' => ':name has sent you an enquiry:',
        'reply_fast' => 'Reply quickly to keep your response rate high.',
    ],

    'enquiry_replied' => [
        'title' => ':vendor replied to your enquiry',
        'body' => 'Read the reply and go on to book.',
        'subject' => ':vendor has replied to your enquiry',
        'intro' => ':vendor replied:',
    ],

    'booking_cancelled' => [
        'note_prefix' => 'Cancelled:',
        'system' => 'Neekah',
        'expired_intro' => 'Booking :reference was cancelled because the deposit was not paid in time.',
        'expired_body' => 'The deposit for :package was not paid; the date has been reopened.',
        'vendor_refunds' => 'The vendor will refund the deposit already paid directly to you, under their terms.',
        'title' => 'Booking :reference cancelled',
        'body' => ':name cancelled the :package booking.',
        'subject' => 'Booking :reference cancelled',
        'intro' => ':name has cancelled booking :reference.',
        'reason' => 'Reason: :reason',
        'no_refund' => 'No payment on this booking was confirmed, so there is nothing to refund through Neekah.',
    ],

    'booking_completed' => [
        'title' => 'Your wedding with :vendor is done',
        'body' => 'Tell other couples how it went.',
        'subject' => 'How was your wedding with :vendor?',
        'intro' => ':vendor has marked booking :reference as complete.',
        'ask_review' => 'Share how it went. Only couples who actually booked can leave a review, so yours means a great deal to other couples.',
    ],

    'booking_confirmed' => [
        'title' => 'Booking :reference confirmed',
        'body' => 'Your date, :date, is now secured.',
        'subject' => 'Booking :reference confirmed',
        'intro' => 'The payment is confirmed and booking :reference is now Confirmed.',
        'outstanding' => ':amount is still unrecorded.',
    ],

    'booking_created_customer' => [
        'title' => 'Booking :reference created',
        'body' => 'Get in touch with :vendor, then record your payment here.',
        'subject' => 'Booking :reference with :vendor',
        'intro' => 'Your booking with :vendor has been recorded.',
        'how_to_pay' => 'Agree the payment with the vendor directly. Once you have paid, record it on the booking page and the vendor will confirm it.',
    ],

    'booking_created_vendor' => [
        'title' => 'New booking :reference',
        'body' => ':name booked :package for :date.',
        'subject' => 'New booking :reference from :name',
        'intro' => ':name has booked :package for their wedding on :date.',
        'awaiting_payment' => 'The booking is confirmed as soon as the customer pays the deposit.',
    ],

    'payment_recorded' => [
        'title' => 'Payment recorded: :amount',
        'body' => ':name recorded a payment for :reference. Confirm it once you have checked your account.',
        'subject' => 'Payment recorded for :reference',
        'intro' => ':name recorded a payment of :amount for :reference.',
        'paid_on' => 'Recorded payment date: :date.',
        'verify' => 'Check your account, then confirm this payment. The booking only becomes Confirmed once you do.',
    ],

    'payment_received' => [
        'title' => 'Payment of :amount confirmed',
        'body' => 'The vendor confirmed your payment for booking :reference.',
        'subject' => 'Payment :reference confirmed',
        'intro' => ':vendor confirmed a payment of :amount for booking :reference.',
        'detail' => 'Wedding: :date · Payment reference: :reference',
    ],

    'payment_rejected' => [
        'title' => 'Payment of :amount not found',
        'body' => 'The vendor could not find this payment in their account. Please check and record it again.',
        'subject' => 'Payment :reference could not be confirmed',
        'intro' => ':vendor could not find the :amount payment you recorded for booking :reference.',
        'what_to_do' => 'Check your receipt and the payment date, get in touch with the vendor if you need to, then record it again.',
    ],

    'customer_registered' => [
        'title' => 'Welcome to Neekah',
        'body' => 'Start by setting your wedding date, then find vendors.',
        'subject' => 'Welcome to Neekah',
        'intro' => 'Your account is ready. Neekah brings the whole wedding into one place: find and book verified vendors, track your budget, checklist and timeline, and send a digital invitation.',
        'next_step' => 'Start by setting your wedding date. After that we can suggest vendors who are still free on it.',
    ],

    'vendor_registered' => [
        'title' => 'Welcome to Neekah',
        'body' => 'Complete your profile, packages and portfolio while an admin reviews your application.',
        'subject' => 'Thank you for signing up with Neekah',
        'received' => 'We have received the application for :vendor and it is waiting for an admin to review it.',
        'meanwhile' => 'In the meantime, complete your profile, packages and portfolio. A complete profile is reviewed faster and appears higher in what couples search for.',
        'will_email' => 'We will email you as soon as it is approved.',
    ],

    'vendor_status' => [
        'title' => 'Vendor status: :status',
        'body' => ':vendor is now :status.',
        'subject' => 'Your vendor status: :status',
        'approved' => 'Congratulations! :vendor is approved and now appears in the Neekah marketplace.',
        'tier' => 'Your tier: :tier Vendor.',
        'suspended' => ':vendor has been suspended and no longer appears in the marketplace.',
        'contact_admin' => 'Please contact an admin for more.',
        'rejected' => 'Sorry, the application for :vendor could not be approved at this time.',
        'rejected_next' => 'You can complete your profile and contact an admin for another review.',
        'pending' => 'The profile for :vendor is now waiting for an admin to review it.',
    ],

    'violation' => [
        'title' => 'Violation upheld: :action',
        'body' => ':type · violation number :number.',
        'subject' => 'Violation recorded: :action',
        'intro' => 'A report about :vendor has been upheld by an admin.',
        'type' => 'Type of violation: :type',
        'number' => 'Violation number :number · Action: :action',
        'admin_note' => 'Admin note: :note',
        'warning' => 'This is a first warning. Make sure every booking and payment is recorded through the platform.',
        'point_deduction' => 'Your points have been deducted and your ranking dropped one tier.',
        'suspension' => 'Your account is suspended and no longer appears in the marketplace.',
        'removal' => 'Your account has been removed from the marketplace for repeated violations.',
    ],

    'partner_invited' => [
        'subject' => ':name has invited you to plan ":wedding"',
        'intro' => ':name has invited you to be their partner on a wedding project on Neekah.',
        'shared' => 'Once you accept, you will both share the same checklist, budget, bookings and payments.',
        'expires' => 'This link is valid for :days days. If you do not recognise this invitation, ignore this email.',
    ],

    'pro_activated' => [
        'title' => 'Neekah Pro is active',
        'body' => 'Your Pro plan runs until :date.',
        'subject' => 'Your Neekah Pro receipt',
        'thanks' => 'Thank you! :vendor is now a Pro vendor on Neekah.',
        'receipt' => 'Reference :reference · :plan plan · RM:amount',
        'until' => 'Your Pro plan runs until :date.',
    ],

    'pro_expiring' => [
        'title' => 'Neekah Pro ends in :days days',
        'body' => 'Your Pro plan ends on :date.',
        'subject' => '{1} Neekah Pro ends tomorrow|[2,*] Neekah Pro ends in :days days',
        'renew' => 'Pay again to keep online booking, monthly boost tokens, analytics and the Pro badge. You lose none of the time left; the new period starts after it.',
        'action' => 'Renew Pro',
    ],

    'booking_held' => [
        'title' => 'Booking :reference is waiting for its deposit',
        'subject' => 'Pay the deposit for :vendor',
        'intro' => 'Your date with :vendor is on hold.',
        'body' => 'Pay the :deposit deposit before :deadline, or the date is released.',
        'pay_online' => 'Pay from your booking page (FPX). The money goes straight to the vendor.',
        'pay_transfer' => 'Transfer the deposit to the vendor\'s bank account (details on your booking page), then record the payment with the receipt.',
    ],

    'deposit_refund' => [
        'title' => 'Deposit for :reference needs a refund',
        'subject' => 'Deposit needs a refund: :reference',
        'body' => 'The :amount deposit for :vendor on :date arrived after the hold ran out, and the date has been taken. The vendor will refund it directly to the couple.',
    ],

    'calendar_reminder' => [
        'weekly_title' => 'Is your calendar still up to date?',
        'weekly_body' => 'Any outside bookings this week (WhatsApp, walk-in)? Close those dates on Neekah so no couple books the same day, then confirm your calendar.',
        'pausing_title' => 'Online booking pauses in 2 days',
        'pausing_body' => 'Your calendar has not been confirmed. Confirm it now so couples can keep booking you online.',
        'paused_title' => 'Online booking is paused',
        'paused_body' => 'Your calendar was not confirmed in time, so the booking form is hidden. Check your calendar and confirm it to reopen.',
        'action' => 'Open calendar',
    ],

    'deposit_waiting' => [
        'title' => 'Deposit receipt :reference is waiting for you',
        'body' => 'The couple recorded a :amount deposit two days ago. Check your account and confirm (or reject) it so the booking is not left hanging.',
    ],

    'ical_failed' => [
        'title' => 'Your Google Calendar could not be imported',
        'body' => 'The last few imports failed (:reason). Dates already imported stay closed, but new bookings in your calendar are not reaching Neekah.',
        'action' => 'Check settings',
    ],

    'camera_activated' => [
        'title' => 'Kamera Majlis :tier is live',
        'body' => 'Share the link or QR with your guests. The album is kept until :date.',
        'receipt' => 'Receipt :reference · RM:amount',
        'action' => 'Open Kamera Majlis',
    ],

    'camera_export' => [
        'title' => 'Your Kamera Majlis ZIP is ready',
        'body' => 'Download every photo and video before the album is deleted on :date.',
    ],

    'camera_retention' => [
        'after_event_title' => 'Thank you for using Kamera Majlis',
        'after_event_body' => 'Your guests shared :count photos and videos. Download the ZIP before the album is deleted on :date.',
        'expiring_7_title' => 'Your Kamera Majlis album is deleted in 7 days',
        'expiring_7_body' => 'Every photo and video will be deleted on :date. Download the ZIP now.',
        'expiring_1_title' => 'Your Kamera Majlis album is deleted tomorrow',
        'expiring_1_body' => 'Last reminder: every photo and video will be deleted on :date.',
        'purged_title' => 'Your Kamera Majlis album has been deleted',
        'purged_body' => 'Its storage period has ended and every photo and video has been deleted from Neekah.',
        'action' => 'Open Kamera Majlis',
    ],

    'boost_tokens' => [
        'title' => ':count boost tokens landed in your account',
        'welcome' => 'Welcome to Neekah! Here are some boost tokens on us. Balance: :balance tokens.',
        'pro_monthly' => 'Your Neekah Pro monthly boost tokens are in. Balance: :balance tokens.',
        'purchase' => 'Thank you for your purchase. Balance: :balance tokens.',
        'admin' => 'The Neekah team added boost tokens for you. Balance: :balance tokens.',
        'how' => 'One token lifts your profile to the top of the Recommended list in a category you choose, for a day.',
        'action' => 'Use boost tokens',
    ],
    'boost_ending' => [
        'title' => 'Your :category boost ends tomorrow',
        'body' => 'Your boost ends on :date. Add days to stay at the top.',
        'action' => 'Extend the boost',
    ],
];

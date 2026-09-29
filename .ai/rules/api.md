---
paths:
  - 'app/Http/Controllers/Api/**'
---

# Api

## The Neekah Pro app API: Sanctum tokens, Pro checked on every call, same actions as the web
Owner, 27 Sep 2026: the Flutter app in mobile/ is for approved vendors with Neekah Pro only. routes/api.php, prefix /api/v1, names api.v1.*. Login (ApiLoginRequest: rate limit per email+IP, AccessCode rule, no Turnstile, no session) issues a Sanctum token only when EnsureApiProVendor::reasonToRefuse() is null; otherwise 403 with a code (not_vendor, not_approved, pro_required + pro_url, account_inactive) the app turns into a screen. Every call past login runs `api.pro` because a token outlives the plan; /me stays open so the app can say Pro ended. Language comes from Accept-Language (SetApiLocale), default ms. Controllers must reuse the web's actions and policies (CancelBooking, CompleteBooking, VerifyManualPayment, ReplyToEnquiry, UpdateVendorCalendar, SwitchOnlineBooking, StartVendorBoost, TierProgress) — never a second copy of the rule. Responses are JsonResource/arrays: money as numbers, dates ISO, statuses {value,label,tone}. Buying Pro or boost packs stays on the website. Its client is the Flutter app in mobile/ (typed models in mobile/lib); covered by tests/Feature/Api.

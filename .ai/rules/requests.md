---
paths:
  - 'app/Http/Requests/**'
---

# Requests

## Vendor signup phone is E.164; city is a daerah of the negeri
Vendor signup and couple-to-vendor conversion (RegisterVendorRequest, ConvertToVendorRequest, also used by admin) take the phone through UiPhoneField (intl-tel-input, every country, Malaysia default) and validate it with propaganistas/laravel-phone `(new Phone)->international()->country('MY')`. prepareForValidation turns a readable number into E.164 via App\Support\PhoneNumber::toE164, so validated() carries "+60123456789"; an unreadable one is left as typed for the rule to refuse. The daerah is its own column, vendors.district (nullable), beside the free-text `city`, which stays exactly as vendors typed it (owner, 25 Sep 2026: never remap or rewrite old cities). Signup and conversion require `district` in States::districts($state); the profile editor offers it as optional so older vendors can add one. Both forms use UiDistrictSelect, which follows the negeri and clears a daerah from another one. The lists live beside each negeri in config/states.php (`area`: daerah, jajahan for Kelantan, kawasan for KL/Perlis/Putrajaya/Labuan; Sabah and Sarawak list daerah, sourced from Wikipedia in Sep 2026).

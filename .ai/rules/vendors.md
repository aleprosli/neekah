---
paths:
  - 'resources/views/vendors/**'
---

# Vendors

## A vendor's phone and WhatsApp are never in public markup
On vendors/show.blade.php the WhatsApp button and the phone number sit inside @auth. A guest gets a "Log masuk untuk WhatsApp vendor" link instead — the number must not be rendered and hidden with CSS, or it is one inspect-element away from being scraped. Vendor::whatsappUrl() is what builds the wa.me link. Do not add the number to the vendor card, the compare page, the JSON-LD (no "telephone" property) or any API response; VendorMarketplaceTest asserts a guest page contains neither "wa.me" nor the number.

---
paths:
  - resources/js/components/customer/CameraPrintDesigner.vue
---

# Components Customer

## Kamera Majlis QR cards are drawn in the browser
The QR and the printable table cards are Vue only (owner, 26 Sep 2026): the npm `qrcode` package, error correction Q, 4-module quiet zone, always dark #111 on white regardless of the design palette so every phone scans it. The QR encodes CameraAlbum::url() (/k/{token}); rotating the token changes it, so nothing is rendered or stored server-side. Designs: ikut-kad (the couple's card palette bg/head/acc/ink and fonts d/s from SiteTemplate::palette/fonts, hidden when they have no card), klasik, bunga, minimalis. Sizes A6/A5 in mm; print opens a sheet with @page (card size, or A4 with 4×A6 / A4 landscape with 2×A5) for print or Save as PDF; PNG is a 300 dpi canvas. The passcode is printed only when the couple types it in the designer (it is stored hashed). The chosen design/options are saved via PUT camera.design (qr_design, qr_options).

## QR table cards: one SVG for preview, print and PNG
26 Sep 2026: the PNG used to be a separate canvas drawing and the print window never loaded the fonts, so both drifted from the preview. tableCard.js renderCard() now returns one pure SVG (no foreignObject — Safari taints the canvas) used for the preview, the print window (fonts embedded as data: URIs from the page's own @font-face rules) and the 300 dpi PNG. Never draw a second version of the card. "Ikut kad" uses the invitation palette, whose ink is for light panels: readableOn() falls back to the heading colour when ink has under 3:1 contrast on the card background.

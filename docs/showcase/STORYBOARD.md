# Neekah — Product Showcase Storyboard

One promise: **find a vendor, close the deal directly, plan the rest on Neekah — free. People like it, so they share it, and the network keeps growing.**

| Part | Story | 16:9 | 9:16 |
| --- | --- | --- | --- |
| Cold open | A stressed bride asks Threads for help → someone replies "Cari kat neekah.my" | 0:00–0:04 | 0:00–0:03 |
| Act 1 | neekah.my → cari "quenno" → WhatsApp → **deal dalam 8 minit** | 0:04–0:36 | 0:03–0:27 |
| Act 2 | Vendor: daftar senang, tunggu lulus — **tiada komitmen** | 0:36–0:51 | 0:27–0:36 |
| Act 3 | **Jaringan**: vendors and couples share Neekah themselves | 0:51–1:03 | 0:36–0:45 |
| Act 4 | Pengantin masuk jaringan: apa yang dia boleh buat | 1:03–1:27 | 0:45–0:55 |
| Outro | Logo, two buttons | 1:27–1:33 | 0:55–0:59 |

| | 16:9 master | 9:16 master |
| --- | --- | --- |
| Length | ~93 s | ~59 s |
| Resolution | 1920×1080 | 1080×1920 |
| Use | YouTube, landing page, pitch | Reels, TikTok, Threads, WhatsApp Status |

**Rules for every frame** (`.ai/rules/general.md`, `docs/FEATURES.md`)
- Neekah is free and a network. The deal and the payment happen between couple and vendor. Never show booking or payment *through Neekah*, commission, in-app chat, or a mobile app.
- Neekah screens are real captures, never redrawn. The only mock-ups are the WhatsApp chat (Act 1) and the rebuilt Threads posts (cold open, Acts 2–3), and both use the real words.
- All captions in Bahasa Melayu. Where the app already says it, use its own words (key in brackets).

**Cast**
- Vendor: **QUENNO ICE CREAM** (`neekah.my/vendors/quenno`) — Dessert, Verified, Selangor/KL, *PAKEJ SWEET* RM385 · *HAPPY* RM550 · *GRAND* RM770. Category and tier as they are in the data.
- Couple: demo account *Aina & Hakim*, 20 Disember 2026, Shah Alam — never a real couple's data.
- Chat: rebuilt from the real Quenno conversation (24 Sep 2026, 12:45 → 12:53).
- Threads posts: real posts, screenshots in `~/Documents` (list in §3).

---

## 0. Prep

| # | Prep | How |
| --- | --- | --- |
| P1 | **Act 1 is filmed on production** | Typing `neekah.my` has to be the real site, and production is where Quenno's photos load today. Only reading pages there — nothing is created |
| P2 | Signed in on production | *WhatsApp vendor* only shows to signed-in users. Sign in first with a film account, so the header shows an account and the button is live. Creating that account on production sends the usual welcome email + admin alert |
| P3 | Acts 2 and 4 are filmed locally | Refresh the local DB from a production dump first, or the images 404 (local paths are older than the files on R2). Set `MEDIA_DISK=local` while filming, or test uploads land in the production bucket. Do **not** run `migrate:fresh` |
| P4 | Vendor sign-up (Act 2) | Fill the form with a dummy business locally; submit, film the pending screen, then delete the account in `/admin/users` |
| P5 | Couple data (Act 4) | Demo couple: wedding created, budget per category = RM30,000, checklist ~68% with 5 tasks left in *Persediaan Majlis*, a short atur cara with Quenno on *Jamuan*, ~20 dummy guests, card filled but **unpublished** |
| P6 | Chat mock | Build per shot 1.7. Real phone number hidden, the couple shown as "Anda", no WhatsApp logo — a generic green chat that reads as WhatsApp |
| P7 | Threads mocks | Rebuild each post clean (avatar, handle, text, like count) in a neutral post card. No Threads logo. Keep the posters' own words and emoji exactly |
| P8 | **Permission** | Quenno for the chat. Every Threads poster used — a DM asking to feature the post. Anyone who says no is dropped; anyone who wants to stay anonymous gets handle and avatar blurred. Do not use ct_.hajar's illustration (not theirs to license) — text only |
| P9 | Recording hygiene | 100% zoom, bookmarks bar hidden, clean browser profile, notifications off, cursor highlight on |

---

## 1. 16:9 master (~93 s)

Camera: **push** = slow push-in · **pan** = lateral move · **macro** = tight crop · **SE** = shared-element cut (element grows into the next screen).

### Cold open — "princess stress" (0:00–0:04)

| Shot | Time | Screen | Action | Camera | Caption | Sound |
| --- | --- | --- | --- | --- | --- | --- |
| 0.1 | 0:00–0:02 | Rebuilt post, **ct_.hajar** | *"dear diary, princess stress nak settlekan hal kawin ni. nak cari itu cari ini…"* · ♥ 1.9K · 138 replies | slow push | — | ambient pad in |
| 0.2 | 0:02–0:04 | Reply slides in under it, **qtfk.19** | *"Cari kat neekah.my"* — the link glows, the camera dives into it | push into the link | — | soft whoosh → cut to 1.1 |

### Act 1 — Jumpa vendor, deal terus (0:04–0:36)

| Shot | Time | Screen | Action | Camera | Caption | Sound |
| --- | --- | --- | --- | --- | --- | --- |
| 1.1 | 0:04–0:07 | Empty browser window, new tab | **n-e-e-k-a-h-.-m-y** typed letter by letter, Enter | macro on address bar | — | soft key clicks |
| 1.2 | 0:07–0:10 | `neekah.my` home | Header, search bar, category strip, vendor cards stagger in | pull back to full browser | **Semua Urusan Majlis, Satu Platform** | soft swell |
| 1.3 | 0:10–0:13 | Home search bar | Type **quenno**, Enter | macro on search bar | — | key clicks |
| 1.4 | 0:13–0:15 | Results `/?q=quenno` | Quenno card (cover, name, Verified, *Dari RM385*); cursor hovers | slow push on card | — | tick |
| 1.5 | 0:15–0:19 | Card → `/vendors/quenno` | SE into the profile; scroll past portfolio and the three packages | SE, then pan down | **Berhubung terus dengan vendor** *(profile.highlight_contact)* | soft whoosh |
| 1.6 | 0:19–0:20 | Profile sidebar | Click **WhatsApp vendor** | macro on button | — | tick |
| 1.7 | 0:20–0:32 | **Chat mock-up** (phone frame, centred; desktop blurs behind) | Bubbles arrive one by one — script below | phone slides in from right, slow push as the chat grows | top: **12:45 PM** → bottom: **12:53 PM** | message pop per bubble |
| 1.8 | 0:32–0:36 | Chat holds on the last bubble | 12:45 and 12:53 highlight; phone steps back | pull back | **8 minit. Deal.** then **Terus antara anda dan vendor. Neekah tidak mengambil komisen dan tidak memegang bayaran anda.** *(profile.highlight_contact_detail)* | soft confirm tone |

**Chat script for 1.7** — rebuilt from the real conversation, trimmed to 12 seconds. Shown from the couple's phone: their messages on the right in green, Quenno .co 🍦 on the left in white. (The source screenshot is from Quenno's phone, so the sides are flipped.)

| Time | Side | Bubble |
| --- | --- | --- |
| 12:45 | Anda | Hai QUENNO ICE CREAM, saya jumpa anda di Neekah. Boleh saya tanya tentang pakej untuk majlis saya? *(the exact message Neekah pre-fills)* |
| 12:45 | Quenno | Hi! Thank you for contacting Quenno .co 🍦 Pls isi detail dibawah untuk saya check availability and package yg sesuai — Name · Date · Type of event · Number of pax · Location · Time |
| 12:50 | Anda | *(form filled — one bubble, details blurred)* |
| 12:52 | Anda | still available ke on that date? |
| 12:52 | Quenno | yes2 available |
| 12:52 | Anda | okey nak book boleh |
| 12:53 | Quenno | boleh · total RM610 · include transport 👍 |
| 12:53 | Quenno | booking fee RM50, the balance … *(confirm the rest with Quenno, or end the bubble here)* |
| 12:53 | Anda | **Locked! Thank you team 🙏. Less than 10m dah boleh close** |

Keep their spelling and mix of Malay and English — it is what makes it read as real.

### Act 2 — Vendor: daftar senang, tiada komitmen (0:36–0:51)

A four-step bar sits at the top and fills as each step happens:
**1 Daftar → 2 Pilih "Vendor" → 3 Isi maklumat → 4 Tunggu kelulusan**

| Shot | Time | Screen | Action | Camera | Caption | Sound |
| --- | --- | --- | --- | --- | --- | --- |
| 2.1 | 0:36–0:38 | Title card | — | static | **Anda vendor? Daftar senang je.** | pad shift |
| 2.2 | 0:38–0:41 | `/register` role chooser | *Anda mendaftar sebagai siapa?* → **Vendor** | macro | — | tick · step 1 → 2 |
| 2.3 | 0:41–0:45 | `/vendor/register` | Fields fill fast: nama, emel, telefon, nama perniagaan, kategori, negeri, bandar, tagline, kata laluan → submit | pan down the form | **Isi maklumat perniagaan** | quick typing · step 3 |
| 2.4 | 0:45–0:48 | `/vendor` dashboard | **Menunggu pengesahan** · *Lengkapkan profil, pakej dan portfolio sementara menunggu semakan admin.* | slow push | **Tunggu kelulusan. Itu sahaja.** | step 4 |
| 2.5 | 0:48–0:51 | Rebuilt post, **pyxza** (vendor) | *"…Anyway pagi tadi I dapat my first lead from Neekah. Soo excited 🥰"* | slow push | **Percuma. Tiada komitmen. Tiada komisen.** | soft confirm |

### Act 3 — Jaringan: orang suka, orang kongsi (0:51–1:03)

Posts drift in on a soft grid, two or three on screen at once, each held long enough to read its line. None of our UI here — this is the network speaking.

| Shot | Time | Post (rebuilt) | The line that shows | Who |
| --- | --- | --- | --- | --- |
| 3.1 | 0:51–0:54 | **bylisaisreena** + **sijarimerah** quote-post | *"To future bride & groom nak ada flower bar experience… can find us dekat Neekah.my okay! 🌷"* → *"My favourite florist @bylisaisreena is listed Neekah.my"* | vendor, and a fan sharing her |
| 3.2 | 0:54–0:56 | **udai.iii** — link card *18byU — Beverages di Setapak · Neekah* | *"18byU di Neekah"* | vendor |
| 3.3 | 0:56–0:58 | **rajazira_** — link card *QUENNO ICE CREAM — Dessert di SELURUH SELANGOR / KL · Neekah* | *"korang tengah cari ice cream vendor…? bolehhh check out heree!"* — ties back to Act 1 | Quenno's side |
| 3.4 | 0:58–1:00 | **sulambypu3** | *"u should try neekah.my situ boleh set bajet and ada macam macam dalam tu"* — bridges into Act 4 | couple side |
| 3.5 | 1:00–1:03 | **shukri_0911** | *"makin kerap lak lalu pasal neekah.my ni… nnti ada calonnya baru nak godek²"* — a smile before the next act | general buzz |

Caption across 3.1–3.5: **Jaringan ni membesar sebab orang suka — mudah, semua di satu tempat.**
Sound: one soft pop per post; the pad lifts slightly.

### Act 4 — Pengantin masuk jaringan (1:03–1:27)

| Shot | Time | Screen | Action | Camera | Caption | Sound |
| --- | --- | --- | --- | --- | --- | --- |
| 4.1 | 1:03–1:05 | `/register` → **Daftar sebagai pengantin** | Chooser → form (or *Google*) → submit | macro | **Percuma. Rancang majlis, urus bajet dan cari vendor di satu tempat.** *(auth_pages)* | tick |
| 4.2 | 1:05–1:07 | `/weddings/create` | Aina & Hakim · 20 Dis 2026 · Shah Alam · RM30,000 → save | pan | **Cipta majlis anda** | — |
| 4.3 | 1:07–1:10 | `/dashboard` | **N hari lagi**; *Jemput pasangan* → **Terhubung**, second avatar | push | **Uruskan majlis berdua** *(couple.uruskan_majlis_berdua)* | soft chime |
| 4.4 | 1:10–1:13 | `/checklist` | 5 ticks in *Persediaan Majlis*; 68 → 82% | pan down, macro on % | **Checklist ikut fasa** | tick ×5 |
| 4.5 | 1:13–1:15 | `/budget` | Category bars fill; total to RM30,000 | push | **Tahu ke mana setiap ringgit** | rising tone |
| 4.6 | 1:15–1:17 | `/timeline` + `/tetamu` | Quenno on *Jamuan*; guest list bulk paste | split | **Atur cara & senarai tetamu** | — |
| 4.7 | 1:17–1:20 | `/kad` | Swipe designs; palette recolours the preview live | macro on preview | **50 reka bentuk kad, warna anda sendiri** | page turns |
| 4.8 | 1:20–1:23 | `/kad` | Toggle *Detik menanti, RSVP, Ucapan & doa, Salam kaut*, muzik; **ainahakim** → available ✓ → **Siarkan kad** | macro | **Alamat sendiri: ainahakim.neekah.my** | confirm tone |
| 4.9 | 1:23–1:27 | Phone (390×844) | Card opens from a chat link, music starts → guest RSVPs, writes an ucapan | SE desktop → phone | **Tetamu buka, sahkan kehadiran, tinggal ucapan** | card music |

### Outro (1:27–1:33)

| Shot | Time | Screen | Action | Camera | Caption | Sound |
| --- | --- | --- | --- | --- | --- | --- |
| 5.1 | 1:27–1:30 | Lockup | Everything fades; logo 96→100% | static | **Semua Urusan Majlis, Satu Platform** | logo tone |
| 5.2 | 1:30–1:33 | Lockup + buttons | Two soft buttons | static | **Cari Vendor Sekarang** · **Sertai Sebagai Vendor** *(landing.*)* · neekah.my | pad out |

---

## 2. 9:16 master (~59 s)

Phone-native; captions in the top third.

| Shot | Time | Screen (390×844 capture) | Action | Caption |
| --- | --- | --- | --- | --- |
| V0 | 0:00–0:03 | ct_.hajar post → qtfk.19 reply | "princess stress…" → "Cari kat neekah.my" | — |
| V1 | 0:03–0:06 | Mobile browser address bar | Types **neekah.my**, Go | — |
| V2 | 0:06–0:08 | Home | Page loads | **Semua Urusan Majlis, Satu Platform** |
| V3 | 0:08–0:11 | Search | **quenno** → Quenno card | — |
| V4 | 0:11–0:14 | `/vendors/quenno` | Thumb scroll → **WhatsApp vendor** | **Hubungi vendor terus** |
| V5 | 0:14–0:23 | Chat mock, full screen | Script 1.7, faster | **12:45 PM → 12:53 PM** |
| V6 | 0:23–0:27 | Last bubble | **Locked! Thank you team 🙏. Less than 10m dah boleh close** | **8 minit. Deal. Neekah tak ambil komisen.** |
| V7 | 0:27–0:36 | Register → Vendor → form → *Menunggu pengesahan* → pyxza "first lead" | Four-step bar fills | **Vendor? Daftar, tunggu lulus. Percuma, tiada komitmen.** |
| V8 | 0:36–0:45 | Posts stack up the screen: bylisaisreena, udai.iii, rajazira_, sulambypu3 | One swipe per post | **Orang suka, orang kongsi.** |
| V9 | 0:45–0:55 | Quick cuts: checklist → bajet → kad → card on phone | One gesture each | **Checklist, bajet, tetamu, kad digital — percuma** |
| V10 | 0:55–0:59 | Lockup | Logo + neekah.my | **Semua Urusan Majlis, Satu Platform** |

---

## 3. Sources & capture list

**Threads posts** (screenshots in `~/Documents`, `Screenshot 2026-09-24 at …`)

| File | Handle | Use | Note |
| --- | --- | --- | --- |
| 7.47.38 PM | ct_.hajar + qtfk.19 | Cold open | Text only, not the illustration |
| 7.46.49 PM | pyxza | 2.5 | "first lead from Neekah" |
| 7.47.15 PM, 7.48.36 PM | bylisaisreena, sijarimerah | 3.1 | |
| 7.48.04 PM | udai.iii | 3.2 | Link card shows 18byU's photo — their OK covers it |
| 7.47.50 PM | rajazira_ | 3.3 | Quenno link card |
| 7.45.54 PM | sulambypu3 | 3.4 | |
| 7.46.06 PM | shukri_0911 | 3.5 | |
| 7.46.17 PM, 7.46.33 PM | pyxza (candy wall RM89) | Spare | A vendor answering a gift thread with their Neekah link — good B-roll |
| 7.49.07 PM | skydream.my | Spare, **visual only** | Shows the real search results UI. Its caption says "senang juga booking kat sini… pilih pakej and date" — booking on Neekah is off, so never show that line |
| 7.47.25 PM | aleprosli | **Not used** | Our own account — not third-party proof |
| 11.18.38 AM | — | **Not used** | Herepay screen, not Neekah |

**Screens**

Desktop at **1920×1080**, phone at **390×844 @2x**, 60 fps.

| Where | Route | Signed in as | Used in |
| --- | --- | --- | --- |
| **Production** | address bar → `neekah.my` | film account | 1.1–1.2, V1–V2 |
| **Production** | `/?q=quenno` | film account | 1.3–1.4, V3 |
| **Production** | `/vendors/quenno` | film account | 1.5–1.6, V4 |
| Built | Chat mock, Threads mocks | — | 0.x, 1.7–1.8, 2.5, Act 3 |
| Local | `/register`, `/vendor/register`, `/vendor` | guest → dummy vendor | Act 2, V7 |
| Local | `/register`, `/weddings/create`, `/dashboard` | demo couple | 4.1–4.3 |
| Local | `/invitations/{id}` | Hakim (demo) | 4.3 |
| Local | `/checklist`, `/budget`, `/timeline`, `/tetamu` | demo couple | 4.4–4.6, V9 |
| Local | `/kad` | demo couple | 4.7–4.8, V9 |
| Local | `ainahakim.neekah.test` | guest | 4.9, V9 |

---

## 4. Motion & sound

- **Timing**: 200 ms micro (ticks, toggles, bubbles) · 400 ms component (cards, posts, rows) · 600 ms section (SE cuts, act changes).
- **Typing** in 1.1 and 1.3 at a human pace, ~8 characters a second, caret visible.
- **Chat bubbles and posts** enter one at a time, 400 ms each, and hold long enough to read (≈ 3 words per 0.5 s). The last chat bubble holds a full second before 1.8.
- **Hierarchy**: primary element first, secondary 80–120 ms later. One focal motion at a time.
- **Never**: bounce, particles, 3D, heavy gradients, distorted UI text.
- **Sound**: one soft ambient bed; light key clicks; one pop reused for every bubble and post; soft confirm on "Locked!" and *Siarkan kad*; logo tone at the end. No cheesy wedding music; the card's own track is the only melody (4.9).
- **Captions**: burned in, two lines max, readable at phone size.

---

## 5. Sign-off before capture

- [ ] Storyboard approved
- [ ] Prep P1–P9 done
- [ ] Quenno agrees to appear, including the rebuilt chat
- [ ] Every Threads poster used has said yes (or asked to be blurred)
- [ ] Every caption checked against `docs/FEATURES.md` — nothing implies booking or payment through Neekah

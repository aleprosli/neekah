# Neekah Partner API — Kampung1998 (Kenduri Rewang)

Versi: **v1** · Dikemaskini: 25 Oktober 2026

API ini membolehkan Kampung1998 memaparkan direktori vendor perkahwinan Neekah dalam leaderboard Kenduri Rewang: senarai kategori, vendor dalam setiap kategori (ikut ranking), detail penuh vendor (gambar, pakej, review), menghantar review, dan menghubungi vendor melalui WhatsApp.

---

## 1. Aliran dalam permainan

```
Leaderboard (GET /leaderboard)
  └─ pemain tekan kategori         → papar data[i].vendors (sudah ada dalam respons yang sama)
       └─ pemain tekan vendor      → GET /vendors/{slug}
            ├─ "Tulis review"      → POST /vendors/{slug}/reviews
            ├─ "WhatsApp vendor"   → buka data.contact.whatsapp_url dalam browser pemain
            └─ "Lihat di Neekah"   → buka data.neekah_url
```

Kategori dan vendor dipulangkan dalam **satu panggilan** (`/leaderboard`), jadi bila pemain tekan kategori, tiada panggilan lain diperlukan.

---

## 2. Asas

| | |
|---|---|
| Base URL | `https://neekah.my/api/partner/v1` |
| Format | JSON (UTF-8) |
| Pengesahan | Header `X-Neekah-Key` |
| Bahasa | Header `Accept-Language: ms` (lalai) atau `en` |

### Header yang wajib dihantar

```
X-Neekah-Key: <API key yang diberi oleh Neekah>
Accept: application/json
```

> **Penting — key mesti disimpan di server.** API ini direka untuk panggilan **server ke server**. Jangan letak key dalam kod JavaScript, aplikasi mudah alih atau game client, kerana sesiapa yang buka browser boleh menyalinnya. Game client patut memanggil backend Kampung1998, dan backend itu yang memanggil Neekah.
>
> CORS tidak dibuka; panggilan terus dari browser akan disekat.

Kalau key perlu ditukar (contohnya bocor), maklumkan kepada Neekah dan kami akan keluarkan key baru.

---

## 3. Endpoints

### 3.1 `GET /leaderboard`

Semua kategori aktif, setiap satu dengan semua vendor yang diluluskan, disusun mengikut ranking.

**Ranking** = `score` vendor di Neekah (dari review tempahan, kerja yang selesai dan mata vendor), kemudian `rating_avg`. Bayaran (Pro / boost) **tidak** menaikkan ranking. Vendor yang bekerja dalam dua kategori akan muncul dalam kedua-duanya.

```bash
curl https://neekah.my/api/partner/v1/leaderboard \
  -H "X-Neekah-Key: $NEEKAH_KEY" \
  -H "Accept: application/json"
```

**Respons 200**

```json
{
  "data": [
    {
      "slug": "catering",
      "name": "Catering",
      "icon": "🍽️",
      "image_url": "https://cdn.neekah.my/categories/1/WDbIcLAEzINoqiw6pVICDinygewEiQXP3xTMDsfi-thumb.webp",
      "vendors_count": 45,
      "vendors": [
        {
          "rank": 1,
          "id": 77,
          "slug": "ef-kitchen",
          "name": "EF KITCHEN",
          "tagline": "Mushroom soup + garlic bread",
          "category": { "slug": "catering", "name": "Catering" },
          "city": "Klang",
          "state": "Selangor",
          "logo_url": null,
          "cover_url": "https://cdn.neekah.my/vendors/77/Zs5Su7PNIsdfp2zGDAFAKJ3UQuRCVql9SYvrMCKs.webp",
          "cover_thumbnail_url": "https://cdn.neekah.my/vendors/77/Zs5Su7PNIsdfp2zGDAFAKJ3UQuRCVql9SYvrMCKs-thumb.webp",
          "tier": { "value": "verified", "label": "Verified" },
          "is_elite": false,
          "is_pro": false,
          "score": 25.4,
          "rating_avg": null,
          "reviews_count": 0,
          "price_from": 80,
          "price_unit": { "value": "package", "label": "pakej" },
          "neekah_url": "https://neekah.my/vendors/ef-kitchen"
        }
      ]
    }
  ]
}
```

| Field kategori | Jenis | Keterangan |
|---|---|---|
| `slug` | string | Pengenal kategori |
| `name` | string | Nama kategori (ikut `Accept-Language`) |
| `icon` | string | Emoji kategori |
| `image_url` | string\|null | Ilustrasi kategori |
| `vendors_count` | int | Bilangan vendor dalam kategori (boleh `0`) |
| `vendors` | array | Senarai vendor, sudah disusun ikut `rank` |

| Field vendor | Jenis | Keterangan |
|---|---|---|
| `rank` | int | Kedudukan dalam kategori ini (1 = teratas) |
| `id`, `slug` | int, string | Gunakan `slug` untuk `/vendors/{slug}` |
| `name`, `tagline` | string, string\|null | |
| `category` | object | Kategori utama vendor |
| `city`, `state` | string | Lokasi |
| `logo_url` | string\|null | Logo (null → paparkan huruf pertama nama) |
| `cover_url` / `cover_thumbnail_url` | string\|null | Gambar cover penuh / kecil |
| `tier` | object | `new`, `verified`, `trusted`, `top`, `recommended` + label |
| `is_elite`, `is_pro` | bool | Lencana Elite / Pro |
| `score` | number | Markah ranking |
| `rating_avg` | number\|null | Purata bintang dari tempahan disahkan (null jika tiada) |
| `reviews_count` | int | Bilangan review tempahan disahkan |
| `price_from` | number | Harga bermula (RM) |
| `price_unit` | object | `package` (setiap pakej) atau `pax` (setiap orang) |
| `neekah_url` | string | Halaman vendor di Neekah |

> Respons ini mengandungi semua vendor (≈200 KB). Sila **cache sekurang-kurangnya 5 minit** di pihak anda. Data di Neekah dikemas kini serta-merta apabila vendor mengubah profil.

---

### 3.2 `GET /vendors/{slug}`

Detail penuh vendor — sama seperti halaman `https://neekah.my/vendors/{slug}`.

```bash
curl https://neekah.my/api/partner/v1/vendors/laguna-studio-enterprise \
  -H "X-Neekah-Key: $NEEKAH_KEY" \
  -H "Accept: application/json"
```

**Respons 200** — semua field vendor dalam 3.1 (tanpa `rank`), ditambah:

```json
{
  "data": {
    "id": 2,
    "slug": "laguna-studio-enterprise",
    "name": "Laguna Studio Enterprise",
    "...": "field yang sama seperti leaderboard",
    "description": "Harga berpatutan dan servis onTOP",
    "district": null,
    "service_states": ["Kedah"],
    "categories": [{ "slug": "photography", "name": "Photography" }],
    "social_links": [
      { "platform": "website", "label": "Laman web", "url": "https://contoh.my" }
    ],
    "contact": {
      "whatsapp_url": "https://neekah.my/api/partner/v1/vendors/laguna-studio-enterprise/whatsapp?partner=kampung1998&signature=b18d…",
      "phone": "0136465590"
    },
    "gallery": [
      {
        "url": "https://cdn.neekah.my/portfolio/2/98KY2KFQ4VMdksjct5xYoG09PaJaJhRzgFopC6Al.webp",
        "thumbnail_url": "https://cdn.neekah.my/portfolio/2/98KY2KFQ4VMdksjct5xYoG09PaJaJhRzgFopC6Al-thumb.webp",
        "caption": "Majlis Saif & Erin"
      }
    ],
    "packages": [
      {
        "id": 2,
        "name": "Basic A",
        "description": "- Hanya gambar sahaja tanpa video",
        "price": 800,
        "duration": "6 Jam",
        "features": ["1 Photographer"],
        "image_url": "https://cdn.neekah.my/packages/2/Y0Q0qG3rml8qJ0BiAZKi52PtDExdT4w5CmoahH7S.webp",
        "thumbnail_url": "https://cdn.neekah.my/packages/2/Y0Q0qG3rml8qJ0BiAZKi52PtDExdT4w5CmoahH7S-thumb.webp"
      }
    ],
    "reviews": {
      "verified": { "count": 0, "average": null },
      "open": { "count": 2, "average": 5 },
      "latest": [
        {
          "id": 1,
          "author_name": "aliff",
          "rating": 5,
          "comment": "Photographer yang sangat membantu, position yang cantik",
          "verified": false,
          "source": "Review terbuka",
          "photos": [],
          "reply": null,
          "replied_at": null,
          "created_at": "2026-09-19T19:25:36+08:00"
        }
      ]
    }
  }
}
```

| Field | Keterangan |
|---|---|
| `description` | Penerangan penuh vendor (teks biasa, mungkin ada baris baru) |
| `district`, `service_states` | Daerah, dan semua negeri yang vendor boleh pergi |
| `categories` | Semua kategori vendor (utama disertakan) |
| `social_links` | Pautan media sosial / laman web: `platform`, `label`, `url` |
| `contact.whatsapp_url` | **Buka dalam browser pemain.** Pautan Neekah yang merekod klik untuk analitik vendor, kemudian terus ke WhatsApp vendor dengan mesej "saya jumpa anda di Kampung1998 melalui Neekah". Tidak memerlukan key. `null` jika vendor tiada nombor. |
| `contact.phone` | Nombor telefon vendor (untuk butang `tel:`) atau `null` |
| `gallery` | Gambar portfolio mengikut susunan vendor |
| `packages` | Pakej aktif: `price` dalam RM, `features` senarai ringkas |
| `reviews.verified` | Bilangan & purata review dari tempahan disahkan (ini yang mempengaruhi ranking) |
| `reviews.open` | Bilangan & purata review terbuka (orang awam, termasuk dari Kampung1998) |
| `reviews.latest` | 12 review terbaru. `source` contoh: "✓ Tempahan disahkan", "Review terbuka", "Melalui Kampung1998". `reply` ialah balasan vendor |

Jangan gabungkan purata `verified` dan `open` menjadi satu angka — paparkan berasingan.

**Ralat:** `404` jika slug tiada atau vendor belum diluluskan/digantung.

---

### 3.3 `POST /vendors/{slug}/reviews`

Hantar review bagi pihak pemain. Review diterbitkan **serta-merta** di halaman vendor Neekah dengan label **"Melalui Kampung1998"**. Admin Neekah boleh menyembunyikan review yang melanggar peraturan.

Hantar sebagai JSON, atau `multipart/form-data` jika ada gambar.

| Field | Wajib | Peraturan |
|---|---|---|
| `rating` | ✓ | Integer 1–5 |
| `comment` | ✓ | 10–1000 aksara |
| `author_name` | ✓ | 2–80 aksara, nama yang dipaparkan |
| `player_id` | ✓ | ID pemain di Kampung1998 (≤100 aksara). Tidak dipaparkan; digunakan untuk had kadar |
| `author_email` | | Emel pemain. Tidak dipaparkan; hanya untuk Neekah menghubungi penulis jika review dipertikaikan |
| `photos[]` | | Sehingga 6 gambar (JPG/PNG/WebP) |

```bash
curl -X POST https://neekah.my/api/partner/v1/vendors/laguna-studio-enterprise/reviews \
  -H "X-Neekah-Key: $NEEKAH_KEY" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "rating": 5,
    "comment": "Gambar cantik dan sampai tepat pada masa.",
    "author_name": "Anak Kampung",
    "player_id": "k98-12345"
  }'
```

**Respons 201**

```json
{
  "data": {
    "id": 31,
    "author_name": "Anak Kampung",
    "rating": 5,
    "comment": "Gambar cantik dan sampai tepat pada masa.",
    "verified": false,
    "source": "Melalui Kampung1998",
    "photos": [],
    "reply": null,
    "replied_at": null,
    "created_at": "2026-10-25T12:30:00+08:00"
  }
}
```

**Respons 422** (validasi gagal)

```json
{
  "message": "Medan bintang mesti antara 1 dan 5. (and 1 more error)",
  "errors": {
    "rating": ["Medan bintang mesti antara 1 dan 5."],
    "comment": ["Medan ulasan wajib diisi."]
  }
}
```

---

### 3.4 `GET /vendors/{slug}/whatsapp?partner=…&signature=…`

Bukan untuk dipanggil oleh server anda. Ini ialah pautan `contact.whatsapp_url` dari 3.2 — buka terus dalam browser/tab baru pemain. Pautan ditandatangani oleh Neekah; jika diubah, ia akan menjawab `403`.

---

## 4. Kod ralat

| HTTP | `code` / maksud | Tindakan |
|---|---|---|
| 401 | `invalid_key` — key tiada atau salah | Semak header `X-Neekah-Key` |
| 404 | Vendor tiada / belum diluluskan | Buang vendor dari cache anda |
| 422 | Validasi gagal (lihat `errors`) | Paparkan mesej kepada pemain |
| 429 | Terlalu banyak permintaan | Tunggu `Retry-After` saat |
| 5xx | Ralat di Neekah | Cuba semula kemudian; guna data cache |

Contoh 401:

```json
{ "message": "Missing or invalid API key.", "code": "invalid_key" }
```

---

## 5. Had kadar

| Endpoint | Had |
|---|---|
| Semua endpoint ber-key | 120 permintaan / minit (untuk seluruh Kampung1998) |
| `POST /reviews` | 5 review / jam bagi setiap `player_id`, dan 100 review / jam untuk seluruh Kampung1998 |
| Pautan WhatsApp | 30 / minit bagi setiap IP pemain |

Respons mengandungi header `X-RateLimit-Limit` dan `X-RateLimit-Remaining`.

---

## 6. Gambar

- Semua gambar dihoskan di CDN Neekah (`https://cdn.neekah.my/…`) dalam format WebP.
- Gunakan `*_thumbnail_url` untuk grid/senarai, dan `url` penuh untuk paparan besar.
- Jangan muat turun dan hos semula gambar — pautan CDN sentiasa terkini.

---

## 7. Atribusi

Sila paparkan "Dikuasakan oleh Neekah" berserta pautan `neekah_url` pada paparan detail vendor.

## 8. Hubungi

Untuk key baru, isu teknikal atau perubahan API: hubungi pasukan Neekah.

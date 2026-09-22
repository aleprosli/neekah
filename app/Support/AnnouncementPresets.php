<?php

namespace App\Support;

use App\Enums\AnnouncementAudience;

/**
 * Ready-made announcements an admin can start from.
 *
 * The same few messages are sent over and over — vendors who have not finished
 * their profile, a feature nobody has noticed, a reminder to answer enquiries —
 * and each one gets rewritten from scratch, a little worse each time. These are
 * a starting point, not a send: the admin lands in the form with every field
 * filled and edits before anything leaves.
 *
 * The copy is Malay only. An announcement goes out in one language to everybody
 * (App\Notifications\AnnouncementPublished opens with "Hai"), and the people
 * reading it are Malaysian couples and vendors — so the admin's own interface
 * language has no business deciding what they are sent. Only the labels on the
 * buttons in the admin follow the interface.
 */
class AnnouncementPresets
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            self::preset('vendor_profile', AnnouncementAudience::Vendors,
                'Lengkapkan profil anda untuk pengesahan Neekah',
                <<<'TEXT'
                Profil yang lengkap adalah syarat sebelum kami boleh mengesahkan dan meluluskan akaun vendor anda. Setiap profil disemak oleh pasukan Neekah sebelum dipaparkan kepada pengantin.

                Sila pastikan perkara ini ada pada profil anda:
                - Nama perniagaan, kategori utama dan negeri yang anda liputi
                - Nombor telefon atau WhatsApp yang aktif
                - Keterangan perkhidmatan yang jelas
                - Sekurang-kurangnya satu pakej lengkap dengan harga
                - Logo dan beberapa gambar kerja terdahulu

                Setelah anda kemas kini, kami akan menyemak semula dan memaklumkan keputusannya melalui emel. Profil yang tidak lengkap tidak dapat kami luluskan buat masa ini.
                TEXT,
                'Kemas kini profil', 'vendor.profile.edit'),

            self::preset('vendor_catalogue', AnnouncementAudience::Vendors,
                'Tambah pakej dan harga supaya pengantin jumpa anda',
                <<<'TEXT'
                Pengantin menapis vendor mengikut kategori, negeri dan julat harga. Vendor tanpa pakej atau harga jarang muncul dalam carian mereka, walaupun profilnya sudah diluluskan.

                Yang membantu paling banyak:
                - Satu pakej untuk setiap jenis perkhidmatan yang anda tawarkan
                - Harga sebenar, bukan "hubungi kami"
                - Apa yang termasuk dan tidak termasuk dalam setiap pakej
                - Gambar portfolio yang mewakili setiap pakej

                Harga yang jelas juga mengurangkan pertanyaan berulang yang tidak menjadi tempahan.
                TEXT,
                'Urus pakej', 'vendor.packages.index'),

            self::preset('vendor_response', AnnouncementAudience::Vendors,
                'Balas pertanyaan pengantin dalam masa 24 jam',
                <<<'TEXT'
                Kadar respons anda diukur daripada pertanyaan yang dijawab berbanding pertanyaan yang sudah melebihi 24 jam. Ia salah satu perkara yang menentukan kedudukan anda dalam senarai vendor disyorkan.

                Yang lebih penting daripada kedudukan: pengantin yang tidak menerima jawapan dalam sehari dua biasanya sudah menempah dengan orang lain.

                Luangkan beberapa minit untuk menyemak pertanyaan yang masih menunggu.
                TEXT,
                'Lihat pertanyaan', 'vendor.enquiries.index'),

            self::preset('feature_launch', AnnouncementAudience::Everyone,
                'Ada yang baharu di Neekah',
                <<<'TEXT'
                Kami baru sahaja menambah [nama ciri] di Neekah.

                [Tulis dalam satu atau dua perenggan apa yang berubah, dan masalah apa yang ia selesaikan untuk mereka.]

                Cuba gunakannya dan beritahu kami pendapat anda — maklum balas anda yang menentukan apa yang kami bina seterusnya.
                TEXT,
                'Buka Neekah', 'vendors.index'),

            self::preset('card_designs', AnnouncementAudience::Customers,
                'Kad jemputan anda kini dengan 50 reka bentuk baharu',
                <<<'TEXT'
                Kad jemputan digital Neekah telah dibina semula. Ada 50 reka bentuk baharu — songket dan tenun tradisional, Islamik, bunga, minimalis dan moden — setiap satu dengan hiasan, warna dan tulisannya sendiri.

                Yang baharu untuk anda:
                - Tukar reka bentuk bila-bila masa; semua maklumat anda kekal
                - Ubah warna dan jenis tulisan mengikut tema majlis anda
                - Pilih bahagian yang mahu dipapar: tentatif, lokasi, galeri, RSVP, ucapan, salam kaut
                - Muzik latar dan statistik siapa yang membuka kad anda

                Jika anda sudah ada kad, ia telah dipindahkan ke reka bentuk baharu yang paling hampir dengan pilihan asal anda. Buka editor untuk melihat dan menukarnya jika mahu.
                TEXT,
                'Buka editor kad', 'site.edit'),

            self::preset('couple_start', AnnouncementAudience::Customers,
                'Mula rancang majlis anda di Neekah',
                <<<'TEXT'
                Anda sudah mendaftar, tetapi perancangan belum bermula. Semua alat ini percuma dan tiada komisen dikenakan:

                - Checklist lengkap mengikut fasa, dari setahun sebelum sehingga hari majlis
                - Bajet supaya anda nampak ke mana perbelanjaan pergi
                - Senarai tetamu dengan pautan peribadi untuk setiap jemputan
                - Kad jemputan digital dengan alamat web anda sendiri
                - Carian vendor mengikut kategori, negeri dan harga

                Mulakan dengan checklist — ia paling cepat menunjukkan apa yang patut dibuat sekarang.
                TEXT,
                'Buka checklist', 'checklist.index'),

            self::preset('guest_links', AnnouncementAudience::Customers,
                'Hantar kad anda dengan pautan peribadi untuk setiap tetamu',
                <<<'TEXT'
                Setiap tetamu dalam senarai anda mempunyai pautannya sendiri. Bila mereka membukanya, nama mereka tertera pada kad dan jawapan RSVP mereka terus masuk ke baris yang betul dalam senarai anda.

                Cara menggunakannya:
                1. Masukkan tetamu anda dalam senarai tetamu (boleh import serentak)
                2. Salin pautan peribadi setiap tetamu dan hantar melalui WhatsApp
                3. Jawapan mereka dikira automatik untuk bilangan kepala katering

                Anda juga boleh melihat berapa ramai yang sudah membuka kad dan siapa yang belum menjawab.
                TEXT,
                'Buka senarai tetamu', 'guests.index'),

            self::preset('maintenance', AnnouncementAudience::Everyone,
                'Penyelenggaraan berjadual pada [tarikh]',
                <<<'TEXT'
                Neekah akan ditutup sementara untuk penyelenggaraan pada [tarikh], dari [masa mula] hingga [masa tamat].

                Sepanjang tempoh itu, laman dan kad jemputan tidak boleh dibuka. Tiada data anda yang terjejas, dan semuanya kembali seperti biasa selepas penyelenggaraan selesai.

                Maaf atas sebarang kesulitan.
                TEXT,
                null, null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function preset(string $key, AnnouncementAudience $audience, string $subject, string $body, ?string $actionLabel, ?string $route): array
    {
        return [
            'key' => $key,
            'label' => __('pages.announcement_presets.'.$key),
            'hint' => __('pages.announcement_preset_hints.'.$key),
            'audience' => $audience->value,
            'subject' => $subject,
            // Heredoc keeps the indentation of the file out of the message.
            'body' => trim($body),
            'action_label' => $actionLabel,
            // The Malay URL, always: the message is Malay, and those are the
            // addresses that have been shared and indexed.
            'action_url' => $route ? url()->routeIn(Locales::DEFAULT, $route) : null,
        ];
    }
}

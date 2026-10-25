<?php

namespace Database\Seeders;

use App\Enums\ThreadsPostType;
use App\Models\ThreadsTemplate;
use Illuminate\Database\Seeder;

/**
 * A starting bank for the Threads planner, so the first night's plan has
 * something to write from. Safe to run again: a template already in the bank
 * (same type and text) is left as it is, with its usage count.
 *
 * Neekah is a network, not a checkout (see .ai/rules/general.md): nothing
 * here may promise booking or paying "di Neekah".
 */
class ThreadsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::templates() as [$type, $body, $poll]) {
            ThreadsTemplate::firstOrCreate(
                ['type' => $type, 'body' => $body],
                ['poll_options' => $poll, 'is_active' => true],
            );
        }
    }

    /**
     * @return list<array{0: ThreadsPostType, 1: string, 2: list<string>|null}>
     */
    public static function templates(): array
    {
        return [
            [ThreadsPostType::Listing, "✨ {nama}\n{kategori} · {negeri}\n\n{tagline}\n\nLihat pakej & portfolio penuh: {pautan}", null],
            [ThreadsPostType::Listing, "Tengah cari {kategori} di {negeri}? 👀\n\nKenali {nama}: {tagline}\n\nPakej bermula {harga}. Semak di sini 👉 {pautan}", null],
            [ThreadsPostType::Listing, "Vendor pilihan hari ini 💍\n\n{nama} ({kategori}, {negeri})\n\"{tagline}\"\n\nWhatsApp terus dari profil mereka: {pautan}", null],
            [ThreadsPostType::Listing, "Simpan untuk majlis nanti 📌\n\n{nama}\n📍 {negeri}\n🏷️ {kategori}\n\n{pautan}", null],

            [ThreadsPostType::Blog, "{tajuk}\n\n{ringkasan}\n\nBaca penuh 👇", null],
            [ThreadsPostType::Blog, "Bakal pengantin, ini untuk anda 📖\n\n{tajuk}\n\n{pautan}", null],

            [ThreadsPostType::Engagement, 'Majlis impian anda: siang atau malam?', ['Siang', 'Malam', 'Dua-dua 😆']],
            [ThreadsPostType::Engagement, 'Yang paling susah nak cari untuk majlis kahwin?', ['Dewan', 'Katering', 'Photographer', 'Mak andam']],
            [ThreadsPostType::Engagement, 'Bajet kahwin korang dalam range mana? Jujur je 👇', ['Bawah RM15k', 'RM15k-30k', 'RM30k-50k', 'Atas RM50k']],
            [ThreadsPostType::Engagement, 'Tema warna majlis pilihan?', ['Pastel', 'Earthy / sage', 'Putih & emas', 'Bold & terang']],
            [ThreadsPostType::Engagement, 'Kad kahwin zaman sekarang: digital atau cetak?', ['Digital je', 'Cetak je', 'Dua-dua']],
            [ThreadsPostType::Engagement, "Yang dah kahwin, apa satu benda korang menyesal TAK buat masa majlis dulu?\n\nShare untuk bakal pengantin 👇", null],
            [ThreadsPostType::Engagement, "Bakal pengantin 2027, angkat tangan 🙋‍♀️🙋‍♂️\n\nDah sampai mana persiapan? Dewan dah lock?", null],
            [ThreadsPostType::Engagement, "Hantaran paling unik pernah korang nampak?\n\nNak tengok kreativiti orang Malaysia 😄", null],
            [ThreadsPostType::Engagement, 'Majlis kecil-kecilan atau besar-besaran?', ['Kecil & intimate', 'Sederhana', 'Besar, jemput semua']],
            [ThreadsPostType::Engagement, "Satu lagu wajib main masa majlis korang?\n\nTulis kat bawah, kita buat playlist 🎶", null],

            [ThreadsPostType::Tip, "Tip persiapan kahwin #1\n\nLock dewan & tarikh dulu, baru vendor lain. Dewan popular penuh 8-12 bulan awal, terutama musim cuti sekolah.", null],
            [ThreadsPostType::Tip, "Sebelum bayar deposit mana-mana vendor, minta 3 benda:\n\n1. Sebut harga bertulis\n2. Apa yang termasuk & tak termasuk\n3. Polisi pembatalan\n\nSimpan semua dalam satu tempat.", null],
            [ThreadsPostType::Tip, "Pecahan bajet kahwin yang ramai guna:\n\n🍛 Katering 35-40%\n🏛️ Dewan 15-20%\n📸 Foto/video 10-15%\n💄 Andam & baju 10%\n🎁 Lain-lain bakinya\n\nLaraskan ikut keutamaan korang.", null],
            [ThreadsPostType::Tip, "Kira tetamu dulu, baru cari katering.\n\nHeadcount yang tepat boleh jimat ribuan ringgit. Catat RSVP sejak awal, jangan teka.", null],
            [ThreadsPostType::Tip, "Nak photographer yang sesuai? Minta tengok satu album penuh dari satu majlis, bukan highlight je.\n\nDari situ nampak konsisten ke tidak.", null],
            [ThreadsPostType::Tip, "3 bulan sebelum majlis:\n\n✅ Sahkan semua vendor\n✅ Hantar kad jemputan\n✅ Fitting baju\n✅ Rancang aturcara hari majlis\n\nJangan tinggal last minute 😅", null],
            [ThreadsPostType::Tip, "Seminggu sebelum majlis, hantar jadual hari kejadian kepada SEMUA vendor & PIC keluarga.\n\nSiapa datang pukul berapa, siapa contact person. Elak kelam-kabut pagi majlis.", null],
            [ThreadsPostType::Tip, "Tip jimat: majlis hari biasa atau waktu malam selalunya lebih murah untuk dewan & sesetengah vendor.\n\nTanya dulu, ramai yang ada harga off-peak.", null],
            [ThreadsPostType::Tip, "Jangan lupa urusan nikah: kursus kahwin, ujian HIV, borang permohonan nikah.\n\nMula awal sebab ada yang ambil masa berminggu-minggu.", null],
            [ThreadsPostType::Tip, "Simpan bajet kecemasan 5-10% daripada jumlah keseluruhan.\n\nSentiasa ada kos yang tak dijangka: tambahan tetamu, transport, tip vendor.", null],

            [ThreadsPostType::Promo, "Rancang kahwin tanpa pening 📋\n\nChecklist lengkap ikut fasa, bajet, timeline & senarai tetamu, semua dalam Neekah. Percuma untuk pasangan.\n\nneekah.my", null],
            [ThreadsPostType::Promo, "Cari vendor kahwin ikut negeri & kategori 💍\n\nTengok pakej, portfolio & review, lepas tu WhatsApp vendor terus.\n\nneekah.my", null],
            [ThreadsPostType::Promo, "Kad kahwin digital dengan RSVP sekali 💌\n\nTetamu sahkan kehadiran, anda nampak jumlah terus. Buat di Neekah.\n\nneekah.my", null],
            [ThreadsPostType::Promo, "Neekah Kenangan 📸\n\nTetamu imbas QR, gambar & video majlis terus masuk satu album untuk pengantin. Tak perlu kejar gambar dalam group WhatsApp lagi.\n\nneekah.my", null],
            [ThreadsPostType::Promo, "Vendor kahwin? Senaraikan bisnes anda di Neekah dan biar pasangan jumpa anda 🙌\n\nDaftar percuma: neekah.my", null],
        ];
    }
}

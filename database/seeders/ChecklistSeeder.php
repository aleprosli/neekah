<?php

namespace Database\Seeders;

use App\Casts\Translatable;
use App\Models\Category;
use App\Models\ChecklistItem;
use App\Models\ChecklistSection;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ChecklistSeeder extends Seeder
{
    /**
     * The master checklist from "Checklist Melangkah ke Alam Perkahwinan": the
     * eight phases a Malaysian couple actually walks through, from the first
     * family conversation to life after the akad. Admin edits these afterwards
     * at Admin -> Checklist; this is only what a fresh install starts with.
     *
     * `months_before` is how long before the event day the task is due, and a
     * null means the task has no deadline (everything after the wedding).
     * `category` links a task to a marketplace category so the couple gets a
     * "Cari vendor" link straight off their checklist.
     *
     * @return array<int, array{title: string, icon: string, note?: string, items: array<int, array{title: string, group?: string, category?: string, months_before?: int|null}>}>
     */
    public static function sections(): array
    {
        return [
            [
                'title' => ['ms' => 'Perancangan Awal', 'en' => 'Early Planning'],
                'icon' => '🗓️',
                'items' => [
                    ['title' => ['ms' => 'Bincang dengan pasangan & keluarga', 'en' => 'Talk it through with your partner & families'], 'months_before' => 12],
                    ['title' => ['ms' => 'Tetapkan tarikh nikah', 'en' => 'Set the date of the akad'], 'months_before' => 12],
                    ['title' => ['ms' => 'Tetapkan lokasi akad nikah', 'en' => 'Decide where the akad will be held'], 'months_before' => 12],
                    ['title' => ['ms' => 'Tentukan mas kahwin / hantaran', 'en' => 'Agree the mas kahwin / hantaran'], 'months_before' => 11],
                    ['title' => ['ms' => 'Tetapkan bajet perkahwinan', 'en' => 'Set the wedding budget'], 'months_before' => 11],
                    ['title' => ['ms' => 'Senaraikan tetamu', 'en' => 'Draw up the guest list'], 'months_before' => 10],
                    ['title' => ['ms' => 'Pilih tema / konsep majlis', 'en' => 'Choose a theme / concept'], 'months_before' => 10],
                    ['title' => ['ms' => 'Tempah venue', 'en' => 'Book the venue'], 'category' => 'venue', 'months_before' => 10],
                    ['title' => ['ms' => 'Tempah photographer', 'en' => 'Book a photographer'], 'category' => 'photography', 'months_before' => 8],
                    ['title' => ['ms' => 'Tempah videographer', 'en' => 'Book a videographer'], 'category' => 'videography', 'months_before' => 8],
                    ['title' => ['ms' => 'Tempah makeup artist', 'en' => 'Book a makeup artist'], 'category' => 'makeup', 'months_before' => 6],
                    ['title' => ['ms' => 'Tempah pelamin / dekorasi', 'en' => 'Book the pelamin / decoration'], 'category' => 'pelamin', 'months_before' => 7],
                    ['title' => ['ms' => 'Tempah katering', 'en' => 'Book the caterer'], 'category' => 'catering', 'months_before' => 9],
                    ['title' => ['ms' => 'Tempah baju nikah & sanding', 'en' => 'Order the akad & reception outfits'], 'category' => 'bridal', 'months_before' => 6],
                ],
            ],
            [
                'title' => ['ms' => 'Urusan Borang & Dokumen Nikah', 'en' => 'Marriage Forms & Documents'],
                'icon' => '📄',
                'note' => ['ms' => 'Prosedur, borang dan kaedah permohonan berbeza mengikut negeri dan keadaan pasangan. Sahkan senarai ini dengan pejabat agama negeri anda sebelum menghantar.', 'en' => 'The procedure, the forms and how you apply differ by state and by your own circumstances. Check this list with your state\'s religious office before you submit anything.'],
                'items' => [
                    ['title' => ['ms' => 'Kad pengenalan pengantin lelaki', 'en' => 'Groom\'s identity card'], 'group' => ['ms' => 'Dokumen Asas', 'en' => 'Core documents'], 'months_before' => 5],
                    ['title' => ['ms' => 'Kad pengenalan pengantin perempuan', 'en' => 'Bride\'s identity card'], 'group' => ['ms' => 'Dokumen Asas', 'en' => 'Core documents'], 'months_before' => 5],
                    ['title' => ['ms' => 'Salinan IC wali', 'en' => 'Copy of the wali\'s identity card'], 'group' => ['ms' => 'Dokumen Asas', 'en' => 'Core documents'], 'months_before' => 5],
                    ['title' => ['ms' => 'Salinan IC saksi', 'en' => 'Copies of the witnesses\' identity cards'], 'group' => ['ms' => 'Dokumen Asas', 'en' => 'Core documents'], 'months_before' => 5],
                    ['title' => ['ms' => 'Gambar passport jika diperlukan', 'en' => 'Passport photos, if required'], 'group' => ['ms' => 'Dokumen Asas', 'en' => 'Core documents'], 'months_before' => 5],
                    ['title' => ['ms' => 'Dokumen berkaitan status perkahwinan jika diperlukan', 'en' => 'Proof of marital status, if required'], 'group' => ['ms' => 'Dokumen Asas', 'en' => 'Core documents'], 'months_before' => 5],
                    ['title' => ['ms' => 'Daftar / isi permohonan kebenaran berkahwin', 'en' => 'Register / fill in the marriage permission application'], 'group' => ['ms' => 'Borang Kebenaran Berkahwin', 'en' => 'Marriage permission form'], 'months_before' => 4],
                    ['title' => ['ms' => 'Isi borang pihak lelaki', 'en' => 'Fill in the groom\'s side of the form'], 'group' => ['ms' => 'Borang Kebenaran Berkahwin', 'en' => 'Marriage permission form'], 'months_before' => 4],
                    ['title' => ['ms' => 'Isi borang pihak perempuan', 'en' => 'Fill in the bride\'s side of the form'], 'group' => ['ms' => 'Borang Kebenaran Berkahwin', 'en' => 'Marriage permission form'], 'months_before' => 4],
                    ['title' => ['ms' => 'Dapatkan pengesahan yang diperlukan', 'en' => 'Get the endorsements you need'], 'group' => ['ms' => 'Borang Kebenaran Berkahwin', 'en' => 'Marriage permission form'], 'months_before' => 4],
                    ['title' => ['ms' => 'Submit permohonan ke pejabat agama', 'en' => 'Submit the application to the religious office'], 'group' => ['ms' => 'Borang Kebenaran Berkahwin', 'en' => 'Marriage permission form'], 'months_before' => 3],
                    ['title' => ['ms' => 'Dapatkan kebenaran berkahwin', 'en' => 'Collect the marriage permission'], 'group' => ['ms' => 'Borang Kebenaran Berkahwin', 'en' => 'Marriage permission form'], 'months_before' => 2],
                ],
            ],
            [
                'title' => ['ms' => 'Kursus & Pemeriksaan Kesihatan', 'en' => 'Course & Health Screening'],
                'icon' => '🩺',
                'items' => [
                    ['title' => ['ms' => 'Daftar Kursus Pra Perkahwinan Islam', 'en' => 'Register for the pre-marriage course'], 'months_before' => 8],
                    ['title' => ['ms' => 'Hadir kursus', 'en' => 'Attend the course'], 'months_before' => 6],
                    ['title' => ['ms' => 'Dapatkan sijil kursus', 'en' => 'Collect the course certificate'], 'months_before' => 6],
                    ['title' => ['ms' => 'Dapatkan borang ujian HIV', 'en' => 'Get the HIV test form'], 'months_before' => 5],
                    ['title' => ['ms' => 'Buat Ujian Saringan HIV', 'en' => 'Take the HIV screening test'], 'months_before' => 4],
                    ['title' => ['ms' => 'Simpan keputusan ujian', 'en' => 'Keep the test result'], 'months_before' => 4],
                    ['title' => ['ms' => 'Pastikan keputusan masih dalam tempoh sah ketika permohonan', 'en' => 'Make sure the result is still valid when you apply'], 'months_before' => 3],
                ],
            ],
            [
                'title' => ['ms' => 'Urusan Wali', 'en' => 'The Wali'],
                'icon' => '👨‍👩‍👧',
                'items' => [
                    ['title' => ['ms' => 'Tentukan wali', 'en' => 'Establish who the wali is'], 'months_before' => 4],
                    ['title' => ['ms' => 'Pastikan wali memenuhi syarat', 'en' => 'Check the wali meets the conditions'], 'months_before' => 4],
                    ['title' => ['ms' => 'Sediakan salinan IC wali', 'en' => 'Prepare a copy of the wali\'s identity card'], 'months_before' => 3],
                    ['title' => ['ms' => 'Dapatkan pengesahan wali jika diperlukan', 'en' => 'Get the wali\'s endorsement, if required'], 'months_before' => 3],
                    ['title' => ['ms' => 'Jika melibatkan wali hakim, semak prosedur dengan pejabat agama', 'en' => 'If a wali hakim is involved, check the procedure with the religious office'], 'months_before' => 3],
                    ['title' => ['ms' => 'Selesaikan dokumen wali sebelum tarikh akad', 'en' => 'Settle the wali paperwork before the akad'], 'months_before' => 1],
                ],
            ],
            [
                'title' => ['ms' => 'Persediaan Pengantin', 'en' => 'Getting the Couple Ready'],
                'icon' => '👰',
                'items' => [
                    ['title' => ['ms' => 'Baju nikah', 'en' => 'Akad outfit'], 'group' => ['ms' => 'Pengantin Perempuan', 'en' => 'The bride'], 'category' => 'bridal', 'months_before' => 3],
                    ['title' => ['ms' => 'Tudung / veil', 'en' => 'Tudung / veil'], 'group' => ['ms' => 'Pengantin Perempuan', 'en' => 'The bride'], 'months_before' => 3],
                    ['title' => ['ms' => 'Makeup', 'en' => 'Makeup'], 'group' => ['ms' => 'Pengantin Perempuan', 'en' => 'The bride'], 'category' => 'makeup', 'months_before' => 3],
                    ['title' => ['ms' => 'Henna / inai', 'en' => 'Henna / inai'], 'group' => ['ms' => 'Pengantin Perempuan', 'en' => 'The bride'], 'months_before' => 1],
                    ['title' => ['ms' => 'Kasut', 'en' => 'Shoes'], 'group' => ['ms' => 'Pengantin Perempuan', 'en' => 'The bride'], 'months_before' => 2],
                    ['title' => ['ms' => 'Barang kemas jika diperlukan', 'en' => 'Jewellery, if needed'], 'group' => ['ms' => 'Pengantin Perempuan', 'en' => 'The bride'], 'months_before' => 2],
                    ['title' => ['ms' => 'Set dokumen nikah', 'en' => 'The marriage document set'], 'group' => ['ms' => 'Pengantin Perempuan', 'en' => 'The bride'], 'months_before' => 1],
                    ['title' => ['ms' => 'Baju Melayu / pakaian nikah', 'en' => 'Baju Melayu / akad outfit'], 'group' => ['ms' => 'Pengantin Lelaki', 'en' => 'The groom'], 'category' => 'bridal', 'months_before' => 3],
                    ['title' => ['ms' => 'Sampin', 'en' => 'Sampin'], 'group' => ['ms' => 'Pengantin Lelaki', 'en' => 'The groom'], 'months_before' => 2],
                    ['title' => ['ms' => 'Songkok', 'en' => 'Songkok'], 'group' => ['ms' => 'Pengantin Lelaki', 'en' => 'The groom'], 'months_before' => 2],
                    ['title' => ['ms' => 'Kasut pengantin lelaki', 'en' => 'Groom\'s shoes'], 'group' => ['ms' => 'Pengantin Lelaki', 'en' => 'The groom'], 'months_before' => 2],
                    ['title' => ['ms' => 'Mas kahwin', 'en' => 'Mas kahwin'], 'group' => ['ms' => 'Pengantin Lelaki', 'en' => 'The groom'], 'months_before' => 1],
                    ['title' => ['ms' => 'Cincin', 'en' => 'Rings'], 'group' => ['ms' => 'Pengantin Lelaki', 'en' => 'The groom'], 'months_before' => 2],
                    ['title' => ['ms' => 'Set dokumen nikah pihak lelaki', 'en' => 'The groom\'s marriage document set'], 'group' => ['ms' => 'Pengantin Lelaki', 'en' => 'The groom'], 'months_before' => 1],
                ],
            ],
            [
                'title' => ['ms' => 'Persediaan Akad Nikah', 'en' => 'Preparing for the Akad'],
                'icon' => '🕌',
                'items' => [
                    ['title' => ['ms' => 'Sahkan tarikh & masa akad', 'en' => 'Confirm the date & time of the akad'], 'months_before' => 1],
                    ['title' => ['ms' => 'Sahkan lokasi akad', 'en' => 'Confirm where the akad will be'], 'months_before' => 1],
                    ['title' => ['ms' => 'Sahkan jurunikah / kadi', 'en' => 'Confirm the jurunikah / kadi'], 'months_before' => 1],
                    ['title' => ['ms' => 'Pastikan wali hadir', 'en' => 'Make sure the wali will be there'], 'months_before' => 1],
                    ['title' => ['ms' => 'Pastikan 2 orang saksi hadir', 'en' => 'Make sure both witnesses will be there'], 'months_before' => 1],
                    ['title' => ['ms' => 'Sediakan mas kahwin', 'en' => 'Have the mas kahwin ready'], 'months_before' => 1],
                    ['title' => ['ms' => 'Sediakan dokumen asal', 'en' => 'Bring the original documents'], 'months_before' => 1],
                    ['title' => ['ms' => 'Sediakan dokumen kebenaran berkahwin', 'en' => 'Bring the marriage permission'], 'months_before' => 1],
                    ['title' => ['ms' => 'Pastikan pengantin faham lafaz akad', 'en' => 'Make sure the groom knows the lafaz akad'], 'months_before' => 1],
                    ['title' => ['ms' => 'Sediakan pen untuk tandatangan dokumen', 'en' => 'Bring a pen for signing'], 'months_before' => 1],
                    ['title' => ['ms' => 'Hadir awal pada hari akad', 'en' => 'Arrive early on the day'], 'group' => ['ms' => 'Hari Akad', 'en' => 'The day of the akad'], 'months_before' => 0],
                    ['title' => ['ms' => 'Bawa IC asal', 'en' => 'Bring the original identity cards'], 'group' => ['ms' => 'Hari Akad', 'en' => 'The day of the akad'], 'months_before' => 0],
                    ['title' => ['ms' => 'Bawa dokumen lengkap', 'en' => 'Bring the complete set of documents'], 'group' => ['ms' => 'Hari Akad', 'en' => 'The day of the akad'], 'months_before' => 0],
                    ['title' => ['ms' => 'Akad nikah', 'en' => 'The akad nikah'], 'group' => ['ms' => 'Hari Akad', 'en' => 'The day of the akad'], 'months_before' => 0],
                    ['title' => ['ms' => 'Tandatangan dokumen', 'en' => 'Sign the documents'], 'group' => ['ms' => 'Hari Akad', 'en' => 'The day of the akad'], 'months_before' => 0],
                    ['title' => ['ms' => 'Terima dokumen / sijil berkaitan perkahwinan', 'en' => 'Receive the marriage documents / certificate'], 'group' => ['ms' => 'Hari Akad', 'en' => 'The day of the akad'], 'months_before' => 0],
                ],
            ],
            [
                'title' => ['ms' => 'Persediaan Majlis', 'en' => 'Preparing the Reception'],
                'icon' => '🎉',
                'items' => [
                    ['title' => ['ms' => 'Confirm venue', 'en' => 'Confirm the venue'], 'category' => 'venue', 'months_before' => 2],
                    ['title' => ['ms' => 'Confirm katering', 'en' => 'Confirm the caterer'], 'category' => 'catering', 'months_before' => 2],
                    ['title' => ['ms' => 'Confirm photographer', 'en' => 'Confirm the photographer'], 'category' => 'photography', 'months_before' => 2],
                    ['title' => ['ms' => 'Confirm videographer', 'en' => 'Confirm the videographer'], 'category' => 'videography', 'months_before' => 2],
                    ['title' => ['ms' => 'Confirm makeup artist', 'en' => 'Confirm the makeup artist'], 'category' => 'makeup', 'months_before' => 2],
                    ['title' => ['ms' => 'Confirm pelamin', 'en' => 'Confirm the pelamin'], 'category' => 'pelamin', 'months_before' => 2],
                    ['title' => ['ms' => 'Confirm PA system', 'en' => 'Confirm the PA system'], 'category' => 'entertainment', 'months_before' => 2],
                    ['title' => ['ms' => 'Confirm emcee', 'en' => 'Confirm the emcee'], 'category' => 'emcee', 'months_before' => 2],
                    ['title' => ['ms' => 'Cetak kad / digital invitation', 'en' => 'Print cards / set up the digital invitation'], 'category' => 'invitation', 'months_before' => 3],
                    ['title' => ['ms' => 'Edarkan jemputan', 'en' => 'Send out the invitations'], 'category' => 'invitation', 'months_before' => 2],
                    ['title' => ['ms' => 'Buat seating arrangement', 'en' => 'Work out the seating'], 'months_before' => 1],
                    ['title' => ['ms' => 'Sediakan doorgift', 'en' => 'Prepare the doorgifts'], 'months_before' => 1],
                    ['title' => ['ms' => 'Sediakan meja hadiah', 'en' => 'Set up the gift table'], 'months_before' => 1],
                    ['title' => ['ms' => 'Sediakan buku tetamu', 'en' => 'Set up the guest book'], 'months_before' => 1],
                    ['title' => ['ms' => 'Final guest count', 'en' => 'Final guest count'], 'months_before' => 1],
                    ['title' => ['ms' => 'Sahkan jumlah tetamu dengan katering', 'en' => 'Confirm the headcount with the caterer'], 'category' => 'catering', 'months_before' => 1],
                    ['title' => ['ms' => 'Buat timeline majlis', 'en' => 'Build the timeline for the day'], 'months_before' => 1],
                    ['title' => ['ms' => 'Jelaskan baki bayaran semua vendor', 'en' => 'Settle the balance with every vendor'], 'months_before' => 1],
                    ['title' => ['ms' => 'Tempah kek kahwin', 'en' => 'Order the wedding cake'], 'category' => 'cake', 'months_before' => 3],
                    ['title' => ['ms' => 'Tempah hiburan atau live band', 'en' => 'Book entertainment or a live band'], 'category' => 'entertainment', 'months_before' => 3],
                ],
            ],
            [
                'title' => ['ms' => 'Selepas Nikah', 'en' => 'After the Wedding'],
                'icon' => '🏠',
                'items' => [
                    ['title' => ['ms' => 'Simpan sijil / dokumen perkahwinan', 'en' => 'Store the marriage certificate & documents'], 'months_before' => null],
                    ['title' => ['ms' => 'Semak status pendaftaran perkahwinan', 'en' => 'Check the marriage registration went through'], 'months_before' => null],
                    ['title' => ['ms' => 'Kemas kini maklumat berkaitan jika diperlukan', 'en' => 'Update your records where needed'], 'months_before' => null],
                    ['title' => ['ms' => 'Urus akaun / dokumen bersama', 'en' => 'Sort out joint accounts & paperwork'], 'months_before' => null],
                    ['title' => ['ms' => 'Rancang tempat tinggal', 'en' => 'Plan where you will live'], 'months_before' => null],
                    ['title' => ['ms' => 'Bincang kewangan suami isteri', 'en' => 'Talk through your finances together'], 'months_before' => null],
                    ['title' => ['ms' => 'Bincang pembahagian tanggungjawab', 'en' => 'Agree who does what'], 'months_before' => null],
                    ['title' => ['ms' => 'Rancang bajet bulanan', 'en' => 'Plan a monthly budget'], 'months_before' => null],
                    ['title' => ['ms' => 'Rancang perlindungan / takaful', 'en' => 'Arrange protection / takaful'], 'months_before' => null],
                    ['title' => ['ms' => 'Mulakan kehidupan sebagai suami isteri', 'en' => 'Start your life together'], 'months_before' => null],
                ],
            ],
        ];
    }

    /**
     * Idempotent: a section or an item already on the server keeps whatever an
     * admin has since done to it, and only what is missing is added. That is
     * what makes this safe to run on every deploy.
     */
    public function run(): void
    {
        /** @var Collection<string, int> $categories */
        $categories = Category::pluck('id', 'slug');

        foreach (self::sections() as $order => $section) {
            // Matched on the Malay title, not the whole value: the column holds
            // every language now, so comparing the full JSON would miss every
            // row that was seeded before English existed and duplicate the lot.
            $record = ChecklistSection::firstOrCreate(
                ['title->'.Locales::DEFAULT => $section['title'][Locales::DEFAULT]],
                [
                    'title' => $section['title'],
                    'icon' => $section['icon'],
                    'note' => $section['note'] ?? null,
                    'sort_order' => $order,
                ],
            );

            self::fillMissingLanguages($record, ['title' => $section['title'], 'note' => $section['note'] ?? null]);

            foreach ($section['items'] as $index => $item) {
                $row = ChecklistItem::firstOrCreate(
                    ['checklist_section_id' => $record->id, 'title->'.Locales::DEFAULT => $item['title'][Locales::DEFAULT]],
                    [
                        'title' => $item['title'],
                        'category_id' => isset($item['category']) ? $categories[$item['category']] ?? null : null,
                        'group' => $item['group'] ?? null,
                        'months_before' => $item['months_before'] ?? null,
                        'sort_order' => $index,
                    ],
                );

                self::fillMissingLanguages($row, ['title' => $item['title'], 'group' => $item['group'] ?? null]);
            }
        }
    }

    /**
     * Add the languages a row does not have yet, and touch nothing it does.
     *
     * A fresh install gets every language from here. An install that has been
     * running since before the site had two only has Malay, and needs the
     * English filling in — but an admin may have edited these since, and a
     * seeder has no business overwriting that.
     *
     * @param  array<string, array<string, string>|null>  $values
     */
    private static function fillMissingLanguages(Model $record, array $values): void
    {
        $missing = [];

        foreach ($values as $column => $translations) {
            if ($translations === null) {
                continue;
            }

            $have = Translatable::all($record, $column);
            $add = collect($translations)->reject(fn (string $text, string $code): bool => filled($have[$code] ?? null))->all();

            if ($add !== []) {
                $missing[$column] = $add;
            }
        }

        if ($missing !== []) {
            $record->forceFill($missing)->save();
        }
    }
}

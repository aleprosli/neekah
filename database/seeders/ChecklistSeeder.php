<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ChecklistItem;
use App\Models\ChecklistSection;
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
                'title' => 'Perancangan Awal',
                'icon' => '🗓️',
                'items' => [
                    ['title' => 'Bincang dengan pasangan & keluarga', 'months_before' => 12],
                    ['title' => 'Tetapkan tarikh nikah', 'months_before' => 12],
                    ['title' => 'Tetapkan lokasi akad nikah', 'months_before' => 12],
                    ['title' => 'Tentukan mas kahwin / hantaran', 'months_before' => 11],
                    ['title' => 'Tetapkan bajet perkahwinan', 'months_before' => 11],
                    ['title' => 'Senaraikan tetamu', 'months_before' => 10],
                    ['title' => 'Pilih tema / konsep majlis', 'months_before' => 10],
                    ['title' => 'Tempah venue', 'category' => 'venue', 'months_before' => 10],
                    ['title' => 'Tempah photographer', 'category' => 'photography', 'months_before' => 8],
                    ['title' => 'Tempah videographer', 'category' => 'videography', 'months_before' => 8],
                    ['title' => 'Tempah makeup artist', 'category' => 'makeup', 'months_before' => 6],
                    ['title' => 'Tempah pelamin / dekorasi', 'category' => 'pelamin', 'months_before' => 7],
                    ['title' => 'Tempah katering', 'category' => 'catering', 'months_before' => 9],
                    ['title' => 'Tempah baju nikah & sanding', 'category' => 'bridal', 'months_before' => 6],
                ],
            ],
            [
                'title' => 'Urusan Borang & Dokumen Nikah',
                'icon' => '📄',
                'note' => 'Prosedur, borang dan kaedah permohonan berbeza mengikut negeri dan keadaan pasangan. Sahkan senarai ini dengan pejabat agama negeri anda sebelum menghantar.',
                'items' => [
                    ['title' => 'Kad pengenalan pengantin lelaki', 'group' => 'Dokumen Asas', 'months_before' => 5],
                    ['title' => 'Kad pengenalan pengantin perempuan', 'group' => 'Dokumen Asas', 'months_before' => 5],
                    ['title' => 'Salinan IC wali', 'group' => 'Dokumen Asas', 'months_before' => 5],
                    ['title' => 'Salinan IC saksi', 'group' => 'Dokumen Asas', 'months_before' => 5],
                    ['title' => 'Gambar passport jika diperlukan', 'group' => 'Dokumen Asas', 'months_before' => 5],
                    ['title' => 'Dokumen berkaitan status perkahwinan jika diperlukan', 'group' => 'Dokumen Asas', 'months_before' => 5],
                    ['title' => 'Daftar / isi permohonan kebenaran berkahwin', 'group' => 'Borang Kebenaran Berkahwin', 'months_before' => 4],
                    ['title' => 'Isi borang pihak lelaki', 'group' => 'Borang Kebenaran Berkahwin', 'months_before' => 4],
                    ['title' => 'Isi borang pihak perempuan', 'group' => 'Borang Kebenaran Berkahwin', 'months_before' => 4],
                    ['title' => 'Dapatkan pengesahan yang diperlukan', 'group' => 'Borang Kebenaran Berkahwin', 'months_before' => 4],
                    ['title' => 'Submit permohonan ke pejabat agama', 'group' => 'Borang Kebenaran Berkahwin', 'months_before' => 3],
                    ['title' => 'Dapatkan kebenaran berkahwin', 'group' => 'Borang Kebenaran Berkahwin', 'months_before' => 2],
                ],
            ],
            [
                'title' => 'Kursus & Pemeriksaan Kesihatan',
                'icon' => '🩺',
                'items' => [
                    ['title' => 'Daftar Kursus Pra Perkahwinan Islam', 'months_before' => 8],
                    ['title' => 'Hadir kursus', 'months_before' => 6],
                    ['title' => 'Dapatkan sijil kursus', 'months_before' => 6],
                    ['title' => 'Dapatkan borang ujian HIV', 'months_before' => 5],
                    ['title' => 'Buat Ujian Saringan HIV', 'months_before' => 4],
                    ['title' => 'Simpan keputusan ujian', 'months_before' => 4],
                    ['title' => 'Pastikan keputusan masih dalam tempoh sah ketika permohonan', 'months_before' => 3],
                ],
            ],
            [
                'title' => 'Urusan Wali',
                'icon' => '👨‍👩‍👧',
                'items' => [
                    ['title' => 'Tentukan wali', 'months_before' => 4],
                    ['title' => 'Pastikan wali memenuhi syarat', 'months_before' => 4],
                    ['title' => 'Sediakan salinan IC wali', 'months_before' => 3],
                    ['title' => 'Dapatkan pengesahan wali jika diperlukan', 'months_before' => 3],
                    ['title' => 'Jika melibatkan wali hakim, semak prosedur dengan pejabat agama', 'months_before' => 3],
                    ['title' => 'Selesaikan dokumen wali sebelum tarikh akad', 'months_before' => 1],
                ],
            ],
            [
                'title' => 'Persediaan Pengantin',
                'icon' => '👰',
                'items' => [
                    ['title' => 'Baju nikah', 'group' => 'Pengantin Perempuan', 'category' => 'bridal', 'months_before' => 3],
                    ['title' => 'Tudung / veil', 'group' => 'Pengantin Perempuan', 'months_before' => 3],
                    ['title' => 'Makeup', 'group' => 'Pengantin Perempuan', 'category' => 'makeup', 'months_before' => 3],
                    ['title' => 'Henna / inai', 'group' => 'Pengantin Perempuan', 'months_before' => 1],
                    ['title' => 'Kasut', 'group' => 'Pengantin Perempuan', 'months_before' => 2],
                    ['title' => 'Barang kemas jika diperlukan', 'group' => 'Pengantin Perempuan', 'months_before' => 2],
                    ['title' => 'Set dokumen nikah', 'group' => 'Pengantin Perempuan', 'months_before' => 1],
                    ['title' => 'Baju Melayu / pakaian nikah', 'group' => 'Pengantin Lelaki', 'category' => 'bridal', 'months_before' => 3],
                    ['title' => 'Sampin', 'group' => 'Pengantin Lelaki', 'months_before' => 2],
                    ['title' => 'Songkok', 'group' => 'Pengantin Lelaki', 'months_before' => 2],
                    ['title' => 'Kasut pengantin lelaki', 'group' => 'Pengantin Lelaki', 'months_before' => 2],
                    ['title' => 'Mas kahwin', 'group' => 'Pengantin Lelaki', 'months_before' => 1],
                    ['title' => 'Cincin', 'group' => 'Pengantin Lelaki', 'months_before' => 2],
                    ['title' => 'Set dokumen nikah pihak lelaki', 'group' => 'Pengantin Lelaki', 'months_before' => 1],
                ],
            ],
            [
                'title' => 'Persediaan Akad Nikah',
                'icon' => '🕌',
                'items' => [
                    ['title' => 'Sahkan tarikh & masa akad', 'months_before' => 1],
                    ['title' => 'Sahkan lokasi akad', 'months_before' => 1],
                    ['title' => 'Sahkan jurunikah / kadi', 'months_before' => 1],
                    ['title' => 'Pastikan wali hadir', 'months_before' => 1],
                    ['title' => 'Pastikan 2 orang saksi hadir', 'months_before' => 1],
                    ['title' => 'Sediakan mas kahwin', 'months_before' => 1],
                    ['title' => 'Sediakan dokumen asal', 'months_before' => 1],
                    ['title' => 'Sediakan dokumen kebenaran berkahwin', 'months_before' => 1],
                    ['title' => 'Pastikan pengantin faham lafaz akad', 'months_before' => 1],
                    ['title' => 'Sediakan pen untuk tandatangan dokumen', 'months_before' => 1],
                    ['title' => 'Hadir awal pada hari akad', 'group' => 'Hari Akad', 'months_before' => 0],
                    ['title' => 'Bawa IC asal', 'group' => 'Hari Akad', 'months_before' => 0],
                    ['title' => 'Bawa dokumen lengkap', 'group' => 'Hari Akad', 'months_before' => 0],
                    ['title' => 'Akad nikah', 'group' => 'Hari Akad', 'months_before' => 0],
                    ['title' => 'Tandatangan dokumen', 'group' => 'Hari Akad', 'months_before' => 0],
                    ['title' => 'Terima dokumen / sijil berkaitan perkahwinan', 'group' => 'Hari Akad', 'months_before' => 0],
                ],
            ],
            [
                'title' => 'Persediaan Majlis',
                'icon' => '🎉',
                'items' => [
                    ['title' => 'Confirm venue', 'category' => 'venue', 'months_before' => 2],
                    ['title' => 'Confirm katering', 'category' => 'catering', 'months_before' => 2],
                    ['title' => 'Confirm photographer', 'category' => 'photography', 'months_before' => 2],
                    ['title' => 'Confirm videographer', 'category' => 'videography', 'months_before' => 2],
                    ['title' => 'Confirm makeup artist', 'category' => 'makeup', 'months_before' => 2],
                    ['title' => 'Confirm pelamin', 'category' => 'pelamin', 'months_before' => 2],
                    ['title' => 'Confirm PA system', 'category' => 'entertainment', 'months_before' => 2],
                    ['title' => 'Confirm emcee', 'category' => 'emcee', 'months_before' => 2],
                    ['title' => 'Cetak kad / digital invitation', 'category' => 'invitation', 'months_before' => 3],
                    ['title' => 'Edarkan jemputan', 'category' => 'invitation', 'months_before' => 2],
                    ['title' => 'Buat seating arrangement', 'months_before' => 1],
                    ['title' => 'Sediakan doorgift', 'months_before' => 1],
                    ['title' => 'Sediakan meja hadiah', 'months_before' => 1],
                    ['title' => 'Sediakan buku tetamu', 'months_before' => 1],
                    ['title' => 'Final guest count', 'months_before' => 1],
                    ['title' => 'Sahkan jumlah tetamu dengan katering', 'category' => 'catering', 'months_before' => 1],
                    ['title' => 'Buat timeline majlis', 'months_before' => 1],
                    ['title' => 'Jelaskan baki bayaran semua vendor', 'months_before' => 1],
                    ['title' => 'Tempah kek kahwin', 'category' => 'cake', 'months_before' => 3],
                    ['title' => 'Tempah hiburan atau live band', 'category' => 'entertainment', 'months_before' => 3],
                ],
            ],
            [
                'title' => 'Selepas Nikah',
                'icon' => '🏠',
                'items' => [
                    ['title' => 'Simpan sijil / dokumen perkahwinan', 'months_before' => null],
                    ['title' => 'Semak status pendaftaran perkahwinan', 'months_before' => null],
                    ['title' => 'Kemas kini maklumat berkaitan jika diperlukan', 'months_before' => null],
                    ['title' => 'Urus akaun / dokumen bersama', 'months_before' => null],
                    ['title' => 'Rancang tempat tinggal', 'months_before' => null],
                    ['title' => 'Bincang kewangan suami isteri', 'months_before' => null],
                    ['title' => 'Bincang pembahagian tanggungjawab', 'months_before' => null],
                    ['title' => 'Rancang bajet bulanan', 'months_before' => null],
                    ['title' => 'Rancang perlindungan / takaful', 'months_before' => null],
                    ['title' => 'Mulakan kehidupan sebagai suami isteri', 'months_before' => null],
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
            $record = ChecklistSection::firstOrCreate(
                ['title' => $section['title']],
                [
                    'icon' => $section['icon'],
                    'note' => $section['note'] ?? null,
                    'sort_order' => $order,
                ],
            );

            foreach ($section['items'] as $index => $item) {
                ChecklistItem::firstOrCreate(
                    ['checklist_section_id' => $record->id, 'title' => $item['title']],
                    [
                        'category_id' => isset($item['category']) ? $categories[$item['category']] ?? null : null,
                        'group' => $item['group'] ?? null,
                        'months_before' => $item['months_before'] ?? null,
                        'sort_order' => $index,
                    ],
                );
            }
        }
    }
}

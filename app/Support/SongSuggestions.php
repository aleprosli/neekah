<?php

namespace App\Support;

class SongSuggestions
{
    /**
     * The wedding songs Malaysian couples keep reaching for, offered on the
     * playlist page so nobody starts from an empty box. A suggestion only fills
     * the form in; the couple still decides which moment it belongs to.
     *
     * An artist is null where the song is known by its title alone.
     *
     * @return array<int, array{title: string, artist: string|null}>
     */
    public static function all(): array
    {
        return array_map(
            fn (array $song): array => ['title' => $song[0], 'artist' => $song[1] ?? null],
            [
                ['Menamakanmu', 'Dato\' Siti Nurhaliza'],
                ['Anugerah Terindah', 'Andmesh'],
                ['Hingga Tua Bersama', 'Rizky Febian'],
                ['Tuhan Jagakan Dia', 'Motif Band'],
                ['A Thousand Years', 'Christina Perri'],
                ['All of Me', 'John Legend'],
                ['Kau Tercipta', 'Lah Ahmad'],
                ['Hal Hebat', 'Govinda'],
                ['Sedetik Lebih', 'Anuar Zain'],
                ['Akad', 'Payung Teduh'],
                ['Kekal Bahagia', 'Ippo Hafiz'],
                ['Sempurna Waktu', 'Ir Radzi'],
                ['Kota Ini Tak Sama Tanpamu', 'Nadhif Basalamah'],
                ['Give Me Your Forever', 'Zack Tabudlo'],
                ['Sampai Tutup Usia', 'Angga Candra'],
                ['Say You Won\'t Let Go', 'James Arthur'],
                ['Cinta Bersatu', 'Liyana Jasmay'],
                ['Risk It All', 'Bruno Mars'],
                ['Sesungguhnya Aku', 'Alif Satar'],
                ['Peluk', 'Hael Husaini ft. Nadeera'],
                ['Kaulah Segalanya', 'Ruth Sahanaya'],
                ['Surat Cinta Untuk Starla', 'Virgoun'],
                ['Satu Saf Di Belakangku', 'Rizky Febian'],
                ['Sampai Ke Hari Tua', 'Aizat Amdan'],
                ['Lautan', 'Yuna'],
                ['Pujaanku', 'Masdo ft. Aisyah Aziz'],
                ['Keabadian Cinta', 'Anuar Zain'],
                ['Ku Akui', 'Hafiz'],
                ['Until I Found You', 'Stephen Sanchez'],
                ['You\'ll Be in My Heart', 'NIKI'],
                ['Young and Beautiful', 'Lana Del Rey'],
                ['Menang', 'Faizal Tahir'],
                ['Mencintaimu', 'Krisdayanti'],
                ['Jodoh Pasti Bertemu', 'Rizky Febian'],
                ['Katakan', 'Harris Baba'],
                ['Di Akhir Perang', 'Nadin Amizah'],
                ['Aku dan Dirimu', 'BCL ft. Ari Lasso'],
                ['Sentiasa', 'Firdaus Rahmat'],
                ['Pujaan Hati', 'Adira Suhaimi'],
                ['Perfect', 'Ed Sheeran'],
                ['Thinking Out Loud', 'Ed Sheeran'],
                ['Rusuk', 'Gery Gany'],
                ['Alamak', 'Rizky Febian ft. Adrian Khalif'],
                ['Nanti Kita Seperti Ini', 'Batas Senja'],
                ['Hari Ini', 'Dayang ft. Hael Husaini'],
                ['Masa Ini, Nanti dan Masa Indah Lainnya'],
                ['Untuk Mencintaimu', 'Seventeen'],
                ['Lagu Pernikahan Kita'],
                ['Manusia Paling Bahagia', 'Ghea Indrawari'],
                ['Penjaga Hati', 'Nadhif Basalamah'],
                ['Bergema Sampai Selamanya', 'Nadhif Basalamah'],
                ['Aku Memilihmu', 'Fabio Asher ft. Brisia Jodie'],
                ['Bukan Cinta Biasa', 'Afgan'],
                ['Dia', 'Sheila Majid'],
                ['Flora Cinta', 'Ayu Damit'],
                ['Hari Bahagia', 'Atta ft. Aurel'],
                ['My Love Mine All Mine', 'Mitski'],
                ['Nafas Cinta', 'Khai Bahar ft. Aina Abdul'],
                ['Just the Way You Are', 'Bruno Mars'],
                ['Cinta Sesungguhnya', 'Sabhi Saddi'],
                ['Makna Cinta', 'Rizky Febian'],
                ['Alangkah', 'Hael Husaini'],
                ['It\'s You', 'Sezairi'],
                ['One in a Million', 'Ne-Yo'],
                ['Little Things', 'One Direction'],
                ['Bermuara', 'Rizky Febian ft. Mahalini'],
                ['Can\'t Help Falling in Love', 'Elvis Presley'],
                ['Cinta Terakhir', 'Ari Lasso'],
                ['Nyaman', 'Andmesh'],
                ['Terima Kasih Cinta', 'Afgan'],
                ['Kau Ilhamku', 'Man Bai'],
                ['Kaulah Segalanya', 'Hazrul'],
                ['Only', 'Lee Hi'],
                ['Dealova', 'Once'],
                ['Kisah Cinta Kita', 'Hafiz Suip'],
                ['Berakhirlah Pencarianku', 'Hafiz Suip ft. Ernie Zakri'],
                ['Saat Bahagia', 'Ippo Hafiz'],
                ['Kisah Ku Inginkan', 'Dato\' Siti Nurhaliza ft. Judika'],
                ['Melamarmu', 'Badai Romantic Project'],
                ['Sampai Akhir', 'Judika'],
                ['Spring Snow', '10cm'],
                ['Tercipta Untukku', 'Ungu ft. Rossa'],
                ['Lover', 'Taylor Swift'],
                ['Bersamamu', 'Jaz'],
                ['Bukti', 'Virgoun'],
                ['Cinta Luar Biasa', 'Andmesh'],
            ],
        );
    }
}

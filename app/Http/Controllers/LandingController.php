<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Vendor;
use App\Support\ContactSettings;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    /**
     * The About page. Neekah is a network for now: couples find vendors here
     * and deal with them directly, and the platform takes no payment and no
     * commission. Nothing on this page may promise otherwise.
     */
    public function __invoke(Seo $seo, ContactSettings $contact): View
    {
        $seo->title(__('seo.landing.title'))
            ->description(__('seo.landing.description'));

        $categories = Category::active()->ordered()->get();

        return view('landing', [
            'flow' => $this->flow(),
            'categories' => $categories,
            'vendors' => Vendor::query()->approved()->with('category')->orderByDesc('score')->orderBy('id')->limit(6)->get(),
            'features' => $this->features(),
            'vendorBenefits' => $this->vendorBenefits(),
            'helpUrl' => $contact->whatsappUrl('Salam Neekah, saya sedang mencari vendor untuk majlis saya. Boleh bantu?'),
        ]);
    }

    /**
     * @return array<int, array{label: string, description: string}>
     */
    private function flow(): array
    {
        return [
            ['label' => 'Rancang', 'description' => 'Cipta majlis & ikut checklist'],
            ['label' => 'Cari', 'description' => 'Vendor ikut kategori, lokasi & bajet'],
            ['label' => 'Hubungi', 'description' => 'WhatsApp vendor terus dari profil'],
            ['label' => 'Deal', 'description' => 'Bincang & bayar terus dengan vendor'],
            ['label' => 'Jemput', 'description' => 'Kongsi kad digital dengan tetamu'],
            ['label' => 'Raikan', 'description' => 'Nikmati hari bahagia anda'],
        ];
    }

    /**
     * @return array<int, array{title: string, description: string, icon: string}>
     */
    private function features(): array
    {
        return [
            ['icon' => '🔎', 'title' => 'Cari & hubungi vendor terus', 'description' => 'Senarai vendor ikut kategori dan lokasi. Lihat pakej dan portfolio, kemudian WhatsApp mereka terus. Tiada orang tengah.'],
            ['icon' => '✉️', 'title' => 'Kad jemputan digital', 'description' => 'Pilih reka bentuk, isi butiran majlis dan kongsi dengan satu pautan. Tetamu boleh RSVP terus dari kad.'],
            ['icon' => '📋', 'title' => 'Checklist persiapan', 'description' => 'Langkah demi langkah, termasuk borang nikah, kursus dan urusan wali. Tandakan bersama pasangan.'],
            ['icon' => '💰', 'title' => 'Bajet majlis', 'description' => 'Pecahkan bajet ikut kategori dan tahu baki anda setiap masa.'],
            ['icon' => '🗓️', 'title' => 'Timeline hari majlis', 'description' => 'Susun perjalanan hari majlis, dari makeup pagi hingga majlis tamat.'],
            ['icon' => '👥', 'title' => 'Senarai tetamu', 'description' => 'Urus tetamu, kumpulan dan jawapan RSVP di satu tempat.'],
        ];
    }

    /**
     * What a vendor gets from being listed. Only what is true today: no
     * commission and no payment through Neekah, so nothing here is about
     * bookings recorded or payments made on the platform.
     *
     * @return array<int, string>
     */
    private function vendorBenefits(): array
    {
        return [
            'Senarai dan profil perniagaan, percuma',
            'Pengantin WhatsApp anda terus',
            'Pakej, harga dan portfolio dipaparkan',
            'Kalendar tarikh yang sudah penuh',
            'Enquiry pengantin dalam satu tempat',
        ];
    }
}

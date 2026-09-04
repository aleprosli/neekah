<?php

namespace App\Http\Controllers;

use App\Support\DemoCatalogue;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function __construct(private DemoCatalogue $catalogue) {}

    /**
     * Show the public landing page with placeholder marketplace data.
     */
    public function __invoke(): View
    {
        return view('landing', [
            'flow' => $this->flow(),
            'categories' => $this->catalogue->categories(),
            'vendors' => $this->catalogue->featured(),
            'features' => $this->features(),
            'vendorPoints' => $this->vendorPoints(),
        ]);
    }

    /**
     * @return array<int, array{label: string, description: string}>
     */
    private function flow(): array
    {
        return [
            ['label' => 'Plan', 'description' => 'Cipta wedding project & checklist'],
            ['label' => 'Discover', 'description' => 'Cari vendor ikut lokasi & bajet'],
            ['label' => 'Book', 'description' => 'Tempah pakej terus dalam platform'],
            ['label' => 'Pay', 'description' => 'Bayar deposit & baki dengan selamat'],
            ['label' => 'Manage', 'description' => 'Urus timeline, bajet & vendor'],
            ['label' => 'Celebrate', 'description' => 'Nikmati hari bahagia anda'],
        ];
    }

    /**
     * @return array<int, array{title: string, description: string, icon: string}>
     */
    private function features(): array
    {
        return [
            ['icon' => '📋', 'title' => 'Wedding Planner & Checklist', 'description' => 'Cipta wedding project, tetapkan tarikh, lokasi dan bajet. Sistem sediakan checklist lengkap dan jejak progress anda.'],
            ['icon' => '🗓️', 'title' => 'Wedding Timeline', 'description' => 'Susun perjalanan majlis dari makeup pagi hingga majlis tamat. Setiap vendor nampak slot mereka sendiri.'],
            ['icon' => '💰', 'title' => 'Budget Management', 'description' => 'Pecahkan bajet ikut kategori, bandingkan budget lawan actual, dan tahu baki anda setiap masa.'],
            ['icon' => '🔒', 'title' => 'Booking & Payment Berpusat', 'description' => 'Deposit dan baki direkod dalam platform. Setiap transaksi dijejak, setiap booking disahkan.'],
            ['icon' => '⭐', 'title' => 'Verified Reviews', 'description' => 'Review hanya daripada pasangan yang benar-benar menempah. Tiada fake review.'],
            ['icon' => '🏆', 'title' => 'Vendor Ranking Sebenar', 'description' => 'Ranking berdasarkan booking selesai, completion rate dan response rate, bukan rating semata-mata.'],
        ];
    }

    /**
     * @return array<int, array{activity: string, points: string}>
     */
    private function vendorPoints(): array
    {
        return [
            ['activity' => 'Booking melalui platform', 'points' => '+100'],
            ['activity' => 'Deposit dibayar', 'points' => '+100'],
            ['activity' => 'Booking selesai', 'points' => '+150'],
            ['activity' => 'Full payment', 'points' => '+150'],
            ['activity' => 'Positive review', 'points' => '+20'],
            ['activity' => 'Fast response', 'points' => '+20'],
        ];
    }
}

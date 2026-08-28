<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OwnerOverviewController extends Controller
{
    /**
     * Render the owner overview shell.
     *
     * These values form a small presentation contract that can later be
     * replaced by authenticated owner and business context services.
     */
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Business $activeBusiness */
        $activeBusiness = $request->attributes->get('activeBusiness');
        $activeMemberships = $request->attributes->get('activeBusinessMemberships');
        $activeBusiness->loadMissing('invitationCode');

        $ownerInitials = Str::of($user->name)
            ->squish()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        $data = [
            'owner' => [
                'name' => $user->name,
                'initials' => $ownerInitials,
            ],
            'businesses' => $activeMemberships
                ->map(fn (BusinessMembership $membership): array => [
                    'id' => $membership->business_id,
                    'name' => $membership->business->name,
                    'location' => $membership->role === BusinessMembership::ROLE_OWNER ? 'Pemilik' : 'Karyawan',
                ])
                ->values()
                ->all(),
            'activeBusinessId' => $activeBusiness->id,
            'activeBusiness' => $activeBusiness,
            'overview' => [
                'dateLabel' => 'Kamis, 27 Agustus 2026',
                'headline' => 'Bisnis Anda tumbuh dengan baik hari ini.',
                'summary' => 'Pendapatan naik 12,4% dibandingkan bulan lalu, didorong oleh peningkatan transaksi pada akhir pekan.',
                'health' => [
                    'score' => 82,
                    'status' => 'Healthy',
                    'change' => '+6 poin dari bulan lalu',
                ],
            ],
            'metrics' => [
                ['label' => 'Pendapatan', 'value' => 'Rp24,8 jt', 'change' => '+12,4%', 'context' => 'dibanding bulan lalu', 'icon' => 'banknote', 'tone' => 'positive', 'trend' => [42, 48, 45, 54, 59, 57, 68]],
                ['label' => 'Transaksi', 'value' => '1.284', 'change' => '+8,2%', 'context' => '96 transaksi lebih banyak', 'icon' => 'receipt-text', 'tone' => 'positive', 'trend' => [34, 39, 37, 43, 41, 49, 54]],
                ['label' => 'Karyawan Aktif', 'value' => '8 / 9', 'change' => '89%', 'context' => '1 karyawan sedang cuti', 'icon' => 'user-round-check', 'tone' => 'neutral', 'trend' => [8, 8, 9, 8, 8, 8, 8]],
                ['label' => 'Rata-rata Pesanan', 'value' => 'Rp193 rb', 'change' => '+5,6%', 'context' => 'naik Rp10 rb per transaksi', 'icon' => 'shopping-basket', 'tone' => 'positive', 'trend' => [45, 44, 49, 51, 50, 55, 59]],
            ],
            'sales' => [
                'defaultPeriod' => '30d',
                'periods' => [
                    '7d' => [
                        'label' => '7 Hari',
                        'labels' => ['20 Agu', '21 Agu', '22 Agu', '23 Agu', '24 Agu', '25 Agu', '26 Agu'],
                        'revenue' => [3.1, 3.4, 2.9, 3.6, 4.4, 4.1, 3.3],
                        'transactions' => [154, 169, 148, 180, 221, 208, 204],
                        'revenueTotal' => 'Rp24,8 jt',
                        'revenueChange' => '+12,4%',
                        'transactionTotal' => '1.284',
                        'transactionChange' => '+8,2%',
                    ],
                    '30d' => [
                        'label' => '30 Hari',
                        'labels' => ['29 Jul', '5 Agu', '12 Agu', '19 Agu', '26 Agu'],
                        'revenue' => [4.2, 5.6, 5.1, 6.8, 3.1],
                        'transactions' => [210, 288, 265, 350, 171],
                        'revenueTotal' => 'Rp24,8 jt',
                        'revenueChange' => '+12,4%',
                        'transactionTotal' => '1.284',
                        'transactionChange' => '+8,2%',
                    ],
                    '3m' => [
                        'label' => '3 Bulan',
                        'labels' => ['Jun', 'Jul', 'Agu'],
                        'revenue' => [19.6, 22.1, 24.8],
                        'transactions' => [1042, 1187, 1284],
                        'revenueTotal' => 'Rp66,5 jt',
                        'revenueChange' => '+18,7%',
                        'transactionTotal' => '3.513',
                        'transactionChange' => '+14,1%',
                    ],
                    '1y' => [
                        'label' => '1 Tahun',
                        'labels' => ['Sep', 'Nov', 'Jan', 'Mar', 'Mei', 'Jul', 'Agu'],
                        'revenue' => [14.2, 15.8, 16.4, 18.9, 20.3, 22.1, 24.8],
                        'transactions' => [820, 874, 901, 1034, 1108, 1187, 1284],
                        'revenueTotal' => 'Rp218,6 jt',
                        'revenueChange' => '+26,8%',
                        'transactionTotal' => '11.462',
                        'transactionChange' => '+21,3%',
                    ],
                ],
            ],
            'insights' => [
                ['type' => 'Sales Growth', 'title' => 'Pertumbuhan penjualan tetap kuat', 'description' => 'Pendapatan meningkat 12,4% bulan ini. Produk kebutuhan rumah menjadi pendorong utamanya.', 'action' => 'Lihat tren penjualan', 'icon' => 'trending-up', 'tone' => 'positive'],
                ['type' => 'Inventory Alert', 'title' => 'Stok Minyak Goreng 2L menipis', 'description' => 'Dengan kecepatan penjualan saat ini, stok diperkirakan habis dalam 2 hari.', 'action' => 'Periksa persediaan', 'icon' => 'package-x', 'tone' => 'warning'],
                ['type' => 'Team Performance', 'title' => 'Produktivitas shift sore menurun', 'description' => 'Produktivitas shift sore turun 8% minggu ini, terutama antara pukul 16.00–18.00.', 'action' => 'Tinjau performa tim', 'icon' => 'users-round', 'tone' => 'info'],
                ['type' => 'Opportunity', 'title' => 'Siapkan stok tambahan untuk Jumat', 'description' => 'Penjualan hari Jumat biasanya 24% lebih tinggi. Prioritaskan stok lima produk terlaris.', 'action' => 'Lihat rekomendasi', 'icon' => 'lightbulb', 'tone' => 'opportunity'],
            ],
            'employees' => [
                ['rank' => 1, 'name' => 'Andi', 'initials' => 'AN', 'sales' => 'Rp12,4 jt', 'score' => 92, 'status' => 'Excellent', 'tone' => 'emerald'],
                ['rank' => 2, 'name' => 'Budi', 'initials' => 'BU', 'sales' => 'Rp11,1 jt', 'score' => 87, 'status' => 'Excellent', 'tone' => 'blue'],
                ['rank' => 3, 'name' => 'Siti', 'initials' => 'SI', 'sales' => 'Rp9,8 jt', 'score' => 84, 'status' => 'Good', 'tone' => 'violet'],
            ],
            'alerts' => [
                ['category' => 'Inventory', 'title' => 'Stok Minyak Goreng 2L hampir habis', 'description' => 'Tersisa 8 unit—cukup untuk sekitar 2 hari.', 'severity' => 'warning', 'icon' => 'package-x'],
                ['category' => 'Employee', 'title' => 'Keterlambatan meningkat 18%', 'description' => 'Empat keterlambatan tercatat selama bulan Agustus.', 'severity' => 'danger', 'icon' => 'clock-3'],
                ['category' => 'Sales', 'title' => 'Penjualan kemarin di bawah rata-rata', 'description' => 'Pendapatan 21% lebih rendah dari rata-rata 30 hari.', 'severity' => 'info', 'icon' => 'chart-no-axes-column-decreasing'],
            ],
            'aiSuggestions' => [
                'Mengapa penjualan saya turun minggu ini?',
                'Siapa karyawan dengan performa terbaik?',
                'Apa yang perlu saya prioritaskan hari ini?',
                'Prediksi penjualan bulan depan',
            ],
        ];

        $data['isLoading'] = $request->boolean('loading');

        if ($request->string('state')->toString() === 'empty') {
            $data['metrics'] = [];
            $data['sales']['periods'] = [];
            $data['insights'] = [];
            $data['employees'] = [];
            $data['alerts'] = [];
        }

        return view('dashboard.overview', $data);
    }
}

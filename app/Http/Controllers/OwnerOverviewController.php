<?php

namespace App\Http\Controllers;

use App\Actions\BuildSalesDashboardStatisticsAction;
use App\Actions\ImportSalesOrdersAction;
use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OwnerOverviewController extends Controller
{
    public function __invoke(Request $request, BuildSalesDashboardStatisticsAction $buildSalesStatistics): View
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Business $activeBusiness */
        $activeBusiness = $request->attributes->get('activeBusiness');
        $activeMemberships = $request->attributes->get('activeBusinessMemberships');
        $activeBusiness->loadMissing('invitationCode');

        $employeeMemberships = $activeBusiness->memberships()
            ->where('role', BusinessMembership::ROLE_EMPLOYEE)
            ->with('user:id,name,email')
            ->oldest('id')
            ->get();
        $activeEmployeeCount = $employeeMemberships
            ->where('status', BusinessMembership::STATUS_ACTIVE)
            ->count();
        $employeeCount = $employeeMemberships->count();
        $salesStatistics = $buildSalesStatistics->execute($activeBusiness);
        $todaySales = $salesStatistics['today'];
        $lastImportAt = $salesStatistics['last_import']['at'];
        $hasImportedSales = $lastImportAt !== null;
        $hasSalesToday = $todaySales['transactions'] > 0;

        $ownerInitials = Str::of($user->name)
            ->squish()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        $insights = [];

        if ($hasSalesToday) {
            $insights[] = [
                'type' => 'Penjualan Hari Ini',
                'title' => number_format($todaySales['transactions'], 0, ',', '.').' transaksi sudah tercatat',
                'description' => 'Nilai penjualan hari ini mencapai '.$this->formatRupiah($todaySales['revenue']).' dari '.number_format($todaySales['units'], 0, ',', '.').' produk.',
                'action' => 'Lihat tren penjualan',
                'icon' => 'trending-up',
                'tone' => 'positive',
            ];
        } elseif ($hasImportedSales) {
            $insights[] = [
                'type' => 'Penjualan Hari Ini',
                'title' => 'Belum ada transaksi bertanggal hari ini',
                'description' => 'File terakhir sudah diproses, tetapi belum memuat pesanan dengan tanggal hari ini.',
                'action' => 'Periksa file terbaru',
                'icon' => 'file-chart-column',
                'tone' => 'info',
            ];
        }

        $insights[] = [
            'type' => 'Tim',
            'title' => "{$activeEmployeeCount} dari {$employeeCount} karyawan aktif",
            'description' => $employeeCount > 0
                ? 'Status dihitung langsung dari keanggotaan karyawan pada usaha aktif.'
                : 'Belum ada karyawan yang bergabung melalui Kode Usaha.',
            'action' => 'Tinjau anggota tim',
            'icon' => 'users-round',
            'tone' => 'info',
        ];

        $alerts = [];

        if (! $hasImportedSales) {
            $alerts[] = [
                'category' => 'Data Penjualan',
                'title' => 'Data penjualan belum diunggah',
                'description' => 'Minta karyawan mengunggah file pesanan dari TikTok Seller atau Shopee agar statistik mulai ditampilkan.',
                'severity' => 'warning',
                'icon' => 'file-chart-column',
            ];
        } elseif (! $lastImportAt->clone()->setTimezone(ImportSalesOrdersAction::SALES_TIMEZONE)->isToday()) {
            $alerts[] = [
                'category' => 'Data Penjualan',
                'title' => 'Data belum diperbarui hari ini',
                'description' => 'File terakhir yang diproses adalah '.$salesStatistics['last_import']['file_name'].'.',
                'severity' => 'info',
                'icon' => 'file-chart-column',
            ];
        }

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
                'dateLabel' => $salesStatistics['date_label'],
                'headline' => $hasSalesToday
                    ? 'Penjualan hari ini sudah tercatat.'
                    : ($hasImportedSales ? 'Belum ada penjualan tercatat hari ini.' : 'Mulai pantau penjualan usaha Anda.'),
                'summary' => $hasSalesToday
                    ? number_format($todaySales['transactions'], 0, ',', '.').' transaksi menghasilkan '.$this->formatRupiah($todaySales['revenue']).' dari '.number_format($todaySales['units'], 0, ',', '.').' produk terjual.'
                    : ($hasImportedSales
                        ? 'Statistik akan langsung berubah ketika file pesanan terbaru memuat pesanan hari ini.'
                        : 'Karyawan dapat mengunggah file pesanan TikTok Seller atau Shopee dan statistik akan langsung tersedia di sini.'),
                'signals' => [
                    [
                        'label' => 'Transaksi hari ini',
                        'value' => number_format($todaySales['transactions'], 0, ',', '.'),
                        'icon' => 'receipt-text',
                    ],
                    [
                        'label' => 'Produk terjual',
                        'value' => number_format($todaySales['units'], 0, ',', '.'),
                        'icon' => 'shopping-basket',
                    ],
                ],
                'health' => [
                    'hasData' => $hasImportedSales,
                    'value' => number_format($todaySales['transactions'], 0, ',', '.'),
                    'valueLabel' => 'transaksi hari ini',
                    'status' => $hasImportedSales ? 'Data penjualan tersedia' : 'Menunggu unggahan',
                    'change' => $salesStatistics['last_import']['label'],
                ],
            ],
            'metrics' => $salesStatistics['metrics'],
            'sales' => $salesStatistics['sales'],
            'insights' => $insights,
            'employees' => $employeeMemberships
                ->map(function (BusinessMembership $membership, int $index): array {
                    $initials = Str::of($membership->user->name)
                        ->squish()
                        ->explode(' ')
                        ->filter()
                        ->take(2)
                        ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
                        ->implode('');

                    return [
                        'membershipId' => $membership->id,
                        'name' => $membership->user->name,
                        'initials' => $initials,
                        'email' => $membership->user->email,
                        'joinedAt' => $membership->created_at?->format('d/m/Y') ?? '—',
                        'status' => $membership->status === BusinessMembership::STATUS_ACTIVE ? 'Aktif' : 'Nonaktif',
                        'isActive' => $membership->status === BusinessMembership::STATUS_ACTIVE,
                        'tone' => ['emerald', 'blue', 'violet'][$index % 3],
                    ];
                })
                ->values()
                ->all(),
            'alerts' => $alerts,
            'aiSuggestions' => [
                'Bagaimana penjualan saya hari ini?',
                'Bagaimana tren penjualan tujuh hari terakhir?',
                'Siapa karyawan dengan tugas paling banyak?',
                'Apa yang perlu saya prioritaskan hari ini?',
            ],
            'lastUpdatedLabel' => $salesStatistics['last_import']['label'],
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

    private function formatRupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}

<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\SalesOrder;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\TaskOccurrenceStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class AnswerBusinessInsightAction
{
    public function __construct(
        private GenerateTaskOccurrencesAction $generateOccurrences,
        private BuildProductSalesStatisticsAction $productStatistics,
        private BuildEmployeePerformanceStatisticsAction $employeeStatistics,
    ) {}

    /**
     * Only validated, allow-listed reports reach this action; the business comes from owner middleware.
     *
     * @param  array{name: string, arguments: array<string, mixed>}  $report
     * @return array{message: string, source: array{label: string, url: string}|null, sources?: list<array{label: string, url: string}>}
     */
    public function execute(Business $business, array $report): array
    {
        if ($report['name'] === 'report_bundle') {
            $replies = array_map(fn (array $item): array => $this->execute($business, $item), $report['arguments']['reports']);
            $sources = collect($replies)->pluck('source')->filter()->unique('url')->values()->all();

            return [
                'message' => implode("\n\n──────────\n\n", array_column($replies, 'message')),
                'source' => $sources[0] ?? null,
                'sources' => $sources,
            ];
        }

        $arguments = $report['arguments'];

        return match ($report['name']) {
            'sales_summary' => $this->salesSummary($business, $arguments),
            'sales_comparison' => $this->salesComparison($business, $arguments),
            'product_ranking', 'product_comparison' => $this->productRanking($business, $arguments),
            'sales_breakdown' => $this->salesBreakdown($business, $arguments),
            'employee_performance' => $this->employeePerformance($business, $arguments),
            'data_availability' => $this->dataAvailability($arguments['topic']),
            'task_summary' => $this->taskSummary($business, $arguments),
            'nadi_help' => $this->help($arguments['topic']),
            default => [
                'message' => match ($arguments['reason'] ?? null) {
                    'unrelated' => 'Pertanyaan tersebut kurang relevan dengan Nadi. Saya hanya membantu seputar penjualan, produk, kinerja tugas karyawan, dan penggunaan fitur Nadi. Coba tanyakan: “Berapa penjualan hari ini?”',
                    'unsupported' => 'Pertanyaan ini belum dapat saya bantu. Saat ini saya dapat membaca penjualan, membandingkan periode, menganalisis produk terlaris dan kanal penjualan, menilai penyelesaian serta ketepatan waktu tugas karyawan, dan menjelaskan fitur Nadi untuk usaha aktif. Data stok, laba, prediksi, perubahan data, dan data usaha lain belum tersedia melalui chat.',
                    default => 'Bisa perjelas pertanyaan Anda tentang Nadi? Sebutkan penjualan, produk, atau kinerja tugas yang ingin diperiksa beserta periodenya, misalnya “Siapa yang belum menyelesaikan tugas hari ini?”',
                },
                'source' => null,
            ],
        };
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{message: string, source: array{label: string, url: string}}
     */
    private function salesSummary(Business $business, array $arguments): array
    {
        $statistics = $this->salesStatistics($business, $arguments['start_date'], $arguments['end_date'], $arguments['platform'] ?? 'all');
        $period = $this->periodLabel($arguments['start_date'], $arguments['end_date']);
        $message = "Penjualan {$business->name} pada {$period} (WIB):\n\n"
            .$this->platformNote($arguments)
            .'• Nilai penjualan bersih: '.$this->rupiah($statistics['revenue'])."\n"
            .'• Transaksi: '.$this->number($statistics['transactions'])."\n"
            .'• Produk terjual: '.$this->number($statistics['units'])." unit\n"
            .'• Rata-rata pesanan: '.$this->rupiah($statistics['average_order'])."\n\n"
            ."Pesanan dibatalkan tidak dihitung. Nilai bersih mengikuti data refund pada laporan penjualan.\n"
            .$this->importNote($business, $statistics['transactions'] === 0);

        return ['message' => $message, 'source' => $this->salesSource()];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{message: string, source: array{label: string, url: string}}
     */
    private function salesComparison(Business $business, array $arguments): array
    {
        $current = $this->salesStatistics($business, $arguments['start_date'], $arguments['end_date'], $arguments['platform'] ?? 'all');
        $previous = $this->salesStatistics($business, $arguments['comparison_start_date'], $arguments['comparison_end_date'], $arguments['platform'] ?? 'all');
        $difference = $current['revenue'] - $previous['revenue'];
        $direction = $difference > 0 ? 'Naik' : ($difference < 0 ? 'Turun' : 'Tetap');
        $change = $previous['revenue'] === 0
            ? 'Persentase perubahan tidak dihitung karena penjualan pembanding Rp0.'
            : 'Perubahan: '.($difference > 0 ? '+' : '').number_format($difference / $previous['revenue'] * 100, 1, ',', '.').'%.';

        return [
            'message' => "Perbandingan penjualan {$business->name} (WIB):\n\n"
                .$this->platformNote($arguments)
                .'• '.$this->periodLabel($arguments['start_date'], $arguments['end_date']).': '.$this->rupiah($current['revenue'])
                .' dari '.$this->number($current['transactions'])." transaksi.\n"
                .'• '.$this->periodLabel($arguments['comparison_start_date'], $arguments['comparison_end_date']).': '.$this->rupiah($previous['revenue'])
                .' dari '.$this->number($previous['transactions'])." transaksi.\n\n"
                .$direction.' '.$this->rupiah(abs($difference)).'. '.$change."\n"
                ."Nilai bersih mengikuti laporan penjualan; pesanan dibatalkan tidak dihitung.\n"
                .$this->importNote($business, $current['transactions'] === 0 || $previous['transactions'] === 0),
            'source' => $this->salesSource(),
        ];
    }

    /**
     * @return array{revenue: int, transactions: int, units: int, average_order: int}
     */
    private function salesStatistics(Business $business, string $start, string $end, string $platform = 'all'): array
    {
        $statistics = $this->salesQuery($business, $start, $end, $platform)
            ->where('is_cancelled', false)
            ->toBase()
            ->selectRaw('COALESCE(SUM(net_sales_amount), 0) as revenue, COUNT(*) as transactions, COALESCE(SUM(quantity), 0) as units')
            ->first();
        $revenue = (int) $statistics->revenue;
        $transactions = (int) $statistics->transactions;

        return [
            'revenue' => $revenue,
            'transactions' => $transactions,
            'units' => (int) $statistics->units,
            'average_order' => $transactions > 0 ? (int) round($revenue / $transactions) : 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{message: string, source: array{label: string, url: string}}
     */
    private function taskSummary(Business $business, array $arguments): array
    {
        $date = $arguments['date'];
        $status = $arguments['status'];

        if ($date === CarbonImmutable::now(Task::TIMEZONE)->toDateString()) {
            $this->generateOccurrences->execute($date, business: $business);
        }

        $query = TaskOccurrence::query()
            ->whereHas('task', fn (Builder $query): Builder => $query->whereBelongsTo($business))
            ->whereDate('occurrence_date', $date);

        $counts = (clone $query)->toBase()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $overdue = (clone $query)->overdue()->count();

        match ($status) {
            'unfinished' => $query->whereNotIn('status', [TaskOccurrenceStatus::Completed->value, TaskOccurrenceStatus::Cancelled->value]),
            'overdue' => $query->overdue(),
            'all' => $query,
            default => $query->where('status', $status),
        };

        $total = (clone $query)->count();
        $people = (clone $query)->distinct()->count('user_id');
        $occurrences = $query->with('assignee:id,name')
            ->orderBy('due_at')->orderBy('id')
            ->limit(20)
            ->get(['id', 'user_id', 'title', 'due_at', 'status']);

        $filterLabel = match ($status) {
            'unfinished' => 'belum selesai',
            'overdue' => 'terlambat',
            'completed' => 'selesai',
            'pending' => 'belum dikerjakan',
            'in_progress' => 'sedang dikerjakan',
            default => 'tercatat',
        };
        $message = "Tugas {$business->name} pada ".$this->dateLabel($date)." (WIB):\n\n"
            .'• Belum dikerjakan: '.$this->number((int) ($counts['pending'] ?? 0))."\n"
            .'• Sedang dikerjakan: '.$this->number((int) ($counts['in_progress'] ?? 0))."\n"
            .'• Selesai: '.$this->number((int) ($counts['completed'] ?? 0))."\n"
            .'• Dibatalkan: '.$this->number((int) ($counts['cancelled'] ?? 0))."\n"
            .'• Terlambat: '.$this->number($overdue)." (bagian dari tugas yang belum selesai).\n\n";

        if ($total === 0) {
            $message .= "Tidak ada tugas {$filterLabel} yang tercatat pada tanggal ini.";
        } else {
            $message .= $this->number($total)." tugas {$filterLabel}, melibatkan ".$this->number($people)." karyawan:\n";
            foreach ($occurrences as $occurrence) {
                $message .= "\n• {$occurrence->assignee->name} — {$occurrence->title}"
                    .' ('.$occurrence->status->label().'; batas '.$occurrence->due_at->format('H:i').' WIB).';
            }

            if ($total > 20) {
                $message .= "\n\nMenampilkan 20 dari ".$this->number($total).' tugas. Buka Monitoring Tugas untuk daftar lengkap.';
            }
        }

        $message .= "\n\nStatus diperiksa pada ".now(Task::TIMEZONE)->format('d-m-Y H:i').' WIB.';

        return [
            'message' => $message,
            'source' => [
                'label' => 'Lihat monitoring tugas',
                'url' => route('task-occurrences.index', [
                    'date' => $date,
                    'status' => in_array($status, ['all', 'unfinished'], true) ? null : $status,
                ]),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{message: string, source: array{label: string, url: string}}
     */
    private function productRanking(Business $business, array $arguments): array
    {
        $groupBy = $arguments['group_by'] ?? 'product';
        $metric = $arguments['metric'];
        $direction = $arguments['direction'] ?? 'desc';
        $limit = (int) ($arguments['limit'] ?? 5);
        $search = trim($arguments['search'] ?? '');
        $current = $this->productStatistics->execute(
            $this->salesQuery($business, $arguments['start_date'], $arguments['end_date'], $arguments['platform'] ?? 'all')->where('is_cancelled', false),
            $groupBy, $search,
        );
        $comparison = isset($arguments['comparison_start_date']);
        $previous = $comparison ? $this->productStatistics->execute(
            $this->salesQuery($business, $arguments['comparison_start_date'], $arguments['comparison_end_date'], $arguments['platform'] ?? 'all')->where('is_cancelled', false),
            $groupBy, $search,
        ) : ['products' => [], 'missing_items' => 0];
        $products = $current['products'];

        foreach ($previous['products'] as $key => $product) {
            $products[$key] ??= [...$product, 'units' => 0, 'subtotal' => 0, 'orders' => 0];
        }

        $sortMetric = ($arguments['sort_by'] ?? 'current') === 'change' ? 'change' : $metric;

        foreach ($products as $key => $product) {
            $products[$key]['change'] = $product[$metric] - ($previous['products'][$key][$metric] ?? 0);
        }

        uasort($products, function (array $left, array $right) use ($sortMetric, $direction): int {
            $order = $left[$sortMetric] <=> $right[$sortMetric];

            return ($direction === 'asc' ? $order : -$order)
                ?: [$left['name'], $left['variation']] <=> [$right['name'], $right['variation']];
        });

        $metricLabel = match ($metric) {
            'subtotal' => 'subtotal produk',
            'orders' => 'jumlah pesanan berisi produk',
            default => 'unit terjual',
        };
        $message = ($comparison ? 'Perbandingan produk ' : 'Peringkat produk ').$business->name.' pada '
            .$this->periodLabel($arguments['start_date'], $arguments['end_date'])." (WIB):\n\n"
            .$this->platformNote($arguments)
            .'Urutan '.($sortMetric === 'change' ? 'selisih ' : '').$metricLabel.($direction === 'asc' ? ' terendah' : ' tertinggi').".\n";

        if ($search !== '') {
            $message .= 'Filter nama mengandung: '.$search.".\n";
        }

        if ($comparison) {
            $message .= 'Pembanding: '.$this->periodLabel($arguments['comparison_start_date'], $arguments['comparison_end_date']).".\n";
        }

        if ($products === []) {
            $message .= "\nBelum ada detail produk yang sesuai pada data impor untuk filter/periode ini.";
        } else {
            $totalMetric = array_sum(array_column($current['products'], $metric));

            foreach (array_slice($products, 0, $limit, true) as $key => $product) {
                $name = $product['name'].($groupBy === 'variant' ? ' — '.($product['variation'] ?: 'Tanpa variasi') : '');
                $message .= "\n• {$name}: ".$this->number($product['units']).' unit; subtotal '
                    .$this->rupiah($product['subtotal']).'; '.$this->number($product['orders']).' pesanan.';

                if ($metric !== 'orders' && $totalMetric > 0) {
                    $message .= ' Kontribusi '.$this->percentage($product[$metric] / $totalMetric * 100).' dari '.$metricLabel.' sesuai filter.';
                }

                if ($comparison) {
                    $before = $previous['products'][$key][$metric] ?? 0;
                    $difference = $product[$metric] - $before;
                    $format = fn (int $value): string => $metric === 'subtotal' ? $this->rupiah($value) : $this->number($value);
                    $message .= ' Pembanding '.$format($before).'; selisih '.($difference > 0 ? '+' : ($difference < 0 ? '-' : ''))
                        .$format(abs($difference)).'. '
                        .($before === 0 ? 'Persentase perubahan tidak dihitung karena pembanding nol/tidak tercatat.'
                            : 'Perubahan '.($difference > 0 ? '+' : '').$this->percentage($difference / $before * 100).'.');
                }
            }

            $message .= "\n\nMenampilkan ".min($limit, count($products)).' dari '.count($products).' produk/variasi tercatat.';
        }

        $message .= "\nPengelompokan mengikuti nama pada impor".($groupBy === 'product' ? '; variasi dengan nama produk yang sama digabung.' : ' dan nama variasi.')
            ."\nPesanan dibatalkan tidak dihitung. Subtotal item bukan omzet bersih atau laba. Refund tercatat per pesanan sehingga belum dapat dialokasikan ke produk; unit mengikuti kuantitas impor, belum dikurangi retur."
            ."\nProduk yang tidak pernah muncul dalam impor tidak dapat dinyatakan tidak laku.";

        if ($current['missing_items'] + $previous['missing_items'] > 0) {
            $message .= "\nAda detail item kosong/tidak lengkap yang dilewati; peringkat belum mencakup seluruh data.";
        }

        $message .= "\n".$this->importNote($business, $current['products'] === [] || ($comparison && $previous['products'] === []));

        return ['message' => $message, 'source' => $this->salesSource()];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{message: string, source: array{label: string, url: string}}
     */
    private function salesBreakdown(Business $business, array $arguments): array
    {
        $groupBy = $arguments['group_by'];
        $column = match ($groupBy) {
            'day' => 'ordered_on',
            'platform' => 'platform',
            'purchase_channel' => 'purchase_channel',
            'order_channel' => 'order_channel',
        };
        $status = $arguments['status'] ?? 'valid';
        $metric = $arguments['metric'] ?? 'revenue';
        $limit = (int) ($arguments['limit'] ?? ($groupBy === 'day' ? 20 : 5));
        $query = $this->salesQuery($business, $arguments['start_date'], $arguments['end_date'], $arguments['platform'] ?? 'all');

        match ($status) {
            'valid' => $query->where('is_cancelled', false),
            'cancelled' => $query->where('is_cancelled', true),
            'refunded' => $query->where('refund_amount', '>', 0),
            default => $query,
        };

        $groups = $query->toBase()->select($column)
            ->selectRaw('SUM(net_sales_amount) as revenue, COUNT(*) as transactions, SUM(quantity) as units, SUM(refund_amount) as refunds')
            ->groupBy($column)->get();
        $chronological = $groupBy === 'day' && ! isset($arguments['metric']) && ! isset($arguments['direction']);
        $sorted = $groups->sort(function (object $left, object $right) use ($metric, $arguments, $chronological, $column): int {
            if ($chronological) {
                return strcmp((string) $right->{$column}, (string) $left->{$column});
            }

            $order = (int) $left->{$metric} <=> (int) $right->{$metric};

            return (($arguments['direction'] ?? 'desc') === 'asc' ? $order : -$order)
                ?: strcmp((string) $left->{$column}, (string) $right->{$column});
        })->take($limit);

        if ($chronological) {
            $sorted = $sorted->reverse();
        }

        $metricLabel = match ($metric) {
            'revenue' => 'nilai penjualan bersih',
            'transactions' => 'jumlah pesanan',
            'units' => 'unit terjual',
            'refunds' => 'nilai refund',
        };
        $message = 'Rincian penjualan '.$business->name.' pada '.$this->periodLabel($arguments['start_date'], $arguments['end_date'])." (WIB):\n\n"
            .$this->platformNote($arguments)
            .'Filter status: '.BuildOwnerSalesReportAction::STATUSES[$status].".\n"
            .($chronological ? "Urutan tanggal; menampilkan hari tercatat paling akhir.\n" : 'Urutan '.$metricLabel.(($arguments['direction'] ?? 'desc') === 'asc' ? ' terendah' : ' tertinggi').".\n");

        foreach ($sorted as $row) {
            $label = $groupBy === 'day' ? $this->dateLabel(substr((string) $row->{$column}, 0, 10))
                : ($groupBy === 'platform' ? $this->platformLabel((string) $row->{$column}) : ($row->{$column} ?: 'Tidak diketahui'));
            $message .= "\n• {$label}: nilai bersih ".$this->rupiah((int) $row->revenue)
                .'; '.$this->number((int) $row->transactions).' pesanan; '.$this->number((int) $row->units)
                .' unit; refund '.$this->rupiah((int) $row->refunds).'.';
        }

        $message .= $groups->isEmpty() ? "\nBelum ada transaksi sesuai filter." : "\n\nMenampilkan ".$sorted->count().' dari '.$groups->count().' kelompok tercatat.';
        $message .= "\nTotal sesuai filter: ".$this->rupiah((int) $groups->sum('revenue')).' nilai bersih; '
            .$this->number((int) $groups->sum('transactions'))." pesanan.\n"
            ."Nilai bersih mengikuti laporan impor; refund ditampilkan sebagai informasi dan tidak dikurangkan lagi. Hari tanpa catatan belum membuktikan tidak ada penjualan. Pola ini belum membuktikan penyebab perubahan.\n"
            .$this->importNote($business, $groups->isEmpty());

        return ['message' => $message, 'source' => $this->salesSource()];
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array{message: string, source: array{label: string, url: string}}
     */
    private function employeePerformance(Business $business, array $arguments): array
    {
        $people = $this->employeeStatistics->execute($business, $arguments['start_date'], $arguments['end_date'], trim($arguments['employee'] ?? ''));
        $metric = $arguments['metric'];
        $limit = (int) ($arguments['limit'] ?? 5);
        $usesRate = in_array($metric, ['completion_rate', 'on_time_rate'], true);
        $rankValue = fn (array $person): float => $usesRate
            && ($person['assessed'] < BuildEmployeePerformanceStatisticsAction::MINIMUM_RATE_SAMPLE || $person[$metric] === null)
                ? -1 : (float) $person[$metric];
        usort($people, fn (array $left, array $right): int => $rankValue($right) <=> $rankValue($left)
            ?: [$left['name'], $left['user_id']] <=> [$right['name'], $right['user_id']]);
        $label = match ($metric) {
            'completed' => 'jumlah tugas selesai',
            'completion_rate' => 'tingkat penyelesaian',
            'on_time_rate' => 'ketepatan waktu tugas',
            'overdue' => 'jumlah tugas belum selesai yang melewati tenggat',
        };
        $message = 'Kinerja tugas '.$business->name.' pada '.$this->periodLabel($arguments['start_date'], $arguments['end_date'])." (WIB):\n\n"
            .'Diurutkan menurut '.$label." tertinggi.\n";

        if (! empty($arguments['employee'])) {
            $message .= 'Filter nama mengandung: '.trim($arguments['employee']).".\n";
        }

        if ($people === []) {
            $message .= "\nBelum ada tugas nonbatal sesuai periode/filter; ini bukan bukti karyawan tidak bekerja.";
        }

        foreach (array_slice($people, 0, $limit) as $person) {
            $message .= "\n• {$person['name']}: {$person['completed']} selesai dari {$person['assigned']} penugasan; {$person['assessed']} dapat dinilai."
                ."\n  Penyelesaian: ".$this->percentage($person['completion_rate'])
                ."; tepat waktu: {$person['on_time']}/{$person['assessed']} (".$this->percentage($person['on_time_rate']).').'
                ."\n  Selesai terlambat: {$person['late_completed']}; belum selesai melewati tenggat: {$person['overdue']}; belum jatuh tempo: {$person['upcoming']}.";

            if ($person['missing_completion_time'] > 0) {
                $message .= "\n  Waktu selesai tidak tercatat pada {$person['missing_completion_time']} tugas; rasio tepat waktu belum dapat dinilai.";
            }

            if ($person['assessed'] < BuildEmployeePerformanceStatisticsAction::MINIMUM_RATE_SAMPLE) {
                $message .= "\n  Sampel kurang dari 5 tugas yang dapat dinilai; belum cukup untuk peringkat rasio.";
            }
        }

        $message .= "\n\nMenampilkan ".min($limit, count($people)).' dari '.count($people).' karyawan dengan tugas sesuai filter.'
            ."\nPeriode mengacu pada tanggal penugasan, dengan status saat ini. Tugas batal dikecualikan. Penyebut rasio adalah tugas yang sudah jatuh tempo atau sudah selesai; tugas belum jatuh tempo yang belum selesai tidak menurunkan rasio."
            ."\nTepat waktu berarti selesai paling lambat pada tenggat WIB. Peringkat rasio memerlukan minimal 5 tugas yang dapat dinilai; jumlah ini hanya batas minimum sampel."
            ."\nIni indikator kinerja tugas, bukan penilaian sifat rajin/malas. Beban, kesulitan, kualitas hasil, jam kerja, dan absensi belum diperhitungkan. Riwayat yang belum tercatat tidak diasumsikan selesai atau absen."
            ."\nStatus diperiksa pada ".now(Task::TIMEZONE)->format('d-m-Y H:i').' WIB.';

        return ['message' => $message, 'source' => ['label' => 'Lihat monitoring tugas', 'url' => route('task-occurrences.index', ['date' => $arguments['end_date']])]];
    }

    /**
     * @return array{message: string, source: null}
     */
    private function dataAvailability(string $topic): array
    {
        return [
            'message' => match ($topic) {
                'stock' => 'Stok aktual belum tersedia melalui chat karena belum ada pencatatan persediaan dan mutasi stok. Unit terjual tidak menentukan sisa stok. Saya dapat membantu melihat produk terlaris dan tren penjualannya.',
                'profit' => 'Laba belum tersedia melalui chat karena HPP, biaya operasional, dan biaya lain belum dicatat lengkap. Omzet bersih setelah refund bukan laba. Saya dapat menampilkan penjualan bersih dan subtotal produk.',
                'forecast' => 'Prediksi belum tersedia melalui chat. Diperlukan riwayat yang cukup dan lengkap serta metode prediksi yang diuji terhadap data historis. Saya dapat menampilkan tren dan perbandingan periode sebagai dasar analisis.',
                'attendance' => 'Absensi dan jam kerja belum tersedia melalui chat. Saya dapat menampilkan penyelesaian, ketepatan waktu, dan keterlambatan tugas; data tersebut tidak membuktikan kehadiran atau sifat pribadi karyawan.',
            },
            'source' => null,
        ];
    }

    private function salesQuery(Business $business, string $start, string $end, string $platform = 'all'): Builder
    {
        return SalesOrder::query()->whereBelongsTo($business)
            ->whereBetween('ordered_on', [$start.' 00:00:00', $end.' 23:59:59'])
            ->when($platform !== 'all', fn (Builder $query): Builder => $query->where('platform', $platform));
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function platformNote(array $arguments): string
    {
        return 'Platform: '.$this->platformLabel($arguments['platform'] ?? 'all').".\n";
    }

    private function platformLabel(string $platform): string
    {
        return match ($platform) {
            'tiktok' => 'TikTok',
            'shopee' => 'Shopee',
            'all' => 'Semua platform',
            default => $platform,
        };
    }

    private function percentage(?float $value): string
    {
        return $value === null ? 'belum dapat dinilai' : number_format($value, 1, ',', '.').'%';
    }

    /**
     * @return array{message: string, source: array{label: string, url: string}|null}
     */
    private function help(string $topic): array
    {
        return [
            'message' => match ($topic) {
                'products' => 'Tanyakan produk terlaris, variasi favorit, kontribusi unit/subtotal, produk dengan penjualan paling sedikit, atau perubahan antarperiode. Anda dapat memfilter nama dan platform TikTok/Shopee. Produk dikelompokkan menurut nama pada impor; subtotal produk belum dikurangi refund pesanan dan bukan laba.',
                'employees' => 'Tanyakan karyawan dengan tugas selesai terbanyak, tingkat penyelesaian, ketepatan waktu, atau keterlambatan dalam periode maksimal 93 hari. Peringkat rasio memerlukan minimal 5 tugas yang dapat dinilai. Kualitas, kesulitan, jam kerja, dan absensi belum dicatat sehingga Nadi menjelaskan indikator tugas, bukan sifat pribadi.',
                'sales' => 'Buka menu Penjualan untuk melihat nilai penjualan, transaksi, dan produk terjual. Data berasal dari file TikTok CSV atau Shopee XLSX yang diunggah melalui akun karyawan. Di chat ini, Anda bisa menanyakan penjualan hari ini, kemarin, membandingkan dua periode, melihat tren per hari/platform/kanal serta pembatalan dan refund (maksimal 93 hari per periode).',
                'tasks' => 'Buka menu Tugas untuk membuat pekerjaan dan memilih karyawan penerima. Gunakan Monitoring Tugas untuk melihat status dan keterlambatan. Tugas harian memiliki progres terpisah untuk setiap hari dalam WIB. Coba tanyakan: “Siapa yang belum menyelesaikan tugas hari ini?”',
                default => 'Halo, saya asisten Nadi untuk usaha aktif Anda. Saya bisa membantu melihat penjualan, membandingkan periode, mencari produk/variasi terlaris, menganalisis tren dan platform, memantau tugas, membandingkan kinerja tugas karyawan, dan menjelaskan fitur Nadi. Beberapa laporan bisa digabung dalam satu pertanyaan. Stok, laba, prediksi, dan absensi memerlukan data tambahan. Coba tanyakan “Berapa penjualan hari ini?” atau “Tugas apa yang terlambat hari ini?”',
            },
            'source' => match ($topic) {
                'sales', 'products' => $this->salesSource(),
                'tasks', 'employees' => ['label' => 'Lihat monitoring tugas', 'url' => route('task-occurrences.index')],
                default => null,
            },
        ];
    }

    private function importNote(Business $business, bool $hasEmptyPeriod): string
    {
        $lastImport = $business->salesOrders()->latest('last_imported_at')->first(['last_imported_at']);

        if ($lastImport === null) {
            return 'Belum ada data penjualan yang diimpor untuk usaha ini. Nilai Rp0 di atas berarti belum ada transaksi tercatat, bukan kepastian tidak ada penjualan.';
        }

        return ($hasEmptyPeriod ? 'Ada periode tanpa transaksi tercatat; ini belum tentu berarti tidak ada penjualan. ' : '')
            .'Impor terakhir: '.$lastImport->last_imported_at->setTimezone(Task::TIMEZONE)->format('d-m-Y H:i')
            .' WIB. Angka hanya mencakup data yang sudah diimpor; kelengkapan periode belum dapat dipastikan.';
    }

    private function dateLabel(string $date): string
    {
        return CarbonImmutable::parse($date, Task::TIMEZONE)->locale('id')->translatedFormat('d F Y');
    }

    private function periodLabel(string $start, string $end): string
    {
        return $start === $end ? $this->dateLabel($start) : $this->dateLabel($start).' – '.$this->dateLabel($end);
    }

    private function number(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    private function rupiah(int $value): string
    {
        return 'Rp'.$this->number($value);
    }

    /**
     * @return array{label: string, url: string}
     */
    private function salesSource(): array
    {
        return ['label' => 'Lihat data penjualan', 'url' => route('sales.index')];
    }
}

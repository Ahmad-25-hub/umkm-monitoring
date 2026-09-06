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
    public function __construct(private GenerateTaskOccurrencesAction $generateOccurrences) {}

    /**
     * Only validated, allow-listed reports reach this action; the business comes from owner middleware.
     *
     * @param  array{name: string, arguments: array<string, string>}  $report
     * @return array{message: string, source: array{label: string, url: string}|null}
     */
    public function execute(Business $business, array $report): array
    {
        $arguments = $report['arguments'];

        return match ($report['name']) {
            'sales_summary' => $this->salesSummary($business, $arguments),
            'sales_comparison' => $this->salesComparison($business, $arguments),
            'task_summary' => $this->taskSummary($business, $arguments),
            'nadi_help' => $this->help($arguments['topic']),
            default => [
                'message' => match ($arguments['reason'] ?? null) {
                    'unrelated' => 'Pertanyaan tersebut kurang relevan dengan Nadi. Saya hanya membantu seputar penjualan, tugas karyawan, dan penggunaan fitur Nadi. Coba tanyakan: “Berapa penjualan hari ini?”',
                    'unsupported' => 'Pertanyaan ini belum dapat saya bantu. Saat ini saya dapat membaca penjualan, membandingkan periode, memantau tugas karyawan, dan menjelaskan fitur Nadi untuk usaha aktif. Data stok, laba, prediksi, perubahan data, dan data usaha lain belum tersedia melalui chat.',
                    default => 'Bisa perjelas pertanyaan Anda tentang Nadi? Sebutkan penjualan atau tugas yang ingin diperiksa beserta periodenya, misalnya “Siapa yang belum menyelesaikan tugas hari ini?”',
                },
                'source' => null,
            ],
        };
    }

    /**
     * @param  array<string, string>  $arguments
     * @return array{message: string, source: array{label: string, url: string}}
     */
    private function salesSummary(Business $business, array $arguments): array
    {
        $statistics = $this->salesStatistics($business, $arguments['start_date'], $arguments['end_date']);
        $period = $this->periodLabel($arguments['start_date'], $arguments['end_date']);
        $message = "Penjualan {$business->name} pada {$period} (WIB):\n\n"
            .'• Nilai penjualan bersih: '.$this->rupiah($statistics['revenue'])."\n"
            .'• Transaksi: '.$this->number($statistics['transactions'])."\n"
            .'• Produk terjual: '.$this->number($statistics['units'])." unit\n"
            .'• Rata-rata pesanan: '.$this->rupiah($statistics['average_order'])."\n\n"
            ."Pesanan dibatalkan tidak dihitung. Nilai bersih mengikuti data refund pada laporan penjualan.\n"
            .$this->importNote($business, $statistics['transactions'] === 0);

        return ['message' => $message, 'source' => $this->salesSource()];
    }

    /**
     * @param  array<string, string>  $arguments
     * @return array{message: string, source: array{label: string, url: string}}
     */
    private function salesComparison(Business $business, array $arguments): array
    {
        $current = $this->salesStatistics($business, $arguments['start_date'], $arguments['end_date']);
        $previous = $this->salesStatistics($business, $arguments['comparison_start_date'], $arguments['comparison_end_date']);
        $difference = $current['revenue'] - $previous['revenue'];
        $direction = $difference > 0 ? 'Naik' : ($difference < 0 ? 'Turun' : 'Tetap');
        $change = $previous['revenue'] === 0
            ? 'Persentase perubahan tidak dihitung karena penjualan pembanding Rp0.'
            : 'Perubahan: '.($difference > 0 ? '+' : '').number_format($difference / $previous['revenue'] * 100, 1, ',', '.').'%.';

        return [
            'message' => "Perbandingan penjualan {$business->name} (WIB):\n\n"
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
    private function salesStatistics(Business $business, string $start, string $end): array
    {
        $statistics = SalesOrder::query()
            ->whereBelongsTo($business)
            ->where('is_cancelled', false)
            ->whereBetween('ordered_on', [$start.' 00:00:00', $end.' 23:59:59'])
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
     * @param  array<string, string>  $arguments
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
     * @return array{message: string, source: array{label: string, url: string}|null}
     */
    private function help(string $topic): array
    {
        return [
            'message' => match ($topic) {
                'sales' => 'Buka menu Penjualan untuk melihat nilai penjualan, transaksi, dan produk terjual. Data berasal dari file TikTok CSV atau Shopee XLSX yang diunggah melalui akun karyawan. Di chat ini, Anda bisa menanyakan penjualan hari ini, kemarin, atau membandingkan dua periode (maksimal 93 hari per periode).',
                'tasks' => 'Buka menu Tugas untuk membuat pekerjaan dan memilih karyawan penerima. Gunakan Monitoring Tugas untuk melihat status dan keterlambatan. Tugas harian memiliki progres terpisah untuk setiap hari dalam WIB. Coba tanyakan: “Siapa yang belum menyelesaikan tugas hari ini?”',
                default => 'Halo, saya asisten Nadi untuk usaha aktif Anda. Saya bisa membantu melihat penjualan, membandingkan dua periode, memantau tugas karyawan, dan menjelaskan fitur Nadi. Coba tanyakan “Berapa penjualan hari ini?” atau “Tugas apa yang terlambat hari ini?”',
            },
            'source' => match ($topic) {
                'sales' => $this->salesSource(),
                'tasks' => ['label' => 'Lihat monitoring tugas', 'url' => route('task-occurrences.index')],
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

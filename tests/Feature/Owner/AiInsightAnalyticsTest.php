<?php

namespace Tests\Feature\Owner;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\SalesOrder;
use App\Models\Task;
use App\Models\TaskOccurrence;
use App\Models\User;
use App\Support\GroqInsightClient;
use App\TaskOccurrenceStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AiInsightAnalyticsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.groq', [
            'api_key' => 'test-key', 'base_url' => 'https://api.groq.com/openai/v1',
            'model' => 'openai/gpt-oss-20b', 'timeout' => 20,
        ]);
        Http::preventStrayRequests();
        $this->partialMock(GroqInsightClient::class)->shouldReceive('compose')
            ->andReturnUsing(fn (string $message, array $report, string $evidence): string => $evidence);
    }

    public function test_product_ranking_aggregates_items_and_distinct_orders_without_allocating_order_refunds(): void
    {
        $this->travelTo('2026-09-06 10:00:00');
        [$owner, $business] = $this->owner();
        SalesOrder::factory()->for($business)->create([
            'ordered_on' => '2026-09-06', 'refund_amount' => 10000, 'net_sales_amount' => 90000,
            'items' => [
                $this->item('Kopi', 2, 40000, 'Besar'),
                $this->item('Kopi', 1, 10000, 'Kecil'),
                $this->item('Teh', 1, 50000),
            ],
        ]);
        SalesOrder::factory()->for($business)->create([
            'ordered_on' => '2026-09-06', 'items' => [$this->item('Kopi', 1, 20000)],
        ]);
        foreach ([
            ['business_id' => $business->id, 'is_cancelled' => true, 'ordered_on' => '2026-09-06'],
            ['business_id' => $business->id, 'ordered_on' => '2026-09-05'],
            ['business_id' => Business::factory()->create()->id, 'ordered_on' => '2026-09-06'],
        ] as $attributes) {
            SalesOrder::factory()->create([...$attributes, 'items' => [$this->item('Produk rahasia', 900, 900000)]]);
        }
        $this->fakeReports([['name' => 'product_ranking', 'arguments' => $this->period() + ['metric' => 'units']]]);

        $response = $this->ask($owner, $business, 'Produk terlaris hari ini?');

        $response->assertOk()->assertJsonPath('messages.1.source.url', route('sales.index'));
        $answer = $response->json('messages.1.message');
        $this->assertStringContainsString('Kopi: 4 unit; subtotal Rp70.000; 2 pesanan.', $answer);
        $this->assertStringContainsString('Kontribusi 80,0%', $answer);
        $this->assertStringContainsString('Teh: 1 unit; subtotal Rp50.000; 1 pesanan.', $answer);
        $this->assertStringContainsString('Subtotal item bukan omzet bersih atau laba', $answer);
        $this->assertStringNotContainsString('Produk rahasia', $answer);
        Http::assertSent(fn (Request $request): bool => ! str_contains($request->body(), 'Kopi') && ! str_contains($request->body(), '70000'));
        Http::assertSentCount(1);
    }

    public function test_product_filters_variants_and_least_selling_order_are_applied_before_limiting(): void
    {
        $this->travelTo('2026-09-06 10:00:00');
        [$owner, $business] = $this->owner();
        SalesOrder::factory()->for($business)->create([
            'ordered_on' => '2026-09-06', 'platform' => 'shopee',
            'items' => [$this->item('Kopi', 2, 60000, 'Besar'), $this->item('Kopi', 1, 10000, 'Kecil'), $this->item('Teh', 1, 5000)],
        ]);
        SalesOrder::factory()->for($business)->create([
            'ordered_on' => '2026-09-06', 'platform' => 'tiktok', 'items' => [$this->item('Kopi', 20, 100000, 'Kecil')],
        ]);
        $this->fakeReports([['name' => 'product_ranking', 'arguments' => $this->period() + [
            'metric' => 'subtotal', 'direction' => 'asc', 'group_by' => 'variant',
            'search' => 'kOPi', 'platform' => 'shopee', 'limit' => 1,
        ]]]);

        $response = $this->ask($owner, $business, 'Variasi Kopi dengan penjualan terendah di Shopee?');

        $answer = $response->json('messages.1.message');
        $this->assertStringContainsString('Kopi — Kecil: 1 unit; subtotal Rp10.000; 1 pesanan.', $answer);
        $this->assertStringContainsString('Menampilkan 1 dari 2', $answer);
        $this->assertStringNotContainsString('Kopi — Besar:', $answer);
        $this->assertStringNotContainsString('Teh:', $answer);
        Http::assertSentCount(1);
    }

    public function test_empty_and_incomplete_product_items_are_explained_without_inventing_products(): void
    {
        $this->travelTo('2026-09-06 10:00:00');
        [$owner, $business] = $this->owner();
        SalesOrder::factory()->for($business)->create(['ordered_on' => '2026-09-06', 'items' => []]);
        SalesOrder::factory()->for($business)->create(['ordered_on' => '2026-09-06', 'items' => [['product_name' => 'Tidak lengkap']]]);
        $this->fakeReports([['name' => 'product_ranking', 'arguments' => $this->period() + ['metric' => 'orders']]]);

        $response = $this->ask($owner, $business, 'Produk terlaris?');

        $answer = $response->json('messages.1.message');
        $this->assertStringContainsString('Belum ada detail produk yang sesuai', $answer);
        $this->assertStringContainsString('detail item kosong/tidak lengkap yang dilewati', $answer);
        $this->assertStringNotContainsString('Tidak lengkap: 0 unit', $answer);
        Http::assertSentCount(1);
    }

    public function test_product_comparison_includes_disappearing_products_and_undefined_growth_from_zero(): void
    {
        $this->travelTo('2026-09-06 10:00:00');
        [$owner, $business] = $this->owner();
        SalesOrder::factory()->for($business)->create([
            'ordered_on' => '2026-09-05', 'items' => [$this->item('Kopi', 2, 20000), $this->item('Teh', 3, 30000)],
        ]);
        SalesOrder::factory()->for($business)->create([
            'ordered_on' => '2026-09-06', 'items' => [$this->item('Kopi', 4, 40000), $this->item('Susu', 1, 10000)],
        ]);
        $this->fakeReports([['name' => 'product_comparison', 'arguments' => $this->period() + [
            'metric' => 'units', 'comparison_start_date' => '2026-09-05', 'comparison_end_date' => '2026-09-05',
        ]]]);

        $response = $this->ask($owner, $business, 'Bagaimana perubahan penjualan tiap produk dibanding kemarin?');

        $answer = $response->json('messages.1.message');
        $this->assertStringContainsString('Pembanding 2; selisih +2. Perubahan +100,0%.', $answer);
        $this->assertStringContainsString('Teh: 0 unit', $answer);
        $this->assertStringContainsString('Pembanding 3; selisih -3. Perubahan -100,0%.', $answer);
        $this->assertStringContainsString('Persentase perubahan tidak dihitung karena pembanding nol/tidak tercatat.', $answer);
        Http::assertSentCount(1);
    }

    public function test_sales_breakdown_sums_all_groups_while_bounding_display_and_excluding_other_businesses(): void
    {
        $this->travelTo('2026-09-06 10:00:00');
        [$owner, $business] = $this->owner();
        SalesOrder::factory()->for($business)->create([
            'ordered_on' => '2026-09-06', 'platform' => 'tiktok', 'net_sales_amount' => 80000, 'refund_amount' => 20000, 'quantity' => 2,
        ]);
        SalesOrder::factory()->for($business)->create([
            'ordered_on' => '2026-09-06', 'platform' => 'shopee', 'net_sales_amount' => 50000, 'quantity' => 1,
        ]);
        SalesOrder::factory()->for($business)->create([
            'ordered_on' => '2026-09-06', 'is_cancelled' => true, 'net_sales_amount' => 500000,
        ]);
        SalesOrder::factory()->create(['ordered_on' => '2026-09-06', 'net_sales_amount' => 900000]);
        $this->fakeReports([['name' => 'sales_breakdown', 'arguments' => $this->period() + ['group_by' => 'platform', 'limit' => 1]]]);

        $response = $this->ask($owner, $business, 'Platform dengan penjualan tertinggi hari ini?');

        $answer = $response->json('messages.1.message');
        $this->assertStringContainsString('TikTok: nilai bersih Rp80.000; 1 pesanan; 2 unit; refund Rp20.000.', $answer);
        $this->assertStringContainsString('refund Rp20.000', $answer);
        $this->assertStringContainsString('Total sesuai filter: Rp130.000 nilai bersih; 2 pesanan.', $answer);
        $this->assertStringContainsString('Menampilkan 1 dari 2 kelompok', $answer);
        $this->assertStringNotContainsString('Shopee:', $answer);
        Http::assertSentCount(1);
    }

    #[DataProvider('salesFilters')]
    public function test_sales_breakdown_supports_refunds_cancellations_and_channels(string $status, string $groupBy, string $expected): void
    {
        $this->travelTo('2026-09-06 10:00:00');
        [$owner, $business] = $this->owner();
        SalesOrder::factory()->for($business)->create([
            'ordered_on' => '2026-09-06', 'net_sales_amount' => 80000, 'refund_amount' => 20000,
            'purchase_channel' => 'Live', 'order_channel' => 'Live streaming',
        ]);
        SalesOrder::factory()->for($business)->create([
            'ordered_on' => '2026-09-06', 'is_cancelled' => true, 'net_sales_amount' => 0, 'quantity' => 3,
            'purchase_channel' => 'Live', 'order_channel' => 'Live streaming',
        ]);
        $this->fakeReports([['name' => 'sales_breakdown', 'arguments' => $this->period() + [
            'status' => $status, 'group_by' => $groupBy,
        ]]]);

        $response = $this->ask($owner, $business, 'Rincian pembatalan atau refund kanal penjualan');

        $this->assertStringContainsString($expected, $response->json('messages.1.message'));
        Http::assertSentCount(1);
    }

    public static function salesFilters(): array
    {
        return [
            'refund' => ['refunded', 'purchase_channel', 'Live: nilai bersih Rp80.000; 1 pesanan; 1 unit; refund Rp20.000.'],
            'cancelled' => ['cancelled', 'order_channel', 'Live streaming: nilai bersih Rp0; 1 pesanan; 3 unit; refund Rp0.'],
            'all' => ['all', 'platform', 'TikTok: nilai bersih Rp80.000; 2 pesanan; 4 unit; refund Rp20.000.'],
        ];
    }

    public function test_daily_trend_is_chronological_and_marks_missing_days_as_unknown(): void
    {
        $this->travelTo('2026-09-06 10:00:00');
        [$owner, $business] = $this->owner();
        SalesOrder::factory()->for($business)->create(['ordered_on' => '2026-09-06', 'net_sales_amount' => 30000]);
        SalesOrder::factory()->for($business)->create(['ordered_on' => '2026-09-04', 'net_sales_amount' => 90000]);
        $this->fakeReports([['name' => 'sales_breakdown', 'arguments' => [
            'start_date' => '2026-09-04', 'end_date' => '2026-09-06', 'group_by' => 'day',
        ]]]);

        $response = $this->ask($owner, $business, 'Tren harian penjualan?');

        $answer = $response->json('messages.1.message');
        $this->assertLessThan(strpos($answer, '• 06 September'), strpos($answer, '• 04 September'));
        $this->assertStringNotContainsString('• 05 September', $answer);
        $this->assertStringContainsString('Hari tanpa catatan belum membuktikan tidak ada penjualan', $answer);
        Http::assertSentCount(1);
    }

    public function test_sales_summary_and_comparison_preserve_platform_filters(): void
    {
        $this->travelTo('2026-09-06 10:00:00');
        [$owner, $business] = $this->owner();
        SalesOrder::factory()->for($business)->create(['ordered_on' => '2026-09-06', 'platform' => 'shopee', 'net_sales_amount' => 60000]);
        SalesOrder::factory()->for($business)->create(['ordered_on' => '2026-09-05', 'platform' => 'shopee', 'net_sales_amount' => 30000]);
        SalesOrder::factory()->for($business)->create(['ordered_on' => '2026-09-06', 'net_sales_amount' => 900000]);
        $this->fakeReports([
            ['name' => 'sales_summary', 'arguments' => $this->period() + ['platform' => 'shopee']],
            ['name' => 'sales_comparison', 'arguments' => $this->period() + [
                'platform' => 'shopee', 'comparison_start_date' => '2026-09-05', 'comparison_end_date' => '2026-09-05',
            ]],
        ]);

        $response = $this->ask($owner, $business, 'Penjualan Shopee dan perbandingan kemarin?');

        $answer = $response->json('messages.1.message');
        $this->assertStringContainsString('Nilai penjualan bersih: Rp60.000', $answer);
        $this->assertStringContainsString('Naik Rp30.000. Perubahan: +100,0%.', $answer);
        $response->assertJsonCount(1, 'messages.1.sources');
        Http::assertSentCount(1);
    }

    public function test_employee_rates_use_wib_deadlines_exclude_future_and_cancelled_tasks_and_require_a_sample(): void
    {
        $this->travelTo('2026-09-06 03:00:00');
        [$owner, $business] = $this->owner();
        $task = Task::factory()->for($business)->create();
        $sari = User::factory()->create(['name' => 'Sari']);
        TaskOccurrence::factory()->for($task)->for($sari, 'assignee')->completed()->count(4)->sequence(
            ['occurrence_date' => '2026-09-01'],
            ['occurrence_date' => '2026-09-02'],
            ['occurrence_date' => '2026-09-03'],
            ['occurrence_date' => '2026-09-04'],
        )->create(['due_at' => '2026-09-06 09:00:00', 'completed_at' => '2026-09-06 02:00:00']);
        TaskOccurrence::factory()->for($task)->for($sari, 'assignee')->completed()->create([
            'occurrence_date' => '2026-09-05', 'due_at' => '2026-09-05 16:00:00', 'completed_at' => '2026-09-05 09:01:00',
        ]);
        TaskOccurrence::factory()->for($task)->for($sari, 'assignee')->create([
            'occurrence_date' => '2026-09-06', 'due_at' => '2026-09-06 09:00:00',
        ]);
        $secondTask = Task::factory()->for($business)->create();
        TaskOccurrence::factory()->for($secondTask)->for($sari, 'assignee')->create([
            'occurrence_date' => '2026-09-06', 'due_at' => '2026-09-06 18:00:00',
        ]);
        TaskOccurrence::factory()->for($secondTask)->for($sari, 'assignee')->create([
            'occurrence_date' => '2026-09-05', 'status' => TaskOccurrenceStatus::Cancelled,
        ]);
        $smallSample = User::factory()->create(['name' => 'Ayu']);
        TaskOccurrence::factory()->for($task)->for($smallSample, 'assignee')->completed()->create([
            'occurrence_date' => '2026-09-06', 'due_at' => '2026-09-06 09:00:00', 'completed_at' => '2026-09-06 01:00:00',
        ]);
        TaskOccurrence::factory()->for($sari, 'assignee')->completed()->create(['occurrence_date' => '2026-09-06']);
        $this->fakeReports([['name' => 'employee_performance', 'arguments' => [
            'start_date' => '2026-09-01', 'end_date' => '2026-09-06', 'metric' => 'on_time_rate', 'limit' => 1,
        ]]]);

        $response = $this->ask($owner, $business, 'Siapa yang paling rajin bulan ini?');

        $answer = $response->json('messages.1.message');
        $this->assertStringContainsString('Sari: 5 selesai dari 7 penugasan; 6 dapat dinilai.', $answer);
        $this->assertStringContainsString('Penyelesaian: 83,3%; tepat waktu: 4/6 (66,7%).', $answer);
        $this->assertStringContainsString('Selesai terlambat: 1; belum selesai melewati tenggat: 1; belum jatuh tempo: 1.', $answer);
        $this->assertStringNotContainsString('• Ayu:', $answer);
        $this->assertStringContainsString('bukan penilaian sifat rajin/malas', $answer);
        Http::assertSent(fn (Request $request): bool => ! str_contains($request->body(), 'Sari'));
        Http::assertSentCount(1);
    }

    public function test_employee_name_filter_does_not_leak_other_businesses_or_treat_missing_timestamps_as_on_time(): void
    {
        $this->travelTo('2026-09-06 03:00:00');
        [$owner, $business] = $this->owner();
        $task = Task::factory()->for($business)->create();
        $employee = User::factory()->create(['name' => 'Dewi']);
        TaskOccurrence::factory()->for($task)->for($employee, 'assignee')->completed()->create([
            'occurrence_date' => '2026-09-06', 'completed_at' => null,
        ]);
        TaskOccurrence::factory()->for($task)->create(['occurrence_date' => '2026-09-06']);
        $foreign = User::factory()->create(['name' => 'Dewi Rahasia']);
        TaskOccurrence::factory()->for($foreign, 'assignee')->create(['occurrence_date' => '2026-09-06']);
        $this->fakeReports([['name' => 'employee_performance', 'arguments' => $this->period() + [
            'metric' => 'completed', 'employee' => 'dEwI',
        ]]]);

        $response = $this->ask($owner, $business, 'Bagaimana kinerja Dewi?');

        $answer = $response->json('messages.1.message');
        $this->assertStringContainsString('Dewi: 1 selesai dari 1 penugasan', $answer);
        $this->assertStringContainsString('rasio tepat waktu belum dapat dinilai', $answer);
        $this->assertStringContainsString('Sampel kurang dari 5', $answer);
        $this->assertStringNotContainsString('Dewi Rahasia', $answer);
        Http::assertSentCount(1);
    }

    public function test_empty_employee_report_does_not_claim_inactivity(): void
    {
        $this->travelTo('2026-09-06 03:00:00');
        [$owner, $business] = $this->owner();
        $this->fakeReports([['name' => 'employee_performance', 'arguments' => $this->period() + ['metric' => 'overdue']]]);

        $response = $this->ask($owner, $business, 'Siapa terlambat?');

        $this->assertStringContainsString('bukan bukti karyawan tidak bekerja', $response->json('messages.1.message'));
        Http::assertSentCount(1);
    }

    public function test_combined_reports_preserve_sources_context_and_escape_product_names_after_reload(): void
    {
        $this->travelTo('2026-09-06 03:00:00');
        [$owner, $business] = $this->owner();
        $name = '<script>alert("produk")</script>';
        SalesOrder::factory()->for($business)->create(['ordered_on' => '2026-09-06', 'items' => [$this->item($name, 1, 10000)]]);
        $reports = [
            ['name' => 'product_ranking', 'arguments' => $this->period() + ['metric' => 'units']],
            ['name' => 'employee_performance', 'arguments' => $this->period() + ['metric' => 'completed']],
            ['name' => 'data_availability', 'arguments' => ['topic' => 'stock']],
        ];
        $this->fakeReports($reports);

        $response = $this->ask($owner, $business, 'Produk terlaris, stok dan kinerja tim?');

        $response->assertOk()->assertJsonCount(2, 'messages.1.sources')
            ->assertSessionHas('ai_insight.'.$owner->id.'.'.$business->id.'.context', [
                'name' => 'report_bundle', 'arguments' => ['reports' => $reports],
            ]);
        $this->assertStringContainsString('Stok aktual belum tersedia', $response->json('messages.1.message'));
        $this->get(route('ai-insight.index'))->assertSee($name)->assertDontSee($name, false)
            ->assertSeeText('Lihat data penjualan')->assertSeeText('Lihat monitoring tugas');
        $this->ask($owner, $business, 'Kalau kemarin?')->assertOk();
        Http::assertSentInOrder([
            fn (Request $request): bool => str_contains($request['messages'][0]['content'], 'Konteks laporan sebelumnya: null'),
            fn (Request $request): bool => str_contains($request['messages'][0]['content'], '"name":"report_bundle"')
                && str_contains($request['messages'][2]['content'], '<script>'),
        ]);
    }

    public function test_all_tool_calls_are_validated_before_any_occurrences_or_history_are_written(): void
    {
        $this->travelTo('2026-09-06 03:00:00');
        [$owner, $business] = $this->owner();
        $task = Task::factory()->for($business)->daily()->create(['starts_on' => '2026-09-01']);
        $task->assignees()->attach(User::factory()->create());
        $this->fakeReports([
            ['name' => 'employee_performance', 'arguments' => $this->period() + ['metric' => 'completed']],
            ['name' => 'product_ranking', 'arguments' => $this->period() + ['metric' => 'units', 'business_id' => 999]],
        ]);

        $response = $this->ask($owner, $business, 'Laporan gabungan');

        $response->assertStatus(502)->assertSessionMissing('ai_insight.'.$owner->id.'.'.$business->id.'.messages');
        $this->assertDatabaseCount('task_occurrences', 0);
        Http::assertSentCount(1);
    }

    public function test_more_than_four_reports_are_rejected_with_502(): void
    {
        $this->travelTo('2026-09-06 03:00:00');
        [$owner, $business] = $this->owner();
        $this->fakeReports(array_fill(0, 5, ['name' => 'sales_summary', 'arguments' => $this->period()]));

        $this->ask($owner, $business, 'Laporan semuanya')->assertStatus(502);

        Http::assertSentCount(1);
    }

    public function test_declined_combined_request_does_not_execute_other_reports(): void
    {
        $this->travelTo('2026-09-06 03:00:00');
        [$owner, $business] = $this->owner();
        $this->fakeReports([
            ['name' => 'sales_summary', 'arguments' => $this->period()],
            ['name' => 'decline_question', 'arguments' => ['reason' => 'unrelated']],
        ]);

        $response = $this->ask($owner, $business, 'Penjualan dan rumus matematika?');

        $this->assertStringContainsString('kurang relevan', $response->json('messages.1.message'));
        $this->assertStringNotContainsString('Nilai penjualan bersih', $response->json('messages.1.message'));
        Http::assertSentCount(1);
    }

    #[DataProvider('invalidAnalytics')]
    public function test_invalid_analytics_filters_return_502(string $name, array $extra): void
    {
        $this->travelTo('2026-09-06 03:00:00');
        [$owner, $business] = $this->owner();
        $this->fakeReports([['name' => $name, 'arguments' => [...$this->period(), ...$extra]]]);

        $this->ask($owner, $business, 'Laporan')->assertStatus(502);

        Http::assertSentCount(1);
    }

    public static function invalidAnalytics(): array
    {
        return [
            'metric injection' => ['product_ranking', ['metric' => 'SUM(password)']],
            'sort injection' => ['product_ranking', ['metric' => 'units', 'direction' => 'desc; DROP TABLE users']],
            'group injection' => ['sales_breakdown', ['group_by' => 'users.email']],
            'unknown platform' => ['product_ranking', ['metric' => 'units', 'platform' => 'secret']],
            'limit too large' => ['product_ranking', ['metric' => 'units', 'limit' => 21]],
            'limit zero' => ['product_ranking', ['metric' => 'units', 'limit' => 0]],
            'name too long' => ['employee_performance', ['metric' => 'completed', 'employee' => str_repeat('a', 101)]],
            'comparison incomplete' => ['product_comparison', ['metric' => 'units', 'comparison_start_date' => '2026-09-05']],
            'comparison future' => ['product_comparison', ['metric' => 'units', 'comparison_start_date' => '2026-09-07', 'comparison_end_date' => '2026-09-07']],
            'long period' => ['employee_performance', ['metric' => 'on_time_rate', 'start_date' => '2026-01-01']],
            'foreign employee id' => ['employee_performance', ['metric' => 'completed', 'employee_id' => 999]],
        ];
    }

    #[DataProvider('unavailableData')]
    public function test_unavailable_data_explains_the_missing_evidence(string $topic, string $expected): void
    {
        [$owner, $business] = $this->owner();
        $this->fakeReports([['name' => 'data_availability', 'arguments' => ['topic' => $topic]]]);

        $response = $this->ask($owner, $business, 'Jelaskan data yang tersedia');

        $response->assertJsonPath('messages.1.source', null);
        $this->assertStringContainsString($expected, $response->json('messages.1.message'));
        Http::assertSentCount(1);
    }

    public static function unavailableData(): array
    {
        return [
            ['stock', 'Unit terjual tidak menentukan sisa stok'],
            ['profit', 'Omzet bersih setelah refund bukan laba'],
            ['forecast', 'metode prediksi yang diuji'],
            ['attendance', 'tidak membuktikan kehadiran'],
        ];
    }

    public function test_product_comparison_can_find_largest_decline_instead_of_lowest_current_sales(): void
    {
        $this->travelTo('2026-09-06 10:00:00');
        [$owner, $business] = $this->owner();
        SalesOrder::factory()->for($business)->create([
            'ordered_on' => '2026-09-05', 'items' => [$this->item('Kopi', 100, 1000000), $this->item('Teh', 2, 20000)],
        ]);
        SalesOrder::factory()->for($business)->create([
            'ordered_on' => '2026-09-06', 'items' => [$this->item('Kopi', 50, 500000), $this->item('Teh', 1, 10000)],
        ]);
        $this->fakeReports([['name' => 'product_comparison', 'arguments' => $this->period() + [
            'metric' => 'units', 'sort_by' => 'change', 'direction' => 'asc', 'limit' => 1,
            'comparison_start_date' => '2026-09-05', 'comparison_end_date' => '2026-09-05',
        ]]]);

        $response = $this->ask($owner, $business, 'Produk dengan penurunan unit terbesar?');

        $answer = $response->json('messages.1.message');
        $this->assertStringContainsString('Kopi: 50 unit', $answer);
        $this->assertStringContainsString('selisih -50', $answer);
        $this->assertStringNotContainsString('• Teh:', $answer);
        Http::assertSentCount(1);
    }

    public function test_daily_ranking_honors_an_explicit_metric_even_without_direction(): void
    {
        $this->travelTo('2026-09-06 10:00:00');
        [$owner, $business] = $this->owner();
        SalesOrder::factory()->for($business)->create(['ordered_on' => '2026-09-06', 'net_sales_amount' => 10000]);
        SalesOrder::factory()->for($business)->create(['ordered_on' => '2026-09-05', 'net_sales_amount' => 90000]);
        $this->fakeReports([['name' => 'sales_breakdown', 'arguments' => [
            'start_date' => '2026-09-05', 'end_date' => '2026-09-06', 'group_by' => 'day', 'metric' => 'revenue', 'limit' => 1,
        ]]]);

        $response = $this->ask($owner, $business, 'Hari dengan penjualan paling tinggi?');

        $answer = $response->json('messages.1.message');
        $this->assertStringContainsString('• 05 September 2026: nilai bersih Rp90.000', $answer);
        $this->assertStringNotContainsString('• 06 September', $answer);
        Http::assertSentCount(1);
    }

    private function period(): array
    {
        return ['start_date' => '2026-09-06', 'end_date' => '2026-09-06'];
    }

    private function item(string $name, int $quantity, int $subtotal, string $variation = ''): array
    {
        return ['product_name' => $name, 'quantity' => $quantity, 'subtotal' => $subtotal, 'variation' => $variation];
    }

    private function fakeReports(array $reports): void
    {
        $calls = array_map(fn (array $report): array => [
            'id' => 'call_'.$report['name'], 'type' => 'function',
            'function' => ['name' => $report['name'], 'arguments' => json_encode($report['arguments'], JSON_THROW_ON_ERROR)],
        ], $reports);
        Http::fake([self::ENDPOINT => Http::response([
            'choices' => [['finish_reason' => 'tool_calls', 'message' => ['content' => 'Do not render model prose', 'tool_calls' => $calls]]],
        ])]);
    }

    private function ask(User $owner, Business $business, string $message): TestResponse
    {
        return $this->actingAs($owner)->withSession(['active_business_id' => $business->id])
            ->postJson(route('ai-insight.store'), ['business_id' => $business->id, 'message' => $message]);
    }

    /** @return array{User, Business} */
    private function owner(): array
    {
        $owner = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()->for($owner)->for($business)->create();

        return [$owner, $business];
    }
}

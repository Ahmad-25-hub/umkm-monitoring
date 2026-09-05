<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;

class PersistImportedSalesOrdersAction
{
    /**
     * @param  array<string, array{
     *     status: string,
     *     substatus: string|null,
     *     quantity: int,
     *     item_subtotal_amount: int,
     *     order_amount: int,
     *     refund_amount: int,
     *     ordered_on: string,
     *     ordered_at: string,
     *     paid_at: mixed,
     *     cancelled_at: mixed,
     *     is_cancelled: bool,
     *     purchase_channel: string|null,
     *     order_channel: string|null,
     *     items: array<int, array<string, mixed>>
     * }>  $orders
     * @return array{row_count: int, order_count: int, new_count: int, updated_count: int}
     *
     * @throws ValidationException
     */
    public function execute(
        Business $business,
        User $employee,
        UploadedFile $file,
        string $platform,
        int $rowCount,
        array $orders,
    ): array {
        if ($orders === []) {
            throw ValidationException::withMessages([
                'sales_file' => 'File tidak memiliki data pesanan yang dapat diimpor.',
            ]);
        }

        $existingOrderIds = [];

        foreach (array_chunk(array_keys($orders), 500) as $orderIds) {
            $existingOrderIds = [
                ...$existingOrderIds,
                ...SalesOrder::query()
                    ->whereBelongsTo($business)
                    ->where('platform', $platform)
                    ->whereIn('platform_order_id', $orderIds)
                    ->pluck('platform_order_id')
                    ->all(),
            ];
        }

        $existingOrderLookup = array_fill_keys($existingOrderIds, true);
        $timestamp = now()->format('Y-m-d H:i:s');
        $sourceFileName = Str::limit(basename($file->getClientOriginalName()), 255, '');
        $records = [];

        foreach ($orders as $orderId => $order) {
            try {
                $items = json_encode($order['items'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            } catch (JsonException) {
                throw ValidationException::withMessages([
                    'sales_file' => "Data produk untuk nomor pesanan {$orderId} tidak dapat diproses.",
                ]);
            }

            $records[] = [
                'business_id' => $business->id,
                'imported_by_user_id' => $employee->id,
                'platform' => $platform,
                'platform_order_id' => $orderId,
                'status' => $order['status'],
                'substatus' => $order['substatus'],
                'quantity' => $order['quantity'],
                'item_subtotal_amount' => $order['item_subtotal_amount'],
                'order_amount' => $order['order_amount'],
                'refund_amount' => $order['refund_amount'],
                'net_sales_amount' => $order['is_cancelled']
                    ? 0
                    : max($order['order_amount'] - $order['refund_amount'], 0),
                'ordered_on' => $order['ordered_on'],
                'ordered_at' => $order['ordered_at'],
                'paid_at' => $order['paid_at']?->utc()->format('Y-m-d H:i:s'),
                'cancelled_at' => $order['cancelled_at']?->utc()->format('Y-m-d H:i:s'),
                'is_cancelled' => $order['is_cancelled'],
                'purchase_channel' => $order['purchase_channel'],
                'order_channel' => $order['order_channel'],
                'items' => $items,
                'source_file_name' => $sourceFileName,
                'last_imported_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::transaction(function () use ($records): void {
            foreach (array_chunk($records, 500) as $recordChunk) {
                SalesOrder::query()->upsert(
                    $recordChunk,
                    ['business_id', 'platform', 'platform_order_id'],
                    [
                        'imported_by_user_id',
                        'status',
                        'substatus',
                        'quantity',
                        'item_subtotal_amount',
                        'order_amount',
                        'refund_amount',
                        'net_sales_amount',
                        'ordered_on',
                        'ordered_at',
                        'paid_at',
                        'cancelled_at',
                        'is_cancelled',
                        'purchase_channel',
                        'order_channel',
                        'items',
                        'source_file_name',
                        'last_imported_at',
                        'updated_at',
                    ],
                );
            }
        });

        $newCount = count(array_filter(
            array_keys($orders),
            fn (string $orderId): bool => ! isset($existingOrderLookup[$orderId]),
        ));

        return [
            'row_count' => $rowCount,
            'order_count' => count($orders),
            'new_count' => $newCount,
            'updated_count' => count($orders) - $newCount,
        ];
    }
}

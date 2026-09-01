<?php

namespace App\Models;

use Database\Factories\SalesOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'imported_by_user_id',
    'platform',
    'platform_order_id',
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
])]
class SalesOrder extends Model
{
    /** @use HasFactory<SalesOrderFactory> */
    use HasFactory;

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'item_subtotal_amount' => 'integer',
            'order_amount' => 'integer',
            'refund_amount' => 'integer',
            'net_sales_amount' => 'integer',
            'ordered_on' => 'date',
            'ordered_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_cancelled' => 'boolean',
            'items' => 'array',
            'last_imported_at' => 'datetime',
        ];
    }
}

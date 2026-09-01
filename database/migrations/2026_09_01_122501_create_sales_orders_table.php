<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('imported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('platform', 32);
            $table->string('platform_order_id', 80);
            $table->string('status', 100);
            $table->string('substatus', 100)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('item_subtotal_amount');
            $table->unsignedBigInteger('order_amount');
            $table->unsignedBigInteger('refund_amount')->default(0);
            $table->unsignedBigInteger('net_sales_amount');
            $table->date('ordered_on');
            $table->dateTime('ordered_at');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->string('purchase_channel', 100)->nullable();
            $table->string('order_channel', 100)->nullable();
            $table->json('items');
            $table->string('source_file_name');
            $table->timestamp('last_imported_at');
            $table->timestamps();

            $table->unique(['business_id', 'platform', 'platform_order_id']);
            $table->index(['business_id', 'ordered_on', 'is_cancelled']);
            $table->index(['business_id', 'last_imported_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};

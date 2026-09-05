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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path')->nullable()->after('email_verified_at');
            $table->string('pending_email')->nullable()->unique()->after('email');
            $table->timestamp('pending_email_requested_at')->nullable()->after('pending_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['pending_email']);
            $table->dropColumn(['avatar_path', 'pending_email', 'pending_email_requested_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('assigned_manager_id')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            $table->string('client_phone')->nullable()->after('client_address');
            $table->timestamp('scheduled_at')->nullable()->after('priority');
            $table->timestamp('due_date')->nullable()->after('scheduled_at');
            $table->string('tracking_token', 64)->nullable()->unique()->after('due_date');
        });

    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_manager_id');
            $table->dropColumn(['client_phone', 'scheduled_at', 'due_date', 'tracking_token']);
        });
    }
};

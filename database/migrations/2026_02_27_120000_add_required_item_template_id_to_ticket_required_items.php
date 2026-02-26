<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_required_items', function (Blueprint $table) {
            $table->foreignId('required_item_template_id')
                ->nullable()
                ->after('ticket_id')
                ->constrained('required_item_templates')
                ->nullOnDelete();
            $table->string('name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_required_items', function (Blueprint $table) {
            $table->dropForeign(['required_item_template_id']);
            $table->string('name')->nullable(false)->change();
        });
    }
};

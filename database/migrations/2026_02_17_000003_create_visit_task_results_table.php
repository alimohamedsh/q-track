<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_task_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_task_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['completed', 'incomplete'])->default('incomplete');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['visit_id', 'ticket_task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_task_results');
    }
};

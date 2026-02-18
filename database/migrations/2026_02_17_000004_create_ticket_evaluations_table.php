<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('technician_rating')->default(0);
            $table->unsignedTinyInteger('company_rating')->default(0);
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_evaluations');
    }
};

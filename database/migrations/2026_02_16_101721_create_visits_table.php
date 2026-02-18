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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained(); 
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->decimal('start_lat', 10, 8)->nullable();
            $table->decimal('start_lng', 11, 8)->nullable();
            $table->decimal('end_lat', 10, 8)->nullable();
            $table->decimal('end_lng', 11, 8)->nullable();
            $table->enum('status', ['completed', 'incomplete'])->default('incomplete');
            $table->string('failure_reason')->nullable(); 
            $table->text('technician_notes')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};

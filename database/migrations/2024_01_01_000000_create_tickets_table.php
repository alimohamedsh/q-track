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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->enum('type', ['installation', 'maintenance']);
            $table->string('client_name');
            $table->string('client_address');
            $table->string('location_url')->nullable(); 
            $table->foreignId('assigned_to')->constrained('users'); 
            $table->enum('status', ['open', 'in_progress', 'closed', 'canceled'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

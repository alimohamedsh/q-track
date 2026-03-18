<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('project_name')->nullable()->after('ticket_number');
            $table->enum('warranty_status', ['in_warranty', 'out_of_warranty'])
                ->default('in_warranty')
                ->after('status');
        });

        // تحديث قيم ENUM لحقل النوع والأولوية
        DB::statement("ALTER TABLE tickets MODIFY COLUMN type ENUM('installation', 'maintenance', 'visit') NOT NULL");
        DB::statement("ALTER TABLE tickets MODIFY COLUMN priority ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium'");
    }

    public function down(): void
    {
        // إعادة ENUM كما كانت وحذف الأعمدة الجديدة
        DB::statement("ALTER TABLE tickets MODIFY COLUMN type ENUM('installation', 'maintenance') NOT NULL");
        DB::statement("ALTER TABLE tickets MODIFY COLUMN priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium'");

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['project_name', 'warranty_status']);
        });
    }
};


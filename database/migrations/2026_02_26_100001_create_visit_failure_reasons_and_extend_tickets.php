<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // أسباب فشل الزيارة (قائمة قياسية)
        Schema::create('visit_failure_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('label');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->foreignId('failure_reason_id')->nullable()->after('failure_reason')->constrained('visit_failure_reasons')->nullOnDelete();
        });

        // إضافة حالات جديدة للتذكرة
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'in_progress', 'on_hold', 'revisit_required', 'closed', 'canceled') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign(['failure_reason_id']);
        });
        Schema::dropIfExists('visit_failure_reasons');
        DB::statement("ALTER TABLE tickets MODIFY COLUMN status ENUM('open', 'in_progress', 'closed', 'canceled') NOT NULL DEFAULT 'open'");
    }
};

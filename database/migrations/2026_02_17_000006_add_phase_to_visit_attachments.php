<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_attachments', function (Blueprint $table) {
            $table->string('phase')->default('check_out')->after('file_type'); // check_in | check_out
        });
    }

    public function down(): void
    {
        Schema::table('visit_attachments', function (Blueprint $table) {
            $table->dropColumn('phase');
        });
    }
};

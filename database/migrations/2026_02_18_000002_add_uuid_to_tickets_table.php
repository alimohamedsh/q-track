<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        foreach (DB::table('tickets')->get() as $ticket) {
            DB::table('tickets')->where('id', $ticket->id)->update(['uuid' => (string) Str::uuid()]);
        }

        Schema::table('tickets', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_field_options', function (Blueprint $table) {
            $table->decimal('score', 8, 2)->nullable()->after('value');
        });
    }

    public function down(): void
    {
        Schema::table('form_field_options', function (Blueprint $table) {
            $table->dropColumn('score');
        });
    }
};

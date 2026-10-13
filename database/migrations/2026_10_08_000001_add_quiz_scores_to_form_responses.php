<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->decimal('quiz_score', 8, 2)->nullable()->after('metadata');
            $table->decimal('quiz_max_score', 8, 2)->nullable()->after('quiz_score');
        });
    }

    public function down(): void
    {
        Schema::table('form_responses', function (Blueprint $table) {
            $table->dropColumn(['quiz_score', 'quiz_max_score']);
        });
    }
};

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
        Schema::table('rounds', function (Blueprint $table) {
            $table->dropColumn(['note', 'guess']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->string('guess')->nullable()->after('question_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rounds', function (Blueprint $table) {
            $table->string('guess')->nullable();
            $table->text('note')->nullable();
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('guess');
        });
    }
};

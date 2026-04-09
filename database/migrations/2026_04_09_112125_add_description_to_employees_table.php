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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('description', 1024)->nullable()->after('mobile_number');
        });

        Schema::table('structure', function (Blueprint $table) {
            $table->string('description', 1024)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('structure', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }
};

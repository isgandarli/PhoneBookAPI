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
        Schema::table('structure', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->change();
            $table->text('description')->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('structure', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->unsignedBigInteger('parent_id')->nullable(false)->change();
        });
    }
};

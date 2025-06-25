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
        Schema::table('publishers', function (Blueprint $table) {
            $table->string('address')->nullable()->after('name');
            $table->string('website')->nullable()->after('address');
            $table->string('email')->unique()->after('website');
            $table->string('phone')->nullable()->after('email');
            $table->year('established_year')->nullable()->after('phone');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publishers', function (Blueprint $table) {
            //
        });
    }
};

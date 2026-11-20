<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // An application that already carries the column keeps its own definition and data.
        if (Schema::hasColumn('users', 'analytics_opt_out')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('analytics_opt_out')->default(false);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'analytics_opt_out')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('analytics_opt_out');
        });
    }
};

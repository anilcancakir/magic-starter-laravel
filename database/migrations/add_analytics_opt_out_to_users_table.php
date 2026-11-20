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

    /**
     * Leaves the column in place: up() skips a column the application already had, so a rollback
     * cannot tell the application's column from this migration's, and dropping it would delete
     * the application's data. A re-run of up() finds the column and changes nothing.
     */
    public function down(): void
    {
        //
    }
};

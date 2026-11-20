<?php

namespace AnilcanCakir\MagicStarter\Tests\Database;

use AnilcanCakir\MagicStarter\Tests\TestCase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Runs the real migration against the users table as an application has it: without the column,
 * and with a column of its own that the migration must leave alone.
 */
class AnalyticsOptOutMigrationTest extends TestCase
{
    public function test_it_adds_the_column_defaulting_to_false(): void
    {
        $this->migrate('create_users_table.php');

        $this->analyticsMigration()->up();

        $this->assertTrue(Schema::hasColumn('users', 'analytics_opt_out'));

        $this->insertUser();

        $this->assertSame(0, (int) DB::table('users')->value('analytics_opt_out'));
    }

    public function test_it_is_a_no_op_when_the_column_already_exists(): void
    {
        $this->migrate('create_users_table.php');
        Schema::table('users', function ($table): void {
            $table->boolean('analytics_opt_out')->nullable();
        });
        $this->insertUser(['analytics_opt_out' => true]);

        $this->analyticsMigration()->up();

        $this->assertSame(1, (int) DB::table('users')->value('analytics_opt_out'));
        $this->assertTrue(
            collect(Schema::getColumns('users'))->firstWhere('name', 'analytics_opt_out')['nullable'],
            'The application\'s own column definition is kept.',
        );
    }

    public function test_running_it_twice_changes_nothing(): void
    {
        $this->migrate('create_users_table.php');
        $migration = $this->analyticsMigration();
        $migration->up();

        $migration->up();

        $this->assertTrue(Schema::hasColumn('users', 'analytics_opt_out'));
    }

    public function test_down_removes_the_column(): void
    {
        $this->migrate('create_users_table.php');
        $migration = $this->analyticsMigration();
        $migration->up();

        $migration->down();

        $this->assertFalse(Schema::hasColumn('users', 'analytics_opt_out'));
        $this->assertTrue(Schema::hasColumn('users', 'email'));
    }

    public function test_down_does_nothing_when_the_column_is_absent(): void
    {
        $this->migrate('create_users_table.php');

        $this->analyticsMigration()->down();

        $this->assertFalse(Schema::hasColumn('users', 'analytics_opt_out'));
    }

    private function analyticsMigration(): Migration
    {
        return require __DIR__ . '/../../database/migrations/add_analytics_opt_out_to_users_table.php';
    }

    private function migrate(string $file): void
    {
        $migration = require __DIR__ . '/../../database/migrations/' . $file;
        $migration->up();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertUser(array $attributes = []): void
    {
        $row = array_merge([
            'name' => 'Reader',
            'email' => 'reader@example.com',
            'password' => 'secret',
        ], $attributes);

        if (config('magic-starter.use_uuids')) {
            $row['id'] = (string) Str::uuid();
        }

        DB::table('users')->insert($row);
    }
}

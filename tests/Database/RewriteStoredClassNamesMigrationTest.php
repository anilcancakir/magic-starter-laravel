<?php

namespace AnilcanCakir\MagicStarter\Tests\Database;

use AnilcanCakir\MagicStarter\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Runs the class name rewrite against tables seeded with what an install wrote
 * before the namespace became `AnilcanCakir\MagicStarter\`: package classes
 * under the old prefix, a host class, a value already under the new prefix and
 * a null, in every column a package class can end up in.
 */
class RewriteStoredClassNamesMigrationTest extends TestCase
{
    private const MIGRATION = __DIR__ . '/../../database/migrations/rewrite_stored_class_names.php';

    private const OLD = 'FlutterSdk\\MagicStarter\\';

    private const NEW = 'AnilcanCakir\\MagicStarter\\';

    private const HOST = 'App\\Models\\User';

    /**
     * The columns the migration covers, by table.
     *
     * @var array<string, list<string>>
     */
    private const COLUMNS = [
        'magic_starter_audits' => [
            'auditable_type',
        ],
        'notifications' => [
            'type',
        ],
        'magic_starter_mail_sends' => [
            'mail_class',
        ],
        'ai_runs' => [
            'subject_type',
            'error_class',
        ],
        'ai_run_attempts' => [
            'error_class',
        ],
        'billing_events' => [
            'billable_type',
        ],
        'billing_grants' => [
            'billable_type',
        ],
        'billing_trials' => [
            'billable_type',
        ],
        'support_proposals' => [
            'billable_type',
        ],
    ];

    /**
     * The package-class suffixes seeded under the old prefix; the first one twice,
     * so a distinct value spread over several rows is covered.
     *
     * @var list<string>
     */
    private const SUFFIXES = [
        'Models\\Team',
        'Models\\Team',
        'Notifications\\SupportReplyNotification',
    ];

    public function test_old_values_are_rewritten_in_every_covered_column(): void
    {
        $this->buildTables();
        $this->seedRows();

        (require self::MIGRATION)->up();

        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertSame(
                    $this->expected(self::NEW),
                    $this->values($table, $column),
                    "{$table}.{$column}",
                );
            }
        }
    }

    public function test_a_column_that_holds_only_host_classes_is_left_alone(): void
    {
        $this->buildTables();
        $this->seedRows();

        (require self::MIGRATION)->up();

        $this->assertSame(
            [
                self::OLD . 'Models\\Team',
            ],
            DB::table('magic_starter_audits')->whereNotNull('actor_type')->pluck('actor_type')->unique()->values()->all(),
        );
    }

    public function test_running_it_again_changes_nothing(): void
    {
        $this->buildTables();
        $this->seedRows();
        (require self::MIGRATION)->up();
        $before = $this->snapshot();

        (require self::MIGRATION)->up();

        $this->assertSame($before, $this->snapshot());
    }

    public function test_a_prefix_spelt_in_another_case_is_rewritten_too(): void
    {
        // PHP resolves class names case-insensitively, so `use flutterSdk\...` stores
        // that spelling; a case-insensitive MySQL collation also merges such values.
        $this->buildTables();
        DB::table('magic_starter_audits')->insert([
            ['auditable_type' => 'flutterSdk\\MagicStarter\\Models\\Team'],
            ['auditable_type' => 'FLUTTERSDK\\MagicStarter\\Models\\Subscription'],
        ]);

        (require self::MIGRATION)->up();

        $this->assertSame(
            [
                self::NEW . 'Models\\Subscription',
                self::NEW . 'Models\\Team',
            ],
            DB::table('magic_starter_audits')->orderBy('auditable_type')->pluck('auditable_type')->all(),
        );
    }

    public function test_an_absent_table_is_skipped_and_the_others_are_rewritten(): void
    {
        $this->buildTables(except: 'magic_starter_audits');
        $this->seedRows();

        (require self::MIGRATION)->up();

        $this->assertFalse(Schema::hasTable('magic_starter_audits'));
        $this->assertSame($this->expected(self::NEW), $this->values('notifications', 'type'));
    }

    public function test_an_absent_column_is_skipped(): void
    {
        $this->buildTables();
        Schema::table('ai_runs', fn (Blueprint $table) => $table->dropColumn('error_class'));
        $this->seedRows();

        (require self::MIGRATION)->up();

        $this->assertSame($this->expected(self::NEW), $this->values('ai_runs', 'subject_type'));
    }

    public function test_down_restores_the_old_values(): void
    {
        $this->buildTables();
        $this->seedRows();
        $migration = require self::MIGRATION;
        $migration->up();

        $migration->down();

        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $this->assertSame(
                    $this->expected(self::OLD, alreadyNew: self::OLD),
                    $this->values($table, $column),
                    "{$table}.{$column}",
                );
            }
        }
    }

    /**
     * Each covered table with an id and its covered columns, nullable strings as
     * the package's own migrations build them, plus the audits' `actor_type`.
     */
    private function buildTables(?string $except = null): void
    {
        foreach (self::COLUMNS as $name => $columns) {
            if ($name === $except) {
                continue;
            }

            Schema::create($name, function (Blueprint $table) use ($name, $columns): void {
                $table->increments('id');

                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }

                if ($name === 'magic_starter_audits') {
                    $table->string('actor_type')->nullable();
                }
            });
        }
    }

    /**
     * Seed every covered column with the old-prefix values, then a host class,
     * a value already under the new prefix and a null.
     */
    private function seedRows(): void
    {
        $values = [
            ...array_map(static fn (string $suffix): string => self::OLD . $suffix, self::SUFFIXES),
            self::HOST,
            self::NEW . 'Models\\TeamInvitation',
            null,
        ];

        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($values as $value) {
                $row = [];

                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $row[$column] = $value;
                    }
                }

                if ($table === 'magic_starter_audits') {
                    $row['actor_type'] = self::OLD . 'Models\\Team';
                }

                DB::table($table)->insert($row);
            }
        }
    }

    /**
     * The seeded values in insertion order, with the package classes under `$prefix`.
     *
     * @param  string  $alreadyNew  The prefix the value seeded under the new prefix is read back with.
     * @return list<string|null>
     */
    private function expected(string $prefix, string $alreadyNew = self::NEW): array
    {
        return [
            ...array_map(static fn (string $suffix): string => $prefix . $suffix, self::SUFFIXES),
            self::HOST,
            $alreadyNew . 'Models\\TeamInvitation',
            null,
        ];
    }

    /**
     * @return list<string|null>
     */
    private function values(string $table, string $column): array
    {
        return DB::table($table)->orderBy('id')->pluck($column)->all();
    }

    /**
     * @return array<string, array<string, list<string|null>>>
     */
    private function snapshot(): array
    {
        $snapshot = [];

        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $snapshot[$table][$column] = $this->values($table, $column);
            }
        }

        return $snapshot;
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrites the class names the package stored under its old namespace.
 *
 * No morph map is registered, so the package's own models and notifications are
 * stored by their fully qualified name, and the rows written before the rename
 * name classes that no longer exist. Only the columns a package class can reach
 * are listed; the ones that only ever hold the host's user model are not.
 *
 * The distinct values are read and matched in PHP, because a backslash is an
 * escape character in a MySQL and PostgreSQL `LIKE` pattern, and each one is
 * rewritten by an exact-match update, which every driver runs the same way. A
 * missing table or column is skipped, and a second run finds nothing to change.
 */
return new class extends Migration
{
    // Written in two pieces so the namespace sweep in the upgrade guide, which also
    // covers `database/`, does not turn this constant into the new prefix and leave
    // the migration swapping new for new.
    private const OLD_PREFIX = 'Flutter' . 'Sdk\\MagicStarter\\';

    private const NEW_PREFIX = 'AnilcanCakir\\MagicStarter\\';

    /**
     * The columns a package class can be stored in, by table.
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

    public function up(): void
    {
        $this->swapPrefix(self::OLD_PREFIX, self::NEW_PREFIX);
    }

    public function down(): void
    {
        $this->swapPrefix(self::NEW_PREFIX, self::OLD_PREFIX);
    }

    private function swapPrefix(string $from, string $to): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $this->swapColumn($table, $column, $from, $to);
            }
        }
    }

    private function swapColumn(string $table, string $column, string $from, string $to): void
    {
        $values = DB::table($table)
            ->whereNotNull($column)
            ->distinct()
            ->pluck($column);

        foreach ($values as $value) {
            if (! str_starts_with((string) $value, $from)) {
                continue;
            }

            DB::table($table)
                ->where($column, $value)
                ->update([
                    $column => $to . substr((string) $value, strlen($from)),
                ]);
        }
    }
};

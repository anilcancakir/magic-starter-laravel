<?php

namespace AnilcanCakir\MagicStarter\Http\Controllers\Concerns;

use AnilcanCakir\MagicStarter\Support\TeamKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Looks a record up by the id in a route.
 *
 * An id that cannot be a primary key is a missing record, answered 404 before any query. PostgreSQL rejects a
 * malformed value against a uuid column, so `findOrFail()` on the raw route segment ended in a 500 with the SQL in
 * the body; `ConditionallyUsesUuids` already guards a bound route key, and this gives the controllers that look a
 * record up themselves the same answer.
 */
trait FindsRecords
{
    /**
     * @param  Builder<Model>  $query
     *
     * @throws ModelNotFoundException<Model>
     */
    protected function findOrNotFound(Builder $query, string $id): Model
    {
        if (! TeamKey::looksLikeOne($id)) {
            throw (new ModelNotFoundException)->setModel($query->getModel()::class, $id);
        }

        return $query->findOrFail($id);
    }
}

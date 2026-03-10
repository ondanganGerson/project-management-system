<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait CommonQueryScopes
 *
 * Reusable Eloquent query scopes for filtering and searching.
 * Apply this trait to any model that needs status filtering or title search.
 */
trait CommonQueryScopes
{
    /**
     * Scope: Filter results by a given status.
     *
     * Usage: User::filterByStatus('admin')->get();
     *        Task::filterByStatus('pending')->get();
     *
     * @param  Builder $query
     * @param  string|null $status
     * @return Builder
     */
    public function scopeFilterByStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null || $status === '') {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Scope: Search results by title using a case-insensitive LIKE query.
     *
     * Usage: Project::searchByTitle('My Project')->get();
     *        Task::searchByTitle('Bug fix')->get();
     *
     * @param  Builder $query
     * @param  string|null $keyword
     * @return Builder
     */
    public function scopeSearchByTitle(Builder $query, ?string $keyword): Builder
    {
        if ($keyword === null || $keyword === '') {
            return $query;
        }

        return $query->where('title', 'LIKE', '%' . $keyword . '%');
    }

    /**
     * Scope: Filter results by date range.
     *
     * @param  Builder $query
     * @param  string|null $from  (Y-m-d)
     * @param  string|null $to    (Y-m-d)
     * @param  string $column     Column name to filter on
     * @return Builder
     */
    public function scopeDateRange(Builder $query, ?string $from, ?string $to, string $column = 'created_at'): Builder
    {
        if ($from) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to) {
            $query->whereDate($column, '<=', $to);
        }

        return $query;
    }

    /**
     * Scope: Order results by the latest created first.
     *
     * @param  Builder $query
     * @return Builder
     */
    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }
}

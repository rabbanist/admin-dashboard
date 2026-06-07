<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ResourceListBuilder
{
    public function __construct(
        protected readonly ResourceCrudService $crudService,
    ) {}

    /**
     * Start a fresh query for the resource, eager-loading any defined relationships.
     */
    public function buildQuery(string $resourceName): Builder
    {
        $modelClass = $this->crudService->getModelInstance($resourceName);
        $query = $modelClass::query();

        // Prevent N+1 queries by eager-loading relationships defined in the configuration
        $relationships = $this->crudService->getRelationships($resourceName);
        $eagerRelations = array_keys($relationships);

        if (! empty($eagerRelations)) {
            $query->with($eagerRelations);
        }

        // Support soft deletes if model trait exists
        if (method_exists($query->getModel(), 'runSoftDelete')) {
            $query->withTrashed();
        }

        return $query;
    }

    /**
     * Apply searches across multiple columns and custom attribute filters.
     */
    public function applyFilters(Builder $query, string $resourceName, Request $request): Builder
    {
        $columns = $this->crudService->getColumns($resourceName);

        // 1. Multi-column search
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search, $columns) {
                foreach ($columns as $columnName => $options) {
                    if (! empty($options['searchable'])) {
                        $q->orWhere($columnName, 'like', "%{$search}%");
                    }
                }
            });
        }

        // 2. Attribute-specific filters
        foreach ($columns as $columnName => $options) {
            $filterName = "filter_{$columnName}";
            if ($request->filled($filterName)) {
                $value = $request->input($filterName);
                if ($options['type'] === 'boolean') {
                    $query->where($columnName, $value === '1');
                } else {
                    $query->where($columnName, $value);
                }
            }
        }

        // 3. Soft deletes state filter
        if (method_exists($query->getModel(), 'runSoftDelete')) {
            $status = $request->input('status');
            if ($status === 'deleted') {
                $query->onlyTrashed();
            } elseif ($status === 'active') {
                $query->whereNull('deleted_at');
            }
        }

        return $query;
    }

    /**
     * Apply sorting to query.
     */
    public function applySorting(Builder $query, string $resourceName, Request $request): Builder
    {
        $columns = $this->crudService->getColumns($resourceName);
        $sort = $request->input('sort');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sort && isset($columns[$sort]) && ! empty($columns[$sort]['sortable'])) {
            $query->orderBy($sort, $direction);
        } else {
            // Default sorting order
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    /**
     * Paginate results.
     */
    public function paginate(Builder $query, int $perPage = 25)
    {
        return $query->paginate($perPage)->withQueryString();
    }
}

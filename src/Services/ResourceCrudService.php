<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Services;

use Illuminate\Support\Facades\Validator;
use Yourvendor\AdminDashboard\Exceptions\AdminDashboardException;

class ResourceCrudService
{
    /**
     * Parse and load schema configuration for a resource.
     */
    public function loadResource(string $resourceName): array
    {
        $resources = config('admin-dashboard.resources', []);

        if (! isset($resources[$resourceName])) {
            throw AdminDashboardException::invalidConfiguration(
                "resources",
                "Dynamic resource [{$resourceName}] is not defined in configuration."
            );
        }

        $config = $resources[$resourceName];

        if (! isset($config['model'])) {
            throw AdminDashboardException::invalidConfiguration(
                "resources.{$resourceName}",
                "Model class is not defined for resource [{$resourceName}]."
            );
        }

        if (! class_exists($config['model'])) {
            throw AdminDashboardException::invalidConfiguration(
                "resources.{$resourceName}",
                "Model class [{$config['model']}] does not exist."
            );
        }

        if (! isset($config['columns']) || ! is_array($config['columns'])) {
            throw AdminDashboardException::invalidConfiguration(
                "resources.{$resourceName}",
                "Columns definition must be an array for resource [{$resourceName}]."
            );
        }

        return $config;
    }

    /**
     * Return column definitions.
     */
    public function getColumns(string $resourceName): array
    {
        $config = $this->loadResource($resourceName);

        return $config['columns'] ?? [];
    }

    /**
     * Get model class name.
     */
    public function getModelInstance(string $resourceName): string
    {
        $config = $this->loadResource($resourceName);

        return $config['model'];
    }

    /**
     * Get defined relationships.
     */
    public function getRelationships(string $resourceName): array
    {
        $config = $this->loadResource($resourceName);

        return $config['relationships'] ?? [];
    }

    /**
     * Run validation on input data.
     */
    public function validateInput(string $resourceName, array $data, $id = null): array
    {
        $config = $this->loadResource($resourceName);
        $rules = $config['validation'] ?? [];

        if ($id !== null) {
            foreach ($rules as $field => &$fieldRules) {
                // Handle rules defined as string
                if (is_string($fieldRules)) {
                    $parts = explode('|', $fieldRules);
                    foreach ($parts as &$part) {
                        if (str_starts_with($part, 'unique:')) {
                            $uniqueParams = explode(',', substr($part, 7));
                            $table = $uniqueParams[0];
                            $column = $uniqueParams[1] ?? $field;
                            $part = "unique:{$table},{$column},{$id}";
                        }
                    }
                    $fieldRules = implode('|', $parts);
                }
                // Handle rules defined as array
                elseif (is_array($fieldRules)) {
                    foreach ($fieldRules as &$rule) {
                        if (is_string($rule) && str_starts_with($rule, 'unique:')) {
                            $uniqueParams = explode(',', substr($rule, 7));
                            $table = $uniqueParams[0];
                            $column = $uniqueParams[1] ?? $field;
                            $rule = "unique:{$table},{$column},{$id}";
                        }
                    }
                }
            }
        }

        return Validator::make($data, $rules)->validate();
    }

    /**
     * Create a record in database.
     */
    public function createRecord(string $resourceName, array $data)
    {
        $modelClass = $this->getModelInstance($resourceName);
        $record = new $modelClass();

        $columns = $this->getColumns($resourceName);

        foreach ($columns as $columnName => $options) {
            $type = $options['type'] ?? 'text';

            if ($type === 'file') {
                if (isset($data[$columnName])) {
                    $record->{$columnName} = $data[$columnName];
                }
                continue;
            }

            if ($type === 'boolean') {
                $record->{$columnName} = (bool) ($data[$columnName] ?? false);
                continue;
            }

            if ($type === 'multiselect') {
                $record->{$columnName} = is_array($data[$columnName] ?? null) 
                    ? json_encode($data[$columnName]) 
                    : null;
                continue;
            }

            if (array_key_exists($columnName, $data)) {
                $record->{$columnName} = $data[$columnName];
            }
        }

        $record->save();

        return $record;
    }

    /**
     * Update a record in database.
     */
    public function updateRecord(string $resourceName, $id, array $data)
    {
        $modelClass = $this->getModelInstance($resourceName);
        $query = $modelClass::query();

        // Support soft deletes if model trait exists
        if (method_exists($query->getModel(), 'runSoftDelete')) {
            $query->withTrashed();
        }

        $record = $query->findOrFail($id);
        $columns = $this->getColumns($resourceName);

        foreach ($columns as $columnName => $options) {
            $type = $options['type'] ?? 'text';

            if ($type === 'file') {
                // If a new file is uploaded, update it (deletion of old handled by generator)
                if (isset($data[$columnName])) {
                    $record->{$columnName} = $data[$columnName];
                }
                continue;
            }

            if ($type === 'boolean') {
                $record->{$columnName} = (bool) ($data[$columnName] ?? false);
                continue;
            }

            if ($type === 'multiselect') {
                $record->{$columnName} = is_array($data[$columnName] ?? null) 
                    ? json_encode($data[$columnName]) 
                    : null;
                continue;
            }

            if (array_key_exists($columnName, $data)) {
                $record->{$columnName} = $data[$columnName];
            }
        }

        $record->save();

        return $record;
    }
}

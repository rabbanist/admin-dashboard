<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Rabbanist\AdminDashboard\Exceptions\AdminDashboardException;

class DynamicFormGenerator
{
    public function __construct(
        protected readonly ResourceCrudService $crudService,
    ) {}

    /**
     * Parse the configuration schema to produce metadata for form construction.
     */
    public function generateFormFields(string $resourceName, $record = null): array
    {
        $columns = $this->crudService->getColumns($resourceName);
        $fields = [];

        foreach ($columns as $columnName => $options) {
            $type = $options['type'] ?? 'text';
            $label = $options['label'] ?? ucwords(str_replace('_', ' ', $columnName));

            // Default value computation
            $defaultValue = null;
            if ($record !== null) {
                if ($type === 'multiselect') {
                    $val = $record->{$columnName};
                    $defaultValue = is_string($val) ? json_decode($val, true) : $val;
                } else {
                    $defaultValue = $record->{$columnName};
                }
            } else {
                $defaultValue = $options['default'] ?? null;
            }

            // Options loader for select, multiselect, and relationships
            $choices = [];
            if ($type === 'select' || $type === 'multiselect' || $type === 'relationship') {
                if (isset($options['options']) && is_array($options['options'])) {
                    $choices = $options['options'];
                } elseif (isset($options['model'])) {
                    $choices = $this->resolveModelOptions($options['model']);
                }
            }

            $fields[$columnName] = [
                'name'     => $columnName,
                'type'     => $type,
                'label'    => $label,
                'value'    => $defaultValue,
                'options'  => $choices,
                'required' => $options['required'] ?? false,
                'placeholder' => $options['placeholder'] ?? '',
                'disk'     => $options['disk'] ?? 'public',
            ];
        }

        return $fields;
    }

    /**
     * Resolve drop-down choices dynamically from a target model.
     */
    protected function resolveModelOptions(string $modelName): array
    {
        $modelClass = class_exists($modelName) ? $modelName : "\\App\\Models\\{$modelName}";

        if (! class_exists($modelClass)) {
            $modelClass = "\\Rabbanist\AdminDashboard\\Models\\{$modelName}";
        }

        if (! class_exists($modelClass)) {
            return [];
        }

        try {
            $instance = new $modelClass();
            $table = $instance->getTable();

            // Auto-detect a friendly column for labels
            $labelColumn = 'name';
            if (! Schema::hasColumn($table, 'name')) {
                if (Schema::hasColumn($table, 'title')) {
                    $labelColumn = 'title';
                } elseif (Schema::hasColumn($table, 'label')) {
                    $labelColumn = 'label';
                } else {
                    $labelColumn = 'id';
                }
            }

            return $modelClass::pluck($labelColumn, 'id')->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Process file uploads and return saved file paths.
     */
    public function handleFileUploads(string $resourceName, Request $request, $record = null): array
    {
        $columns = $this->crudService->getColumns($resourceName);
        $fileData = [];

        foreach ($columns as $columnName => $options) {
            $type = $options['type'] ?? 'text';

            if ($type === 'file' && $request->hasFile($columnName)) {
                $disk = $options['disk'] ?? config('admin-dashboard.uploads.disk', 'public');
                
                // If editing, delete old file
                if ($record !== null && $record->{$columnName}) {
                    if (Storage::disk($disk)->exists($record->{$columnName})) {
                        Storage::disk($disk)->delete($record->{$columnName});
                    }
                }

                // Store new file
                $path = $request->file($columnName)->store("admin-uploads/{$resourceName}", $disk);
                $fileData[$columnName] = $path;
            }
        }

        return $fileData;
    }

    /**
     * Strip HTML tags and clean user input.
     */
    public function sanitizeInput(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim(strip_tags($value));
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}

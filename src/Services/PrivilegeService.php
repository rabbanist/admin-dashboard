<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Yourvendor\AdminDashboard\Models\Privilege;

class PrivilegeService
{
    /**
     * Create a new privilege.
     */
    public function createPrivilege(array $data): Privilege
    {
        Validator::make($data, [
            'name'          => ['required', 'string', 'max:255'],
            'slug'          => ['required', 'string', 'max:255', 'unique:admin_privileges,slug'],
            'resource_type' => ['required', 'string', 'max:255'],
            'module'        => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:1000'],
        ])->validate();

        return Privilege::create([
            'name'          => $data['name'],
            'slug'          => $data['slug'],
            'resource_type' => strtolower($data['resource_type']),
            'module'        => strtolower($data['module']),
            'description'   => $data['description'] ?? null,
        ]);
    }

    /**
     * Update an existing privilege.
     */
    public function updatePrivilege(Privilege $privilege, array $data): Privilege
    {
        Validator::make($data, [
            'name'          => ['required', 'string', 'max:255'],
            'slug'          => ['required', 'string', 'max:255', 'unique:admin_privileges,slug,' . $privilege->id],
            'resource_type' => ['required', 'string', 'max:255'],
            'module'        => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $privilege->update([
            'name'          => $data['name'],
            'slug'          => $data['slug'],
            'resource_type' => strtolower($data['resource_type']),
            'module'        => strtolower($data['module']),
            'description'   => $data['description'] ?? null,
        ]);

        return $privilege;
    }

    /**
     * Bulk create privileges for a specific resource type.
     */
    public function createForResource(string $resourceName, array $actions = ['view', 'create', 'update', 'delete'], string $module = 'core'): Collection
    {
        $created = new Collection();
        $resourceClean = strtolower(trim($resourceName));
        $resourceNameLabel = ucfirst(str_replace('_', ' ', $resourceClean));

        DB::transaction(function () use ($resourceClean, $resourceNameLabel, $actions, $module, $created) {
            foreach ($actions as $action) {
                $actionClean = strtolower(trim($action));
                $actionLabel = ucfirst($actionClean);
                $slug = "{$actionClean}.{$resourceClean}";

                $privilege = Privilege::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name'          => "{$actionLabel} {$resourceNameLabel}",
                        'resource_type' => $resourceClean,
                        'module'        => strtolower($module),
                        'description'   => "Allows {$actionClean} operations on {$resourceClean}.",
                    ]
                );

                $created->push($privilege);
            }
        });

        return $created;
    }

    /**
     * Get privileges grouped/filtered by resource type.
     */
    public function getPrivilegesByResource(string $resourceType): Collection
    {
        return Privilege::where('resource_type', strtolower($resourceType))->get();
    }

    /**
     * Sync database privileges with a defined set of core resources.
     */
    public function synchronizePrivileges(): array
    {
        // Default package resources
        $resources = [
            'users'      => ['view', 'create', 'update', 'delete', 'suspend', 'impersonate'],
            'roles'      => ['view', 'create', 'update', 'delete'],
            'privileges' => ['view', 'create', 'update', 'delete'],
            'audit_logs' => ['view', 'export'],
            'settings'   => ['view', 'update'],
            'files'      => ['view', 'upload', 'delete'],
        ];

        // Merge/override with any user defined resources from config if set
        $configResources = config('admin-dashboard.authorization.resources', []);
        $resources = array_merge($resources, $configResources);

        $synced = [];
        foreach ($resources as $resource => $actions) {
            $module = in_array($resource, ['audit_logs', 'settings'], true) ? 'system' : 'core';
            $results = $this->createForResource($resource, $actions, $module);
            $synced[$resource] = $results->pluck('slug')->toArray();
        }

        return $synced;
    }
}

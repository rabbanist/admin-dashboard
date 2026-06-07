<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rabbanist\AdminDashboard\Contracts\AuditLoggerInterface;
use Rabbanist\AdminDashboard\Services\ResourceCrudService;
use Rabbanist\AdminDashboard\Services\DynamicFormGenerator;
use Rabbanist\AdminDashboard\Services\ResourceListBuilder;

class DynamicResourceController extends Controller
{
    public function __construct(
        protected readonly ResourceCrudService $crudService,
        protected readonly DynamicFormGenerator $formGenerator,
        protected readonly ResourceListBuilder $listBuilder,
        protected readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Display a listing of the resource records.
     */
    public function index(string $resourceName, Request $request)
    {
        $this->authorizeUserAccess('view', $resourceName);

        $config = $this->crudService->loadResource($resourceName);
        $columns = $this->crudService->getColumns($resourceName);

        $query = $this->listBuilder->buildQuery($resourceName);
        $query = $this->listBuilder->applyFilters($query, $resourceName, $request);
        $query = $this->listBuilder->applySorting($query, $resourceName, $request);

        $perPage = (int) $request->input('per_page', config('admin-dashboard.pagination.per_page', 25));
        $records = $this->listBuilder->paginate($query, $perPage);

        return view('admin-dashboard::dynamic.index', compact('resourceName', 'config', 'columns', 'records'));
    }

    /**
     * Show the form for creating a new resource record.
     */
    public function create(string $resourceName)
    {
        $this->authorizeUserAccess('create', $resourceName);

        $config = $this->crudService->loadResource($resourceName);
        $fields = $this->formGenerator->generateFormFields($resourceName);

        return view('admin-dashboard::dynamic.create', compact('resourceName', 'config', 'fields'));
    }

    /**
     * Store a newly created record.
     */
    public function store(string $resourceName, Request $request)
    {
        $this->authorizeUserAccess('create', $resourceName);

        $sanitized = $this->formGenerator->sanitizeInput($request->all());
        $this->crudService->validateInput($resourceName, $sanitized);

        // Upload any files
        $filePaths = $this->formGenerator->handleFileUploads($resourceName, $request);
        $saveData = array_merge($sanitized, $filePaths);

        $record = $this->crudService->createRecord($resourceName, $saveData);

        $this->auditLogger->log(
            action: 'resource_record_created',
            description: "Created a new record in {$resourceName} with ID #{$record->getKey()}",
            context: ['resource' => $resourceName, 'record_id' => $record->getKey()]
        );

        return redirect()->route('admin.resources.index', $resourceName)
            ->with('success', "Record #{$record->getKey()} created successfully.");
    }

    /**
     * Display the specified resource details.
     */
    public function show(string $resourceName, $id)
    {
        $this->authorizeUserAccess('view', $resourceName);

        $config = $this->crudService->loadResource($resourceName);
        $columns = $this->crudService->getColumns($resourceName);

        $modelClass = $this->crudService->getModelInstance($resourceName);
        $query = $modelClass::query();

        if (method_exists($query->getModel(), 'runSoftDelete')) {
            $query->withTrashed();
        }

        $record = $query->findOrFail($id);

        return view('admin-dashboard::dynamic.show', compact('resourceName', 'config', 'columns', 'record'));
    }

    /**
     * Show the form for editing the specified record.
     */
    public function edit(string $resourceName, $id)
    {
        $this->authorizeUserAccess('update', $resourceName);

        $config = $this->crudService->loadResource($resourceName);
        
        $modelClass = $this->crudService->getModelInstance($resourceName);
        $query = $modelClass::query();

        if (method_exists($query->getModel(), 'runSoftDelete')) {
            $query->withTrashed();
        }

        $record = $query->findOrFail($id);
        $fields = $this->formGenerator->generateFormFields($resourceName, $record);

        return view('admin-dashboard::dynamic.edit', compact('resourceName', 'config', 'fields', 'record'));
    }

    /**
     * Update the specified record.
     */
    public function update(string $resourceName, $id, Request $request)
    {
        $this->authorizeUserAccess('update', $resourceName);

        $sanitized = $this->formGenerator->sanitizeInput($request->all());
        $this->crudService->validateInput($resourceName, $sanitized, $id);

        $modelClass = $this->crudService->getModelInstance($resourceName);
        $query = $modelClass::query();

        if (method_exists($query->getModel(), 'runSoftDelete')) {
            $query->withTrashed();
        }
        $record = $query->findOrFail($id);

        // Upload files & merge data
        $filePaths = $this->formGenerator->handleFileUploads($resourceName, $request, $record);
        $saveData = array_merge($sanitized, $filePaths);

        $this->crudService->updateRecord($resourceName, $id, $saveData);

        $this->auditLogger->log(
            action: 'resource_record_updated',
            description: "Updated record ID #{$id} in {$resourceName}",
            context: ['resource' => $resourceName, 'record_id' => $id]
        );

        return redirect()->route('admin.resources.index', $resourceName)
            ->with('success', "Record #{$id} updated successfully.");
    }

    /**
     * Remove the specified record from storage.
     */
    public function destroy(string $resourceName, $id)
    {
        $this->authorizeUserAccess('delete', $resourceName);

        $modelClass = $this->crudService->getModelInstance($resourceName);
        $record = $modelClass::findOrFail($id);

        $record->delete();

        $this->auditLogger->log(
            action: 'resource_record_deleted',
            description: "Deleted record ID #{$id} in {$resourceName}",
            context: ['resource' => $resourceName, 'record_id' => $id]
        );

        return redirect()->route('admin.resources.index', $resourceName)
            ->with('success', "Record #{$id} deleted successfully.");
    }

    /**
     * Export resources list as CSV.
     */
    public function export(string $resourceName, Request $request)
    {
        $this->authorizeUserAccess('view', $resourceName);

        $query = $this->listBuilder->buildQuery($resourceName);
        $query = $this->listBuilder->applyFilters($query, $resourceName, $request);
        $query = $this->listBuilder->applySorting($query, $resourceName, $request);

        $records = $query->get();
        $columns = array_keys($this->crudService->getColumns($resourceName));

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$resourceName}_export_" . now()->format('YmdHis') . '.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($records, $columns) {
            $file = fopen('php://output', 'w');
            
            // Add Header
            fputcsv($file, $columns);

            foreach ($records as $record) {
                $row = [];
                foreach ($columns as $column) {
                    $val = $record->{$column};
                    if (is_array($val) || is_object($val)) {
                        $val = json_encode($val);
                    }
                    $row[] = $val;
                }
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Duplicate/clone a record.
     */
    public function cloneRecord(string $resourceName, $id)
    {
        $this->authorizeUserAccess('create', $resourceName);

        $modelClass = $this->crudService->getModelInstance($resourceName);
        $record = $modelClass::findOrFail($id);

        $clone = $record->replicate();
        
        // Auto append " (Clone)" to a name/title column if it exists to help visual identification
        if (isset($clone->name)) {
            $clone->name = $clone->name . ' (Clone)';
        } elseif (isset($clone->title)) {
            $clone->title = $clone->title . ' (Clone)';
        }

        $clone->save();

        $this->auditLogger->log(
            action: 'resource_record_cloned',
            description: "Cloned record ID #{$id} in {$resourceName} into ID #{$clone->getKey()}",
            context: ['resource' => $resourceName, 'original_id' => $id, 'clone_id' => $clone->getKey()]
        );

        return redirect()->back()
            ->with('success', "Record cloned successfully as ID #{$clone->getKey()}.");
    }

    /**
     * Apply bulk operations to records.
     */
    public function bulkAction(string $resourceName, Request $request)
    {
        $request->validate([
            'record_ids' => ['required', 'array'],
            'record_ids.*' => ['required'],
            'action'     => ['required', 'in:delete,restore'],
        ]);

        $ids = $request->record_ids;
        $action = $request->action;

        $modelClass = $this->crudService->getModelInstance($resourceName);
        
        if ($action === 'delete') {
            $this->authorizeUserAccess('delete', $resourceName);
            $count = $modelClass::whereIn('id', $ids)->delete();
        } elseif ($action === 'restore' && method_exists($modelClass, 'restore')) {
            $this->authorizeUserAccess('update', $resourceName);
            $count = $modelClass::onlyTrashed()->whereIn('id', $ids)->restore();
        } else {
            $count = 0;
        }

        $this->auditLogger->log(
            action: "bulk_resource_{$action}",
            description: "Bulk {$action} on {$count} records in {$resourceName}",
            context: ['resource' => $resourceName, 'record_ids' => $ids, 'count' => $count]
        );

        return redirect()->back()
            ->with('success', "Bulk action applied to {$count} records.");
    }

    /**
     * Check permissions matching action.resource convention.
     */
    protected function authorizeUserAccess(string $action, string $resource): void
    {
        $user = auth()->user();

        if (is_null($user)) {
            abort(401, 'Unauthenticated.');
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        $privilege = "{$action}.{$resource}";

        if (method_exists($user, 'hasPrivilege')) {
            if (! $user->hasPrivilege($privilege)) {
                abort(403, "You do not have permission to {$action} this resource.");
            }
        } else {
            abort(403, 'Unauthorized.');
        }
    }
}

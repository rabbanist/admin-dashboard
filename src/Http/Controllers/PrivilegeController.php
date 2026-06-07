<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Yourvendor\AdminDashboard\Contracts\AuditLoggerInterface;
use Yourvendor\AdminDashboard\Models\Privilege;
use Yourvendor\AdminDashboard\Services\PrivilegeService;

class PrivilegeController extends Controller
{
    public function __construct(
        protected readonly PrivilegeService $privilegeService,
        protected readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Display a listing of the privileges.
     */
    public function index()
    {
        $this->authorizeUserAccess('view.privileges');

        $privileges = Privilege::all()->groupBy('resource_type');

        return view('admin-dashboard::privileges.index', compact('privileges'));
    }

    /**
     * Show the form for creating a new privilege.
     */
    public function create()
    {
        $this->authorizeUserAccess('create.privileges');

        return view('admin-dashboard::privileges.create');
    }

    /**
     * Store a newly created privilege in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeUserAccess('create.privileges');

        if ($request->boolean('generate_crud')) {
            $request->validate([
                'resource_type' => ['required', 'string', 'max:255'],
                'module'        => ['required', 'string', 'max:255'],
            ]);

            $actions = ['view', 'create', 'update', 'delete'];
            $created = $this->privilegeService->createForResource(
                $request->input('resource_type'),
                $actions,
                $request->input('module', 'core')
            );

            $this->auditLogger->log(
                action: 'privilege_crud_generated',
                description: "Bulk generated CRUD privileges for resource [{$request->resource_type}]",
                context: ['resource_type' => $request->resource_type, 'actions' => $actions]
            );

            return redirect()->route('admin.privileges.index')
                ->with('success', "CRUD privileges generated for [{$request->resource_type}].");
        }

        $privilege = $this->privilegeService->createPrivilege($request->all());

        $this->auditLogger->log(
            action: 'privilege_created',
            description: "Created privilege [{$privilege->name}]",
            context: ['privilege_id' => $privilege->id, 'slug' => $privilege->slug]
        );

        return redirect()->route('admin.privileges.index')
            ->with('success', "Privilege [{$privilege->name}] has been created successfully.");
    }

    /**
     * Show the form for editing the specified privilege.
     */
    public function edit($id)
    {
        $this->authorizeUserAccess('update.privileges');

        $privilege = Privilege::findOrFail($id);

        return view('admin-dashboard::privileges.edit', compact('privilege'));
    }

    /**
     * Update the specified privilege in storage.
     */
    public function update(Request $request, $id)
    {
        $this->authorizeUserAccess('update.privileges');

        $privilege = Privilege::findOrFail($id);
        $this->privilegeService->updatePrivilege($privilege, $request->all());

        $this->auditLogger->log(
            action: 'privilege_updated',
            description: "Updated privilege [{$privilege->name}]",
            context: ['privilege_id' => $privilege->id, 'slug' => $privilege->slug]
        );

        return redirect()->route('admin.privileges.index')
            ->with('success', "Privilege [{$privilege->name}] has been updated successfully.");
    }

    /**
     * Remove the specified privilege from storage.
     */
    public function destroy($id)
    {
        $this->authorizeUserAccess('delete.privileges');

        $privilege = Privilege::findOrFail($id);
        $name = $privilege->name;

        $privilege->delete();

        $this->auditLogger->log(
            action: 'privilege_deleted',
            description: "Deleted privilege [{$name}]",
            context: ['name' => $name]
        );

        return redirect()->route('admin.privileges.index')
            ->with('success', "Privilege [{$name}] has been deleted successfully.");
    }

    /**
     * Validate that the current user has the required privilege.
     */
    protected function authorizeUserAccess(string $privilege): void
    {
        $user = auth()->user();

        if (is_null($user)) {
            abort(401, 'Unauthenticated.');
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return;
        }

        if (method_exists($user, 'hasPrivilege')) {
            if (! $user->hasPrivilege($privilege)) {
                abort(403, "You do not have the required privilege [{$privilege}].");
            }
        } else {
            abort(403, 'Unauthorized.');
        }
    }
}

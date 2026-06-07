<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rabbanist\AdminDashboard\Contracts\AuditLoggerInterface;
use Rabbanist\AdminDashboard\Models\Role;
use Rabbanist\AdminDashboard\Models\Privilege;
use Rabbanist\AdminDashboard\Services\RoleService;

class RoleController extends Controller
{
    public function __construct(
        protected readonly RoleService $roleService,
        protected readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Display a listing of the roles.
     */
    public function index()
    {
        $this->authorizeUserAccess('view.roles');

        $roles = Role::withCount(['privileges', 'users'])->get();

        return view('admin-dashboard::roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $this->authorizeUserAccess('create.roles');

        $privileges = Privilege::all();

        return view('admin-dashboard::roles.create', compact('privileges'));
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeUserAccess('create.roles');

        $role = $this->roleService->createRole($request->all());

        $this->auditLogger->log(
            action: 'role_created',
            description: "Created role [{$role->name}]",
            context: ['role_id' => $role->id, 'slug' => $role->slug]
        );

        return redirect()->route('admin.roles.index')
            ->with('success', "Role [{$role->name}] has been created successfully.");
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit($id)
    {
        $this->authorizeUserAccess('update.roles');

        $role = Role::with('privileges')->findOrFail($id);
        $privileges = Privilege::all();

        return view('admin-dashboard::roles.edit', compact('role', 'privileges'));
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, $id)
    {
        $this->authorizeUserAccess('update.roles');

        $role = Role::findOrFail($id);
        $this->roleService->updateRole($role, $request->all());

        $this->auditLogger->log(
            action: 'role_updated',
            description: "Updated role [{$role->name}]",
            context: ['role_id' => $role->id, 'slug' => $role->slug]
        );

        return redirect()->route('admin.roles.index')
            ->with('success', "Role [{$role->name}] has been updated successfully.");
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy($id)
    {
        $this->authorizeUserAccess('delete.roles');

        $role = Role::findOrFail($id);
        $name = $role->name;

        $this->roleService->deleteRole($role);

        $this->auditLogger->log(
            action: 'role_deleted',
            description: "Deleted role [{$name}]",
            context: ['name' => $name]
        );

        return redirect()->route('admin.roles.index')
            ->with('success', "Role [{$name}] has been deleted successfully.");
    }

    /**
     * Display the view to assign users to a role.
     */
    public function assignUsers($id)
    {
        $this->authorizeUserAccess('update.roles');

        $role = Role::with('users')->findOrFail($id);
        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        $users = $userModelClass::all();

        return view('admin-dashboard::roles.assign-users', compact('role', 'users'));
    }

    /**
     * Bulk assign users to a role.
     */
    public function syncUsers(Request $request, $id)
    {
        $this->authorizeUserAccess('update.roles');

        $request->validate([
            'user_ids'   => ['nullable', 'array'],
            'user_ids.*' => ['exists:' . (new (config('admin-dashboard.user_model', \App\Models\User::class)))->getTable() . ',id'],
        ]);

        $role = Role::findOrFail($id);
        $userIds = $request->input('user_ids', []);

        $this->roleService->syncUsers($role, $userIds);

        $this->auditLogger->log(
            action: 'role_users_synced',
            description: "Synced users for role [{$role->name}]",
            context: ['role_id' => $role->id, 'user_ids' => $userIds]
        );

        return redirect()->route('admin.roles.index')
            ->with('success', "Users successfully assigned to role [{$role->name}].");
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

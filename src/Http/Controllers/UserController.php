<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Yourvendor\AdminDashboard\Contracts\AuditLoggerInterface;
use Yourvendor\AdminDashboard\Http\Requests\StoreUserRequest;
use Yourvendor\AdminDashboard\Http\Requests\UpdateUserRequest;
use Yourvendor\AdminDashboard\Models\Role;
use Yourvendor\AdminDashboard\Models\Privilege;
use Yourvendor\AdminDashboard\Models\AuditLog;

class UserController extends Controller
{
    public function __construct(
        protected readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        $this->authorizeUserAccess('view.users');

        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        $query = $userModelClass::query();

        // 1. Search by name/email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 2. Filter by role
        if ($roleId = $request->input('role_id')) {
            $query->whereHas('roles', function ($q) use ($roleId) {
                $q->where('admin_roles.id', $roleId);
            });
        }

        // 3. Filter by status (active/suspended/deleted)
        if ($status = $request->input('status')) {
            if ($status === 'suspended') {
                $query->whereNotNull('suspended_at');
            } elseif ($status === 'active') {
                $query->whereNull('suspended_at');
            } elseif ($status === 'deleted' && method_exists($query->getModel(), 'runSoftDelete')) {
                // If model has soft deletes, retrieve only deleted or withTrashed
                $query->onlyTrashed();
            }
        }

        // 4. Sorting
        $sort = $request->input('sort', 'created_at');
        $direction = $request->input('direction', 'desc');
        $allowedSorts = ['name', 'email', 'last_login_at', 'created_at'];

        if (in_array($sort, $allowedSorts, true)) {
            $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');
        }

        $perPage = config('admin-dashboard.pagination.per_page', 25);
        $users = $query->with('roles')->paginate($perPage)->withQueryString();

        $roles = Role::all();

        return view('admin-dashboard::users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $this->authorizeUserAccess('create.users');

        $roles = Role::all();
        $privileges = Privilege::all();

        return view('admin-dashboard::users.create', compact('roles', 'privileges'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $this->authorizeUserAccess('create.users');

        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        
        $user = new $userModelClass();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->bio = $request->bio;
        $user->phone = $request->phone;
        
        if (isset($user->two_factor_enabled)) {
            $user->two_factor_enabled = (bool) $request->input('two_factor_enabled', false);
        }

        $user->save();

        // Assign Roles
        if ($request->has('roles') && method_exists($user, 'syncRoles')) {
            $user->syncRoles($request->roles);
        }

        // Trigger Email Verification if applicable
        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail) {
            $user->sendEmailVerificationNotification();
        }

        $this->auditLogger->log(
            action: 'user_created',
            description: "Created user account for {$user->email}",
            context: ['user_id' => $user->getKey(), 'email' => $user->email]
        );

        return redirect()->route('admin.users.index')
            ->with('success', "User [{$user->name}] was successfully created.");
    }

    /**
     * Display the specified user details.
     */
    public function show(Request $request, $id)
    {
        $this->authorizeUserAccess('view.users');

        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        
        // Include soft deleted users if the model supports it
        $query = $userModelClass::query();
        if (method_exists($query->getModel(), 'runSoftDelete')) {
            $query->withTrashed();
        }
        
        $user = $query->with(['roles', 'privileges'])->findOrFail($id);

        // Fetch activity timeline for this user from AuditLog
        $activityLogs = AuditLog::where('user_id', $user->getKey())
            ->orderByDesc('performed_at')
            ->paginate(10, ['*'], 'activity_page');

        return view('admin-dashboard::users.show', compact('user', 'activityLogs'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($id)
    {
        $this->authorizeUserAccess('update.users');

        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        $query = $userModelClass::query();
        if (method_exists($query->getModel(), 'runSoftDelete')) {
            $query->withTrashed();
        }
        
        $user = $query->with('roles')->findOrFail($id);
        $roles = Role::all();
        $privileges = Privilege::all();

        return view('admin-dashboard::users.edit', compact('user', 'roles', 'privileges'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, $id)
    {
        $this->authorizeUserAccess('update.users');

        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        $query = $userModelClass::query();
        if (method_exists($query->getModel(), 'runSoftDelete')) {
            $query->withTrashed();
        }
        
        $user = $query->findOrFail($id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->bio = $request->bio;
        $user->phone = $request->phone;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if (isset($user->two_factor_enabled)) {
            $user->two_factor_enabled = (bool) $request->input('two_factor_enabled', false);
        }

        // Photo upload handling
        if ($request->hasFile('profile_photo')) {
            $this->handlePhotoUpload($request, $user);
        }

        $user->save();

        // Sync Roles
        if ($request->has('roles') && method_exists($user, 'syncRoles')) {
            $user->syncRoles($request->roles);
        }

        $this->auditLogger->log(
            action: 'user_updated',
            description: "Updated user account for {$user->email}",
            context: ['user_id' => $user->getKey(), 'email' => $user->email]
        );

        return redirect()->route('admin.users.index')
            ->with('success', "User [{$user->name}] was successfully updated.");
    }

    /**
     * Remove the specified user from storage (Soft delete/Hard delete).
     */
    public function destroy($id)
    {
        $this->authorizeUserAccess('delete.users');

        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        $user = $userModelClass::findOrFail($id);

        // Prevent self deletion
        if (auth()->id() === $user->getKey()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        $this->auditLogger->log(
            action: 'user_deleted',
            description: "Deleted user {$user->email}",
            context: ['user_id' => $user->getKey()]
        );

        return redirect()->route('admin.users.index')
            ->with('success', "User [{$user->name}] has been successfully deleted.");
    }

    /**
     * Suspend the user account.
     */
    public function suspend(Request $request, $id)
    {
        $this->authorizeUserAccess('suspend.users');

        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        $user = $userModelClass::findOrFail($id);

        if (auth()->id() === $user->getKey()) {
            return redirect()->back()->with('error', 'You cannot suspend your own account.');
        }

        $reason = $request->input('reason', 'Suspended by Administrator');

        if (method_exists($user, 'suspend')) {
            $user->suspend($reason);
        } else {
            $user->update([
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ]);
        }

        $this->auditLogger->log(
            action: 'user_suspended',
            description: "Suspended user {$user->email} — Reason: {$reason}",
            context: ['user_id' => $user->getKey(), 'reason' => $reason]
        );

        return redirect()->back()->with('success', "User [{$user->name}] account has been suspended.");
    }

    /**
     * Restore a suspended or deleted user.
     */
    public function restore($id)
    {
        $this->authorizeUserAccess('suspend.users');

        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        $query = $userModelClass::query();
        if (method_exists($query->getModel(), 'runSoftDelete')) {
            $query->withTrashed();
        }
        
        $user = $query->findOrFail($id);

        // Restore soft-deleted state
        if (method_exists($user, 'restore') && $user->trashed()) {
            $user->restore();
        }

        // Lift suspension
        if (method_exists($user, 'unsuspend')) {
            $user->unsuspend();
        } else {
            $user->update([
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);
        }

        $this->auditLogger->log(
            action: 'user_restored',
            description: "Restored user account {$user->email}",
            context: ['user_id' => $user->getKey()]
        );

        return redirect()->back()->with('success', "User [{$user->name}] account has been restored.");
    }

    /**
     * Handle profile photo upload.
     */
    public function updatePhoto(Request $request, $id)
    {
        $this->authorizeUserAccess('update.users');

        $request->validate([
            'profile_photo' => ['required', 'image', 'max:2048', 'mimes:jpeg,png,jpg,gif,webp'],
        ]);

        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        $user = $userModelClass::findOrFail($id);

        $this->handlePhotoUpload($request, $user);
        $user->save();

        $this->auditLogger->log(
            action: 'profile_photo_updated',
            description: "Updated profile photo for user {$user->email}",
            context: ['user_id' => $user->getKey()]
        );

        return redirect()->back()->with('success', 'Profile photo updated successfully.');
    }

    /**
     * Start user impersonation.
     */
    public function impersonate(Request $request, $id)
    {
        $this->authorizeUserAccess('impersonate.users');

        if (! config('admin-dashboard.features.user_impersonation', false)) {
            abort(403, 'User impersonation is not enabled.');
        }

        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        $targetUser = $userModelClass::findOrFail($id);

        if (auth()->id() === $targetUser->getKey()) {
            return redirect()->back()->with('error', 'You cannot impersonate yourself.');
        }

        // Store current user ID as impersonator
        $request->session()->put('impersonator_id', auth()->id());

        // Authenticate as the target user
        auth()->login($targetUser);

        $this->auditLogger->log(
            action: 'impersonation_started',
            description: "Started impersonating user {$targetUser->email}",
            context: ['target_user_id' => $targetUser->getKey()]
        );

        return redirect()->route('admin.dashboard')
            ->with('success', "You are now impersonating [{$targetUser->name}].");
    }

    /**
     * Stop user impersonation.
     */
    public function stopImpersonating(Request $request)
    {
        if (! $request->session()->has('impersonator_id')) {
            return redirect()->route('admin.dashboard');
        }

        $impersonatorId = $request->session()->pull('impersonator_id');
        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        $originalAdmin = $userModelClass::findOrFail($impersonatorId);

        // Authenticate back as administrator
        auth()->login($originalAdmin);

        $this->auditLogger->log(
            action: 'impersonation_stopped',
            description: "Stopped impersonating user",
            context: ['admin_user_id' => $originalAdmin->getKey()]
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'Returned to administrator session.');
    }

    /**
     * Handle bulk operations for users.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'user_ids' => ['required', 'array'],
            'user_ids.*' => ['required'],
            'action'   => ['required', 'in:delete,suspend,restore'],
            'reason'   => ['nullable', 'string', 'max:255'],
        ]);

        $userIds = $request->user_ids;
        $action = $request->action;
        $reason = $request->input('reason', 'Bulk administrative action');

        $userModelClass = config('admin-dashboard.user_model', \App\Models\User::class);
        $users = $userModelClass::whereIn('id', $userIds)->get();

        $count = 0;
        foreach ($users as $user) {
            // Protect current user
            if ($user->getKey() === auth()->id()) {
                continue;
            }

            if ($action === 'delete') {
                $this->authorizeUserAccess('delete.users');
                $user->delete();
                $count++;
            } elseif ($action === 'suspend') {
                $this->authorizeUserAccess('suspend.users');
                if (method_exists($user, 'suspend')) {
                    $user->suspend($reason);
                } else {
                    $user->update([
                        'suspended_at' => now(),
                        'suspension_reason' => $reason,
                    ]);
                }
                $count++;
            } elseif ($action === 'restore') {
                $this->authorizeUserAccess('suspend.users');
                if (method_exists($user, 'restore') && method_exists($user, 'trashed') && $user->trashed()) {
                    $user->restore();
                }
                if (method_exists($user, 'unsuspend')) {
                    $user->unsuspend();
                } else {
                    $user->update([
                        'suspended_at' => null,
                        'suspension_reason' => null,
                    ]);
                }
                $count++;
            }
        }

        $this->auditLogger->log(
            action: "bulk_{$action}",
            description: "Performed bulk {$action} on {$count} user(s).",
            context: ['user_ids' => $userIds, 'reason' => $reason, 'count' => $count]
        );

        return redirect()->back()->with('success', "Bulk action [{$action}] applied to {$count} user(s).");
    }

    /**
     * Perform photo file upload securely.
     */
    protected function handlePhotoUpload(Request $request, $user): void
    {
        // Delete old photo file if it exists
        if ($user->profile_photo_path) {
            $disk = config('admin-dashboard.uploads.disk', 'public');
            if (Storage::disk($disk)->exists($user->profile_photo_path)) {
                Storage::disk($disk)->delete($user->profile_photo_path);
            }
        }

        // Store new photo file
        $disk = config('admin-dashboard.uploads.disk', 'public');
        $path = $request->file('profile_photo')->store('admin-uploads/profile-photos', $disk);
        $user->profile_photo_path = $path;
    }

    /**
     * Enforce privilege authorization helper.
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
            // Fallback if privileges trait is not present
            abort(403, 'Unauthorized.');
        }
    }
}

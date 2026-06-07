@extends('admin-dashboard::layouts.app')

@section('title', 'Role Management - ' . config('admin-dashboard.title', 'Admin Dashboard'))

@section('sidebar')
    <ul class="admin-sidebar-nav">
        <li>
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        </li>
        <li>
            <a href="{{ route('admin.users.index') }}">Users</a>
        </li>
        <li class="active">
            <a href="{{ route('admin.roles.index') }}">Roles</a>
        </li>
        <li>
            <a href="{{ route('admin.privileges.index') }}">Privileges</a>
        </li>
    </ul>
@endsection

@section('content')
    <div class="admin-header">
        <h1>Role Management</h1>
        @admin
            <a href="{{ route('admin.roles.create') }}" class="admin-btn admin-btn--primary">
                Add Role
            </a>
        @endadmin
    </div>

    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Role Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Privileges</th>
                    <th>Users</th>
                    <th>System Role</th>
                    <th width="200" style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td style="font-weight: 700; color: #0f172a;">{{ $role->name }}</td>
                        <td><code>{{ $role->slug }}</code></td>
                        <td style="color: var(--admin-text-muted); font-size: 0.85rem;">{{ $role->description ?? 'No description.' }}</td>
                        <td>
                            <span class="admin-badge admin-badge--info">{{ $role->privileges_count }} privileges</span>
                        </td>
                        <td>
                            <span class="admin-badge admin-badge--neutral">{{ $role->users_count }} users</span>
                        </td>
                        <td>
                            @if($role->is_protected)
                                <span class="admin-badge admin-badge--success">Protected</span>
                            @else
                                <span class="admin-badge admin-badge--neutral">Custom</span>
                            @endif
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <div style="display: inline-flex; gap: 0.35rem;">
                                <a href="{{ route('admin.roles.assign-users', $role->id) }}" class="admin-btn admin-btn--secondary admin-btn--sm" title="Assign Users">
                                    Sync Users
                                </a>

                                <a href="{{ route('admin.roles.edit', $role->id) }}" class="admin-btn admin-btn--secondary admin-btn--sm" title="Edit Role">
                                    Edit
                                </a>

                                @if(! $role->is_protected)
                                    <button type="button" onclick="triggerDeleteRole({{ $role->id }}, '{{ $role->name }}')" class="admin-btn admin-btn--danger admin-btn--sm" title="Delete Role">
                                        Delete
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 3rem 0;">
                            No roles found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Delete Role Modal --}}
    <div id="delete-role-modal" class="admin-modal">
        <div class="admin-modal__backdrop" onclick="closeModal('delete-role-modal')"></div>
        <div class="admin-modal__content">
            <div class="admin-modal__header">
                <h3>Delete Role</h3>
                <button type="button" onclick="closeModal('delete-role-modal')" style="background: none; border: none; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            <div class="admin-modal__body">
                <p>Are you sure you want to delete the role <strong id="delete-role-name"></strong>? This will detach the role from all assigned users. This action cannot be undone.</p>
            </div>
            <div class="admin-modal__footer">
                <button type="button" onclick="closeModal('delete-role-modal')" class="admin-btn admin-btn--secondary">Cancel</button>
                <form id="delete-role-form" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="admin-btn admin-btn--danger">Delete Role</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Toast Notification container --}}
    <div class="toast-container" id="toast-container"></div>

    @push('scripts')
    <script>
        function triggerDeleteRole(roleId, roleName) {
            document.getElementById('delete-role-name').innerText = roleName;
            const form = document.getElementById('delete-role-form');
            form.action = `{{ url(config('admin-dashboard.route_prefix', 'admin') . '/roles') }}/${roleId}`;
            openModal('delete-role-modal');
        }

        function openModal(id) {
            document.getElementById(id).classList.add('open');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `admin-toast admin-toast--${type}`;
            toast.innerHTML = `<span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        @if(session('success'))
            showToast("{{ session('success') }}", 'success');
        @endif
        @if(session('error'))
            showToast("{{ session('error') }}", 'error');
        @endif
    </script>
    @endpush
@endsection

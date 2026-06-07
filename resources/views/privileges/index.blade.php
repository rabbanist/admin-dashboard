@extends('admin-dashboard::layouts.app')

@section('title', 'Privilege Management - ' . config('admin-dashboard.title', 'Admin Dashboard'))

@section('sidebar')
    <ul class="admin-sidebar-nav">
        <li>
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        </li>
        <li>
            <a href="{{ route('admin.users.index') }}">Users</a>
        </li>
        <li>
            <a href="{{ route('admin.roles.index') }}">Roles</a>
        </li>
        <li class="active">
            <a href="{{ route('admin.privileges.index') }}">Privileges</a>
        </li>
    </ul>
@endsection

@section('content')
    <div class="admin-header">
        <h1>Privilege Management</h1>
        <div style="display: flex; gap: 0.5rem;">
            @admin
                <a href="{{ route('admin.privileges.create') }}" class="admin-btn admin-btn--primary">
                    Add Privilege
                </a>
            @endadmin
        </div>
    </div>

    {{-- Privilege listing grouped by resource --}}
    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
        @forelse($privileges as $resource => $resourcePrivileges)
            <div class="admin-card" style="padding: 1.5rem; border-top: 4px solid var(--admin-info);">
                <h3 style="text-transform: uppercase; font-size: 0.95rem; font-weight: 800; color: #0f172a; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
                    <span>{{ str_replace('_', ' ', $resource) }}</span>
                    <span class="admin-badge admin-badge--info" style="font-size: 0.7rem;">{{ $resourcePrivileges->count() }} privileges</span>
                </h3>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Privilege Name</th>
                                <th>Slug</th>
                                <th>Module</th>
                                <th>Description</th>
                                <th width="100" style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resourcePrivileges as $privilege)
                                <tr>
                                    <td style="font-weight: 700; color: #1e293b;">{{ $privilege->name }}</td>
                                    <td><code>{{ $privilege->slug }}</code></td>
                                    <td>
                                        <span class="admin-badge admin-badge--neutral">{{ $privilege->module }}</span>
                                    </td>
                                    <td style="font-size: 0.825rem; color: var(--admin-text-muted);">{{ $privilege->description ?? 'No description.' }}</td>
                                    <td style="text-align: right;">
                                        <button type="button" onclick="triggerDeletePrivilege({{ $privilege->id }}, '{{ $privilege->name }}')" class="admin-btn admin-btn--danger admin-btn--sm" title="Delete Privilege">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="admin-card" style="text-align: center; color: #94a3b8; padding: 4rem 0;">
                No privileges found. Use "Add Privilege" to register new permissions or bulk generate CRUD controls.
            </div>
        @endforelse
    </div>

    {{-- Delete Privilege Modal --}}
    <div id="delete-privilege-modal" class="admin-modal">
        <div class="admin-modal__backdrop" onclick="closeModal('delete-privilege-modal')"></div>
        <div class="admin-modal__content">
            <div class="admin-modal__header">
                <h3>Delete Privilege</h3>
                <button type="button" onclick="closeModal('delete-privilege-modal')" style="background: none; border: none; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            <div class="admin-modal__body">
                <p>Are you sure you want to delete the privilege <strong id="delete-privilege-name"></strong>? This will detach the privilege from all roles. This action cannot be undone.</p>
            </div>
            <div class="admin-modal__footer">
                <button type="button" onclick="closeModal('delete-privilege-modal')" class="admin-btn admin-btn--secondary">Cancel</button>
                <form id="delete-privilege-form" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="admin-btn admin-btn--danger">Delete Privilege</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Toast Notification container --}}
    <div class="toast-container" id="toast-container"></div>

    @push('scripts')
    <script>
        function triggerDeletePrivilege(privilegeId, privilegeName) {
            document.getElementById('delete-privilege-name').innerText = privilegeName;
            const form = document.getElementById('delete-privilege-form');
            form.action = `{{ url(config('admin-dashboard.route_prefix', 'admin') . '/privileges') }}/${privilegeId}`;
            openModal('delete-privilege-modal');
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

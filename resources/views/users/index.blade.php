@extends('admin-dashboard::layouts.app')

@section('title', 'User Management - ' . config('admin-dashboard.title', 'Admin Dashboard'))

@section('sidebar')
    <ul class="admin-sidebar-nav">
        <li>
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        </li>
        <li class="active">
            <a href="{{ route('admin.users.index') }}">Users</a>
        </li>
    </ul>
@endsection

@section('content')
    {{-- Impersonation active banner --}}
    @if(session()->has('impersonator_id'))
        <div class="impersonation-banner">
            <span>You are currently impersonating a user.</span>
            <form action="{{ route('admin.users.stop-impersonating') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit">Return to Admin</button>
            </form>
        </div>
    @endif

    <div class="admin-header">
        <h1>Users List</h1>
        @admin
            <a href="{{ route('admin.users.create') }}" class="admin-btn admin-btn--primary">
                Add User
            </a>
        @endadmin
    </div>

    {{-- Filter and Search Controls --}}
    <div class="admin-controls-panel">
        <form method="GET" action="{{ route('admin.users.index') }}" class="admin-search-form">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..." class="admin-input">
            <button type="submit" class="admin-btn admin-btn--secondary">Search</button>
            @if(request('search') || request('role_id') || request('status'))
                <a href="{{ route('admin.users.index') }}" class="admin-btn admin-btn--secondary" title="Clear Filters">Clear</a>
            @endif
        </form>

        <form method="GET" action="{{ route('admin.users.index') }}" class="admin-filter-group">
            <select name="role_id" onchange="this.form.submit()" class="admin-select">
                <option value="">All Roles</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()" class="admin-select">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="deleted" {{ request('status') === 'deleted' ? 'selected' : '' }}>Deleted</option>
            </select>
        </form>
    </div>

    {{-- Bulk Action & Main Table form --}}
    <form id="bulk-action-form" method="POST" action="{{ route('admin.users.bulk') }}">
        @csrf
        
        <div class="admin-controls-panel" style="margin-bottom: 1rem; padding: 0.75rem 1.25rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <select name="action" id="bulk-action-select" class="admin-select" style="width: auto;">
                    <option value="">Bulk Actions</option>
                    <option value="suspend">Suspend Selected</option>
                    <option value="restore">Restore Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <input type="text" name="reason" id="bulk-action-reason" placeholder="Reason (for suspension)..." class="admin-input" style="width: 250px; display: none;">
                <button type="button" onclick="confirmBulkAction()" class="admin-btn admin-btn--secondary admin-btn--sm">Apply</button>
            </div>
        </div>

        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" id="select-all-checkbox" class="admin-checkbox">
                        </th>
                        <th>User</th>
                        <th class="sortable" onclick="window.location.href='{{ request()->fullUrlWithQuery(['sort' => 'email', 'direction' => request('sort') === 'email' && request('direction') === 'asc' ? 'desc' : 'asc']) }}'">
                            Email
                            @if(request('sort') === 'email')
                                {!! request('direction') === 'asc' ? '&#8593;' : '&#8595;' !!}
                            @endif
                        </th>
                        <th>Roles</th>
                        <th class="sortable" onclick="window.location.href='{{ request()->fullUrlWithQuery(['sort' => 'last_login_at', 'direction' => request('sort') === 'last_login_at' && request('direction') === 'asc' ? 'desc' : 'asc']) }}'">
                            Last Login
                            @if(request('sort') === 'last_login_at')
                                {!! request('direction') === 'asc' ? '&#8593;' : '&#8595;' !!}
                            @endif
                        </th>
                        <th>Status</th>
                        <th width="150" style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            $photoUrl = $user->profile_photo_path 
                                ? Storage::disk(config('admin-dashboard.uploads.disk', 'public'))->url($user->profile_photo_path)
                                : 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->email))) . '?d=mp';
                        @endphp
                        <tr>
                            <td>
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="admin-checkbox user-checkbox">
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <img src="{{ $photoUrl }}" alt="{{ $user->name }}" class="admin-avatar">
                                    <div>
                                        <div style="font-weight: 700; color: #0f172a;">{{ $user->name }}</div>
                                        <span style="font-size: 0.75rem; color: #64748b;">ID: {{ $user->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @forelse($user->roles as $role)
                                    <span class="admin-badge admin-badge--neutral" style="margin-right: 0.25rem; font-size: 0.7rem;">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span style="font-size: 0.75rem; color: #cbd5e1;">None</span>
                                @endforelse
                            </td>
                            <td>
                                @if($user->last_login_at)
                                    <span title="{{ $user->last_login_at }}">{{ $user->last_login_at->diffForHumans() }}</span>
                                    <div style="font-size: 0.75rem; color: #94a3b8;">{{ $user->last_login_ip }}</div>
                                @else
                                    <span style="font-size: 0.75rem; color: #cbd5e1;">Never</span>
                                @endif
                            </td>
                            <td>
                                @if(method_exists($user, 'trashed') && $user->trashed())
                                    <span class="admin-badge admin-badge--danger">Deleted</span>
                                @elseif(method_exists($user, 'isSuspended') && $user->isSuspended())
                                    <span class="admin-badge admin-badge--warning" title="Reason: {{ $user->suspension_reason }}">Suspended</span>
                                @else
                                    <span class="admin-badge admin-badge--success">Active</span>
                                @endif
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 0.35rem;">
                                    <a href="{{ route('admin.users.show', $user->id) }}" class="admin-btn admin-btn--secondary admin-btn--sm" title="View Details">
                                        View
                                    </a>
                                    
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="admin-btn admin-btn--secondary admin-btn--sm" title="Edit User">
                                        Edit
                                    </a>

                                    @if(config('admin-dashboard.features.user_impersonation', false) && auth()->id() !== $user->id && (!method_exists($user, 'isSuspended') || !$user->isSuspended()))
                                        <button type="button" onclick="triggerImpersonation({{ $user->id }}, '{{ $user->name }}')" class="admin-btn admin-btn--secondary admin-btn--sm" title="Impersonate User" style="background-color: var(--admin-info-light); color: #155e75; border-color: transparent;">
                                            Login
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: #94a3b8; padding: 3rem 0;">
                                No users found matching the criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Custom styled pagination --}}
            @if($users->hasPages())
                <div class="admin-pagination-wrapper">
                    <div style="font-size: 0.875rem; color: #64748b;">
                        Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
                    </div>
                    <div>
                        {!! $users->links() !!}
                    </div>
                </div>
            @endif
        </div>
    </form>

    {{-- Interactive Modals --}}
    
    {{-- Impersonate Confirmation Modal --}}
    <div id="impersonate-modal" class="admin-modal">
        <div class="admin-modal__backdrop" onclick="closeModal('impersonate-modal')"></div>
        <div class="admin-modal__content">
            <div class="admin-modal__header">
                <h3>Confirm Impersonation</h3>
                <button type="button" onclick="closeModal('impersonate-modal')" style="background: none; border: none; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            <div class="admin-modal__body">
                <p>Are you sure you want to log in as user <strong id="impersonate-username"></strong>? Your active administrator session will be saved, and you can return to it at any time.</p>
            </div>
            <div class="admin-modal__footer">
                <button type="button" onclick="closeModal('impersonate-modal')" class="admin-btn admin-btn--secondary">Cancel</button>
                <form id="impersonate-form" method="POST" action="">
                    @csrf
                    <button type="submit" class="admin-btn admin-btn--primary">Confirm</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Bulk Action Confirmation Modal --}}
    <div id="bulk-modal" class="admin-modal">
        <div class="admin-modal__backdrop" onclick="closeModal('bulk-modal')"></div>
        <div class="admin-modal__content">
            <div class="admin-modal__header">
                <h3>Confirm Bulk Action</h3>
                <button type="button" onclick="closeModal('bulk-modal')" style="background: none; border: none; cursor: pointer; font-size: 1.25rem;">&times;</button>
            </div>
            <div class="admin-modal__body">
                <p>Are you sure you want to perform bulk <strong id="bulk-action-text"></strong> on all selected users?</p>
            </div>
            <div class="admin-modal__footer">
                <button type="button" onclick="closeModal('bulk-modal')" class="admin-btn admin-btn--secondary">Cancel</button>
                <button type="button" onclick="submitBulkForm()" class="admin-btn admin-btn--danger">Execute</button>
            </div>
        </div>
    </div>

    {{-- Dynamic Toast Notification --}}
    <div class="toast-container" id="toast-container"></div>

    @push('scripts')
    <script>
        // Select All handler
        document.getElementById('select-all-checkbox').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // Toggle reason field for bulk action
        document.getElementById('bulk-action-select').addEventListener('change', function() {
            const reasonInput = document.getElementById('bulk-action-reason');
            if (this.value === 'suspend') {
                reasonInput.style.display = 'block';
            } else {
                reasonInput.style.display = 'none';
            }
        });

        // Impersonation modal trigger
        function triggerImpersonation(userId, userName) {
            document.getElementById('impersonate-username').innerText = userName;
            const form = document.getElementById('impersonate-form');
            form.action = `{{ url(config('admin-dashboard.route_prefix', 'admin') . '/users') }}/${userId}/impersonate`;
            openModal('impersonate-modal');
        }

        // Modal Helpers
        function openModal(id) {
            document.getElementById(id).classList.add('open');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
        }

        // Bulk Action helpers
        function confirmBulkAction() {
            const action = document.getElementById('bulk-action-select').value;
            const selected = document.querySelectorAll('.user-checkbox:checked').length;
            
            if (!action) {
                showToast('Please select a bulk action.', 'error');
                return;
            }
            if (selected === 0) {
                showToast('Please select at least one user.', 'error');
                return;
            }

            document.getElementById('bulk-action-text').innerText = `${action} (${selected} user${selected > 1 ? 's' : ''})`;
            openModal('bulk-modal');
        }

        function submitBulkForm() {
            document.getElementById('bulk-action-form').submit();
        }

        // JavaScript Toast Trigger
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

        // Handle Laravel Flash Messages via JS Toast
        @if(session('success'))
            showToast("{{ session('success') }}", 'success');
        @endif
        @if(session('error'))
            showToast("{{ session('error') }}", 'error');
        @endif
    </script>
    @endpush
@endsection

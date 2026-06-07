@extends('admin-dashboard::layouts.app')

@section('title', 'Assign Users - ' . $role->name . ' - ' . config('admin-dashboard.title', 'Admin Dashboard'))

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
        <h1>Assign Users to Role: {{ $role->name }}</h1>
        <a href="{{ route('admin.roles.index') }}" class="admin-btn admin-btn--secondary">
            Back to List
        </a>
    </div>

    <div class="admin-card">
        <p style="color: var(--admin-text-muted); margin-bottom: 1.5rem;">
            Select which users should be assigned the <strong>{{ $role->name }}</strong> role. Synced users will inherit all privileges defined for this role instantly.
        </p>

        {{-- Interactive Search Box --}}
        <div class="admin-form-group" style="max-width: 400px;">
            <input type="text" id="user-search-input" placeholder="Quick search users by name or email..." class="admin-input">
        </div>

        <form method="POST" action="{{ route('admin.roles.sync-users', $role->id) }}">
            @csrf

            <div style="border: 1px solid var(--admin-border); border-radius: var(--admin-radius); overflow: hidden; max-height: 400px; overflow-y: auto; margin-bottom: 2rem;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="select-all-users" class="admin-checkbox">
                            </th>
                            <th>User Name</th>
                            <th>Email Address</th>
                        </tr>
                    </thead>
                    <tbody id="users-tbody">
                        @php
                            $assignedUserIds = $role->users->pluck('id')->toArray();
                        @endphp
                        @forelse($users as $user)
                            <tr class="user-row" data-name="{{ strtolower($user->name) }}" data-email="{{ strtolower($user->email) }}">
                                <td>
                                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" 
                                        class="admin-checkbox user-assign-checkbox"
                                        {{ in_array($user->id, $assignedUserIds) ? 'checked' : '' }}>
                                </td>
                                <td style="font-weight: 700; color: #0f172a;">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center; color: #cbd5e1; padding: 2rem 0;">
                                    No users found in the system.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; border-top: 1px solid var(--admin-border); padding-top: 1.5rem;">
                <a href="{{ route('admin.roles.index') }}" class="admin-btn admin-btn--secondary">Cancel</a>
                <button type="submit" class="admin-btn admin-btn--primary">Sync Users</button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('user-search-input');
            const rows = document.querySelectorAll('.user-row');

            // Quick search users
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                rows.forEach(function(row) {
                    const name = row.dataset.name;
                    const email = row.dataset.email;
                    if (name.includes(query) || email.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            // Select all users checkbox
            const selectAll = document.getElementById('select-all-users');
            const userCheckboxes = document.querySelectorAll('.user-assign-checkbox');

            selectAll.addEventListener('change', function() {
                const checked = this.checked;
                userCheckboxes.forEach(function(cb) {
                    // Only check visible ones when filtered
                    if (cb.closest('.user-row').style.display !== 'none') {
                        cb.checked = checked;
                    }
                });
            });

            // Update Select All state initially
            const total = userCheckboxes.length;
            const checkedCount = document.querySelectorAll('.user-assign-checkbox:checked').length;
            if (total === checkedCount && total > 0) {
                selectAll.checked = true;
            }
        });
    </script>
    @endpush
@endsection

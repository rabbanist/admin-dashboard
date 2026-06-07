@extends('admin-dashboard::layouts.app')

@section('title', 'Create Role - ' . config('admin-dashboard.title', 'Admin Dashboard'))

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
        <h1>Create Role</h1>
        <a href="{{ route('admin.roles.index') }}" class="admin-btn admin-btn--secondary">
            Back to List
        </a>
    </div>

    @if($errors->any())
        <div class="admin-alert admin-alert--error" style="margin-bottom: 1.5rem;">
            <ul style="list-style: none;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="admin-card">
        <form method="POST" action="{{ route('admin.roles.store') }}">
            @csrf

            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="name" class="admin-label">Role Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="Content Editor" class="admin-input" required autocomplete="off">
                </div>

                <div class="admin-form-group">
                    <label for="slug" class="admin-label">Role Slug</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug') }}" placeholder="content-editor" class="admin-input" required autocomplete="off">
                    <span style="font-size: 0.75rem; color: #94a3b8;">Must be unique and hyphenated.</span>
                </div>
            </div>

            <div class="admin-form-group">
                <label for="description" class="admin-label">Description</label>
                <textarea name="description" id="description" rows="3" placeholder="Describe the purpose of this role..." class="admin-textarea">{{ old('description') }}</textarea>
            </div>

            <div style="margin-top: 1.5rem; border-top: 1px solid var(--admin-border); padding-top: 1.5rem;">
                <label class="admin-label" style="font-size: 1.125rem; font-weight: 700; margin-bottom: 0.25rem;">Select Role Privileges</label>
                <span style="font-size: 0.85rem; color: #64748b; display: block; margin-bottom: 1.5rem;">Check the permissions that this role should grant to its users.</span>
                
                @include('admin-dashboard::role-privilege-picker', ['privileges' => $privileges])
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 1rem; border-top: 1px solid var(--admin-border); padding-top: 1.5rem;">
                <a href="{{ route('admin.roles.index') }}" class="admin-btn admin-btn--secondary">Cancel</a>
                <button type="submit" class="admin-btn admin-btn--primary">Create Role</button>
            </div>
        </form>
    </div>

    @push('scripts')
    <script>
        // Automatic slug generation from role name
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        let userEditedSlug = false;

        slugInput.addEventListener('input', function() {
            userEditedSlug = true;
        });

        nameInput.addEventListener('input', function() {
            if (!userEditedSlug) {
                slugInput.value = nameInput.value
                    .toLowerCase()
                    .replace(/[^a-z0-9 -]/g, '') // Remove invalid chars
                    .replace(/\s+/g, '-')        // Replace spaces with -
                    .replace(/-+/g, '-');        // Collapse multiple -
            }
        });
    </script>
    @endpush
@endsection
